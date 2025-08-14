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

php bin/console doctrine:migrations:migrate --no-interaction

if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

exec "$@"
