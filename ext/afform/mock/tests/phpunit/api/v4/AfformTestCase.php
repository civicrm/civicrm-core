<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Base class for Afform API tests.
 */
abstract class api_v4_AfformTestCase extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(version_compare(CRM_Utils_System::version(), '5.19.alpha1', '<') ? ['org.civicrm.api4'] : [])
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
    CRM_Core_Config::singleton()->userPermissionTemp = new CRM_Core_Permission_Temp();
    CRM_Core_Config::singleton()->userPermissionTemp->grant('administer CiviCRM');
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

}
