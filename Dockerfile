FROM php:8.4-apache

# pdo_mysql is the only extension the app needs beyond the defaults in the official image.
RUN docker-php-ext-install pdo_mysql

# mod_rewrite gives the app clean URLs (/result instead of /result.php); the rules are in docker/clean-urls.conf.
RUN a2enmod rewrite

COPY docker/clean-urls.conf /etc/apache2/conf-available/clean-urls.conf
RUN a2enconf clean-urls

COPY . /var/www/html/

EXPOSE 80
