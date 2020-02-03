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

    $dao = CRM_Core_DAO::executeQuery("SELECT
    DISTINCT ( price_set_id ) as id, s.title
    FROM
    civicrm_price_set s
    INNER JOIN civicrm_price_field f ON f.price_set_id = s.id
    INNER JOIN civicrm_price_field_value v ON v.price_field_id = f.id
    WHERE
    is_quick_config = 0  AND s.is_active = 1  AND s.financial_type_id IN (3,1,4,2) AND v.financial_type_id IN (3,1,4,2)
    GROUP BY s.id");
    echo "\nPrice sets found:\n";
    while ($dao->fetch()) {
      echo "\nTitle: $dao->title\n";
    }
  }

}
