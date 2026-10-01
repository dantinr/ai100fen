#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then
    printf '%s\n' 'Run setup as root on the AI100fen server.' >&2
    exit 1
fi

cd "${1:-/var/www/ai100fen}"
project_root=$(git rev-parse --show-toplevel)
cd "$project_root"
test -f artisan
test -f composer.lock
command -v php >/dev/null
command -v composer >/dev/null
systemctl is-active --quiet php8.5-fpm

if [ -n "$(git status --porcelain)" ]; then
    printf '%s\n' 'Keep the server checkout clean before enabling deployment.' >&2
    exit 1
fi

existing_hooks=$(git config --local --get core.hooksPath || true)
if [ -n "$existing_hooks" ] && [ "$existing_hooks" != "deploy/hooks" ]; then
    printf '%s\n' 'Existing hooksPath detected; preserve it and review setup first.' >&2
    exit 1
fi

if [ -z "$existing_hooks" ]; then
    for existing_hook in "$(git rev-parse --git-path hooks)"/*; do
        [ -f "$existing_hook" ] || continue
        case "$existing_hook" in
            *.sample) continue ;;
        esac
        printf '%s\n' 'Existing Git hook detected; preserve it and review setup first.' >&2
        exit 1
    done
fi

git config --local pull.ff only
git config --local core.hooksPath deploy/hooks
git config --local ai100fen.deploy true
printf '%s\n' 'Enabled: future git pulls refresh dependencies and Laravel caches.'
