<?php
/**
 * Test bootstrap.
 *
 * The transport is the seam: a test says what Google returns, and the
 * assertions are about what the library does with it.
 *
 * @package ArrayPress\Google\Geocoding
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

foreach ( [ 'MINUTE_IN_SECONDS' => 60, 'HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400, 'WEEK_IN_SECONDS' => 604800 ] as $name => $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * What the next request returns, what was asked, and what is cached.
 *
 * @var array
 */
$GLOBALS['gg'] = [
	'response' => null,
	'requests' => [],
	'options'  => [],
];

/**
 * Reset everything a test set up.
 *
 * @return void
 */
function gg_reset(): void {
	$GLOBALS['gg'] = [
		'response' => null,
		'requests' => [],
		'options'  => [],
	];
}

/**
 * Queue the response the next request receives.
 *
 * @param mixed $response A WP_Error, or an array with 'body'.
 *
 * @return void
 */
function gg_will_return( $response ): void {
	$GLOBALS['gg']['response'] = $response;
}

/**
 * A successful geocode body.
 *
 * @param array $overrides Fields to merge into the first result.
 *
 * @return array
 */
function gg_ok( array $overrides = [] ): array {
	return [
		'body' => wp_json_encode( [
			'status'  => 'OK',
			'results' => [
				array_replace_recursive( [
					'formatted_address' => '10 Downing St, London SW1A 2AA, UK',
					'place_id'          => 'ChIJRxzEqhwFdkgRcgHz1UEhVPk',
					'types'             => [ 'street_address' ],
					'geometry'          => [
						'location'      => [ 'lat' => 51.5033635, 'lng' => -0.1276248 ],
						'location_type' => 'ROOFTOP',
					],
					'address_components' => [
						[ 'long_name' => '10', 'short_name' => '10', 'types' => [ 'street_number' ] ],
						[ 'long_name' => 'Downing Street', 'short_name' => 'Downing St', 'types' => [ 'route' ] ],
						[ 'long_name' => 'London', 'short_name' => 'London', 'types' => [ 'postal_town' ] ],
						[ 'long_name' => 'SW1A 2AA', 'short_name' => 'SW1A 2AA', 'types' => [ 'postal_code' ] ],
						[ 'long_name' => 'United Kingdom', 'short_name' => 'GB', 'types' => [ 'country', 'political' ] ],
					],
				], $overrides ),
			],
		] ),
	];
}

/**
 * A Google error body.
 *
 * @param string $status The status Google returns.
 *
 * @return array
 */
function gg_status( string $status ): array {
	return [ 'body' => wp_json_encode( [ 'status' => $status, 'results' => [] ] ) ];
}

/**
 * How many HTTP requests were made.
 *
 * @return int
 */
function gg_request_count(): int {
	return count( $GLOBALS['gg']['requests'] );
}

/**
 * The URL of the most recent request.
 *
 * @return string
 */
