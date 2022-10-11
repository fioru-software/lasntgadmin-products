#! /bin/sh

dockerize -timeout 300s -wait tcp://$DB_HOST:3306

if ! runuser -s /bin/sh -c 'wp core is-installed' www-data; then
    runuser -s /bin/sh -c 'wp core install --url=$SITE_URL --title="$SITE_TITLE" --admin_user=$ADMIN_USERNAME --admin_email=$ADMIN_EMAIL --admin_password=$ADMIN_PASSWORD' www-data
    runuser -s /bin/sh -c 'wp rewrite structure --hard "/%postname%/"' www-data
    runuser -s /bin/sh -c 'wp plugin install $WP_PLUGINS' www-data
    runuser -s /bin/sh -c 'wp plugin activate $WP_PLUGINS' www-data
    runuser -s /bin/sh -c 'wp theme install $WP_THEME --activate' www-data
    runuser -s /bin/sh -c 'ln -sfn /usr/local/src/ /var/www/html/wp-content/plugins/$WP_PLUGIN' www-data
    runuser -s /bin/sh -c 'wp plugin activate $WP_PLUGIN' www-data
    #runuser -s /bin/sh -c 'wp scaffold --force plugin-tests $WP_PLUGIN' www-data
    runuser -s /bin/sh -c '/var/www/html/wp-content/plugins/$WP_PLUGIN/bin/install-wp-tests.sh wordpress_test root "" db latest' www-data
    runuser -s /bin/sh -c 'ln -sfn /usr/local/src/ /tmp/wordpress/wp-content/plugins/$WP_PLUGIN' www-data
fi

if runuser -s /bin/sh -c 'wp core is-installed' www-data; then

    runuser -s /bin/sh -c 'wp db import /tmp/groups.sql' www-data

    if ! runuser -s /bin/sh -c 'wp role exists national_manager' www-data; then
        runuser -s /bin/sh -c 'wp role create national_manager "National Manager"' www-data
    fi
    if ! runuser -s /bin/sh -c 'wp role exists regional_training_centre_manager' www-data; then
        runuser -s /bin/sh -c 'wp role create regional_training_centre_manager "Regional Training Centre Manager"' www-data
    fi
    if ! runuser -s /bin/sh -c 'wp role exists training_officer' www-data; then
        runuser -s /bin/sh -c 'wp role create training_officer "Training Officer"' www-data
    fi
fi

apache2-foreground
