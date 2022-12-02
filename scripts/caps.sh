#! /bin/sh

dir="/usr/local/src/exports/caps/*.txt"

for role_file in $dir; do
    role_name=$(basename $role_file .txt)
    while read -r cap; do
        runuser -s /bin/sh -c "wp cap add $role_name $cap" www-data
    done <$role_file
done;
