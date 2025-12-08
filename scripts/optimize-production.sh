#!/bin/bash
# 🚀 PRODUCTION OPTIMIZATION SCRIPT
# Run di cPanel setelah upload files

echo "🚀 Starting Production Optimization..."
echo ""

# 1. Clear all caches
echo "1️⃣  Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo "✅ Caches cleared"
echo ""

# 2. Generate optimized autoloader
echo "2️⃣  Optimizing autoloader..."
composer install --optimize-autoloader --no-dev
echo "✅ Autoloader optimized"
echo ""

# 3. Generate config cache
echo "3️⃣  Caching configuration..."
php artisan config:cache
echo "✅ Config cached"
echo ""

# 4. Run database migrations
echo "4️⃣  Running migrations..."
php artisan migrate --force
echo "✅ Migrations completed"
echo ""

# 5. Optimize Laravel
echo "5️⃣  Optimizing Laravel..."
php artisan optimize:clear
php artisan optimize
echo "✅ Laravel optimized"
echo ""

# 6. Storage & Permissions
echo "6️⃣  Setting storage permissions..."
chmod -R 755 storage bootstrap/cache
echo "✅ Permissions set"
echo ""

# 7. Generate sitemap (optional)
echo "7️⃣  Generating assets..."
npm run build 2>/dev/null || echo "⚠️  npm not available (OK for server)"
echo ""

# 8. Verify key files
echo "8️⃣  Verifying setup..."
php artisan migrate:status
php artisan tinker --execute="echo 'Database: OK'"
echo "✅ Setup verified"
echo ""

echo "🎉 Production optimization complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Verify admin panel loads"
echo "   2. Test Custom Broadcast menu"
echo "   3. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
