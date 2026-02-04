FROM php:8.2-fpm

COPY php.ini /usr/local/etc/php/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        zlib1g-dev \
        libzip-dev \
        unzip \
        curl \
        libonig-dev \
    && docker-php-ext-install pdo_mysql zip mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www

