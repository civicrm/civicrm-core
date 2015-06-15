<?php
// This is an adapter which allows old scripts to continue booting.
require_once __DIR__ . '/Civi/Bootstrap.php';
Civi\Bootstrap::singleton()->boot(array(
  'search' => TRUE,
  'prefetch' => FALSE,
));
