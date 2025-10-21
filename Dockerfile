FROM dunglas/frankenphp:1.9-php8.4-alpine AS frankenphp_upstream

FROM frankenphp_upstream AS builder

RUN apk add --no-cache \
    git \
    nodejs \
    npm \
    sqlite-dev \
    sqlite \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        gd \
        opcache \
        zip \
        bcmath \
        pcntl \
        exif

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY package*.json ./

RUN npm ci --only=production

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

RUN npm run build

FROM frankenphp_upstream AS production

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    zip \
    bash \
    curl \
    icu-dev \
    && docker-php-ext-configure gd \
        --with-freetype=/usr/include/ \
        --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        gd \
        opcache \
        zip \
        bcmath \
        pcntl \
        exif

RUN adduser -D -g '' -G www-data laravel

COPY --from=builder --chown=laravel:www-data /app /app

WORKDIR /app

RUN mkdir -p \
    /app/storage/app/public \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/testing \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/storage/database \
    /app/bootstrap/cache \
    && chown -R laravel:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

COPY <<EOF /usr/local/etc/php/conf.d/app.ini
; perfomance settings
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.interned_strings_buffer=16

; security settings
expose_php = Off
display_errors = Off
log_errors = On
error_log = /app/storage/logs/php_errors.log

; laravel specific settings
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 60
memory_limit = 256M

; session settings
session.cookie_httponly = 1
session.use_only_cookies = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
EOF

COPY <<EOF /etc/caddy/Caddyfile
{
    auto_https off

    frankenphp {
        worker {
            file /app/public/index.php
            num 2
            env APP_RUNTIME frankenphp
        }
    }

    servers {
        timeouts {
            read_body 30s
            read_header 10s
            write 30s
            idle 2m
        }
        max_header_size 16k
    }
}

# Слушаем только HTTP на внутреннем порту
:8000 {
    # Корневая директория
    root * /app/public

    # Сжатие (опционально, если внешний Caddy не делает)
    encode zstd gzip

    # Логирование
    log {
        output file /app/storage/logs/frankenphp_access.log
        format json
    }

    # PHP обработка через FrankenPHP
    php_server {
        # Настройки для Laravel
        env APP_ENV production
        env LOG_CHANNEL stderr

        # Принимаем заголовки от reverse proxy
        trusted_proxies private_ranges

        # Таймауты
        timeouts {
            read 30s
            write 30s
        }
    }

    # Обслуживание статических файлов
    @static {
        file
        path *.ico *.css *.js *.gif *.webp *.avif *.jpg *.jpeg *.png *.svg *.woff *.woff2
    }
    handle @static {
        header Cache-Control "public, max-age=31536000, immutable"
    }
}

# Health check endpoint
:8080 {
    respond /health "OK" 200
}
EOF

# supervisor configuration for Laravel queue workers
COPY <<EOF /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
numprocs=2
redirect_stderr=true
stdout_logfile=/app/storage/logs/queue.log
stopwaitsecs=3600
EOF

COPY <<'EOF' /usr/local/bin/docker-entrypoint.sh
#!/bin/sh
set -e

# migrate database
php artisan migrate --force

# setup storage symlink
php artisan storage:link

# cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# run queue workers if not using sync driver
if [ "$QUEUE_CONNECTION" != "sync" ]; then
    supervisord -c /etc/supervisor/supervisord.conf
fi

# frankenphp with caddy
exec frankenphp run --config /etc/caddy/Caddyfile
EOF

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# change to non-root user
USER laravel

# Expose ports
EXPOSE 8000 8080

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# Entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
