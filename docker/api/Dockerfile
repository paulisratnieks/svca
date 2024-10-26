FROM serversideup/php:8.3-fpm

USER root

COPY --chown=www-data:www-data ./api /var/www/html

RUN composer install

USER www-data
