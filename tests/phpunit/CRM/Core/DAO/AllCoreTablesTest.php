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
}
