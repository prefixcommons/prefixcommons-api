FROM php:7-apache

MAINTAINER Alexander Malic <alexander.malic@maastrichtuniversity.nl>
MAINTAINER Vincent Emonet <vincent.emonet@maastrichtuniversity.nl>
MAINTAINER Michel Dumontier <michel.dumontier@maastrichtuniversity.nl>

ARG APP_ENV=prod

WORKDIR /tmp

RUN apt-get update && apt-get install -y zip && \
  a2enmod rewrite && \
  a2enmod headers && \
  echo "Header set Access-Control-Allow-Origin \"*\"" >> /etc/apache2/sites-available/000-default.conf && \
  php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer


WORKDIR /var/www/html

COPY ./slim-server/SwaggerServer/ .  
  
RUN composer install

