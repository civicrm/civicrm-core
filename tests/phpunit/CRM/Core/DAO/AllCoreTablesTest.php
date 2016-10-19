<?php

/**
 * Class CRM_Core_DAO_AllCoreTablesTest
 * @group headless
 */
class CRM_Core_DAO_AllCoreTablesTest extends CiviUnitTestCase {
  public function testGetTableForClass() {
    $this->assertEquals('civicrm_email', CRM_Core_DAO_AllCoreTables::getTableForClass('CRM_Core_DAO_Email'));
    $this->assertEquals('civicrm_email', CRM_Core_DAO_AllCoreTables::getTableForClass('CRM_Core_BAO_Email'));
  }

  /**
   * Ensure that hook_civicrm_entityTypes runs and correctly handles the
   * 'fields_callback' option.
   */
  public function testHook() {
    // 1. First, check the baseline fields()...
    $fields = CRM_Core_DAO_Email::fields();
    $this->assertFalse(isset($fields['location_type_id']['foo']));

    $exports = CRM_Core_DAO_Email::export();
    $this->assertFalse(isset($exports['contact_id']));

    // 2. Now, let's hook into it...
    $this->hookClass->setHook('civicrm_entityTypes', array($this, '_hook_civicrm_entityTypes'));
    unset(Civi::$statics['CRM_Core_DAO_Email']);
    CRM_Core_DAO_AllCoreTables::init(1);

    // 3. And see if the data has changed...
    $fields = CRM_Core_DAO_Email::fields();
    $this->assertEquals('bar', $fields['location_type_id']['foo']);

    $exports = CRM_Core_DAO_Email::export();
    $this->assertTrue(is_array($exports['contact_id']));
  }

  public function _hook_civicrm_entityTypes(&$entityTypes) {
    $entityTypes['CRM_Core_DAO_Email']['fields_callback'][] = function ($class, &$fields) {
      $fields['location_type_id']['foo'] = 'bar';
      $fields['contact_id']['export'] = TRUE;
    };
  }

  protected function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_DAO_AllCoreTables::init(1);
    parent::tearDown();
  }

}
