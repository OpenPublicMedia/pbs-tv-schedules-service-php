# PBS TV Schedules Service (TVSS) PHP Library

This library abstracts interactions with the
[PBS TV Schedules Service API](https://docs.pbs.org/display/tvsapi).

## Installation

Install via composer:

```bash
composer require openpublicmedia/pbs-tv-schedules-service-php
```

## Use

The primary class provided by this library is the
`OpenPublicMedia\PbsTvSchedulesService\Client`. A `Client` instance can be used
to query the API's endpoints for schedule data.

### Examples

#### Creating a client

```php
use OpenPublicMedia\PbsTvSchedulesService\Client;

$api_key = 'xxxxxxxxxxxxxx';
$call_sign = 'xxxx';

$client = new Client($api_key, $call_sign);
```

The `$api_key` and `$call_sign` parameters are optional, as not all endpoints
require them.

#### Getting listings for a day.

```php
$today = new DateTime();
$results = $client->getListings($today);
var_dump(reset($results));
class stdClass#45 (4) {
    public $cid => string(36) "6ecdcaa4-a42d-4360-88a0-90131287f9ef"
    public $external_id => string(6) "110268"
    public $short_name => string(7) "KCTSDT4"
    public $full_name => string(10) "KCTS World"
    public $timezone => string(19) "America/Los_Angeles"
    public $listings => array(20) { ... }
    public $analog_channel => string(1) "9"
    public $digital_channel => string(3) "9.4"
}
```

Listings are returned in and organized by _channel_. Each object in the array
contains a `$listings` property with listings data.

#### Searching listings data.

```php
$results = $client->search('jamestown');
var_dump($results);
class stdClass#31 (2) {
    public $program_results => array(2) { ... }
    public $show_results => array(5) { ... }
}
```

Search results are organized in `$program_results` and `$show_results`. Utility
methods are provided to filter this results (`searchPrograms`, `searchShows`).

## Development goals

See [CONTRIBUTING](CONTRIBUTING.md) for information about contributing to
this project.

### v1

 - [x] API authentication (`OpenPublicMedia\PbsTvSchedulesService\Client`)
 - [x] API direct querying (`$client->get()`)
 - [x] Result/error handling
 - [x] GET wrappers for endpoints (`$client->getXXX()`)
 - [x] Search endpoints support  (`$client->search()`)
