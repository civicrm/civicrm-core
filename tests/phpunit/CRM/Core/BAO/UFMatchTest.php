<?php

/**
 * Class CRM_Core_BAO_UFMatchTest
 * @group headless
 */
class CRM_Core_BAO_UFMatchTest extends CiviUnitTestCase {

  /**
   * Don't crash if the uf_id doesn't exist
   */
  public function testGetUFValuesWithNonexistentUFId() {
    $max_id = (int) CRM_Core_DAO::singleValueQuery('SELECT MAX(uf_id) FROM civicrm_uf_match');
    $dontcrash = CRM_Core_BAO_UFMatch::getUFValues($max_id + 1);
    $this->assertNull($dontcrash);
  }

}
