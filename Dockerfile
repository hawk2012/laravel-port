# --- Стадия 1: сборка фронтенда -------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.ts tsconfig.json ./
COPY resources ./resources

# Клиентский бандл в public/build, SSR-бандл в bootstrap/ssr
RUN npm run build

# --- Стадия 2: PHP-зависимости --------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# --- Стадия 3: рантайм php-fpm --------------------------------------------
FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/app/private storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

# --- Стадия 4: nginx со статикой ------------------------------------------
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend /app/public/build /var/www/html/public/build

EXPOSE 80

# --- Стадия 5: Inertia SSR ------------------------------------------------
FROM node:22-alpine AS ssr

ENV NODE_ENV=production

WORKDIR /app

COPY --from=frontend /app/bootstrap/ssr ./bootstrap/ssr

EXPOSE 13714

CMD ["node", "bootstrap/ssr/ssr.js"]