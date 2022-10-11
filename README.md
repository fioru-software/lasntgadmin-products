# WordPress plugin template

Please see [WordPress plugin developer handbook](https://developer.wordpress.org/plugins/) for detailed info. 

## Development

Emails will be sent to [Mailtrap Inbox](https://mailtrap.io/). Credentials are available on our [Bitwarden](https://bitwarden.veri.ie).

Create `.env` file.

```
SITE_URL=localhost:8080
SITE_TITLE=WordPress
WP_PLUGIN=example # this plugin
WP_PLUGINS=groups woocommerce advanced-custom-fields user-role-editor convergewoocommerce wp-mail-smtp
WP_THEME=storefront
WP_PLUGIN=lasntg-plugin_template
ADMIN_USERNAME=admin
ADMIN_PASSWORD=secret
ADMIN_EMAIL=admin@example.com
```

Build images and run WordPress.

```sh
# build image.
# override Dockerfile ARG's by appending --build-arg USER_ID=1000 to command
docker-compose build
# start container
docker-compose up wordpress 
```

Run the tests

```sh
docker exec -ti -u www-data lasntg-plugin_template_wordpress_1 bash
cd wp-content/plugins/$WP_PLUGIN
composer all
```

Visit [http://localhost:8080/wp-login.php](localhost:8080/wp-login.php)
