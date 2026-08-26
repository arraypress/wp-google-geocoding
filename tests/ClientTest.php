<?php
/**
 * Client tests.
 *
 * @package ArrayPress\Google\Geocoding
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding\Tests;

use ArrayPress\Google\Geocoding\Client;
use ArrayPress\Google\Geocoding\Response;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * Covers Client.
 */
final class ClientTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		gg_reset();
	}

	/**
	 * A geocode returns a Response carrying the result.
	 *
	 * @return void
	 */
	public function test_geocode_returns_a_response(): void {
		gg_will_return( gg_ok() );

		$result = ( new Client( 'key' ) )->geocode( '10 Downing Street' );

		$this->assertInstanceOf( Response::class, $result );
		$this->assertSame( 51.5033635, $result->get_latitude() );
		$this->assertSame( -0.1276248, $result->get_longitude() );
	}

	/**
	 * The address and key reach the query string.
	 *
	 * @return void
	 */
	public function test_geocode_sends_address_and_key(): void {
		gg_will_return( gg_ok() );

		( new Client( 'secret-key' ) )->geocode( '10 Downing Street' );

		$url = gg_last_url();

		$this->assertStringStartsWith( 'https://maps.googleapis.com/maps/api/geocode/json?', $url );
		$this->assertStringContainsString( 'address=' . rawurlencode( '10 Downing Street' ), str_replace( '+', '%20', $url ) );
		$this->assertStringContainsString( 'key=secret-key', $url );
	}

	/**
	 * A reverse geocode sends latlng, not address.
	 *
	 * @return void
	 */
	public function test_reverse_geocode_sends_latlng(): void {
		gg_will_return( gg_ok() );

		( new Client( 'key' ) )->reverse_geocode( 51.5033635, -0.1276248 );

		$url = gg_last_url();

		$this->assertStringContainsString( 'latlng=51.5033635%2C-0.1276248', $url );
		$this->assertStringNotContainsString( 'address=', $url );
	}

	/**
	 * ZERO_RESULTS is a Response with nothing in it, not an error.
	 *
	 * Google uses it for "that address does not exist", which is an answer.
	 *
	 * @return void
	 */
	public function test_zero_results_is_not_an_error(): void {
		gg_will_return( gg_status( 'ZERO_RESULTS' ) );

		$result = ( new Client( 'key' ) )->geocode( 'nowhere at all' );

		$this->assertInstanceOf( Response::class, $result );
		$this->assertNull( $result->get_coordinates() );
		$this->assertSame( 'ZERO_RESULTS', $result->get_status() );
	}

	/**
	 * Google's own error statuses become WP_Errors naming the status.
	 *
	 * @return void
	 */
	public function test_google_error_statuses_become_errors(): void {
		foreach ( [ 'REQUEST_DENIED', 'OVER_QUERY_LIMIT', 'INVALID_REQUEST', 'UNKNOWN_ERROR' ] as $status ) {
			gg_reset();
			gg_will_return( gg_status( $status ) );

			$result = ( new Client( 'key' ) )->geocode( 'somewhere' );

			$this->assertInstanceOf( WP_Error::class, $result, $status );
			$this->assertStringContainsString( $status, $result->get_error_message() );
		}
	}

	/**
	 * A transport failure is reported, and names the underlying reason.
	 *
	 * @return void
	 */
	public function test_transport_error(): void {
		gg_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );

		$result = ( new Client( 'key' ) )->geocode( 'somewhere' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Connection timed out', $result->get_error_message() );
	}

	/**
	 * A body that is not JSON is an error, not an empty Response.
	 *
	 * @return void
	 */
	public function test_unparseable_body(): void {
		gg_will_return( [ 'body' => '<html>502 Bad Gateway</html>' ] );

		$result = ( new Client( 'key' ) )->geocode( 'somewhere' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'json_error', $result->get_error_code() );
	}

	/**
	 * Valid JSON in an unexpected shape is an error rather than a notice.
	 *
	 * Reading $data['status'] straight off the decoded body warns when Google
	 * returns something else -- an HTML error page that happens to parse, or a
	 * proxy's JSON.
	 *
	 * @return void
	 */
	public function test_json_without_a_status(): void {
		gg_will_return( [ 'body' => wp_json_encode( [ 'unexpected' => true ] ) ] );

		$result = ( new Client( 'key' ) )->geocode( 'somewhere' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'json_error', $result->get_error_code() );
	}

	/**
	 * A repeat lookup is served from cache.
	 *
	 * @return void
	 */
	public function test_repeat_lookup_is_cached(): void {
		gg_will_return( gg_ok() );

		$client = new Client( 'key' );
		$client->geocode( '10 Downing Street' );
		$client->geocode( '10 Downing Street' );

		$this->assertSame( 1, gg_request_count() );
	}

	/**
	 * A different address is a different request.
	 *
	 * @return void
	 */
	public function test_different_address_is_not_cached(): void {
		gg_will_return( gg_ok() );

		$client = new Client( 'key' );
		$client->geocode( 'one place' );
		$client->geocode( 'another place' );

		$this->assertSame( 2, gg_request_count() );
	}

	/**
	 * Forward and reverse lookups do not share a cache entry.
	 *
	 * @return void
	 */
	public function test_forward_and_reverse_have_separate_keys(): void {
		gg_will_return( gg_ok() );

		$client = new Client( 'key' );
		$client->geocode( '51.5,-0.1' );
		$client->reverse_geocode( 51.5, -0.1 );

		$this->assertSame( 2, gg_request_count() );
	}

	/**
	 * Two keys do not share cached answers.
	 *
	 * @return void
	 */
	public function test_cache_key_includes_the_api_key(): void {
		gg_will_return( gg_ok() );

		( new Client( 'key-one' ) )->geocode( 'somewhere' );
		( new Client( 'key-two' ) )->geocode( 'somewhere' );

		$this->assertSame( 2, gg_request_count() );
	}

	/**
	 * Caching can be switched off.
	 *
	 * @return void
	 */
	public function test_cache_can_be_disabled(): void {
		gg_will_return( gg_ok() );

		$client = new Client( 'key', false );
		$client->geocode( 'somewhere' );
		$client->geocode( 'somewhere' );

		$this->assertSame( 2, gg_request_count() );
	}

	/**
	 * A failure is remembered briefly, so an outage costs one visitor a timeout.
	 *
	 * Without it every request re-asks a dead endpoint and waits out the full
	 * HTTP timeout, and a quota error burns the next allowance as fast as it is
	 * granted.
	 *
	 * @return void
	 */
	public function test_failures_are_not_retried_immediately(): void {
		gg_will_return( new WP_Error( 'http_request_failed', 'down' ) );

		$client = new Client( 'key' );

		$first = $client->geocode( 'somewhere' );
		$this->assertInstanceOf( WP_Error::class, $first );

		$second = $client->geocode( 'somewhere' );

		$this->assertInstanceOf( WP_Error::class, $second );
		$this->assertSame( 'geocoding_recent_failure', $second->get_error_code() );
		$this->assertSame( 1, gg_request_count() );
	}

	/**
	 * One failing address does not blind the client to every other one.
	 *
	 * @return void
	 */
	public function test_failure_is_remembered_per_address(): void {
		gg_will_return( new WP_Error( 'http_request_failed', 'down' ) );

		$client = new Client( 'key' );
		$client->geocode( 'bad address' );

		gg_will_return( gg_ok() );

		$this->assertInstanceOf( Response::class, $client->geocode( 'good address' ) );
	}

	/**
	 * The failure memory can be switched off.
	 *
	 * @return void
	 */
	public function test_failure_ttl_can_be_disabled(): void {
		gg_will_return( new WP_Error( 'http_request_failed', 'down' ) );

		$client = new Client( 'key' );
		$client->set_failure_ttl( 0 );

		$client->geocode( 'somewhere' );
		$client->geocode( 'somewhere' );

		$this->assertSame( 2, gg_request_count() );
	}

	/**
	 * A negative failure ttl is clamped rather than treated as "always failed".
	 *
	 * @return void
	 */
	public function test_negative_failure_ttl_is_clamped(): void {
		$client = new Client( 'key' );
		$client->set_failure_ttl( -30 );

		$this->assertSame( 0, $client->get_failure_ttl() );
	}

	/**
	 * A successful answer is not shadowed by an earlier failure record.
	 *
	 * @return void
	 */
	public function test_success_after_the_failure_window(): void {
		gg_will_return( new WP_Error( 'http_request_failed', 'down' ) );

		$client = new Client( 'key' );
		$client->set_failure_ttl( 0 );
		$client->geocode( 'somewhere' );

		gg_will_return( gg_ok() );

		$this->assertInstanceOf( Response::class, $client->geocode( 'somewhere' ) );
	}

	/**
	 * clear_cache() removes the timeout rows as well as the values.
	 *
	 * @return void
	 */
	public function test_clear_cache_leaves_nothing_behind(): void {
		gg_will_return( gg_ok() );

		$client = new Client( 'key' );
		$client->geocode( 'somewhere' );

		$this->assertNotEmpty( $GLOBALS['gg']['options'] );

		$client->clear_cache();

		$this->assertSame( [], $GLOBALS['gg']['options'] );
	}

	/**
	 * Errors are not cached as answers.
	 *
	 * @return void
	 */
	public function test_errors_are_not_cached_as_results(): void {
		gg_will_return( gg_status( 'OVER_QUERY_LIMIT' ) );

		$client = new Client( 'key' );
		$client->set_failure_ttl( 0 );
		$this->assertInstanceOf( WP_Error::class, $client->geocode( 'somewhere' ) );

		gg_will_return( gg_ok() );

		$this->assertInstanceOf( Response::class, $client->geocode( 'somewhere' ) );
	}

}
