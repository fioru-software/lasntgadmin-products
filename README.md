# LASNTG WordPress plugin template

Please see [WordPress plugin developer handbook](https://developer.wordpress.org/plugins/) for detailed info. 

## Development

### PHP

Emails will be sent to [Mailtrap Inbox](https://mailtrap.io/). Credentials are available on our [Bitwarden](https://bitwarden.veri.ie).

Create `.env` file. 

Add your plugin folder name to the `WP_PLUGIN=` environment variable.

```
SITE_URL=localhost:8080
SITE_TITLE=WordPress
WP_PLUGIN=example-plugin
WP_PLUGINS=groups woocommerce advanced-custom-fields user-role-editor wp-mail-smtp
WP_THEME=storefront
ADMIN_USERNAME=admin
ADMIN_PASSWORD=secret
ADMIN_EMAIL=admin@example.com
```

Build images and run WordPress.

```sh
# build Docker image
docker-compose build # optionally override Dockerfile build arguments by appending --build-arg USER_ID=$(id -u)
# start container
docker-compose up wordpress 
```

Run the tests

```sh
docker exec -ti -u www-data lasntg-plugin_template_wordpress_1 bash
cd wp-content/plugins/$WP_PLUGIN
composer install
composer all
```

__Note:__ Most WordPress coding convention errors can be automatically fixed by running `composer fix`

Visit [http://localhost:8080/wp-login.php](localhost:8080/wp-login.php)

### React

```sh
docker run --rm -u node:node -v $(pwd):/usr/local/src -w /usr/local/src -ti node:lts-alpine ash
npm install
npm start
npm build
```

## Release a version

Update `composer.json` file with appropriate `name`, `version` and `installer-name`.

Update `Version: 0.0.0` of main plugin file, in this case `example-plugin.php`.

Use `git` to commit and push changes to origin.

Use `git` to tag the commit and push it to origin.

```sh
# list existing tag
git tag -l
# tag the commit with appropriate version
git tag -a 0.1.4
# push tag as a release
git push -u origin 0.1.4
```

## Plugins

- [Creating your first WooCommerce extension](https://developer.woocommerce.com/extension-developer-guide/creating-your-first-extension/)
- [Create your first app with Gutenberg data](https://developer.wordpress.org/block-editor/how-to-guides/data-basics/1-data-basics-setup/)
- [Introduction to WordPress Plugin Development](https://developer.wordpress.org/plugins/intro/)
- [Tutorial: Adding React Support to a WooCommerce Extension](https://developer.woocommerce.com/2020/11/13/tutorial-adding-react-support-to-a-woocommerce-extension/)
- [Groups Documentation](https://docs.itthinx.com/document/groups/)
- [WooCommerce Developer Resources](https://developer.woocommerce.com/)
- [Evalon WooCommerce Payment Gateway](https://developer.elavon.com/na/docs/converge/1.0.0/integration-guide/shopping_carts/woocommerce_installation_guide)
- [Advanced Custom Fields](https://www.advancedcustomfields.com/resources)
- [WooCommerce Storefront Theme](https://woocommerce.com/documentation/themes/storefront/)
- [WP Mail SMTP](https://wpmailsmtp.com/docs/)
