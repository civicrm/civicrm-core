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

$version = \CRM_Utils_System::version() . '.1'; /* Higher priority than contrib copies of same version... */
\pathload()->addSearchItem('civimix-schema', $version, __DIR__ . '/civimix-schema');
