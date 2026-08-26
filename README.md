# Google Geocoding API for WordPress

A PHP library for integrating with the Google Geocoding API in WordPress, providing address to coordinate conversion and structured address information. Features WordPress transient caching and WP_Error support.

## Features

- 🗺️ **Address Geocoding**: Convert addresses to coordinates
- 📍 **Reverse Geocoding**: Convert coordinates to addresses
- 🏠 **Address Components**: Access structured address information
- ⚡ **WordPress Integration**: Native transient caching and WP_Error support
- 🛡️ **Type Safety**: Full type hinting and strict types
- 🔄 **Response Parsing**: Clean response object for easy data access
- 🌍 **Global Support**: Works with addresses worldwide
- 📐 **Viewport Information**: Access location viewport bounds
- 🏢 **Business Detection**: Identify business locations
- 🔍 **Detailed Components**: Access neighborhood and sublocality information
- 📋 **Multiple Result Types**: Handle various location types
- ✨ **Plus Code Support**: Access global and compound plus codes

## Measuring Distance

The reason to geocode an order in the first place: compare where the billing
address is against where the request came from.

```php
use ArrayPress\Google\Geocoding\Client;
use ArrayPress\Google\Geocoding\Distance;

$billing = ( new Client( $api_key ) )->geocode( $order->get_billing_address() );

if ( ! is_wp_error( $billing ) ) {
    $km = Distance::between_points(
        $billing->get_coordinates(),          // [ 'latitude' => .., 'longitude' => .. ]
        [ 'lat' => $ip_lat, 'lng' => $ip_lng ] // whatever your IP lookup returned
    );

    if ( null !== $km && $km > 500 ) {
        // Worth a look.
    }
}
```

`Distance::between()` takes four floats if you already have them, and
`between_points()` takes two arrays keyed either `lat`/`lng` or
`latitude`/`longitude`, so it composes with `get_coordinates()` directly.
Pass `'mi'` as the last argument for miles.


## Requirements

- PHP 8.3 or later
- WordPress 5.0 or later
- Google Geocoding API key

## Installation

Install via Composer:

```bash
composer require arraypress/wp-google-geocoding
```

## Basic Usage

```php
use ArrayPress\Google\Geocoding\Client;

// Initialize client with your API key
$client = new Client( 'your-google-api-key' );

// Forward geocoding (Address to Coordinates)
$result = $client->geocode( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
	$coordinates = $result->get_coordinates();
	echo "Latitude: {$coordinates['latitude']}\n";
	echo "Longitude: {$coordinates['longitude']}\n";
}

// Reverse geocoding (Coordinates to Address)
$result = $client->reverse_geocode( 37.4220, - 122.0841 );
if ( ! is_wp_error( $result ) ) {
	echo $result->get_formatted_address();
}
```

## Extended Examples

### Getting Structured Address Components

```php
$result = $client->geocode( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
	$address = $result->get_structured_address();

	// Basic Components
	$street       = $result->get_street_number() . ' ' . $result->get_street_name();
	$city         = $result->get_city();
	$state        = $result->get_state();
	$state_code   = $result->get_state_short();
	$postal       = $result->get_postal_code();
	$country      = $result->get_country();
	$country_code = $result->get_country_short();

	// Additional Components
	$neighborhood        = $result->get_neighborhood();
	$sublocality         = $result->get_sublocality();
	$sublocality_level_1 = $result->get_sublocality_level_1();
}
```

### Working with Business Locations

```php
$result = $client->geocode( '123 Business Street' );
if ( ! is_wp_error( $result ) ) {
	if ( $result->is_business_location() ) {
		echo "This is a business location\n";
	}

	// Get all place types
	$types = $result->get_types();

	// Get specific components by types
	$business_components = $result->get_address_components_by_types( [ 'establishment', 'point_of_interest' ] );
}
```

### Handling Plus Codes and Location Types

