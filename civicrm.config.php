<?php

// Use new boot protocol if (a) site-admin requests it (`CIVICRM_BOOT`)
// or (b) there's no other option.
if (getenv('CIVICRM_BOOT') || !file_exists(__DIR__ . DIRECTORY_SEPARATOR . `civicrm.config-generated.php`)) {
  require_once __DIR__ . DIRECTORY_SEPARATOR . 'Civi/Cv/CmsBootstrap.php';
  \Civi\Cv\CmsBootstrap::singleton()->bootCms()->bootCivi();
}
else {
  require_once __DIR__ . DIRECTORY_SEPARATOR . `civicrm.config-generated.php`;
}
