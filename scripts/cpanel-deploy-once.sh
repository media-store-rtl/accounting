#!/usr/bin/env bash
set -Eeuo pipefail

REPOSITORY_PATH="${ACCOUNTING_REPOSITORY_PATH:-/home/mediast1/repositories/accounting}"
DEPLOYPATH="${ACCOUNTING_DEPLOY_PATH:-/home/mediast1/accounting/}"
STATE_DIR="${ACCOUNTING_DEPLOY_STATE_DIR:-/home/mediast1/.accounting-deploy-state}"
DEPLOY_DELAY_SECONDS="${ACCOUNTING_DEPLOY_DELAY_SECONDS:-60}"
LAST_ATTEMPT_FILE="$STATE_DIR/last-attempted-main-sha"

mkdir -p "$STATE_DIR"
exec 9>"$STATE_DIR/deploy.lock"
if ! flock -n 9; then
    echo "Accounting deploy skipped: another deployment check is already running."
    exit 0
fi

main_sha() {
    git -C "$REPOSITORY_PATH" rev-parse refs/heads/main
}

CURRENT_SHA="$(main_sha)"
if [[ -z "$CURRENT_SHA" ]]; then
    echo "Accounting deploy failed: could not read main commit SHA."
    exit 1
fi

if [[ -f "$LAST_ATTEMPT_FILE" ]] && [[ "$(cat "$LAST_ATTEMPT_FILE")" == "$CURRENT_SHA" ]]; then
    echo "Accounting deploy skipped: main commit $CURRENT_SHA was already attempted."
    exit 0
fi

echo "New Accounting main commit detected: $CURRENT_SHA. Waiting ${DEPLOY_DELAY_SECONDS} seconds."
sleep "$DEPLOY_DELAY_SECONDS"

LATEST_SHA="$(main_sha)"
if [[ "$LATEST_SHA" != "$CURRENT_SHA" ]]; then
    echo "Accounting deploy skipped: main changed during the wait; latest commit is $LATEST_SHA."
    exit 0
fi

# Record the attempt before running commands so a scheduled cron cannot repeat
# a failed or successful deployment for the same commit on every invocation.
TEMP_MARKER="$LAST_ATTEMPT_FILE.tmp.$$"
printf '%s\n' "$CURRENT_SHA" > "$TEMP_MARKER"
mv "$TEMP_MARKER" "$LAST_ATTEMPT_FILE"

if [[ "${ACCOUNTING_DEPLOY_DRY_RUN:-0}" == "1" ]]; then
    echo "DRY RUN: deployment would run once for $CURRENT_SHA."
    exit 0
fi

export DEPLOYPATH
export PATH="/home/mediast1/.nvm/versions/node/v22.14.0/bin:$PATH"

cd "$REPOSITORY_PATH"
/bin/mkdir -p "$DEPLOYPATH"
/bin/cp -R app config database docs resources routes bootstrap artisan composer.json composer.lock package.json vite.config.js README.md .env.example .gitignore .cpanel.yml "$DEPLOYPATH"
/bin/rm -f "$DEPLOYPATH/bootstrap/cache/packages.php" "$DEPLOYPATH/bootstrap/cache/services.php"
/bin/mkdir -p "$DEPLOYPATH/public"
cd "$REPOSITORY_PATH/public"
/usr/bin/find . -mindepth 1 -maxdepth 1 ! -name storage ! -name build -exec /bin/cp -R {} "$DEPLOYPATH/public/" \;
cd "$DEPLOYPATH"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm install
npm run build
mkdir -p storage/app storage/framework/cache storage/framework/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if [[ ! -f .env ]]; then
    cp .env.example .env
fi
if ! grep -q '^APP_KEY=.' .env; then
    php artisan key:generate --force
fi
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force

echo "Accounting deployment completed for main commit $CURRENT_SHA."
