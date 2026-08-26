# Google Geocoding

Turn an address into coordinates, coordinates back into an address, and
measure the distance between two points.

## What it does

Wraps Google's Geocoding API so a lookup is one call and a cached one costs
nothing. The response comes back as an object with named methods rather than
a nest of arrays — Google's own shape puts the postcode four levels down, and
every consumer ends up writing the same loop to find it.

The distance helper is here because everything that geocodes eventually wants
it: nearest store, delivery radius, how far apart two orders were placed.

## Features

* Turn a written address into latitude and longitude
* Turn coordinates back into a formatted address
* Pull a single component — postcode, country, city — without walking the response
* See how precise the match was, so a rooftop hit can be trusted over an approximation
* Measure the distance between two points in kilometres or miles
* Cache lookups for as long as you like, since an address rarely moves

## Installation

```bash
composer require arraypress/wp-google-geocoding
```

## Quick start

Store coordinates when an address is saved, so nothing is looked up twice:

```php
use ArrayPress\Google\Geocoding\Client;

$client = new Client( $api_key );
$result = $client->geocode( '10 Downing Street, London' );

if ( ! is_wp_error( $result ) ) {
	update_post_meta( $id, '_lat', $result->get_latitude() );
	update_post_meta( $id, '_lng', $result->get_longitude() );
}
```

Then, finding what is nearby:

```php
use ArrayPress\Google\Geocoding\Distance;

$miles = Distance::between( $lat1, $lng1, $lat2, $lng2, 'mi' );
```

That is a straight-line distance, not a driving one — right for "within 10
miles", wrong for a delivery estimate.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later
* A Google Maps Platform API key with Geocoding enabled

## License

GPL-2.0-or-later
