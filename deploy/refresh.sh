#!/bin/sh
set -eu

project_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$project_root"

if [ "$(git config --local --get ai100fen.deploy || true)" != "true" ] || [ "$(id -u)" -ne 0 ]; then
    printf '%s\n' 'Deployment requires the enabled production checkout and root.' >&2
    exit 1
fi

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

COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan project:sync-history
chown -R www-data:www-data storage bootstrap/cache
chmod -R u=rwX,g=rwX,o= storage bootstrap/cache
systemctl reload php8.5-fpm
printf 'Deployed AI100fen %s\n' "$(git rev-parse --short HEAD)"
