#!/bin/sh
set -e
cd /var/www/html

# Render gives us a PORT; make Apache listen on it.
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Aiven requires SSL: paste the CA certificate into the MYSQL_CA_CERT env var.
if [ -n "$MYSQL_CA_CERT" ]; then
    printf '%s\n' "$MYSQL_CA_CERT" > /var/www/html/storage/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/mysql-ca.pem
fi

php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Demo data (admin/owner/player accounts + sample turfs). Only runs when
# RUN_SEED=true and the database has no users yet.
if [ "$RUN_SEED" = "true" ]; then
    COUNT=$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n1)
    if [ "$COUNT" = "0" ]; then
        php artisan db:seed --force
    fi
fi

chown -R www-data:www-data storage bootstrap/cache
exec "$@"
