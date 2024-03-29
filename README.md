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

#### Getting listings with images.

```php
$today = new DateTime();
$results = $client->getListings($today, false, true);
var_dump($results[0]->listings[0]);
class stdClass#41 (25) {
    [...]
    public $images => array(19) {
        [0] => class stdClass#33 (8) {
          public $type => string(10) "image/jpeg"
          public $image => string(84) "https://image.pbs.org/gracenote/pbsd.tmsimg.com/assets/p7879062_n183662_cc_v9_aa.jpg"
          public $ratio => string(3) "3:4"
          public $width => string(4) "1080"
          public $height => string(4) "1440"
          public $caption => string(10) "Ray Suarez"
          public $updated_at => string(20) "2012-12-12T00:00:00Z"
          public $external_profile => string(17) "Cast in Character"
        }
        [1] => class stdClass#34 (8) {
          public $type => string(10) "image/jpeg"
          public $image => string(84) "https://image.pbs.org/gracenote/pbsd.tmsimg.com/assets/p7879062_n191589_cc_v9_ab.jpg"
          public $ratio => string(3) "3:4"
          public $width => string(4) "1080"
          public $height => string(4) "1440"
          public $caption => string(10) "Gwen Ifill"
          public $updated_at => string(20) "2013-09-04T21:02:00Z"
          public $external_profile => string(17) "Cast in Character"
        }
        [...]
    }
    [...]
}
```

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

### v2

- [x] PHP 8 support

### v3.x

 - [ ] Create entities for response data
   - [ ] Channel (???)
   - [ ] Listing
   - [ ] Image
 - [ ] Swap "kids only" and "fetch images" parameters where appropriate
