<?php
/**
 * Distance tests.
 *
 * @package ArrayPress\Google\Geocoding
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding\Tests;

use ArrayPress\Google\Geocoding\Distance;
use PHPUnit\Framework\TestCase;

/**
 * Covers Distance.
 *
 * The expected figures are published great-circle distances, checked to within
 * a kilometre. A tolerance that loose still fails on a wrong radius, a missing
 * deg2rad, or a swapped lat/lng, which are the ways this actually breaks.
 */
final class DistanceTest extends TestCase {

	/**
	 * Known city pairs come out at their published distances.
	 *
	 * @return void
	 */
	public function test_known_distances(): void {
		$cases = [
			// [ lat1, lng1, lat2, lng2, km ]
			'London to Paris'   => [ 51.5074, -0.1278, 48.8566, 2.3522, 343.6 ],
			'New York to LA'    => [ 40.7128, -74.0060, 34.0522, -118.2437, 3935.8 ],
			'Sydney to Auckland' => [ -33.8688, 151.2093, -36.8485, 174.7633, 2155.0 ],
		];

		foreach ( $cases as $label => [ $lat1, $lng1, $lat2, $lng2, $km ] ) {
			$this->assertEqualsWithDelta( $km, Distance::between( $lat1, $lng1, $lat2, $lng2 ), 1.0, $label );
		}
	}

	/**
	 * A point is no distance from itself.
	 *
	 * @return void
	 */
	public function test_same_point_is_zero(): void {
		$this->assertSame( 0.0, Distance::between( 51.5, -0.1, 51.5, -0.1 ) );
	}

	/**
	 * Antipodal points come back as half the circumference, not NAN.
	 *
	 * Floating point can push the haversine term a hair past 1 for opposite
	 * points, and asin() of anything above 1 is NAN. Clamping is what stops a
	 * fraud score becoming NAN for a genuinely far-away order.
	 *
	 * @return void
	 */
	public function test_antipodal_points(): void {
		$distance = Distance::between( 0.0, 0.0, 0.0, 180.0 );

		$this->assertFalse( is_nan( $distance ) );
		$this->assertEqualsWithDelta( 20015.1, $distance, 1.0 );

		$poles = Distance::between( 90.0, 0.0, -90.0, 0.0 );

		$this->assertFalse( is_nan( $poles ) );
		$this->assertEqualsWithDelta( 20015.1, $poles, 1.0 );
	}

	/**
	 * Near-antipodal pairs that actually overflow the haversine term.
	 *
	 * The clean antipodal cases above land on exactly 1.0 and never exercise
	 * the clamp. These three do: each produces a haversine term a hair above 1,
	 * and sqrt() does not round it back, so an unclamped asin() returns NAN.
	 * Found by sweeping two million near-antipodal pairs -- 9 of them overflow,
	 * so this is rare rather than theoretical, and a fraud score that silently
	 * becomes NAN for one order in a couple of hundred thousand is exactly the
	 * kind of thing nobody ever traces back.
	 *
	 * @return void
	 */
	public function test_haversine_term_overflow_is_clamped(): void {
		$pairs = [
			[ 62.67430000, -31.63100000, -62.67429976, 148.36899897 ],
			[ -68.99410000, -156.00620000, 68.99410045, 23.99379942 ],
			[ 54.03710000, -166.47220000, -54.03710009, 13.52780013 ],
		];

		foreach ( $pairs as [ $lat1, $lng1, $lat2, $lng2 ] ) {
			$distance = Distance::between( $lat1, $lng1, $lat2, $lng2 );

			$this->assertFalse( is_nan( $distance ), "{$lat1},{$lng1} -> {$lat2},{$lng2}" );
			$this->assertEqualsWithDelta( 20015.1, $distance, 1.0 );
		}
	}

	/**
	 * Distance is the same in both directions.
	 *
	 * @return void
	 */
	public function test_is_symmetric(): void {
		$there = Distance::between( 51.5074, -0.1278, 48.8566, 2.3522 );
		$back  = Distance::between( 48.8566, 2.3522, 51.5074, -0.1278 );

		$this->assertSame( $there, $back );
	}

