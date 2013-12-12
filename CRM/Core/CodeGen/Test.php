<?php

/**
 * Generate files used during testing.
 */
class CRM_Core_CodeGen_Test extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateCiviTestTruncate();
  }

  function generateCiviTestTruncate() {
    echo "Generating tests truncate file\n";

    # TODO template
    $truncate = '<?xml version="1.0" encoding="UTF-8" ?>
        <!--  Truncate all tables that will be used in the tests  -->
        <dataset>';
    $tbls = array_keys($this->tables);
    foreach ($tbls as $d => $t) {
      $truncate = $truncate . "\n  <$t />\n";
    }

    $truncate = $truncate . "</dataset>\n";
    file_put_contents($this->config->sqlCodePath . "../tests/phpunit/CiviTest/truncate.xml", $truncate);
    unset($truncate);
  }
}
