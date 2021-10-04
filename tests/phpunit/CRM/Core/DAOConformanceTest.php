<?php

/**
 * Class for testing new DAO meet required standards.
 *
 * @group headless
 */
class CRM_Core_DAOConformanceTest extends CiviUnitTestCase {

  /**
   * Check all fields have defined titles.
   */
  public function testFieldsHaveTitles() {
    foreach (CRM_Core_DAO_AllCoreTables::getClasses() as $class) {
      $dao = new $class();
      $fields = $dao->fields();
      foreach ($fields as $name => $field) {
        $this->assertArrayHasKey('title', $field, "A title must be defined for $name in $class");
      }
    }
  }

}
