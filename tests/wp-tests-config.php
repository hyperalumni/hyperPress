<?php
define( 'ABSPATH', dirname( __DIR__ ) . '/vendor/johnpbloch/wordpress-core/' );

define( 'DB_NAME', getenv( 'WP_DB_NAME' ) ?: 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_DB_PASSWORD' ) ?: 'root' );
define( 'DB_HOST', getenv( 'WP_DB_HOST' ) ?: 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'HYPERpress Theme Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

$table_prefix = 'wptests_';
