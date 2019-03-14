# Duration for PHP

Working with durations made easy.

[![Current version](https://img.shields.io/packagist/v/gamez/duration.svg)](https://packagist.org/packages/gamez/duration)
[![Supported PHP version](https://img.shields.io/packagist/php-v/gamez/duration.svg)]()

[![Build Status](https://travis-ci.com/jeromegamez/duration-php.svg?branch=master)](https://travis-ci.com/jeromegamez/duration-php)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=jeromegamez_duration-php&metric=alert_status)](https://sonarcloud.io/dashboard?id=jeromegamez_duration-php)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=jeromegamez_duration-php&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=jeromegamez_duration-php)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=jeromegamez_duration-php&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=jeromegamez_duration-php)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=jeromegamez_duration-php&metric=coverage)](https://sonarcloud.io/dashboard?id=jeromegamez_duration-php)

Do you like to use `DateInterval` to compute and work with durations? Me neither, so let's fix that!

* [Installation](#installation)
* [Reference](#reference)
  * [Supported Input Values](#supported-input-values)
  * [Transformations](#transformations)
  * [Comparisons](#comparisons)
  * [Operations](#operations)
* [Roadmap](#roadmap)

---

## Installation

You can install the package with [Composer](https://getcomposer.org):

```bash
composer require gamez/duration
```

You can then use Duration:

```php
<?php
require 'vendor/autoload.php';

use Gamez\Duration;

$duration = Duration::make('13 minutes 37 seconds');
// or start with nothing
$duration = Duration::none();
```

---

## Reference

### Supported input values

#### DateIntervals

```php
Duration::make('P13M37S');
Duration::make(new DateInterval('P13M37S'));
```

#### Colon notation

```php
Duration::make('13:37'); // minutes:seconds
Duration::make('13:37:37'); // hours:minutes:seconds
```

#### Textual notation

A textual notation is any value that can be processed by 
[DateInterval::createFromDateString()](https://secure.php.net/manual/en/dateinterval.createfromdatestring.php)

```php
Duration::make('13 minutes 37 seconds');
```

### Transformations

When transformed, a Duration will be

* converted to a DateInterval representation
* optimized in the sense that an input value of 60 seconds would result in an output value of "1 minute", 
  for example "PT60S" would be converted to "PT1H"

```php
$duration = Duration::make('8 days 29 hours 77 minutes');

echo (string) $duration; // P9DT6H17M
echo json_encode($duration); // "P9DT6H17M"
echo get_class($duration->toDateInterval()); // DateInterval
```

### Comparisons

```php
$oneSecond = Duration::make('1 second');
$sixtySeconds = Duration::make('60 seconds');
$oneMinute = Duration::make('1 minute');
$oneHour = Duration::make('1 hour');

$oneSecond->isSmallerThan($oneMinute); // true
$oneHour->isLargerThan($oneMinute); // true
$oneMinute->equals($sixtySeconds); // true

$durations = [$oneMinute, $oneSecond, $oneHour, $sixtySeconds];

usort($durations, function ($a, $b) {
    return $a->compareTo($b);
}); // -> [$oneSecond, $sixtySeconds, $oneMinute, $oneHour]
```

### Operations

Results will always be rounded by the second.

```php
$thirty = Duration::make('30 seconds');

echo $thirty->withAdded('31 seconds'); // PT1M1S
echo $thirty->withSubtracted('29 seconds'); // PT1S
echo $thirty->multipliedBy(3); // PT1M30S
echo $thirty->dividedBy(2.5); // PT12S

$thirty->multipliedBy(-1); // InvalidArgumentException
$thirty->withSubtracted('31 seconds'); // InvalidArgumentException
```

---

## Roadmap

* Support more input formats
* Add "output for humans" (like colon notation)
* Support precision (similar to [spatie/period](https://github.com/spatie/period))
* ...

