FROM php:7.4

RUN apt-get update && apt-get install -y \
    git zlib1g-dev libicu-dev libc-client-dev libkrb5-dev libgmp-dev libpq-dev libzip-dev unzip

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/bin/composer

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev

RUN docker-php-ext-configure gd \
    --with-jpeg=/usr/include/ \
    --with-freetype=/usr/include/ \
    && docker-php-ext-install gd

RUN pecl install pcov && docker-php-ext-enable pcov
