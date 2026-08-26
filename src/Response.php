<?php
/**
 * Google Geocoding API Response Class
 *
 * @package     ArrayPress\Google\Geocoding
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding;

/**
 * Class Response
 *
 * Handles and structures the response data from Google Geocoding API.
 */
class Response {

	/**
	 * Raw response data from the API
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize the response object
	 *
	 * @param array $data Raw response data from Geocoding API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get raw data array
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->data;
	}

	/**
	 * Get the first result from the response
	 *
	 * @return array|null
	 */
	public function get_first_result(): ?array {
		return $this->data['results'][0] ?? null;
	}

	/**
	 * Get all results from the response
	 *
	 * @return array
	 */
	public function get_results(): array {
		return $this->data['results'] ?? [];
	}

	/**
	 * Get formatted address
	 *
	 * @return string|null
	 */
	public function get_formatted_address(): ?string {
		$result = $this->get_first_result();

		return $result['formatted_address'] ?? null;
	}

	/**
	 * Get coordinates
	 *
	 * @return array|null Array with 'latitude' and 'longitude' or null if not available
	 */
	public function get_coordinates(): ?array {
		$result = $this->get_first_result();
		if ( isset( $result['geometry']['location'] ) ) {
			return [
				'latitude'  => $result['geometry']['location']['lat'],
				'longitude' => $result['geometry']['location']['lng'],
			];
		}

		return null;
	}

	/**
	 * Get latitude
	 *
	 * @return float|null
	 */
	public function get_latitude(): ?float {
		$coordinates = $this->get_coordinates();

		return $coordinates ? $coordinates['latitude'] : null;
	}

	/**
	 * Get longitude
	 *
	 * @return float|null
	 */
	public function get_longitude(): ?float {
		$coordinates = $this->get_coordinates();

		return $coordinates ? $coordinates['longitude'] : null;
	}

	/**
	 * Get place ID
	 *
	 * @return string|null
	 */
	public function get_place_id(): ?string {
		$result = $this->get_first_result();

		return $result['place_id'] ?? null;
	}

	/**
	 * Get plus code information
	 *
	 * @return array|null Array containing compound_code and global_code
	 */
	public function get_plus_code(): ?array {
		return $this->data['plus_code'] ?? $this->get_first_result()['plus_code'] ?? null;
	}

	/**
	 * Get plus code compound code
	 *
	 * @return string|null
	 */
	public function get_plus_code_compound(): ?string {
		$plus_code = $this->get_plus_code();

		return $plus_code['compound_code'] ?? null;
	}

	/**
	 * Get plus code global code
	 *
	 * @return string|null
	 */
	public function get_plus_code_global(): ?string {
		$plus_code = $this->get_plus_code();

		return $plus_code['global_code'] ?? null;
	}

	/**
	 * Get location type (ROOFTOP, RANGE_INTERPOLATED, GEOMETRIC_CENTER, APPROXIMATE)
	 *
	 * @return string|null
	 */
	public function get_location_type(): ?string {
		$result = $this->get_first_result();

		return $result['geometry']['location_type'] ?? null;
	}

	/**
	 * Get result types
	 *
	 * @return array Types like 'street_address', 'route', 'political', etc.
	 */
	public function get_types(): array {
		$result = $this->get_first_result();

		return $result['types'] ?? [];
	}

	/**
	 * Get address components
	 *
	 * @return array
	 */
	public function get_address_components(): array {
		$result = $this->get_first_result();

		return $result['address_components'] ?? [];
	}

	/**
	 * Get address components by multiple types
	 *
	 * @param array $types Array of types to match
	 *
	 * @return array Array of matching address components
	 */
	public function get_address_components_by_types( array $types ): array {
		return array_filter( $this->get_address_components(), function ( $component ) use ( $types ) {
			return count( array_intersect( $types, $component['types'] ) ) === count( $types );
		} );
	}

	/**
	 * Get specific address component
	 *
	 * @param string $type The type of address component to retrieve
	 *
	 * @return string|null
	 */
	public function get_address_component( string $type ): ?string {
		$components = $this->get_address_components();
		foreach ( $components as $component ) {
			if ( in_array( $type, $component['types'], true ) ) {
				return $component['long_name'];
			}
		}

		return null;
	}

	/**
	 * Get specific address component short name
	 *
	 * @param string $type The type of address component to retrieve
	 *
	 * @return string|null
	 */
	public function get_address_component_short( string $type ): ?string {
		$components = $this->get_address_components();
		foreach ( $components as $component ) {
			if ( in_array( $type, $component['types'], true ) ) {
				return $component['short_name'];
			}
		}

		return null;
	}

