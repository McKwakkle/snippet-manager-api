FROM php:8.2-apache

ARG CACHE_BUST=2

RUN apt-get update && apt-get install -y libpq-dev libzip-dev zip unzip git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pgsql pdo_pgsql zip

RUN a2enmod rewrite

COPY apache.conf /etc/apache2/sites-available/snippet-manager-api.conf
RUN a2ensite snippet-manager-api.conf && a2dissite 000-default.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 80

CMD ["apache2-foreground"]