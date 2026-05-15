<?php
/**
 * PHPUnit bootstrap — sets up autoloading and WordPress stubs.
 *
 * @package WeatherBlock\Tests
 */

declare( strict_types=1 );

// Plugin autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// ─── WordPress constants ──────────────────────────────────────────────────────
defined( 'ABSPATH' )               || define( 'ABSPATH', '/tmp/wp/' );
defined( 'WEATHER_BLOCK_VERSION' ) || define( 'WEATHER_BLOCK_VERSION', '1.0.0' );
defined( 'WEATHER_BLOCK_FILE' )    || define( 'WEATHER_BLOCK_FILE', dirname( __DIR__, 2 ) . '/weather-block.php' );
defined( 'WEATHER_BLOCK_PATH' )    || define( 'WEATHER_BLOCK_PATH', dirname( __DIR__, 2 ) . '/' );
defined( 'WEATHER_BLOCK_URL' )     || define( 'WEATHER_BLOCK_URL', 'http://localhost/wp-content/plugins/weather-block/' );
defined( 'HOUR_IN_SECONDS' )       || define( 'HOUR_IN_SECONDS', 3600 );

// ─── WP_Error stub ────────────────────────────────────────────────────────────
if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for unit tests (no WordPress required).
	 */
	class WP_Error { // phpcs:ignore
		private string $code;
		private string $message;
		/** @var mixed */
		private mixed $data;

		/** @param mixed $data */
		public function __construct( string $code = '', string $message = '', mixed $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		/** @return mixed */
		public function get_error_data(): mixed {
			return $this->data;
		}
	}
}
