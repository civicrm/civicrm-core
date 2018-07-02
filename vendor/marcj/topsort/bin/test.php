<?php

require __DIR__ . '/../vendor/autoload.php';

$class = $argv[1];
$count = $argv[2];


function getElementsFlat($count) {
    $elements = [];
    for ($i = 0; $i < $count/3; $i++) {
        $elements['car' . $i] = ['brand' . $i];
        $elements['owner' . $i] = ['brand' . $i, 'car' . $i];
        $elements['brand' . $i] = [];
    }

    return $elements;
}

function getElementsGroup($count) {
    $elements = [];
    for ($i = 0; $i < $count/3; $i++) {
        $elements['car' . $i] = ['car', ['brand' . $i]];
        $elements['owner' . $i] = ['owner', ['brand' . $i, 'car' . $i]];
        $elements['brand' . $i] = ['brand', []];
    }
    return $elements;
}

$elements = 0 === strpos($class, 'Grouped') ? getElementsGroup($count) : getElementsFlat($count);

$class = '\MJS\TopSort\Implementations\\' . $class;
$sorted = new $class($elements);

$start = microtime(true);
gc_collect_cycles();
$lastMemory = memory_get_peak_usage();

$sorted->sort();

echo json_encode([
    'memory' => memory_get_peak_usage() - $lastMemory,
    'time' => microtime(true) - $start
]);
