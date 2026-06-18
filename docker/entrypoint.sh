#!/bin/bash
set -e
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "▶ composer install..."
    composer install --working-dir=/var/www --no-interaction --prefer-dist --quiet
fi
exec "$@"
