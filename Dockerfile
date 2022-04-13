FROM php:8-fpm-alpine3.14

# for laravel lumen run smoothly
RUN apk --no-cache add \
php \
php-fpm \
php-pdo \
php-mbstring \
php-openssl

# for our code run smoothly
RUN apk --no-cache add \
php-json \
php-dom \
curl \
php-curl

# for composer & our project depency run smoothly
RUN apk --no-cache add \
php-phar \
php-xml \
php-xmlwriter

# RUN apk --no-cache add \
#       libzip-dev \
#       zip \
#     && docker-php-ext-install zip
# if need composer to update plugin / vendor used
RUN php -r "copy('http://getcomposer.org/installer', 'composer-setup.php');" && \
php composer-setup.php --install-dir=/usr/bin --filename=composer && \
php -r "unlink('composer-setup.php');"

# RUN ln -sf /usr/bin/php7 /usr/bin/php && \
# ln -s /etc/php7/php.ini /etc/php7/conf.d/php.ini

# RUN set -x \
# addgroup -g 82 -S www-data \
# adduser -u 82 -D -S -G www-data www-data

# copy all of the file in folder to /src
COPY . /src
WORKDIR /src

RUN composer update

#ADD .env.example /src/.env
# RUN chmod -R 777 storage

# run the php server service
# move this command to -> docker-compose.yml
 CMD php -S 0.0.0.0:8006 public/index.php