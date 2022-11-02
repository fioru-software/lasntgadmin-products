#! /bin/sh

if runuser -s /bin/sh -c 'wp core is-installed' www-data; then
    runuser -s /bin/sh -c 'wp db import /tmp/groups.sql' www-data
fi