function gg_last_url(): string {
	$requests = $GLOBALS['gg']['requests'];

	return end( $requests ) ?: '';
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in for core's error object.
	 */
	class WP_Error {

		/** @var string */
		private string $code;

		/** @var string */
		private string $message;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/** @return string */
		public function get_error_code(): string {
			return $this->code;
		}

		/** @return string */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value.
	 *
	 * @return bool
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 *
	 * @return string
	 */
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $value Value.
	 *
	 * @return string|false
	 */
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * @param string $url  URL.
	 * @param array  $args Arguments.
	 *
	 * @return mixed
	 */
	function wp_remote_get( string $url, array $args = [] ) {
		$GLOBALS['gg']['requests'][] = $url;

		return $GLOBALS['gg']['response'] ?? [ 'body' => '{}' ];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * @param mixed $response Response.
	 *
	 * @return string
	 */
	function wp_remote_retrieve_body( $response ): string {
		return (string) ( $response['body'] ?? '' );
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * @param string $key Key.
	 *
	 * @return mixed
	 */
	function get_transient( string $key ) {
		$options = &$GLOBALS['gg']['options'];

		if ( ! array_key_exists( '_transient_' . $key, $options ) ) {
			return false;
		}

		$timeout = $options[ '_transient_timeout_' . $key ] ?? 0;

		if ( $timeout && $timeout < time() ) {
			unset( $options[ '_transient_' . $key ], $options[ '_transient_timeout_' . $key ] );

			return false;
		}

		return $options[ '_transient_' . $key ];
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * @param string $key        Key.
	 * @param mixed  $value      Value.
	 * @param int    $expiration Lifetime.
	 *
	 * @return bool
	 */
	function set_transient( string $key, $value, int $expiration = 0 ): bool {
		$GLOBALS['gg']['options'][ '_transient_' . $key ] = $value;

		if ( $expiration ) {
			$GLOBALS['gg']['options'][ '_transient_timeout_' . $key ] = time() + $expiration;
		}

		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * Core removes both rows. A stub that removes one cannot show the
	 * orphaned-timeout bug a raw DELETE leaves behind.
	 *
	 * @param string $key Key.
	 *
	 * @return bool
	 */
	function delete_transient( string $key ): bool {
		unset(
			$GLOBALS['gg']['options'][ '_transient_' . $key ],
			$GLOBALS['gg']['options'][ '_transient_timeout_' . $key ]
		);

		return true;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Enough of core's behaviour for a bare URL with an array of args.
	 *
	 * @param array  $args Arguments.
	 * @param string $url  URL.
	 *
	 * @return string
	 */
	function add_query_arg( array $args, string $url ): string {
		return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $args );
	}
}


if ( ! class_exists( 'GG_Test_wpdb' ) ) {
	/**
	 * Enough of $wpdb for clear_cache().
	 *
	 * LIKE is implemented properly rather than with str_starts_with: in SQL an
	 * unescaped `_` matches any single character, so a prefix built without
	 * esc_like() returns more rows than intended. A stub that ignores that
	 * cannot catch the bug it exists to catch.
	 */
	class GG_Test_wpdb {

		/** @var string */
		public string $options = 'wp_options';

		/**
		 * @param string $text Text.
		 *
		 * @return string
		 */
		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}

		/**
		 * @param string $query   Query.
		 * @param mixed  ...$args Values.
		 *
		 * @return string
		 */
		public function prepare( string $query, ...$args ): string {
			foreach ( $args as $arg ) {
				$query = preg_replace(
					'/%[sd]/',
					is_int( $arg ) ? (string) $arg : "'" . str_replace( "'", "''", (string) $arg ) . "'",
					$query,
					1
				);
			}

			return $query;
		}

		/**
		 * @param string $query Query.
		 *
		 * @return array
		 */
		public function get_col( string $query ): array {
			if ( ! preg_match( "/option_name LIKE '(.*)'\s*$/", trim( $query ), $m ) ) {
				return [];
			}

			$pattern = str_replace( "''", "'", $m[1] );

			return array_values(
				array_filter(
					array_keys( $GLOBALS['gg']['options'] ),
					static fn( $name ) => (bool) preg_match( gg_like_to_regex( $pattern ), $name )
				)
			);
		}
	}
}

if ( ! function_exists( 'gg_like_to_regex' ) ) {
	/**
	 * Translate a SQL LIKE pattern into a regex, honouring backslash escapes.
	 *
	 * @param string $pattern LIKE pattern.
	 *
	 * @return string
	 */
	function gg_like_to_regex( string $pattern ): string {
		$out    = '';
		$length = strlen( $pattern );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $pattern[ $i ];

			if ( '\\' === $char && $i + 1 < $length ) {
				$out .= preg_quote( $pattern[ ++$i ], '#' );
				continue;
			}

			$out .= match ( $char ) {
				'%'     => '.*',
				'_'     => '.',
				default => preg_quote( $char, '#' ),
			};
		}

		return '#^' . $out . '$#';
	}
}

$GLOBALS['wpdb'] = new GG_Test_wpdb();

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
