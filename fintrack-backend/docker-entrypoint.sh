#!/bin/sh
set -e

# If vendor directory doesn't exist, install dependencies
if [ ! -d "vendor" ]; then
  echo "Installing composer dependencies..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

# Ensure storage permissions (non-fatal if it fails)
chown -R www-data:www-data /var/www/html || true
chmod -R 755 storage || true

exec "$@"
