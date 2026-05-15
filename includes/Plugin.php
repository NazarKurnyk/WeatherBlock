<?php

declare( strict_types=1 );

namespace WeatherBlock;

use WeatherBlock\API\WeatherEndpoint;
use WeatherBlock\Block\WeatherBlock;
use WeatherBlock\CLI\WeatherCommand;
use WeatherBlock\Settings\AdminPage;

final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );

		( new WeatherBlock() )->register();
		( new WeatherEndpoint() )->register();
		( new AdminPage() )->register();

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'weather-block', WeatherCommand::class );
		}
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'weather-block',
			false,
			dirname( plugin_basename( WEATHER_BLOCK_FILE ) ) . '/languages'
		);
	}
}
