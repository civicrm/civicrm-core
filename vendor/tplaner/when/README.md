# When
Date/Calendar recursion library for PHP 5.3+

[![Build Status](https://travis-ci.org/tplaner/When.png?branch=develop)](https://travis-ci.org/tplaner/When)

Author: Tom Planer

## Installation
```
$ composer require tplaner/when
```

```
{
    "require": {
        "tplaner/when": "2.*"
    }
}
```

## Current Features
Currently this version does everything version 1 was capable of, it also supports `byhour`, `byminute`, and `bysecond`. Please check the [unit tests](https://github.com/tplaner/When/tree/develop/tests) for information about how to use it.

Here are some basic examples.

```php
// friday the 13th for the next 5 occurrences
$r = new When();
$r->startDate(new DateTime("19980213T090000"))
  ->freq("monthly")
  ->count(5)
  ->byday("fr")
  ->bymonthday(13)
  ->generateOccurrences();

print_r($r->occurrences);
```

```php
// friday the 13th for the next 5 occurrences rrule
$r = new When();
$r->startDate(new DateTime("19980213T090000"))
  ->rrule("FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13")
  ->generateOccurrences();

print_r($r->occurrences);
```

```php
// friday the 13th for the next 5 occurrences, skipping known friday the 13ths
$r = new When();
$r->startDate(new DateTime("19980213T090000"))
  ->freq("monthly")
  ->count(5)
  ->byday("fr")
  ->bymonthday(13)
  ->exclusions('19990813T090000,20001013T090000')
  ->generateOccurrences();

print_r($r->occurrences);
```

```php
// friday the 13th forever; see which ones occur in 2018
$r = new When();
$r->startDate(new DateTime("19980213T090000"))
  ->rrule("FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13");


$occurrences = $r->getOccurrencesBetween(new DateTime('2018-01-01 09:00:00'),
                                         new DateTime('2019-01-01 09:00:00'));
print_r($occurrences);
```

## License
When is licensed under the MIT License, see `LICENSE` for specific details.
