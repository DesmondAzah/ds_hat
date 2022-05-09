FROM registry.bon.dev/bon/docker/php_8.0-apache

# Customize any core extensions here
#RUN apt-get update && apt-get install -y \
#        libfreetype6-dev \
#    && docker-php-ext-install -j$(nproc) iconv mcrypt \
#    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
#    && docker-php-ext-install -j$(nproc) gd

RUN apt-get update && apt-get install -y \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) zip pdo pdo_mysql \
    && docker-php-ext-enable pdo_mysql

COPY --from=registry.bon.dev/bon/docker/composer /usr/bin/composer /usr/bin/composer

RUN sed -i 's/\/var\/www\/html/\/var\/www\/html\/public/' /etc/apache2/sites-enabled/000-default.conf

COPY . /var/www/html/

WORKDIR /var/www/html

RUN a2enmod rewrite

RUN composer install
