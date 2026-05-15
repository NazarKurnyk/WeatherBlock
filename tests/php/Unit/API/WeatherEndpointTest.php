<?php
/**
 * Unit tests for WeatherEndpoint.
 *
 * Uses Brain\Monkey to stub WordPress functions so the tests run
 * without a full WordPress install.
 *
 * @package WeatherBlock\Tests\Unit\API
 */

declare( strict_types=1 );

namespace WeatherBlock\Tests\Unit\API;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WeatherBlock\API\WeatherEndpoint;

/**
 * @covers \WeatherBlock\API\WeatherEndpoint
 */
class WeatherEndpointTest extends TestCase {

	use MockeryPHPUnitIntegration;

	// ─── Lifecycle ──────────────────────────────────────────────────────────

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// ─── Helpers ────────────────────────────────────────────────────────────

	/** Access a private/protected method via reflection. */
	private function call( object $object, string $method, mixed ...$args ): mixed {
		$ref = new \ReflectionMethod( $object, $method );
		$ref->setAccessible( true );
		return $ref->invoke( $object, ...$args );
	}

	// ─── Latitude validation ─────────────────────────────────────────────────

	/**
	 * @dataProvider valid_lat_provider
	 */
	public function test_valid_lat_accepts_correct_values( string $lat ): void {
		$this->assertTrue( $this->call( new WeatherEndpoint(), 'is_valid_lat', $lat ) );
	}

	/** @return array<string, array{string}> */
	public static function valid_lat_provider(): array {
		return [
			'zero'         => [ '0' ],
			'positive'     => [ '50.4501' ],
			'negative'     => [ '-33.8688' ],
			'north pole'   => [ '90' ],
			'south pole'   => [ '-90' ],
			'decimal zero' => [ '0.0' ],
		];
	}

	/**
	 * @dataProvider invalid_lat_provider
	 */
	public function test_valid_lat_rejects_incorrect_values( string $lat ): void {
		$this->assertFalse( $this->call( new WeatherEndpoint(), 'is_valid_lat', $lat ) );
	}

	/** @return array<string, array{string}> */
	public static function invalid_lat_provider(): array {
		return [
			'above 90'      => [ '90.1' ],
			'below -90'     => [ '-91' ],
			'empty string'  => [ '' ],
			'text'          => [ 'north' ],
			'sql injection' => [ "50'; DROP TABLE wp_options; --" ],
		];
	}

	// ─── Longitude validation ────────────────────────────────────────────────

	/**
	 * @dataProvider valid_lon_provider
	 */
	public function test_valid_lon_accepts_correct_values( string $lon ): void {
		$this->assertTrue( $this->call( new WeatherEndpoint(), 'is_valid_lon', $lon ) );
	}

	/** @return array<string, array{string}> */
	public static function valid_lon_provider(): array {
		return [
			'zero'       => [ '0' ],
			'positive'   => [ '30.5234' ],
			'negative'   => [ '-73.9857' ],
			'east limit' => [ '180' ],
			'west limit' => [ '-180' ],
		];
	}

	/**
	 * @dataProvider invalid_lon_provider
	 */
	public function test_valid_lon_rejects_incorrect_values( string $lon ): void {
		$this->assertFalse( $this->call( new WeatherEndpoint(), 'is_valid_lon', $lon ) );
	}

	/** @return array<string, array{string}> */
	public static function invalid_lon_provider(): array {
		return [
			'above 180'  => [ '180.1' ],
			'below -180' => [ '-181' ],
			'text'       => [ 'east' ],
			'empty'      => [ '' ],
		];
	}

	// ─── Cache key ───────────────────────────────────────────────────────────

	public function test_cache_key_starts_with_prefix(): void {
		$key = $this->call( new WeatherEndpoint(), 'cache_key', '50.4501', '30.5234' );
		$this->assertStringStartsWith( 'weather_block_', (string) $key );
	}

	public function test_cache_key_is_deterministic(): void {
		$ep   = new WeatherEndpoint();
		$key1 = $this->call( $ep, 'cache_key', '50.4501', '30.5234' );
		$key2 = $this->call( $ep, 'cache_key', '50.4501', '30.5234' );
		$this->assertSame( $key1, $key2 );
	}

	public function test_cache_key_differs_for_different_coords(): void {
		$ep    = new WeatherEndpoint();
		$kyiv  = $this->call( $ep, 'cache_key', '50.4501', '30.5234' );
		$paris = $this->call( $ep, 'cache_key', '48.8566', '2.3522' );
		$this->assertNotSame( $kyiv, $paris );
	}

	public function test_cache_key_differs_when_only_lat_changes(): void {
		$ep   = new WeatherEndpoint();
		$key1 = $this->call( $ep, 'cache_key', '50.0', '30.5234' );
		$key2 = $this->call( $ep, 'cache_key', '51.0', '30.5234' );
		$this->assertNotSame( $key1, $key2 );
	}

	// ─── Normalize ───────────────────────────────────────────────────────────

