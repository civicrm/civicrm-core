<?php

/**
 * Class CRM_Core_Permission_GenericTest
 * @group headless
 */
class CRM_Core_Permission_GenericTest extends CiviUnitTestCase {

  /**
   * @return array
   *   Array of CRM_Core_Permission_Base
   */
  public function permissionClasses() {
    $cases = array();

    $cases[] = array('CRM_Core_Permission_Drupal');
    $cases[] = array('CRM_Core_Permission_Drupal6');
    $cases[] = array('CRM_Core_Permission_Joomla');
    $cases[] = array('CRM_Core_Permission_WordPress');

    return $cases;
  }

  /**
   * @dataProvider permissionClasses
   * @param string $providerClass
   */
  public function testAlwaysDenyPermission($providerClass) {
    $provider = new $providerClass();
    $this->assertEquals(FALSE, $provider->check(CRM_Core_Permission::ALWAYS_DENY_PERMISSION));
  }

  /**
   * @dataProvider permissionClasses
   * @param string $providerClass
   */
  public function testAlwaysAllowPermission($providerClass) {
    $provider = new $providerClass();
    $this->assertEquals(TRUE, $provider->check(CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION));
  }

}
