<?php

declare( strict_types=1 );

namespace WeatherBlock\API;

class WeatherEndpoint {

	public const NONCE_ACTION   = 'weather_block_nonce';
	public const OPTION_API_KEY = 'weather_block_api_key';
	private const CACHE_PREFIX  = 'weather_block_';
	private const CACHE_TTL     = HOUR_IN_SECONDS;
	private const API_URL       = 'https://api.openweathermap.org/data/2.5/weather';

	public function register(): void {
		add_action( 'wp_ajax_weather_block_get_weather', [ $this, 'handle_request' ] );
		add_action( 'wp_ajax_nopriv_weather_block_get_weather', [ $this, 'handle_request' ] );
	}

	public function handle_request(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$lat = sanitize_text_field( wp_unslash( (string) ( $_POST['lat'] ?? '' ) ) );
		$lon = sanitize_text_field( wp_unslash( (string) ( $_POST['lon'] ?? '' ) ) );

		if ( ! $this->is_valid_lat( $lat ) || ! $this->is_valid_lon( $lon ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid coordinates provided.', 'weather-block' ) ],
				400
			);
		}

		$result = $this->get_weather( $lat, $lon );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 500 );
		}

		wp_send_json_success( $result );
	}

	public function get_weather( string $lat, string $lon ): array|\WP_Error {
		$cache_key = $this->cache_key( $lat, $lon );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$api_key = (string) get_option( self::OPTION_API_KEY, '' );

		if ( '' === $api_key ) {
			return new \WP_Error(
				'weather_block_no_api_key',
				__( 'OpenWeatherMap API key is not configured.', 'weather-block' )
			);
		}

		$url = add_query_arg(
			[
				'lat'   => $lat,
				'lon'   => $lon,
				'appid' => $api_key,
				'units' => 'metric',
			],
			self::API_URL
		);

		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 10,
				'user-agent' => 'WeatherBlock/' . WEATHER_BLOCK_VERSION . '; ' . get_bloginfo( 'url' ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'weather_block_request_failed', $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status ) {
			return new \WP_Error(
				'weather_block_api_error',
				/* translators: %d: HTTP response code */
				sprintf( __( 'API returned HTTP %d.', 'weather-block' ), $status )
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error(
				'weather_block_parse_error',
				__( 'Failed to parse weather response.', 'weather-block' )
			);
		}

		$weather = $this->normalize( $data );
		set_transient( $cache_key, $weather, self::CACHE_TTL );

		return $weather;
	}

	public function clear_cache( ?string $lat = null, ?string $lon = null ): int {
		if ( null !== $lat && null !== $lon ) {
			delete_transient( $this->cache_key( $lat, $lon ) );
			return 1;
		}

		return $this->clear_all_cache();
	}

	private function clear_all_cache(): int {
		global $wpdb;

		$prefix  = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%';
		$results = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$prefix
			)
		);

		$count = 0;
		foreach ( $results as $option_name ) {
			$key = str_replace( '_transient_', '', (string) $option_name );
			delete_transient( $key );
			++$count;
		}

		return $count;
	}

	private function normalize( array $data ): array {
		$timezone = (int) ( $data['timezone'] ?? 0 );
		$sunrise  = isset( $data['sys']['sunrise'] ) ? (int) $data['sys']['sunrise'] + $timezone : null;
		$sunset   = isset( $data['sys']['sunset'] ) ? (int) $data['sys']['sunset'] + $timezone : null;

		return [
			'location'    => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'temperature' => isset( $data['main']['temp'] ) ? round( (float) $data['main']['temp'], 1 ) : null,
			'feelsLike'   => isset( $data['main']['feels_like'] ) ? round( (float) $data['main']['feels_like'], 1 ) : null,
			'condition'   => sanitize_text_field( (string) ( $data['weather'][0]['description'] ?? '' ) ),
			'icon'        => sanitize_text_field( (string) ( $data['weather'][0]['icon'] ?? '' ) ),
			'humidity'    => isset( $data['main']['humidity'] ) ? (int) $data['main']['humidity'] : null,
			'pressure'    => isset( $data['main']['pressure'] ) ? (int) $data['main']['pressure'] : null,
			'windSpeed'   => isset( $data['wind']['speed'] ) ? round( (float) $data['wind']['speed'], 1 ) : null,
			'sunrise'     => null !== $sunrise ? gmdate( 'H:i', $sunrise ) : null,
			'sunset'      => null !== $sunset ? gmdate( 'H:i', $sunset ) : null,
		];
	}

	private function cache_key( string $lat, string $lon ): string {
		return self::CACHE_PREFIX . md5( $lat . ',' . $lon );
	}

	private function is_valid_lat( string $value ): bool {
		return is_numeric( $value ) && (float) $value >= -90.0 && (float) $value <= 90.0;
	}

	private function is_valid_lon( string $value ): bool {
		return is_numeric( $value ) && (float) $value >= -180.0 && (float) $value <= 180.0;
	}
}
