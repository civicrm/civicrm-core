<?php

/**
 * Class CRM_Core_SssTest
 * @group headless
 */
class CRM_Core_SssTest extends CiviUnitTestCase {

  /**
   * Test
   */
  public function testA() {
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_country DROP COLUMN is_active");
    $result = $this->callAPISuccess('Address', 'getfield', array('entity' => 'Address', 'context' => 'create', 'name' => 'country_id', 'action' => 'create', ));
  }

}
