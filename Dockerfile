FROM php:8.4-fpm

WORKDIR /var/www

# System deps + libraries needed to compile PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    fonts-dejavu-core \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql zip gd bcmath \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App code
COPY . .

# Ensure runtime dirs exist (will be bind-mounted in dev, but safe to create)
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

#USER www-data

CMD ["php-fpm"]
