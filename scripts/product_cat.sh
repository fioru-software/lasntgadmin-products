#! /bin/sh

dir="/usr/local/src/exports/products"
parent_file="$dir/parents.txt"

while read -r parent_name; do

    parent_slug=$(echo "$parent_name" | tr '[:upper:]' '[:lower:]')
    parent_id=$(runuser -s /bin/sh -c "wp term get product_cat '$parent_slug' --by=slug --field=id" www-data)

    if [ -z $parent_id ]; then

        parent_id=$(runuser -s /bin/sh -c "wp term create product_cat '$parent_name' --porcelain" www-data)
        child_file="$dir/$parent_slug.txt"

        while read -r child_name; do
            child_slug=$(echo "$child_name" | tr '[:upper:]' '[:lower:]')
            child_id=$(runuser -s /bin/sh -c "wp term get product_cat '$child_slug' --by=slug --field=id" www-data)
            if [ -z $child_id ]; then
                runuser -s /bin/sh -c "wp term create product_cat '$child_name' --parent=$parent_id" www-data
            fi
        done <$child_file

    fi

done <$parent_file