	/**
	 * Get street number
	 *
	 * @return string|null
	 */
	public function get_street_number(): ?string {
		return $this->get_address_component( 'street_number' );
	}

	/**
	 * Get street name
	 *
	 * @return string|null
	 */
	public function get_street_name(): ?string {
		return $this->get_address_component( 'route' );
	}

	/**
	 * Get combined street address (number and name)
	 *
	 * @return string|null
	 */
	public function get_street(): ?string {
		$number = $this->get_street_number();
		$name   = $this->get_street_name();

		if ( empty( $number ) && empty( $name ) ) {
			return null;
		}

		return trim( $number . ' ' . $name );
	}

	/**
	 * Get neighborhood
	 *
	 * @return string|null
	 */
	public function get_neighborhood(): ?string {
		return $this->get_address_component( 'neighborhood' );
	}

	/**
	 * Get sublocality
	 *
	 * @return string|null
	 */
	public function get_sublocality(): ?string {
		return $this->get_address_component( 'sublocality' );
	}

	/**
	 * Get sublocality level 1
	 *
	 * @return string|null
	 */
	public function get_sublocality_level_1(): ?string {
		return $this->get_address_component( 'sublocality_level_1' );
	}

	/**
	 * Get city/locality
	 *
	 * @return string|null
	 */
	public function get_city(): ?string {
		return $this->get_address_component( 'locality' );
	}

	/**
	 * Get state/province
	 *
	 * @return string|null
	 */
	public function get_state(): ?string {
		return $this->get_address_component( 'administrative_area_level_1' );
	}

	/**
	 * Get state/province short name
	 *
	 * @return string|null
	 */
	public function get_state_short(): ?string {
		return $this->get_address_component_short( 'administrative_area_level_1' );
	}

	/**
	 * Get county
	 *
	 * @return string|null
	 */
	public function get_county(): ?string {
		return $this->get_address_component( 'administrative_area_level_2' );
	}

	/**
	 * Get postal code
	 *
	 * @return string|null
	 */
	public function get_postal_code(): ?string {
		return $this->get_address_component( 'postal_code' );
	}

	/**
	 * Get country
	 *
	 * @return string|null
	 */
	public function get_country(): ?string {
		return $this->get_address_component( 'country' );
	}

	/**
	 * Get country short name (ISO 3166-1)
	 *
	 * @return string|null
	 */
	public function get_country_short(): ?string {
		return $this->get_address_component_short( 'country' );
	}

	/**
	 * Get viewport coordinates
	 *
	 * @return array|null Array with northeast and southwest bounds
	 */
	public function get_viewport(): ?array {
		$result = $this->get_first_result();

		return $result['geometry']['viewport'] ?? null;
	}

	/**
	 * Get complete structured address components
	 *
	 * @return array Structured address components
	 */
	public function get_structured_address(): array {
		return [
			'street_number'     => $this->get_street_number(),
			'street_name'       => $this->get_street_name(),
			'neighborhood'      => $this->get_neighborhood(),
			'sublocality'       => $this->get_sublocality(),
			'city'              => $this->get_city(),
			'county'            => $this->get_county(),
			'state'             => $this->get_state(),
			'state_short'       => $this->get_state_short(),
			'postal_code'       => $this->get_postal_code(),
			'country'           => $this->get_country(),
			'country_short'     => $this->get_country_short(),
			'formatted_address' => $this->get_formatted_address(),
		];
	}

	/**
	 * Get the API response status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->data['status'] ?? '';
	}

	/**
	 * Check if the location is a business or point of interest
	 *
	 * @return bool
	 */
	public function is_business_location(): bool {
		$types = $this->get_types();

		return in_array( 'establishment', $types, true ) ||
				in_array( 'point_of_interest', $types, true );
	}

	/**
	 * Check if the first result is a partial match
	 *
	 * @return bool
	 */
	public function is_partial_match(): bool {
		$result = $this->get_first_result();

		return isset( $result['partial_match'] ) && $result['partial_match'];
	}

	/**
	 * Iterate over all results and apply a callback
	 *
	 * @param callable $callback Callback function to apply to each result
	 *
	 * @return array Results after applying the callback
	 */
	public function iterate_results( callable $callback ): array {
		return array_map( $callback, $this->get_results() );
	}
}
