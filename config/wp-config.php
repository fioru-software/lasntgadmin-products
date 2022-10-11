<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //

/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'wordpress' );

/** Database password */
define( 'DB_PASSWORD', 'secret' );

/** Database hostname */
define( 'DB_HOST', 'db' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/** SMTP settings */
define( 'WPMS_ON', true );

// specific configuration settings
define( 'WPMS_SMTP_HOST', 'smtp.mailtrap.io' );
define( 'WPMS_SMTP_PORT', 587 );
define( 'WPMS_SSL', 'tls' );
define( 'WPMS_SMTP_AUTH', true );
define( 'WPMS_SMTP_USER', '88041be34d1a29' );
define( 'WPMS_SMTP_PASS', '63b436fd784ab0' );
define( 'WPMS_SMTP_AUTOTLS', true );
define( 'WPMS_MAILER', 'smtp' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', '@-1Md$L|||fz@q<1ym{P__o6*2*m)|-ff1i}-bf6G]p#U+i<uJ)9K6W$Gp4CF9tk' );
define( 'SECURE_AUTH_KEY', '[CcY_CYBl*Gl+|[85b6y5J&<bZ_z-gH(,iEI[8^&qHV:3LB@L9N0Lb$bgBWrum]^' );
define( 'LOGGED_IN_KEY', 'VlmGx+.koSc|4<D3&DL9&0z7XC?ac0-~T9L s]0]y2Q21Zrl)1=}SPv$(wR$/9-C' );
define( 'NONCE_KEY', 'kp+&roE[<~:Vc8-M}gjol;-=ru-qy`!xF:~#:16}[l- -E@e|.PYcJGmn}Tc<[F<' );
define( 'AUTH_SALT', '|J=(;7CSGb!cupzCA3CxDck#m8={QjbXcQFNcu8n~-LK(saLCiI.)zL}GTY{,p`.' );
define( 'SECURE_AUTH_SALT', '3=^AD}sX/N1!usS6Z=+4T8oc|BASa^KcWJI+z7.}42up-6$,ue/Q.|u{zjWlJ(!7' );
define( 'LOGGED_IN_SALT', 'Zo(]+<hF?|4!6q}& c~8l{+imNcRm4(w+%rB.+G>F,$e`e1sy#:`)7g!n5[H54j2' );
define( 'NONCE_SALT', '.mdJ|(VN}2V(a:4I&5<^<WaW1XK48mnpgDZmI/Dq|K+`i>_;N]fg-=6?1+#}j@b=' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */

/* Add any custom values between this line and the "stop editing" line. */

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
