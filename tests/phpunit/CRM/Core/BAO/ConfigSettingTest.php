<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CiviReportTestCase
 * @group headless
 */
class CRM_Core_BAO_ConfigSettingTest extends CiviUnitTestCase {

  public function testToggleComponent() {
    $origNames = array();
    foreach (CRM_Core_Component::getEnabledComponents() as $c) {
      $origNames[] = $c->name;
    }
    $this->assertTrue(!in_array('CiviCase', $origNames));

    $enableResult = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->assertTrue($enableResult, 'Cannot enable CiviCase in line ' . __LINE__);

    $newNames = array();
    foreach (CRM_Core_Component::getEnabledComponents() as $c) {
      $newNames[] = $c->name;
    }

    $this->assertTrue(in_array('CiviCase', $newNames));
    $this->assertEquals(count($newNames), count($origNames) + 1);
  }

}
