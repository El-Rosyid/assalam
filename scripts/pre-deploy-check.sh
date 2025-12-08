#!/bin/bash

# ===========================================
# Pre-Deployment Check Script
# ===========================================

echo "🔍 Running pre-deployment checks..."
echo ""

# Check 1: Git status
echo "1. Checking Git status..."
if [[ -n $(git status -s) ]]; then
    echo "⚠️  WARNING: You have uncommitted changes:"
    git status -s
    echo ""
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
else
    echo "✅ No uncommitted changes"
fi
echo ""

# Check 2: Current branch
echo "2. Checking current branch..."
CURRENT_BRANCH=$(git branch --show-current)
echo "Current branch: $CURRENT_BRANCH"
if [[ "$CURRENT_BRANCH" != "main" ]]; then
    echo "⚠️  WARNING: You are not on 'main' branch"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
else
    echo "✅ On main branch"
fi
echo ""

# Check 3: .env file
echo "3. Checking .env file..."
if [[ ! -f .env ]]; then
    echo "❌ ERROR: .env file not found!"
    exit 1
else
    # Check critical variables
    if ! grep -q "APP_KEY=base64:" .env; then
        echo "❌ ERROR: APP_KEY not set in .env!"
        exit 1
    fi
    if ! grep -q "APP_ENV=production" .env; then
        echo "⚠️  WARNING: APP_ENV is not set to 'production'"
    fi
    if ! grep -q "APP_DEBUG=false" .env; then
        echo "⚠️  WARNING: APP_DEBUG is not set to 'false'"
    fi
    echo "✅ .env file exists"
fi
echo ""

# Check 4: Database connection
echo "4. Checking database connection..."
if php artisan migrate:status &> /dev/null; then
    echo "✅ Database connection successful"
else
    echo "❌ ERROR: Cannot connect to database!"
    exit 1
fi
echo ""

# Check 5: Storage permissions
echo "5. Checking storage permissions..."
if [[ -w storage ]]; then
    echo "✅ Storage directory is writable"
else
    echo "⚠️  WARNING: Storage directory is not writable"
fi
echo ""

# Check 6: Composer dependencies
echo "6. Checking Composer dependencies..."
if composer validate --no-check-all --no-check-publish &> /dev/null; then
    echo "✅ composer.json is valid"
else
    echo "⚠️  WARNING: composer.json may have issues"
fi
echo ""

# Check 7: NPM dependencies
echo "7. Checking NPM build..."
if [[ -d "public/build" ]]; then
    echo "✅ Assets are built"
else
    echo "⚠️  WARNING: Assets not built. Run 'npm run build'"
fi
echo ""

# Check 8: Queue worker
echo "8. Checking queue configuration..."
QUEUE_CONNECTION=$(grep "QUEUE_CONNECTION=" .env | cut -d '=' -f2)
if [[ "$QUEUE_CONNECTION" == "database" ]]; then
    echo "✅ Queue connection: database (queue worker required)"
    echo "   Remember to run: php artisan queue:work --daemon"
elif [[ "$QUEUE_CONNECTION" == "sync" ]]; then
    echo "⚠️  Queue connection: sync (not recommended for production)"
else
    echo "ℹ️  Queue connection: $QUEUE_CONNECTION"
fi
echo ""

# Summary
echo "=========================================="
echo "Pre-deployment checks completed!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Commit and push all changes: git add . && git commit -m 'message' && git push"
echo "2. On server, run: bash deploy.sh"
echo "3. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
