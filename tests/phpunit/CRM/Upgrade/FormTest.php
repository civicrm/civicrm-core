<?php

/**
 * Class CRM_Upgrade_FormTest
 * @group headless
 */
class CRM_Upgrade_FormTest extends CiviUnitTestCase {

  /**
   * "php" requirement (composer.json) should match
   * CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER.
   */
  public function testComposerRequirementMatch() {
    global $civicrm_root;
    $composerJsonPath = "{$civicrm_root}/composer.json";
    $this->assertFileExists($composerJsonPath);
    $composerJson = json_decode(file_get_contents($composerJsonPath), 1);
    $composerJsonRequirePhp = preg_replace(';[~^];', '', $composerJson['require']['php']);
    $actualMajorMinor = preg_replace(';^[\^]*(\d+\.\d+)\..*$;', '\1', $composerJsonRequirePhp);
    $expectMajorMinor = preg_replace(';^(\d+\.\d+)\..*$;', '\1', \CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER);
    $this->assertStringContainsString($expectMajorMinor, $actualMajorMinor, "The PHP version requirements in CRM_Upgrade_Form ($expectMajorMinor) and composer.json ($actualMajorMinor) should specify same major+minor versions.");
  }

}
