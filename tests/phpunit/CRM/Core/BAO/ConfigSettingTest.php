<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CiviReportTestCase
 * @group headless
 */
class CRM_Core_BAO_ConfigSettingTest extends CiviUnitTestCase {

  public function testToggleComponent() {
    $origNames = [];
    foreach (CRM_Core_Component::getEnabledComponents() as $c) {
      $origNames[] = $c->name;
    }
    $this->assertTrue(!in_array('CiviCase', $origNames));

    $enableResult = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->assertTrue($enableResult, 'Cannot enable CiviCase in line ' . __LINE__);

    $newNames = [];
    foreach (CRM_Core_Component::getEnabledComponents() as $c) {
      $newNames[] = $c->name;
    }

    $this->assertTrue(in_array('CiviCase', $newNames));
    $this->assertEquals(count($newNames), count($origNames) + 1);
  }

}
