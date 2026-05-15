<?php
/**
 * Unit tests for WeatherCommand.
 *
 * @package WeatherBlock\Tests\Unit\CLI
 */

declare( strict_types=1 );

namespace WeatherBlock\Tests\Unit\CLI;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WeatherBlock\CLI\WeatherCommand;

/**
 * @covers \WeatherBlock\CLI\WeatherCommand
 */
class WeatherCommandTest extends TestCase {

	use MockeryPHPUnitIntegration;

	/** @var string Captured WP_CLI::success message (public so eval'd stub can write to it) */
	public static string $lastSuccess = '';

	/** @var string Captured WP_CLI::error message (public so eval'd stub can write to it) */
	public static string $lastError = '';

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		self::$lastSuccess = '';
		self::$lastError   = '';

		// Stub WP_CLI if the real class is not loaded (outside WP context).
		if ( ! class_exists( '\WP_CLI' ) ) {
			// phpcs:disable
			eval( '
				class WP_CLI {
					public static function success( string $m ): void {
						\WeatherBlock\Tests\Unit\CLI\WeatherCommandTest::$lastSuccess = $m;
					}
					public static function error( string $m, bool $exit = true ): void {
						\WeatherBlock\Tests\Unit\CLI\WeatherCommandTest::$lastError = $m;
						throw new \RuntimeException( $m );
					}
				}
			' );
			// phpcs:enable
		}
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// ─── Validation errors ────────────────────────────────────────────────────

	public function test_errors_when_only_lat_is_provided(): void {
		Functions\stubs( [ 'sanitize_text_field' => static fn ( $v ) => $v ] );
		Functions\stubs( [ '__' => static fn ( $s ) => $s ] );

		$this->expectException( \RuntimeException::class );

		( new WeatherCommand() )->clear_cache( [], [ 'lat' => '50.45' ] );
	}

	public function test_errors_when_only_lon_is_provided(): void {
		Functions\stubs( [ 'sanitize_text_field' => static fn ( $v ) => $v ] );
		Functions\stubs( [ '__' => static fn ( $s ) => $s ] );

		$this->expectException( \RuntimeException::class );

		( new WeatherCommand() )->clear_cache( [], [ 'lon' => '30.52' ] );
	}

	public function test_does_not_error_when_both_coords_are_provided(): void {
		Functions\stubs( [
			'sanitize_text_field' => static fn ( $v ) => $v,
			'__'                  => static fn ( $s ) => $s,
			'_n'                  => static fn ( $s ) => $s,
		] );

		// Stub the WP functions that WeatherEndpoint::clear_cache() calls.
		Functions\stubs( [ 'delete_transient' ] );

		$this->expectNotToPerformAssertions();

		( new WeatherCommand() )->clear_cache( [], [ 'lat' => '50.45', 'lon' => '30.52' ] );
	}

	public function test_does_not_error_when_no_coords_provided(): void {
		global $wpdb;

		Functions\stubs( [
			'sanitize_text_field' => static fn ( $v ) => $v,
			'__'                  => static fn ( $s ) => $s,
			'_n'                  => static fn ( $s, $p, $n ) => $n === 1 ? $s : $p,
			'delete_transient'    => static fn () => true,
		] );

		// Mock global $wpdb for the "clear all" query path.
		$wpdb         = new \stdClass();
		$wpdb->options = 'wp_options';
		$wpdb->expects = []; // phpcs:ignore
		// Make esc_like and prepare pass through, get_col return empty array.
		$wpdbMock = \Mockery::mock( 'wpdb' );
		$wpdbMock->options = 'wp_options';
		$wpdbMock->shouldReceive( 'esc_like' )->andReturnUsing( static fn ( $v ) => $v );
		$wpdbMock->shouldReceive( 'prepare' )->andReturn( '' );
		$wpdbMock->shouldReceive( 'get_col' )->andReturn( [] );
		$GLOBALS['wpdb'] = $wpdbMock;

		$this->expectNotToPerformAssertions();

		( new WeatherCommand() )->clear_cache( [], [] );

		$GLOBALS['wpdb'] = null;
	}
}
