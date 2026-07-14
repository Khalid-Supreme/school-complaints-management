# ---- Stage 1: build frontend (Vite) ----
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci --silent
COPY . .
RUN npm run build

# ---- Stage 2: backend (PHP 8.4) ----
FROM php:8.4-fpm-alpine

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1

# Install runtime deps, build tools for PHP extensions
RUN apk add --no-cache --update \
    bash \
    git \
    openssh-client \
    icu-dev \
    libzip-dev \
    zlib-dev \
    oniguruma-dev \
    autoconf \
    gcc \
    g++ \
    make \
    curl \
    nginx \
    supervisor

# Configure and install required PHP extensions
RUN docker-php-ext-configure zip && \
    docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl intl && \
    apk del autoconf gcc g++ make

# Bring composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Copy built frontend assets
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install PHP dependencies
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction || true

# Run artisan caches (non-fatal to avoid build failures)
RUN php artisan key:generate --ansi || true
RUN php artisan config:cache --no-interaction || true
RUN php artisan route:cache --no-interaction || true

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public || true

# Copy runtime configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord","-n","-c","/etc/supervisord.conf"]