	/**
	 * Miles are miles, and the conversion is right.
	 *
	 * @return void
	 */
	public function test_miles(): void {
		$km = Distance::between( 40.7128, -74.0060, 34.0522, -118.2437 );
		$mi = Distance::between( 40.7128, -74.0060, 34.0522, -118.2437, 'mi' );

		$this->assertEqualsWithDelta( 2445.6, $mi, 1.0 );
		$this->assertEqualsWithDelta( $km * 0.621371, $mi, 1.0 );
	}

	/**
	 * The unit is case-insensitive, and an unknown one falls back to km.
	 *
	 * @return void
	 */
	public function test_unit_handling(): void {
		$km = Distance::between( 51.5074, -0.1278, 48.8566, 2.3522 );

		$this->assertSame( Distance::between( 51.5074, -0.1278, 48.8566, 2.3522, 'MI' ), Distance::between( 51.5074, -0.1278, 48.8566, 2.3522, 'mi' ) );
		$this->assertSame( $km, Distance::between( 51.5074, -0.1278, 48.8566, 2.3522, 'furlongs' ) );
	}

	/**
	 * Crossing the antimeridian is a short hop, not most of the way round.
	 *
	 * @return void
	 */
	public function test_antimeridian(): void {
		$distance = Distance::between( 0.0, 179.9, 0.0, -179.9 );

		$this->assertLessThan( 50.0, $distance );
	}

	/**
	 * between_points() accepts both key spellings, including a mix.
	 *
	 * get_coordinates() returns latitude/longitude; most other sources use
	 * lat/lng, so both have to work or the two never compose.
	 *
	 * @return void
	 */
	public function test_between_points_accepts_both_key_spellings(): void {
		$expected = Distance::between( 51.5074, -0.1278, 48.8566, 2.3522 );

		$this->assertSame( $expected, Distance::between_points(
			[ 'lat' => 51.5074, 'lng' => -0.1278 ],
			[ 'lat' => 48.8566, 'lng' => 2.3522 ]
		) );

		$this->assertSame( $expected, Distance::between_points(
			[ 'latitude' => 51.5074, 'longitude' => -0.1278 ],
			[ 'latitude' => 48.8566, 'longitude' => 2.3522 ]
		) );

		$this->assertSame( $expected, Distance::between_points(
			[ 'lat' => 51.5074, 'lng' => -0.1278 ],
			[ 'latitude' => 48.8566, 'longitude' => 2.3522 ]
		) );
	}

	/**
	 * An incomplete point yields null rather than treating a missing value as 0.
	 *
	 * A missing longitude read as 0 puts the point in the Gulf of Guinea, which
	 * would score a perfectly ordinary order as thousands of miles out.
	 *
	 * @return void
	 */
	public function test_incomplete_points_are_null(): void {
		$good = [ 'lat' => 51.5, 'lng' => -0.1 ];

		$this->assertNull( Distance::between_points( [], $good ) );
		$this->assertNull( Distance::between_points( $good, [] ) );
		$this->assertNull( Distance::between_points( [ 'lat' => 51.5 ], $good ) );
		$this->assertNull( Distance::between_points( [ 'lng' => -0.1 ], $good ) );
		$this->assertNull( Distance::between_points( [ 'lat' => null, 'lng' => null ], $good ) );
		$this->assertNull( Distance::between_points( [ 'lat' => 'north', 'lng' => 'west' ], $good ) );
	}

	/**
	 * Numeric strings are accepted -- JSON and meta both hand them back.
	 *
	 * @return void
	 */
	public function test_numeric_strings(): void {
		$this->assertEqualsWithDelta(
			343.6,
			Distance::between_points(
				[ 'lat' => '51.5074', 'lng' => '-0.1278' ],
				[ 'lat' => '48.8566', 'lng' => '2.3522' ]
			),
			1.0
		);
	}

	/**
	 * The radii are the mean earth radius, in the right units.
	 *
	 * @return void
	 */
	public function test_radius_table(): void {
		$this->assertEqualsWithDelta( 6371.0, Distance::RADIUS['km'], 1.0 );
		$this->assertEqualsWithDelta( 3958.8, Distance::RADIUS['mi'], 1.0 );
	}

}
