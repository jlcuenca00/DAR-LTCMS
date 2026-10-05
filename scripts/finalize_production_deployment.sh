#!/usr/bin/env bash
set -euo pipefail

cd "${1:?Application directory is required}"
deployment_sha="${2:?Deployment commit is required}"
[[ "$deployment_sha" =~ ^[0-9a-f]{40}$ ]] || exit 1

fail_closed() {
    deployment_status=$?
    if [ "$deployment_status" -ne 0 ]; then
        echo "Deployment verification failed; returning the application to maintenance."
        php artisan down --retry=60 --render="errors::503" || true
    fi
    exit "$deployment_status"
}
trap fail_closed EXIT

php artisan up
php artisan dar:check-deployment-http

# Publish the revision only after the live HTTP and asset checks succeed.
printf '%s\n' "$deployment_sha" > .release-commit.next
chmod 644 .release-commit.next
mv .release-commit.next .release-commit
echo "Verified deployed commit: $deployment_sha"
