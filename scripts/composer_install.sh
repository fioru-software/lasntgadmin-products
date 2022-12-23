#! /bin/sh

runuser -s /bin/sh -c 'ln -s /usr/local/src/composer.json /var/www/html/composer.json' www-data
runuser -s /bin/sh -c 'ln -s /usr/local/src/composer.lock /var/www/html/composer.lock' www-data
runuser -s /bin/sh -c 'composer install --no-cache --no-dev --optimize-autoloader --working-dir=/usr/local/src' www-data
runuser -s /bin/sh -c 'for plugin_path in /usr/local/src/wp-content/plugins/*; do ln -sfn $plugin_path/ /var/www/html/wp-content/plugins/$(basename $plugin_path); done;' www-data
