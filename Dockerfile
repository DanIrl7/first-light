FROM php:8.4-apache

# pdo_mysql is the only extension the app needs beyond the defaults in the official image.
RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/

EXPOSE 80
