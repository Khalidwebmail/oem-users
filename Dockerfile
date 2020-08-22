# Dockerfile for user service
FROM php:fpm-alpine

# Install packages
RUN apk update && apk add \
  git \
  zip \
  curl \
  sudo \
  unzip 
    

RUN docker-php-ext-install pdo pdo_mysql
# Ensure PHP logs are captured by the container
ENV LOG_CHANNEL=stderr

ARG ENV="production"

WORKDIR /var/www/html


ADD https://getcomposer.org/download/1.6.2/composer.phar /usr/bin/composer
RUN chmod +rx /usr/bin/composer

ADD [ "./src", "/var/www/html"] 

# RUN if [ "$ENV" = "production" ]; then mv -f /var/www/temp/* /var/www/temp/.[!.]* /var/www/html/ || :; fi
RUN if [ "$ENV" = "production" ] ; then composer install || :;  fi
RUN if [ "$ENV" = "production" ]; then cp .env.example .env || :; fi
RUN if [ "$ENV" = "production" ]; then php artisan key:generate || :; fi
RUN if [ "$ENV" = "production" ]; then chmod -R 775 /var/www/html/storage || :; fi
 

EXPOSE 8000


CMD php artisan serve --host=0.0.0.0 --port=8000