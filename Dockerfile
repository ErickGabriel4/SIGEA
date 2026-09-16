# syntax=docker/dockerfile:1
#
# SIGEA — imagem de produção (Railway)
# nginx + php-fpm 8.3 em um único container, orquestrados por supervisord.
#

# ───────────────── Estágio 1: build dos assets (Vite) ─────────────────
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build


# ───────────────── Estágio 2: dependências PHP (Composer) ─────────────
FROM php:8.3-cli-alpine AS vendor

RUN apk add --no-cache git unzip \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev \
 && docker-php-ext-install -j"$(nproc)" zip intl bcmath \
 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev --no-scripts --no-autoloader \
      --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts


# ───────────────── Estágio 3: runtime ─────────────────────────────────
FROM php:8.3-fpm-alpine

# Pacotes de runtime e extensões PHP exigidas pela aplicação:
#   pdo_mysql → MySQL | intl → locale pt_BR | zip + gd → maatwebsite/excel e dompdf
#   exif → validação de imagens | pcntl → queue:work | opcache → desempenho
RUN apk add --no-cache \
      nginx supervisor gettext tzdata \
      icu-libs libzip libpng libjpeg-turbo freetype \
 && apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
      pdo_mysql bcmath intl zip gd exif pcntl opcache \
 && apk del .build-deps \
 && rm -rf /tmp/* /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-sigea.ini

WORKDIR /app

# Código com vendor otimizado + assets compilados
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

# nginx, supervisord e entrypoint
COPY docker/nginx.conf       /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
 && rm -f /etc/nginx/http.d/default.conf \
 && chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
