<?php

/**
 * test.
 *
 * Class CRM_Core_FullGroupByTest
 * @group headless
 */
class CRM_Core_FullGroupByTest extends CiviUnitTestCase {

  /**
   * test
   */
  public function testFullGroupBy() {
    //$result = db_query("SELECT @@sql_mode as foo");
    //foreach ($result as $r) {
    //  echo "SQL MODE: {$r->foo}";
    //}

    $dao = CRM_Core_DAO::executeQuery("SELECT @@sql_mode as foo");
    $dao->fetch();
    echo "SQL MODE: {$dao->foo}";
  }

}
