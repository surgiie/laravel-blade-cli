FROM php:8.3-cli-alpine

RUN addgroup -g 1000 php && adduser -u 1000 -S php -G php

WORKDIR /app
ARG PHP_EXTS="pcntl"
ARG DEBIAN_FRONTEND=noninteractive

RUN apk --no-cache update \
    && docker-php-ext-install -j$(nproc) ${PHP_EXTS} \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chown -R php:php /app/

USER php

RUN composer require surgiie/laravel-blade-cli:0.1.0
ENV PATH=$PATH:/app/vendor/bin

