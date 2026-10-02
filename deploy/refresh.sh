#!/bin/sh
set -eu

project_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$project_root"

deployment_user=$(git config --local --get ai100fen.deployUser || true)
if [ "$(git config --local --get ai100fen.deploy || true)" != "true" ] || [ -z "$deployment_user" ] || [ "$(id -u)" -eq 0 ] || [ "$(id -un)" != "$deployment_user" ] || [ "$(stat -c %U "$project_root")" != "$deployment_user" ]; then
    printf '%s\n' 'Deployment requires the enabled checkout owned by its configured non-root deployment user.' >&2
    exit 1
fi

# Source and vendor files remain readable, but not writable, by the web group.
# Runtime directories inherit their separately configured default write ACLs.
umask 0027
sudo -n -l /usr/bin/systemctl reload php8.5-fpm >/dev/null
test -w storage
test -w bootstrap/cache

trap '
    deployment_status=$?
    if [ "$deployment_status" -ne 0 ]; then
        printf "%s\n" "Deployment failed. Git has updated the checkout; fix the error and run: sh deploy/refresh.sh" >&2
    fi
' 0

# Validate all bundled assets before changing application caches.
php -r '
$manifest = json_decode(file_get_contents("public/build/manifest.json"), true, 512, JSON_THROW_ON_ERROR);
foreach (["resources/css/app.css", "resources/css/themes/pop.css", "resources/css/themes/future.css", "resources/js/app.js"] as $source) {
    if (!isset($manifest[$source]["file"])) {
        throw new RuntimeException("Missing build entry: ".$source);
    }
}
foreach ($manifest as $entry) {
    foreach (array_merge([$entry["file"]], $entry["css"] ?? [], $entry["assets"] ?? []) as $asset) {
        if (!is_file("public/build/".$asset)) {
            throw new RuntimeException("Missing built asset: ".$asset);
        }
    }
}
'

composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan project:sync-history
sudo -n /usr/bin/systemctl reload php8.5-fpm
printf 'Deployed AI100fen %s\n' "$(git rev-parse --short HEAD)"
