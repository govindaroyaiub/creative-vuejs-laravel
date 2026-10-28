FROM php:8.4-cli

# System libraries + PHP extensions this app needs
# gd/zip -> dompdf + phpspreadsheet, pdo_mysql -> MySQL, pcntl -> queue/reverb
RUN apt-get update && apt-get install -y git unzip libzip-dev libpng-dev \
        libjpeg-dev libfreetype6-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath exif pcntl \
    && rm -rf /var/lib/apt/lists/*

# Copy the composer binary from the official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html