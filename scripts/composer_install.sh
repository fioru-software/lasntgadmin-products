#! /bin/sh

runuser -s /bin/sh -c 'ln -s /usr/local/src/composer.json /var/www/html/composer.json' www-data
runuser -s /bin/sh -c 'ln -s /usr/local/src/composer.lock /var/www/html/composer.lock' www-data
runuser -s /bin/sh -c 'composer install --no-cache --no-dev --optimize-autoloader --working-dir=/var/www/html' www-data
