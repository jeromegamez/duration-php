# Changelog

## 4.2 - 2020-12-19

* Added support for PHP 8.0

## 4.1 - 2020-04-26

* PHP 7.3.4 is now the minimum required version.
* `Duration` now extends `DateInterval` and can be used interchangeably.
* Objects having a `__toString()` method are now supported values.
* `Duration::toDateInterval()` is now deprecated.

## 4.0 - 2019-09-03

* PHP 7.3 is now the minimum required version.
* `0`, `null`, `false`, `true` are now supported values as in that they result in `Duration::none()` (`PT0S`).
* An `InvalidDuration` error will be thrown if a value is given without a unit or if the given value cannot be parsed. 
* `toIntervalSpec()` did more than it needed to do. Instead of formatting the current value the spec itself is now returned.

## 3.0.1 - 2019-01-28

* New `DateTimeImmutable` instances ignore timezones when created [#3](https://github.com/jeromegamez/duration-php/issues/3)

## 3.0 - 2019-01-28

* The `Duration` class is final again (and will stay that way) [#2](https://github.com/jeromegamez/duration-php/issues/2)

## 2.0 - 2019-01-25

* The `Duration` class is now extensible instead of macroable

## 1.1 - 2019-01-24

* Thrown exceptions implement `Gamez\Duration\DurationException`
* An operation resulting in an invalid duration will throw a `Gamez\Duration\Exception\InvalidDuration`

## 1.0.1 - 2019-01-24

* Fixed errors in the documentation

## 1.0 - 2019-01-24

Initial release
