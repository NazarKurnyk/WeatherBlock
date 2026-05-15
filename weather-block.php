<?php
/**
 * Plugin Name:       Weather Block
 * Plugin URI:        https://www.linkedin.com/in/nazar-k-745829263/
 * Description:       A Gutenberg block that displays weather information.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Nazar Kurnyk
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       weather-block
 * Domain Path:       /languages
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WEATHER_BLOCK_VERSION', '1.0.0' );
define( 'WEATHER_BLOCK_FILE', __FILE__ );
define( 'WEATHER_BLOCK_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEATHER_BLOCK_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( WEATHER_BLOCK_PATH . 'vendor/autoload.php' ) ) {
	require_once WEATHER_BLOCK_PATH . 'vendor/autoload.php';
}

add_action(
	'plugins_loaded',
	static function (): void {
		WeatherBlock\Plugin::get_instance()->init();
	}
);
