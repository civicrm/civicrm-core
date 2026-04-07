<?php

// Unfortunately, crypto.subtle isn't available on many local dev sites and demo sites.
// So instead, we pull in a pure-JS library.

return [
  'ext' => 'civicrm',
  'js' => ['bower_components/js-spark-md5/spark-md5.js', 'ang/md5.js'],
  'basePages' => [],
];
