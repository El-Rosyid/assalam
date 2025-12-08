#!/bin/bash

# ===========================================
# Deploy Script untuk TK ABA ASSALAM System
# ===========================================

echo "🚀 Starting deployment process..."

# 1. Masuk ke maintenance mode
echo "📦 Putting application into maintenance mode..."
php artisan down || true

# 2. Pull latest code dari repository
echo "📥 Pulling latest code from repository..."
git pull origin main

# 3. Install/Update Composer dependencies (production only)
echo "📚 Installing/Updating Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Install/Update NPM dependencies
echo "📦 Installing/Updating NPM dependencies..."
npm ci

# 5. Build assets
echo "🔨 Building frontend assets..."
npm run build

# 6. Clear all caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 7. Cache configuration for production
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 9. Optimize application
echo "⚡ Optimizing application..."
php artisan optimize

# 10. Set proper permissions
echo "🔐 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 11. Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# 12. Keluar dari maintenance mode
echo "✅ Bringing application back online..."
php artisan up

echo "🎉 Deployment completed successfully!"
echo "⚠️  Don't forget to:"
echo "   - Restart queue worker: php artisan queue:work --daemon"
echo "   - Check supervisor configuration"
echo "   - Monitor application logs"
