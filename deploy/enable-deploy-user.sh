#!/bin/sh
set -eu

# One-time privileged setup, explicitly scoped to this site's checkout.
[ "$(id -u)" -eq 0 ] || { printf '%s\n' 'Run ownership setup as root.' >&2; exit 1; }
project_root=$(realpath -e -- "${1:-/var/www/ai100fen}")
[ "$project_root" = /var/www/ai100fen ] || { printf '%s\n' 'Only /var/www/ai100fen is supported.' >&2; exit 1; }
deployment_user=dante
deployment_group=dante
deployment_home=/home/dante
cd "$project_root"
test -d .git
test -f artisan
test -f .env
test -d storage
test -d bootstrap/cache
for setup_tool in getfacl setfacl sudo visudo useradd usermod runuser; do
    command -v "$setup_tool" >/dev/null
done
systemctl is-active --quiet php8.5-fpm
systemctl is-active --quiet nginx

# Run Git as its current owner; no global safe.directory bypass is installed.
previous_owner=$(stat -c %U "$project_root")
test -z "$(runuser -u "$previous_owner" -- git status --porcelain)"

if id "$deployment_user" >/dev/null 2>&1; then
    [ "$(id -u "$deployment_user")" -ge 1000 ]
    [ "$(id -gn "$deployment_user")" = "$deployment_group" ]
    [ "$(getent passwd "$deployment_user" | cut -d: -f6)" = "$deployment_home" ]
    [ "$(getent passwd "$deployment_user" | cut -d: -f7)" = /bin/bash ]
fi

install -d -m 700 -o root -g root /var/backups/ai100fen
backup_root=$(mktemp -d /var/backups/ai100fen/deploy-user-XXXXXXXX)
chmod 700 "$backup_root"
getfacl -R -P -p "$project_root" > "$backup_root/permissions.acl"
cp -p .git/config "$backup_root/git-config"
sha256sum .env > "$backup_root/env.sha256"
getent passwd www-data > "$backup_root/web-user"
id www-data > "$backup_root/web-groups"
sudo_rule=/etc/sudoers.d/ai100fen-deploy
if [ -e "$sudo_rule" ]; then
    cp -p "$sudo_rule" "$backup_root/sudo-rule"
    expected_rule='dante ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.5-fpm'
    [ "$(cat "$sudo_rule")" = "$expected_rule" ] || { printf '%s\n' 'Existing sudo rule differs; preserve it and review.' >&2; exit 1; }
fi

if ! id "$deployment_user" >/dev/null 2>&1; then
    useradd --create-home --user-group --shell /bin/bash "$deployment_user"
fi
# The web user joins this site's read group; deployment does not join www-data.
usermod --append --groups "$deployment_group" www-data
chmod 750 "$deployment_home"
install -d -m 700 -o "$deployment_user" -g "$deployment_group" "$deployment_home/.ssh"
if [ ! -e "$deployment_home/.ssh/authorized_keys" ]; then
    test -f /root/.ssh/authorized_keys
    install -m 600 -o "$deployment_user" -g "$deployment_group" /root/.ssh/authorized_keys "$deployment_home/.ssh/authorized_keys"
fi

printf '%s\n' 'dante ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.5-fpm' > "$backup_root/new-sudo-rule"
visudo -cf "$backup_root/new-sudo-rule"
install -m 440 -o root -g root "$backup_root/new-sudo-rule" "$sudo_rule"
visudo -c

# Pick up the read group while the old source permissions still allow access.
systemctl reload php8.5-fpm
systemctl reload nginx

# Physical traversal changes symlink ownership, never an external target.
chown -R -P --no-dereference "$deployment_user:$deployment_group" "$project_root"
for runtime_directory in storage bootstrap/cache; do
    setfacl -R -P -b "$runtime_directory"
    chmod -R u=rwX,g=rwX,o= "$runtime_directory"
    find -P "$runtime_directory" -type d -exec chmod g+s {} +
    setfacl -R -P -m u::rwX,g::rwX,m::rwX,o::--- "$runtime_directory"
    find -P "$runtime_directory" -type d -exec setfacl -m d:u::rwx,d:g::rwx,d:m::rwx,d:o::--- {} +
done

# Exclude live runtime paths; source directories inherit read-only group access
# even when an SSH session's umask would otherwise create group-writable files.
find -P . -path ./.git -prune -o -path ./storage -prune -o -path ./bootstrap/cache -prune -o \( -type f -o -type d \) -exec setfacl -b {} +
find -P . -path ./.git -prune -o -path ./storage -prune -o -path ./bootstrap/cache -prune -o \( -type f -o -type d \) -exec chmod u=rwX,g=rX,o= {} +
find -P . -path ./.git -prune -o -path ./storage -prune -o -path ./bootstrap/cache -prune -o -type d -exec setfacl -m d:u::rwx,d:g::r-x,d:m::r-x,d:o::--- {} +
setfacl -R -P -b "$project_root/.git"
chmod -R u=rwX,go= "$project_root/.git"
find -P "$project_root/.git" -type d -exec setfacl -m d:u::rwx,d:g::---,d:m::---,d:o::--- {} +
chmod 640 "$project_root/.env"

runuser -u "$deployment_user" -- git config --local ai100fen.deployUser "$deployment_user"
sha256sum -c "$backup_root/env.sha256"
printf 'Code owner: %s; permissions backup: %s\n' "$deployment_user" "$backup_root"
printf '%s\n' 'Continue as dante: cd /var/www/ai100fen && git pull'
