<?php
namespace Civi\PathLoadSetup;

// Pathload folder would usually be registered by saying something like:
//
//   pathload()->addSearchDir(__DIR__ . '/lib');
//
// However, that would detect version#'s from the filenames. While that
// convention is handy for downstreams (using backports), it can be annoying
// here (as canonical source). It would be inconvenient to have to rename
// the canonical files/folders whenever we make an edit.
//
// `addSearchItem()` allows us to register with a programmatic
// version-number -- so we don't have to manually set the number.

$version6 = \CRM_Utils_System::version() . '.1'; /* Higher priority than contrib copies of same version... */
$version5 = preg_replace_callback(';^6\.(\d+)\.;', function ($m) {
  /* civimix-schema@5.83=>6.0 was purely superficial (to match core#)s. Continue 5.x option for compat. */
  return '5.' . (83 + $m[1]) . '.';
}, $version6);

// Register civimix-schema@5.x
\pathload()->addSearchItem('civimix-schema', $version5, __DIR__ . '/civimix-schema@5');
