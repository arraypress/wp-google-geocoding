<?php
/**
 * Distance Between Coordinates
 *
 * @package     ArrayPress\Google\Geocoding
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.1.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding;

/**
 * Great-circle distance between two points.
 *
 * The reason this lives beside the geocoder: the fraud check is
 * geocode the billing address, take the coordinates the IP lookup already
 * gives you, and measure the gap. Splitting those two steps across two
 * packages only means importing both every time.
 */
class Distance {

	/**
	 * Mean earth radius, in each supported unit.
	 *
	 * @since 1.1.0
	 * @var array<string, float>
	 */
	public const RADIUS = [
		'km' => 6371.0088,
		'mi' => 3958.7613,
	];

	/**
	 * Distance between two coordinate pairs.
	 *
	 * Haversine, which treats the earth as a sphere. That is accurate to about
	 * 0.5% -- far tighter than the question "is this order coming from where
	 * the card says it is" needs, and it has no edge cases at the poles or the
	 * antimeridian the way a flat approximation does.
	 *
	 * @param float  $lat1 Latitude of the first point.
	 * @param float  $lng1 Longitude of the first point.
	 * @param float  $lat2 Latitude of the second point.
	 * @param float  $lng2 Longitude of the second point.
	 * @param string $unit 'km' or 'mi'. Anything else is treated as 'km'.
	 *
	 * @return float The distance, in the requested unit.
	 * @since 1.1.0
	 */
	public static function between( float $lat1, float $lng1, float $lat2, float $lng2, string $unit = 'km' ): float {
		$radius = self::RADIUS[ strtolower( $unit ) ] ?? self::RADIUS['km'];

		$lat_delta = deg2rad( $lat2 - $lat1 );
		$lng_delta = deg2rad( $lng2 - $lng1 );

		$a = sin( $lat_delta / 2 ) ** 2
			+ cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $lng_delta / 2 ) ** 2;

		/*
		 * asin( sqrt( a ) ), not atan2 -- $a is clamped below because floating
		 * point can push it a hair past 1 for antipodal points, and asin( 1.0000000001 )
		 * is NAN.
		 */
		return 2 * $radius * asin( min( 1.0, sqrt( $a ) ) );
	}

	/**
	 * Distance between two [ 'lat' => float, 'lng' => float ] pairs.
	 *
	 * Accepts the shape Response::get_coordinates() returns, so the two compose
	 * without unpacking.
	 *
	 * @param array  $from First point, keyed lat/lng or latitude/longitude.
	 * @param array  $to   Second point, same shape.
	 * @param string $unit 'km' or 'mi'.
	 *
	 * @return float|null The distance, or null when either point is incomplete.
	 * @since 1.1.0
	 */
	public static function between_points( array $from, array $to, string $unit = 'km' ): ?float {
		$a = self::coordinates( $from );
		$b = self::coordinates( $to );

		if ( null === $a || null === $b ) {
			return null;
		}

		return self::between( $a[0], $a[1], $b[0], $b[1], $unit );
	}

	/**
	 * Read a coordinate pair out of an array.
	 *
	 * @param array $point Point keyed lat/lng or latitude/longitude.
	 *
	 * @return array{0: float, 1: float}|null
	 * @since 1.1.0
	 */
	private static function coordinates( array $point ): ?array {
		$lat = $point['lat'] ?? $point['latitude'] ?? null;
		$lng = $point['lng'] ?? $point['longitude'] ?? null;

		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			return null;
		}

		return [ (float) $lat, (float) $lng ];
	}
}
