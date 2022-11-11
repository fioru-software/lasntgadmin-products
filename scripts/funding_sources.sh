#! /bin/sh

if runuser -s /bin/sh -c 'wp core is-installed' www-data; then
    runuser -s /bin/sh -c 'wp db import /usr/local/src/exports/funding_sources.sql' www-data
fi
