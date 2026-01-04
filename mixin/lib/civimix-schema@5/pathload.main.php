<?php
namespace CiviMix\Schema;

\pathload()->activatePackage('civimix-schema@5', __DIR__, [
  'reloadable' => TRUE,
  // The civimix-schema library specifically supports installation processes. From a
  // bootstrap/service-availability POV, this is a rough environment which leads to
  // the "Multi-Activation Issue" and "Multi-Download Issue". To adapt to them,
  // civimix-schema follows "Reloadable Library" patterns.
  // More information: https://github.com/totten/pathload-poc/blob/master/doc/issues.md
]);

// When reloading, we make newer instance of the Facade object.
$GLOBALS['CiviMixSchema'] = require __DIR__ . '/src/CiviMixSchema.php';

if (!interface_exists(__NAMESPACE__ . '\SchemaHelperInterface')) {
  require __DIR__ . '/src/SchemaHelperInterface.php';
}

// \CiviMix\Schema\loadClass() is a facade. The facade should remain identical across versions.
if (!function_exists(__NAMESPACE__ . '\loadClass')) {

  function loadClass(string $class) {
    return $GLOBALS['CiviMixSchema']->loadClass($class);
  }

  spl_autoload_register(__NAMESPACE__ . '\loadClass');
}
