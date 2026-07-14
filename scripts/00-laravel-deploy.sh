#!/usr/bin/env bash
echo "--- Starting Deployment Script ---"

# 1. Compile Vue frontend assets
npm install
npm run build

# 2. Cache Laravel configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Run database migrations safely
php artisan migrate --force
