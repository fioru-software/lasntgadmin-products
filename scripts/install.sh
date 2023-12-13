#! /bin/sh

dockerize -timeout 300s -wait tcp://$DB_HOST:3306

if ! runuser -s /bin/sh -c 'wp core is-installed --path=/var/www/html' www-data; then
    runuser -s /bin/sh -c 'wp core install --url=$SITE_URL --title="$SITE_TITLE" --admin_user=$ADMIN_USERNAME --admin_email=$ADMIN_EMAIL --admin_password=$ADMIN_PASSWORD --path=/var/www/html' www-data
    runuser -s /bin/sh -c 'wp rewrite structure --hard "/%postname%/" --path=/var/www/html' www-data
fi
