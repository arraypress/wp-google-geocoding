<?php
/**
 * Google Geocoding API Parameters Trait
 *
 * @package     ArrayPress\Google\Geocoding
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Geocoding\Traits;

use WP_Error;

/**
 * Trait Parameters
 *
 * Manages parameters for the Google Geocoding API.
 *
 * @package ArrayPress\Google\Geocoding
 */
trait Parameters {

	/**
	 * API key for Google Geocoding
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Cache settings
	 *
	 * @var array
	 */
	private array $cache_settings = [
		'enabled'    => true,
		'expiration' => DAY_IN_SECONDS,
	];

	/** API Key ******************************************************************/

	/**
	 * Set API key
	 *
	 * @param string $api_key The API key to use
	 *
	 * @return self
	 */
	public function set_api_key( string $api_key ): self {
		$this->api_key = $api_key;

		return $this;
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/** Cache ********************************************************************/

	/**
	 * Set cache status
	 *
	 * @param bool $enable Whether to enable caching
	 *
	 * @return self
	 */
	public function set_cache_enabled( bool $enable ): self {
		$this->cache_settings['enabled'] = $enable;

		return $this;
	}

	/**
	 * Get cache status
	 *
	 * @return bool
	 */
	public function is_cache_enabled(): bool {
		return $this->cache_settings['enabled'];
	}

	/**
	 * Set cache expiration time
	 *
	 * @param int $seconds Cache expiration time in seconds
	 *
	 * @return self|WP_Error
	 */
	public function set_cache_expiration( int $seconds ) {
		if ( $seconds < 0 ) {
			return new WP_Error(
				'invalid_expiration',
				__( 'Cache expiration time cannot be negative', 'arraypress' )
			);
		}
		$this->cache_settings['expiration'] = $seconds;

		return $this;
	}

	/**
	 * Get cache expiration time in seconds
	 *
	 * @return int
	 */
	public function get_cache_expiration(): int {
		return $this->cache_settings['expiration'];
	}

	/**
	 * Get all cache settings
	 *
	 * @return array Current cache settings
	 */
	public function get_cache_settings(): array {
		return $this->cache_settings;
	}
}
