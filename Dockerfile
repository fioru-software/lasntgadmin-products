FROM php:8-apache

ARG USER_ID
ARG WP_VERSION
ARG WP_LOCALE

RUN a2enmod rewrite                                                                                                                                                                           
RUN apt update; \
    apt install -y default-mysql-client vim libzip-dev subversion

RUN docker-php-ext-install mysqli zip

RUN chown -R www-data:www-data /var/www /usr/local/src; \
    usermod -u $USER_ID www-data; \
    groupmod -g $USER_ID www-data

WORKDIR /tmp

# install wp-cli
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

# install composer
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# install dockerize
RUN curl -sfL $(curl -s https://api.github.com/repos/powerman/dockerize/releases/latest | grep -i /dockerize-$(uname -s)-$(uname -m)\" | cut -d\" -f4) | install /dev/stdin /usr/local/bin/dockerize

COPY --chown=www-data:www-data config/.htaccess config/wp-config.php /var/www/html/
COPY scripts/* /usr/local/bin/
RUN chmod +x /usr/local/bin/*

USER www-data
WORKDIR /var/www/html

RUN wp core download --skip-content --version=$WP_VERSION --locale=$WP_LOCALE --path=/var/www/html; \
    mkdir -p /var/www/html/wp-content/plugins /var/www/html/wp-content/themes

USER root
