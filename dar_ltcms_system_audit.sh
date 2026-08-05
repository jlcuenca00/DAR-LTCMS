#!/usr/bin/env bash

section () {
    printf "\n============================================================\n"
    printf "%s\n" "$1"
    printf "============================================================\n"
}

section "PROJECT VERSION"
printf "Branch: "
git branch --show-current 2>/dev/null || true

printf "Commit: "
git rev-parse --short HEAD 2>/dev/null || true

section "CORE SOFTWARE VERSIONS"
php artisan --version 2>/dev/null || true
php -v 2>/dev/null | head -n 2
composer --version 2>/dev/null || true
node --version 2>/dev/null || true
npm --version 2>/dev/null || true

section "IMPORTANT COMPOSER PACKAGES"
for package in \
    laravel/framework \
    laravel/breeze \
    laravel/sanctum \
    laravel/tinker \
    spatie/laravel-permission
do
    echo
    echo "[$package]"
    composer show "$package" 2>/dev/null | grep -E '^(name|versions|description)' || echo "Not installed"
done

section "IMPORTANT FRONTEND PACKAGES"
npm list --depth=0 2>/dev/null | grep -Ei \
'vite|laravel-vite-plugin|leaflet|axios|tailwind|alpine' || true

section "SAFE ENVIRONMENT SETTINGS"
if [ -f .env ]; then
    grep -E \
'^(APP_ENV|APP_URL|DB_CONNECTION|QUEUE_CONNECTION|FILESYSTEM_DISK|FILESYSTEM_DRIVER|MAIL_MAILER|CACHE_STORE|CACHE_DRIVER|SESSION_DRIVER)=' \
.env || true
else
    echo ".env not found"
fi

section "LARAVEL APPLICATION INFORMATION"
php artisan about 2>/dev/null || true

section "DATABASE INFORMATION AND TABLE COUNTS"
php artisan db:show --counts --views 2>/dev/null \
    || php artisan db:show 2>/dev/null \
    || echo "Unable to run artisan db:show"

section "MIGRATION COUNT"
printf "Migration files: "
find database/migrations -maxdepth 1 -type f -name '*.php' 2>/dev/null | wc -l

section "QUEUE AND FILE STORAGE CONFIGURATION"
php artisan tinker --execute="
echo 'Queue connection: '.config('queue.default').PHP_EOL;
echo 'Filesystem disk: '.config('filesystems.default').PHP_EOL;
echo 'Session driver: '.config('session.driver').PHP_EOL;
echo 'Cache store: '.config('cache.default').PHP_EOL;
echo 'Mail transport: '.config('mail.default').PHP_EOL;
" 2>/dev/null || echo "Tinker configuration check unavailable"

section "NOTIFICATION CLASSES"
NOTIFICATION_FILES=$(grep -RIl \
    "extends Notification" app \
    --include='*.php' 2>/dev/null || true)

printf "Notification class count: "
if [ -n "$NOTIFICATION_FILES" ]; then
    printf "%s\n" "$NOTIFICATION_FILES" | wc -l
    printf "%s\n" "$NOTIFICATION_FILES"
else
    echo "0"
fi

section "QUEUED CLASSES AND JOBS"
printf "Job files: "
find app/Jobs -type f -name '*.php' 2>/dev/null | wc -l
find app/Jobs -type f -name '*.php' 2>/dev/null || true

echo
echo "Classes implementing ShouldQueue:"
grep -RIl \
    "ShouldQueue" app \
    --include='*.php' 2>/dev/null || true

section "SCHEDULED TASKS"
php artisan schedule:list 2>/dev/null || echo "No scheduled tasks or command unavailable"

section "AUTHORIZATION AND ROLE-BASED ACCESS"
echo "Policies:"
find app/Policies -type f -name '*.php' 2>/dev/null || true

echo
echo "Middleware:"
find app/Http/Middleware -type f -name '*.php' 2>/dev/null || true

echo
echo "Role, policy, and authorization references:"
grep -RInE \
'Gate::|Policy|authorize\(|can\(|role|permission' \
app routes database/migrations \
--include='*.php' 2>/dev/null | head -n 120 || true

section "MAP AND EXTERNAL SERVICE REFERENCES"
grep -RInE \
'OpenStreetMap|openstreetmap|Leaflet|leaflet|GeoJSON|geojson|Resend|Twilio|SMS|OAuth|reCAPTCHA|Http::|Guzzle|Mail::|Storage::disk' \
app config routes resources package.json composer.json \
--exclude-dir=node_modules \
--exclude-dir=vendor 2>/dev/null | head -n 160 || true

section "DEPLOYMENT CONFIGURATION FILES"
find . -maxdepth 3 -type f \( \
    -iname 'Dockerfile' -o \
    -iname 'docker-compose.yml' -o \
    -iname 'docker-compose.yaml' -o \
    -iname 'Procfile' -o \
    -iname 'railway.json' -o \
    -iname 'render.yaml' -o \
    -iname 'vercel.json' -o \
    -iname 'fly.toml' -o \
    -iname 'nixpacks.toml' -o \
    -iname 'nginx.conf' -o \
    -iname 'apache.conf' \
\) -print 2>/dev/null

section "LOCALLY AVAILABLE WEB SERVERS"
for command_name in herd nginx apache2 httpd caddy; do
    if command -v "$command_name" >/dev/null 2>&1; then
        echo
        echo "[$command_name]"
        "$command_name" --version 2>&1 | head -n 3
    fi
done

section "UPLOAD AND GENERATED FILE DIRECTORIES"
find storage/app public -maxdepth 3 -type d 2>/dev/null | sort | head -n 100

section "REPORT COMPLETE"
echo "No passwords, APP_KEY values, tokens, or database credentials were intentionally included."
