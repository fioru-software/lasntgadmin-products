#! /bin/sh

if runuser -s /bin/sh -c 'wp core is-installed' www-data; then

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
