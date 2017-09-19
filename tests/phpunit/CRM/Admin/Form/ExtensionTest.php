<?php

/**
 * Class CRM_Admin_Form_ExtensionTest
 * @group headless
 */
class CRM_Admin_Form_ExtensionTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function extenionKeyTests() {
    $keys = array();
    $keys[] = array('org.civicrm.multisite');
    $keys[] = array('au.org.contribute2016');
    $keys[] = array('%3Csvg%20onload=alert(0)%3E');
    return $keys;
  }

  /**
   * @param $key
   * @dataProvider extenionKeyTests
   */
  public function testExtenionKeyValid($key) {
    if ($key == '%3Csvg%20onload=alert(0)%3E') {
      $this->assertFalse(CRM_Admin_Form_Extensions::checkExtesnionKeyIsValid($key));
    }
    else {
      $this->assertTrue(CRM_Admin_Form_Extensions::checkExtesnionKeyIsValid($key));
    }
  }

}
