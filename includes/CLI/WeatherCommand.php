<?php

declare( strict_types=1 );

namespace WeatherBlock\CLI;

use WeatherBlock\API\WeatherEndpoint;

/**
 * Manages Weather Block cached data.
 */
class WeatherCommand {

	/**
	 * Clears cached weather data.
	 *
	 * ## OPTIONS
	 *
	 * [--lat=<lat>]
	 * : Latitude of the location to clear. Must be used together with --lon.
	 *
	 * [--lon=<lon>]
	 * : Longitude of the location to clear. Must be used together with --lat.
	 *
	 * ## EXAMPLES
	 *
	 *     wp weather-block clear-cache
	 *     wp weather-block clear-cache --lat=50.45 --lon=30.52
	 *
	 * @subcommand clear-cache
	 */
	public function clear_cache( array $args, array $assoc_args ): void {
		$lat = isset( $assoc_args['lat'] ) ? sanitize_text_field( $assoc_args['lat'] ) : null;
		$lon = isset( $assoc_args['lon'] ) ? sanitize_text_field( $assoc_args['lon'] ) : null;

		if ( ( null !== $lat ) !== ( null !== $lon ) ) {
			\WP_CLI::error(
				__( '--lat and --lon must be provided together.', 'weather-block' )
			);
		}

		$endpoint = new WeatherEndpoint();
		$count    = $endpoint->clear_cache( $lat, $lon );

		if ( null !== $lat && null !== $lon ) {
			\WP_CLI::success(
				sprintf(
					/* translators: 1: latitude, 2: longitude */
					__( 'Weather cache cleared for lat=%1$s, lon=%2$s.', 'weather-block' ),
					$lat,
					$lon
				)
			);
		} else {
			\WP_CLI::success(
				sprintf(
					/* translators: %d: number of cache entries cleared */
					_n(
						'Cleared %d weather cache entry.',
						'Cleared %d weather cache entries.',
						$count,
						'weather-block'
					),
					$count
				)
			);
		}
	}
}
