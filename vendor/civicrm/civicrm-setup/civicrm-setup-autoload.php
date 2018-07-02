<?php

if (!function_exists('_civicrm_setup_autoload')) {
  function _civicrm_setup_autoload($class) {
    // Facade class
    if ($class === 'Civi\\Setup') {
      require __DIR__ . '/src/Setup.php';
      return;
    }

    // All other classes

    $prefix = 'Civi\\Setup\\';
    $base_dir = __DIR__ . '/src/Setup/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
      return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
      require $file;
    }
  }
  spl_autoload_register('_civicrm_setup_autoload');
}
