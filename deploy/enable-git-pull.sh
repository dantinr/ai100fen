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
command -v timeout >/dev/null
systemctl is-active --quiet php8.5-fpm

if [ -n "$(git status --porcelain)" ]; then
    printf '%s\n' 'Keep the server checkout clean before enabling deployment.' >&2
    exit 1
fi

existing_hooks=$(git config --get core.hooksPath || true)
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

# The server pulls the Gitee mirror; local development also pushes to GitHub.
deployment_remote=gitee
deployment_url=https://gitee.com/dantinr/ai100fen.git
deployment_branch=master
if ! server_branch=$(git symbolic-ref --quiet --short HEAD); then
    printf '%s\n' 'Use an attached server branch before enabling deployment.' >&2
    exit 1
fi
existing_url=$(git remote get-url "$deployment_remote" 2>/dev/null || true)
if [ -n "$existing_url" ] && [ "$existing_url" != "$deployment_url" ]; then
    printf '%s\n' 'Existing Gitee remote differs; preserve it and review setup first.' >&2
    exit 1
fi
if [ -z "$existing_url" ]; then
    git remote add "$deployment_remote" "$deployment_url"
fi
timeout 30s git -c http.lowSpeedLimit=1 -c http.lowSpeedTime=15 fetch "$deployment_remote" "$deployment_branch"
if ! git merge-base --is-ancestor HEAD "$deployment_remote/$deployment_branch"; then
    printf '%s\n' 'Server history differs from Gitee master; review it without resetting.' >&2
    exit 1
fi
git branch --set-upstream-to="$deployment_remote/$deployment_branch" "$server_branch"

git config --local pull.ff only
git config --local core.hooksPath deploy/hooks
git config --local ai100fen.deploy true
printf '%s\n' 'Enabled: git pull from Gitee master refreshes dependencies and Laravel caches.'
