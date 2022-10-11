# WordPress plugin template

Please see [WordPress plugin developer handbook](https://developer.wordpress.org/plugins/) for detailed info. 

## Development

Create `.env` file.

```
SITE_URL=localhost:8080
SITE_TITLE=WordPress
WP_PLUGINS=hello-dolly
WP_THEME=twentytwentytwo
WP_PLUGIN=wordpress-plugin
ADMIN_USERNAME=admin
ADMIN_PASSWORD=secret
ADMIN_EMAIL=wordpress@example.com
```

Build images and run WordPress.

```sh
# build image.
# override Dockerfile ARG's by appending --build-arg USER_ID=1000 to command
docker-compose build
# start container
docker-compose up -d
# wait for db
docker exec -t wordpress-plugin_template_wordpress_1 dockerize -timeout 300s -wait tcp://db:3306
# install WordPress and scaffold plugin tests
docker exec -ti wordpress-plugin_template_wordpress_1 /usr/local/bin/install.sh
# tail logs
docker exec -ti -u www-data:www-data wordpress-plugin_template_wordpress_1 tail -f /var/www/html/wp-content/debug.log
```

Run the tests

```sh
docker exec -ti -u www-data wordpress-plugin_template_wordpress_1 bash
cd wp-content/plugins/$WP_PLUGIN
composer all
```
