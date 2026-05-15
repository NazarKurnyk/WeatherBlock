<?php

declare( strict_types=1 );

namespace WeatherBlock\Settings;

use WeatherBlock\API\WeatherEndpoint;

class AdminPage {

	private const MENU_SLUG     = 'weather-block-settings';
	private const SETTINGS_GROUP = 'weather_block_settings_group';
	private const SECTION_ID    = 'weather_block_api_section';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_page(): void {
		add_options_page(
			__( 'Weather Block Settings', 'weather-block' ),
			__( 'Weather Block', 'weather-block' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			WeatherEndpoint::OPTION_API_KEY,
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			]
		);

		add_settings_section(
			self::SECTION_ID,
			__( 'API Configuration', 'weather-block' ),
			'__return_null',
			self::MENU_SLUG
		);

		add_settings_field(
			WeatherEndpoint::OPTION_API_KEY,
			__( 'OpenWeatherMap API Key', 'weather-block' ),
			[ $this, 'render_api_key_field' ],
			self::MENU_SLUG,
			self::SECTION_ID
		);
	}

	public function render_api_key_field(): void {
		$value = (string) get_option( WeatherEndpoint::OPTION_API_KEY, '' );
		?>
		<input
			type="password"
			id="<?php echo esc_attr( WeatherEndpoint::OPTION_API_KEY ); ?>"
			name="<?php echo esc_attr( WeatherEndpoint::OPTION_API_KEY ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="new-password"
			aria-describedby="weather-block-api-key-description"
		/>
		<p class="description" id="weather-block-api-key-description">
			<?php
			printf(
				/* translators: %s: link to OpenWeatherMap */
				wp_kses(
					__( 'Enter your free API key from <a href="%s" target="_blank" rel="noopener noreferrer">OpenWeatherMap</a>.', 'weather-block' ),
					[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
				),
				'https://openweathermap.org/api'
			);
			?>
		</p>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::MENU_SLUG );
				submit_button( __( 'Save Settings', 'weather-block' ) );
				?>
			</form>
		</div>
		<?php
	}
}
