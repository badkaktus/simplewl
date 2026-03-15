# syntax=docker/dockerfile:1
ARG NODE_VERSION=20

# Stage 1: PHP dependencies
FROM dunglas/frankenphp:php8.4 AS php_base

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_sqlite \
    pdo_mysql \
    redis \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    opcache \
    exif

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

# Stage 2: Build frontend assets
FROM node:${NODE_VERSION}-alpine AS node_build

WORKDIR /app
COPY . .
COPY --from=php_base /var/www/html/vendor /app/vendor

RUN if [ -f yarn.lock ]; then \
        yarn install --frozen-lockfile; \
    else \
        npm ci --no-audit; \
    fi

RUN if [ -f yarn.lock ]; then yarn build; else npm run build; fi

# Stage 3: Final production image
FROM php_base AS final

COPY --from=node_build /app/public/build /var/www/html/public/build

COPY docker/frankenphp/entrypoint-prod.sh /entrypoint-prod.sh
RUN chmod +x /entrypoint-prod.sh

ENV APP_ENV=production
ENV SERVER_NAME=":8080"
ENV SERVER_ROOT="/var/www/html/public"

EXPOSE 8080

ENTRYPOINT ["/entrypoint-prod.sh"]
