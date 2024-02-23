FROM php:7-apache

ARG USER_ID
ARG WP_VERSION
ARG WP_LOCALE
ARG GITHUB_TOKEN

RUN a2enmod rewrite                                                                                                                                                                           
RUN apt update; \
    apt install -y default-mysql-client vim libzip-dev unzip libpng-dev libmagickwand-dev libicu-dev subversion ssl-cert git

RUN pecl install --configureoptions='with-imagick="autodetect"' imagick; \
    docker-php-ext-enable imagick

RUN docker-php-ext-install mysqli zip gd intl exif

RUN chown -R www-data:www-data /var/www /usr/local/src; \
    usermod -u $USER_ID www-data; \
    groupmod -g $USER_ID www-data

RUN chown -R www-data:www-data /var/www; \
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp; \
    curl -sfL $(curl -s https://api.github.com/repos/powerman/dockerize/releases/latest | grep -i /dockerize-$(uname -s)-$(uname -m)\" | cut -d\" -f4) | install /dev/stdin /usr/local/bin/dockerize

# install composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY etc/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=www-data:www-data config/.htaccess config/wp-config.php /var/www/html/

USER www-data
WORKDIR /var/www/html

RUN wp core download --skip-content --version=$WP_VERSION --locale=$WP_LOCALE --path=/var/www/html; \
    mkdir -p /var/www/html/wp-content/plugins /var/www/html/wp-content/themes

# composer
RUN composer config --global --auth github-oauth.github.com ${GITHUB_TOKEN}; \
    composer config --global --no-plugins allow-plugins.composer/installers true

# plugins
RUN mkdir /tmp/plugins
COPY --chown=www-data:www-data plugins/* /tmp/plugins/
RUN for plugin in /tmp/plugins/*.zip; do if [ -f $plugin ]; then unzip -q $plugin -d /var/www/html/wp-content/plugins/; fi; done;

USER root
RUN a2enmod ssl; \
    a2ensite default-ssl;

CMD ["/usr/local/src/scripts/run.sh"]
