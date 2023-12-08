#! /bin/sh

if runuser -s /bin/sh -c 'wp core is-installed --path=/var/www/html' www-data; then

    if ! runuser -s /bin/sh -c 'wp role exists national_manager --path=/var/www/html' www-data; then
        runuser -s /bin/sh -c 'wp role create national_manager "National Manager" --path=/var/www/html' www-data
    fi
    if ! runuser -s /bin/sh -c 'wp role exists regional_training_centre_manager --path=/var/www/html' www-data; then
        runuser -s /bin/sh -c 'wp role create regional_training_centre_manager "Regional Training Centre Manager" --path=/var/www/html' www-data
    fi
    if ! runuser -s /bin/sh -c 'wp role exists training_officer --path=/var/www/html' www-data; then
        runuser -s /bin/sh -c 'wp role create training_officer "Training Officer" --path=/var/www/html' www-data
    fi

fi
