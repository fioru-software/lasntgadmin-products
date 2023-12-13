#! /bin/sh

if runuser -s /bin/sh -c 'wp core is-installed --path=/var/www/html' www-data; then
    runuser -s /bin/sh -c 'wp db import /usr/local/src/exports/groups.sql --path=/var/www/html' www-data
fi
