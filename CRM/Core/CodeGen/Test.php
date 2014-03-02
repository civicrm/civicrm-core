<?php

/**
 * Generate files used during testing.
 */
class CRM_Core_CodeGen_Test extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateCiviTestTruncate();
  }

  function generateCiviTestTruncate() {
    $truncate = '<?xml version="1.0" encoding="UTF-8" ?>
        <!--  Truncate all tables that will be used in the tests  -->
        <dataset>';
    $tbls = array();
    foreach ($this->config->doctrine->metadata as $class_metadata) {
      $tbls[] = $class_metadata->getTableName();
    }
    foreach ($tbls as $d => $t) {
      $truncate = $truncate . "\n  <$t />\n";
    }

    $truncate = $truncate . "</dataset>\n";
    $truncate_file_path = CRM_Utils_Path::join($this->config->civicrm_root_path, 'tests', 'phpunit', 'CiviTest', 'truncate.xml');
    file_put_contents($truncate_file_path, $truncate);
    unset($truncate);
  }
}
