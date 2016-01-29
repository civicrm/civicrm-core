<?php

/**
 * Class for testing new DAO meet required standards.
 *
 * Class CRM_Core_DAOTest
 */
class CRM_Core_DAOConformanceTest extends CiviUnitTestCase {

  /**
   * Check all fields have defined titles.
   *
   * @dataProvider getAllDAO
   */
  public function testFieldsHaveTitles($class) {
    $dao = new $class();
    $fields = $dao->fields();
    foreach ($fields as $name => $field) {
      $this->assertArrayHasKey('title', $field, "A title must be defined for $name in $class");
    }
  }

  /**
   * Get all DAO classes.
   */
  public function getAllDAO() {
    $this->setUp(); // Ugh. Need full bootstrap to enumerate classes.
    $classList = CRM_Core_DAO_AllCoreTables::getClasses();
    $return = array();
    foreach ($classList as $class) {
      $return[] = array($class);
    }
    return $return;
  }

}
