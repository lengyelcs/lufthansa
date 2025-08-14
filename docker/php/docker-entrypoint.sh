#!/bin/bash
set -e

cd /var/www/symfony
composer install

# Copy .env.example to .env if .env doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file from .env.example"
fi

if grep -q "APP_SECRET=$" .env; then
    APP_SECRET=$(openssl rand -hex 16)
    sed -i "s/APP_SECRET=/APP_SECRET=$APP_SECRET/" .env
    echo "Generated new APP_SECRET in .env file"
fi

echo "Waiting for database (db:3306) to be ready..."
ATTEMPTS=0
until bash -c "</dev/tcp/db/3306" 2>/dev/null; do
 ATTEMPTS=$((ATTEMPTS+1))
 if [ $ATTEMPTS -gt 60 ]; then
   echo "Database did not become ready in time (timeout)." >&2
   exit 1
 fi
 echo "Database not ready yet... ($ATTEMPTS)"
 sleep 2
done


echo "Database is reachable. Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || {
 echo "Migrations failed." >&2
 exit 1
}


if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

exec "$@"