# Changelog

## Unreleased

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
