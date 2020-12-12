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
 * Class CRM_Core_KeyTest
 * @group headless
 */
class CRM_Core_KeyTest extends CiviUnitTestCase {

  public function testOK() {
    $key = CRM_Core_Key::get('CRM_Bread_Butter');
    $this->assertTrue(CRM_Core_Key::valid($key));
    $this->assertEquals($key, CRM_Core_Key::validate($key, 'CRM_Bread_Butter'));
  }

  public function testMalformed() {
    $key = CRM_Core_Key::get('CRM_Bread_Butter') . '<script>';
    $this->assertFalse(CRM_Core_Key::valid($key));
    $this->assertEquals(NULL, CRM_Core_Key::validate($key, 'CRM_Bread_Butter'));
  }

  public function testMixedUp() {
    $key = CRM_Core_Key::get('CRM_Toast_Jam');
    $this->assertTrue(CRM_Core_Key::valid($key));
    $this->assertEquals(NULL, CRM_Core_Key::validate($key, 'CRM_Bread_Butter'));
  }

}
