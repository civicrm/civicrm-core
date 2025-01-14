<?php
namespace Civi\PathLoadSetup;

// Pathload folder would usually be registered by saying something like:
//
//   pathload()->addSearchDir(__DIR__ . '/lib');
//
// However, that would detect version#'s from the filenames. In this folder,
// we want all subprojects to have the same version-number as the main
// project. It would be quite inconvenient to rename them every month.
//
// So instead, we use `addSearchItem()` and register with explicit versions.

$version6 = \CRM_Utils_System::version() . '.1'; /* Higher priority than contrib copies of same version... */
$version5 = preg_replace_callback(';^6\.(\d+)\.;', function ($m) {
  /* civimix-schema@5.83=>6.0 was purely superficial (to match core#)s. Continue 5.x option for compat. */
  return '5.' . (83 + $m[1]) . '.';
}, $version6);

// Register civimix-schema@5.x
\pathload()->addSearchItem('civimix-schema', $version5, __DIR__ . '/civimix-schema');

// (Optional) Register civimix-schema@6.x
// \pathload()->addSearchItem('civimix-schema', $version6, __DIR__ . '/civimix-schema');
