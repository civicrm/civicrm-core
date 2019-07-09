<?php

/**
 * Class CRM_Core_BAO_MappingTest.
 *
 * @group headless
 */
class CRM_Core_BAO_MappingTest extends CiviUnitTestCase {

  /**
   * Test calling saveMapping.
   */
  public function testSaveMappingFields() {
    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'teest']);
    CRM_Core_BAO_Mapping::saveMappingFields([], $mapping['id']);
  }

}