```php
$result = $client->geocode( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
	// Plus Codes
	$plus_code = $result->get_plus_code();
	echo "Compound Code: " . $result->get_plus_code_compound() . "\n";
	echo "Global Code: " . $result->get_plus_code_global() . "\n";

	// Location Type
	$location_type = $result->get_location_type(); // ROOFTOP, RANGE_INTERPOLATED, etc.

	// Viewport Information
	$viewport = $result->get_viewport();
	if ( $viewport ) {
		echo "Northeast: {$viewport['northeast']['lat']}, {$viewport['northeast']['lng']}\n";
		echo "Southwest: {$viewport['southwest']['lat']}, {$viewport['southwest']['lng']}\n";
	}
}
```

### Handling Responses with Caching

```php
// Initialize with custom cache duration (1 hour = 3600 seconds)
$client = new Client( 'your-api-key', true, 3600 );

// Results will be cached
$result = $client->geocode('1600 Amphitheatre Parkway, Mountain View, CA');

// Clear specific cache
$client->clear_cache('geocode_1600 Amphitheatre Parkway, Mountain View, CA');

// Clear all geocoding caches
$client->clear_cache();
```

### Working with Multiple Results

```php
$result = $client->geocode( 'Springfield' );
if ( ! is_wp_error( $result ) ) {
	// Get all results
	$all_results = $result->get_results();

	// Iterate over results with a callback
	$locations = $result->iterate_results( function ( $location ) {
		return [
			'address'     => $location['formatted_address'],
			'coordinates' => $location['geometry']['location']
		];
	} );
}
```

## API Methods

### Client Methods

* `geocode( $address )`: Convert address to coordinates
* `reverse_geocode( $lat, $lng )`: Convert coordinates to address
* `clear_cache( $identifier = null )`: Clear cached responses

### Response Methods

#### Basic Information
* `get_all()`: Get complete raw response data
* `get_first_result()`: Get first result from response
* `get_results()`: Get all results from response
* `get_status()`: Get API response status

#### Address Information
* `get_formatted_address()`: Get full formatted address
* `get_structured_address()`: Get all components in structured format
* `is_partial_match()`: Check if result is a partial match
* `is_business_location()`: Check if location is a business/POI

#### Geographic Information
* `get_coordinates()`: Get latitude/longitude array
* `get_latitude()`: Get latitude
* `get_longitude()`: Get longitude
* `get_viewport()`: Get viewport bounds
* `get_location_type()`: Get location type (ROOFTOP, etc.)
* `get_types()`: Get all place types

#### Plus Codes
* `get_plus_code()`: Get plus code information
* `get_plus_code_compound()`: Get compound plus code
* `get_plus_code_global()`: Get global plus code

#### Place Information
* `get_place_id()`: Get Google Place ID

#### Address Components
* `get_street_number()`: Get street number
* `get_street_name()`: Get street name
* `get_neighborhood()`: Get neighborhood
* `get_sublocality()`: Get sublocality
* `get_sublocality_level_1()`: Get sublocality level 1
* `get_city()`: Get city/locality
* `get_county()`: Get county
* `get_state()`: Get state/province
* `get_state_short()`: Get state/province code
* `get_postal_code()`: Get postal code
* `get_country()`: Get country
* `get_country_short()`: Get country code

#### Component Helpers
* `get_address_component( $type )`: Get specific address component
* `get_address_component_short( $type )`: Get specific address component short name
* `get_address_components()`: Get all address components
* `get_address_components_by_types( array $types )`: Get components matching multiple types

#### Result Processing
* `iterate_results( callable $callback )`: Process all results with a callback

## Use Cases

* **Address Validation**: Verify and standardize addresses
* **Coordinate Lookup**: Get coordinates for addresses
* **Location Services**: Support location-based features
* **Address Parsing**: Extract address components
* **Geographic Analysis**: Analyze location data
* **Map Integration**: Support for mapping features
* **Address Autocomplete**: Base for address lookup systems
* **Business Location Detection**: Identify commercial locations
* **Neighborhood Analysis**: Access detailed area information
* **Multiple Result Handling**: Process ambiguous locations

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-google-geocoding)
- [Issue Tracker](https://github.com/arraypress/wp-google-geocoding/issues)