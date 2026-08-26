<?php
/**
 * Response tests.
 *
 * @package ArrayPress\Google\Geocoding
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding\Tests;

use ArrayPress\Google\Geocoding\Distance;
use ArrayPress\Google\Geocoding\Response;
use PHPUnit\Framework\TestCase;

/**
 * Covers Response.
 */
final class ResponseTest extends TestCase {

	/**
	 * A decoded OK body.
	 *
	 * @return array
	 */
	private function body(): array {
		return json_decode( gg_ok()['body'], true );
	}

	/**
	 * Coordinates come back as floats under latitude/longitude.
	 *
	 * @return void
	 */
	public function test_coordinates(): void {
		$response = new Response( $this->body() );

		$this->assertSame(
			[ 'latitude' => 51.5033635, 'longitude' => -0.1276248 ],
			$response->get_coordinates()
		);
		$this->assertSame( 51.5033635, $response->get_latitude() );
		$this->assertSame( -0.1276248, $response->get_longitude() );
	}

	/**
	 * Address components are read out by type, long and short.
	 *
	 * @return void
	 */
	public function test_address_components(): void {
		$response = new Response( $this->body() );

		$this->assertSame( 'United Kingdom', $response->get_country() );
		$this->assertSame( 'GB', $response->get_country_short() );
		$this->assertSame( 'SW1A 2AA', $response->get_postal_code() );
		$this->assertSame( '10', $response->get_street_number() );
		$this->assertSame( 'Downing Street', $response->get_street_name() );
	}

	/**
	 * The formatted address, place id and location type are exposed.
	 *
	 * @return void
	 */
	public function test_result_metadata(): void {
		$response = new Response( $this->body() );

		$this->assertSame( '10 Downing St, London SW1A 2AA, UK', $response->get_formatted_address() );
		$this->assertSame( 'ChIJRxzEqhwFdkgRcgHz1UEhVPk', $response->get_place_id() );
		$this->assertSame( 'ROOFTOP', $response->get_location_type() );
		$this->assertSame( 'OK', $response->get_status() );
		$this->assertSame( [ 'street_address' ], $response->get_types() );
	}

	/**
	 * An empty response reads as nulls rather than throwing.
	 *
	 * @return void
	 */
	public function test_empty_response(): void {
		$response = new Response( [ 'status' => 'ZERO_RESULTS', 'results' => [] ] );

		$this->assertNull( $response->get_coordinates() );
		$this->assertNull( $response->get_latitude() );
		$this->assertNull( $response->get_formatted_address() );
		$this->assertNull( $response->get_country() );
		$this->assertSame( [], $response->get_results() );
		$this->assertSame( 'ZERO_RESULTS', $response->get_status() );
	}

	/**
	 * A missing component is null, not an empty string.
	 *
	 * @return void
	 */
	public function test_missing_component(): void {
		$this->assertNull( ( new Response( $this->body() ) )->get_address_component( 'subpremise' ) );
	}

	/**
	 * A partial match is reported, because it is a fraud signal in itself.
	 *
	 * @return void
	 */
	public function test_partial_match(): void {
		$body = $this->body();

		$this->assertFalse( ( new Response( $body ) )->is_partial_match() );

		$body['results'][0]['partial_match'] = true;

		$this->assertTrue( ( new Response( $body ) )->is_partial_match() );
	}

	/**
	 * Coordinates feed Distance directly, which is the whole point of the pair.
	 *
	 * @return void
	 */
	public function test_coordinates_compose_with_distance(): void {
		$response = new Response( $this->body() );

		$distance = Distance::between_points(
			$response->get_coordinates(),
			[ 'lat' => 48.8566, 'lng' => 2.3522 ]
		);

		$this->assertNotNull( $distance );
		$this->assertEqualsWithDelta( 343.0, $distance, 2.0 );
	}

}