	public function test_normalize_maps_full_api_response(): void {
		Functions\expect( 'sanitize_text_field' )->andReturnFirstArg();

		$raw = [
			'name'     => 'Kyiv',
			'timezone' => 10800,
			'main'     => [
				'temp'       => 20.567,
				'feels_like' => 18.234,
				'humidity'   => 65,
				'pressure'   => 1013,
			],
			'weather'  => [ [ 'description' => 'clear sky', 'icon' => '01d' ] ],
			'wind'     => [ 'speed' => 5.55 ],
			'sys'      => [ 'sunrise' => 1715660000, 'sunset' => 1715710000 ],
		];

		$result = $this->call( new WeatherEndpoint(), 'normalize', $raw );

		$this->assertSame( 'Kyiv', $result['location'] );
		$this->assertSame( 20.6, $result['temperature'] );
		$this->assertSame( 18.2, $result['feelsLike'] );
		$this->assertSame( 'clear sky', $result['condition'] );
		$this->assertSame( '01d', $result['icon'] );
		$this->assertSame( 65, $result['humidity'] );
		$this->assertSame( 1013, $result['pressure'] );
		$this->assertSame( 5.6, $result['windSpeed'] );
		$this->assertMatchesRegularExpression( '/^\d{2}:\d{2}$/', (string) $result['sunrise'] );
		$this->assertMatchesRegularExpression( '/^\d{2}:\d{2}$/', (string) $result['sunset'] );
	}

	public function test_normalize_returns_nulls_for_missing_fields(): void {
		Functions\expect( 'sanitize_text_field' )->andReturnFirstArg();

		$raw = [
			'name'     => '',
			'timezone' => 0,
			'weather'  => [ [ 'description' => '', 'icon' => '' ] ],
			'main'     => [],
		];

		$result = $this->call( new WeatherEndpoint(), 'normalize', $raw );

		$this->assertNull( $result['temperature'] );
		$this->assertNull( $result['feelsLike'] );
		$this->assertNull( $result['humidity'] );
		$this->assertNull( $result['pressure'] );
		$this->assertNull( $result['windSpeed'] );
		$this->assertNull( $result['sunrise'] );
		$this->assertNull( $result['sunset'] );
	}

	// ─── get_weather ─────────────────────────────────────────────────────────

	public function test_get_weather_returns_cached_data_without_api_call(): void {
		$cached = [ 'location' => 'Kyiv', 'temperature' => 20.0 ];

		Functions\expect( 'get_transient' )->once()->andReturn( $cached );
		Functions\expect( 'wp_remote_get' )->never();

		$result = ( new WeatherEndpoint() )->get_weather( '50.4501', '30.5234' );

		$this->assertSame( $cached, $result );
	}

	public function test_get_weather_returns_wp_error_when_api_key_missing(): void {
		Functions\expect( 'get_transient' )->andReturn( false );
		Functions\expect( 'get_option' )
			->with( WeatherEndpoint::OPTION_API_KEY, '' )
			->andReturn( '' );
		Functions\stubs( [ '__' => static fn ( $s ) => $s ] );

		$result = ( new WeatherEndpoint() )->get_weather( '50.4501', '30.5234' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'weather_block_no_api_key', $result->get_error_code() );
	}

	public function test_get_weather_returns_wp_error_on_failed_http_request(): void {
		// wp_remote_get itself returns a WP_Error (e.g. connection refused).
		$httpError = new \WP_Error( 'http_request_failed', 'cURL error 6' );

		Functions\expect( 'get_transient' )->andReturn( false );
		Functions\expect( 'get_option' )
			->with( WeatherEndpoint::OPTION_API_KEY, '' )
			->andReturn( 'my_valid_key' );
		Functions\expect( 'add_query_arg' )->andReturnFirstArg();
		Functions\expect( 'get_bloginfo' )->andReturn( 'http://localhost' );
		Functions\expect( 'wp_remote_get' )->andReturn( $httpError );
		Functions\expect( 'is_wp_error' )->with( $httpError )->andReturn( true );

		$result = ( new WeatherEndpoint() )->get_weather( '50.4501', '30.5234' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'weather_block_request_failed', $result->get_error_code() );
	}

	public function test_get_weather_returns_wp_error_on_non_200_status(): void {
		$fakeResponse = [ 'response' => [ 'code' => 401 ], 'body' => '{"cod":401}' ];

		Functions\expect( 'get_transient' )->andReturn( false );
		Functions\expect( 'get_option' )
			->with( WeatherEndpoint::OPTION_API_KEY, '' )
			->andReturn( 'my_valid_key' );
		Functions\expect( 'add_query_arg' )->andReturnFirstArg();
		Functions\expect( 'get_bloginfo' )->andReturn( 'http://localhost' );
		Functions\expect( 'wp_remote_get' )->andReturn( $fakeResponse );
		Functions\expect( 'is_wp_error' )->with( $fakeResponse )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 401 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( '{"cod":401}' );
		Functions\expect( '__' )->andReturnFirstArg();

		$result = ( new WeatherEndpoint() )->get_weather( '50.4501', '30.5234' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'weather_block_api_error', $result->get_error_code() );
	}
}
