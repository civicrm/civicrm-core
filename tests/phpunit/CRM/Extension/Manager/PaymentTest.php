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
 * Class CRM_Extension_Manager_PaymentTest
 * @group headless
 */
class CRM_Extension_Manager_PaymentTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    if (class_exists('test_extension_manager_paymenttest')) {
      test_extension_manager_paymenttest::$counts = [];
    }
    $this->system = new CRM_Extension_System([
      'extensionsDir' => '',
      'extensionsURL' => '',
    ]);
    $this->quickCleanup(['civicrm_payment_processor']);
  }

  public function tearDown() {
    parent::tearDown();
    $this->system = NULL;
    $this->quickCleanup(['civicrm_payment_processor']);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableUninstall() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $manager->install(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['install']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');

    $manager->disable(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['disable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 0');

    $manager->uninstall(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['uninstall']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableEnable() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');

    $manager->install(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['install']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');

    $manager->disable(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['disable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 0');

    $manager->enable(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['enable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');
  }

  /**
   * Install an extension and create a payment processor which uses it.
   * Attempts to uninstall fail
   */
  public function testInstall_Add_FailUninstall() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');

    $manager->install(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['install']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');
    $payment_processor_type_id = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_payment_processor_type  WHERE class_name = "test.extension.manager.paymenttest"');

    $ppDAO = CRM_Financial_BAO_PaymentProcessor::create([
      'payment_processor_type_id' => $payment_processor_type_id,
      'domain_id' => CRM_Core_Config::domainID(),
    ]);

    $manager->disable(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['disable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 0');

    // first attempt to uninstall -- fail
    try {
      $manager->uninstall(['test.extension.manager.paymenttest']);
      $this->fail('Failed to catch expected exception');
    }
    catch (CRM_Extension_Exception_DependencyException $e) {
    }
    $this->assertEquals(0, test_extension_manager_paymenttest::getCount('uninstall'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');

    $ppDAO->delete();

    // second attempt to uninstall -- ok
    $manager->uninstall(['test.extension.manager.paymenttest']);
    $this->assertEquals(1, test_extension_manager_paymenttest::getCount('uninstall'));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
  }

}
