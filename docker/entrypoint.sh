#!/bin/sh
set -e

echo "🚀 AI Employee Platform — Starting deployment..."

# Wait for database
if [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database..."
    until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
        sleep 2
    done
    echo "✅ Database ready"
fi

# Run migrations
echo "📦 Running migrations..."
php /var/www/artisan migrate --force

# Seed if first run
if [ ! -f /var/www/storage/app/.seeded ]; then
    echo "🌱 Seeding database..."
    php /var/www/artisan db:seed --force
    touch /var/www/storage/app/.seeded
fi

# Clear cache
php /var/www/artisan config:cache
php /var/www/artisan route:cache
php /var/www/artisan view:cache

echo "✨ Ready. Starting services..."
exec "$@"