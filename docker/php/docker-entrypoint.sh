#!/bin/sh
set -e

if [ "$(id -u)" = "0" ]; then
    mkdir -p \
        /var/www/backend/storage

    # El volumen app-storage (GitOps) persiste entre redeploys; PHP-FPM corre como
    # PHP-FPM necesita ser dueño del almacenamiento de la aplicación.
    chown -R www-data:www-data /var/www/backend/storage

    find /var/www/backend/storage -type d -exec chmod 775 {} \;
    find /var/www/backend/storage -type f -exec chmod 664 {} \;
fi

if [ -f /var/www/backend/composer.json ] && [ ! -f /var/www/backend/vendor/autoload.php ]; then
    echo "Installing PHP dependencies (vendor missing on mounted volume)..."
    composer install --no-dev --optimize-autoloader --no-interaction --working-dir=/var/www/backend
fi

echo "Waiting for MariaDB and applying migrations..."
until php /var/www/backend/bin/migrate.php; do
    echo "MariaDB is not ready yet; retrying migrations in 3 seconds..."
    sleep 3
done

exec docker-php-entrypoint "$@"
