# Topological Sort / Dependency resolver in PHP

[![Build Status](https://travis-ci.org/marcj/topsort.php.svg)](https://travis-ci.org/marcj/topsort.php)
[![Code Climate](https://codeclimate.com/github/marcj/topsort.php/badges/gpa.svg?)](https://codeclimate.com/github/marcj/topsort.php)
[![Test Coverage](https://codeclimate.com/github/marcj/topsort.php/badges/coverage.svg?)](https://codeclimate.com/github/marcj/topsort.php)

This library provides several implementations of a Topological Sort (topSort).
In additional to the plain sorting algorithm it provides several implementations of a Grouped Topological Sort,
means you can pass items with a type which will be grouped together in the sorting. With its implementation
of using strings instead of arrays its over 20x faster than regular implementations.

## What is it?

A topological sort is useful for determining dependency loading. It tells you which elements need to be proceeded first
in order to fulfill all dependencies in the correct order.

Example usage: Unit of Work (relations), simple Package manager, Dependency Injection, ...

Examples:
 
```php
$sorter = new StringSort();

$sorter->add('car1', ['owner1', 'brand1']);
$sorter->add('brand1');
$sorter->add('brand2');
$sorter->add('owner1', ['brand1']);
$sorter->add('owner2', ['brand2']);

$result = $sorter->sort();
// output would be:
[
 'brand1',
 'owner1',
 'car1',
 'brand2',
 'owner2'
]
```

Sometimes you want to group equal types together (imagine a UnitOfWork which wants to combine all elements from the
same type to stored those in one batch):

```php
$sorter = new GroupedStringSort();

$sorter->add('car1', 'car', ['owner1', 'brand1']);
$sorter->add('brand1', 'brand');
$sorter->add('brand2', 'brand');
$sorter->add('owner1', 'user', ['brand1']);
$sorter->add('owner2', 'user', ['brand2']);

$result = $sorter->sort();
// output would be:
[
 'brand2',
 'brand1',
 'owner2',
 'owner1',
 'car1'
]

$groups = $sorter->getGroups();
[
   {type: 'brand', level: 0, position: 0, length: 2},
   {type: 'user', level: 1, position: 2, length: 2},
   {type: 'car', level: 2, position: 4, length: 1},
]
//of course there may be several groups with the same type, if the dependency graphs makes this necessary.

foreach ($groups as $group) {
   $firstItem = $result[$groups->position];
   $allItemsOfThisGroup = array_slice($result, $group->position, $group->length);
}
```

You can only store strings as elements.
To sort PHP objects you can stored its hash instead. `$sorter->add(spl_object_hash($obj1), [spl_object_hash($objt1Dep)])`. 

## Installation

Use composer package: [marcj/topsort)[https://packagist.org/packages/marcj/topsort]
```
{
    "require": {
        "marcj/topsort": "~0.1"
    }
}
```

```php
include 'vendor/autoload.php';

$sorter = new GroupedStringSort;
$sorter->ad(...);

$result = $sorter->sort();
```

## Implementations

tl;dr: Use `FixedArraySort` for normal topSort or `GroupedStringSort` for grouped topSort since its always the fastest
and has a good memory footprint.

### ArraySort

This is the most basic, most inefficient implementation of topSort using plain php arrays.

### FixedArraySort

This uses \SplFixedArray of php and is therefore much more memory friendly.

### StringSort

This uses a string as storage and has therefore no array overhead. It's thus a bit faster and has almost equal
memory footprint like FixedArraySort.
Small drawback: You can not store element ids containing a null byte.

### GroupedArraySort

This is the most basic, not so efficient implementation of grouped topSort using plain php arrays.

### GroupedStringSort

This uses a string as storage and has therefore no array operations overhead. It's extremely faster
 and has better memory footprint than GroupedArraySort.
Small drawback: You can not store element ids containing a null byte.

## Benchmarks with PHP 7.0.9

Test data: 1/3 has two edges, 1/3 has one edge and 1/3 has no edges. Use the `benchmark` command in `./bin/console`
to play with it.

+-----------+----------------+--------------+----------+
| Count     | Implementation | Memory       | Duration |
+-----------+----------------+--------------+----------+
| 50        | FixedArraySort |           0b | 0.0001s  |
| 50        | ArraySort      |           0b | 0.0001s  |
| 50        | StringSort     |           0b | 0.0001s  |
| 1,000     | FixedArraySort |      53,432b | 0.0013s  |
| 1,000     | ArraySort      |      37,720b | 0.0012s  |
| 1,000     | StringSort     |      89,112b | 0.0013s  |
| 10,000    | FixedArraySort |     692,464b | 0.0141s  |
| 10,000    | ArraySort      |     529,240b | 0.0138s  |
| 10,000    | StringSort     |   1,080,472b | 0.0154s  |
| 100,000   | FixedArraySort |   5,800,200b | 0.1540s  |
| 100,000   | ArraySort      |   4,199,280b | 0.1499s  |
| 100,000   | StringSort     |  10,124,000b | 0.1645s  |
| 1,000,000 | FixedArraySort |  49,561,888b | 1.5456s  |
| 1,000,000 | ArraySort      |  33,559,408b | 1.5597s  |
| 1,000,000 | StringSort     |  95,480,848b | 1.7942s  |
+-----------+----------------+--------------+----------+

+-----------+-------------------+--------------+-----------+
| Count     | Implementation    | Memory       | Duration  |
+-----------+-------------------+--------------+-----------+
| 50        | GroupedArraySort  |           0b | 0.0002s   |
| 50        | GroupedStringSort |           0b | 0.0002s   |
| 1,000     | GroupedArraySort  |     112,280b | 0.0090s   |
| 1,000     | GroupedStringSort |      99,440b | 0.0025s   |
| 10,000    | GroupedArraySort  |   1,431,320b | 0.8385s   |
| 10,000    | GroupedStringSort |   1,176,304b | 0.0267s   |
| 100,000   | GroupedArraySort  |  12,318,072b | 132.9709s |
| 100,000   | GroupedStringSort |  11,129,144b | 0.2788s   |
| 1,000,000 | GroupedArraySort  |            - | too long  |
| 1,000,000 | GroupedStringSort | 106,488,496b | 3.0879s   |
+-----------+-------------------+--------------+-----------+