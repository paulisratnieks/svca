FROM serversideup/php:8.3-fpm

COPY ./api /var/www/html

USER root

RUN composer install

USER www-data
