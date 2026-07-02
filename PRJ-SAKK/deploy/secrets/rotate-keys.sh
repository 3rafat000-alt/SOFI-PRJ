#!/bin/bash
# API Key Rotation Script
# Run: ./rotate-keys.sh <environment>
ENV=${1:-production}
echo "🔄 Rotating API Keys for $ENV..."

# Generate new APP_KEY
php artisan key:generate --force

# Invalidate all Sanctum tokens
php artisan sanctum:invalidate-all

# Clear cache
php artisan config:clear
php artisan cache:clear

# Restart PHP-FPM
if command -v systemctl &> /dev/null; then
    sudo systemctl restart php8.2-fpm
    sudo systemctl reload nginx
fi

echo "✅ Keys rotated. Remember to update deploy secrets!"
