<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Custom.php';
class CRM_Core_TransactionTest extends CiviUnitTestCase {

  function setUp() {
    parent::setUp();
    $this->quickCleanup(array('civicrm_contact', 'civicrm_activity'));
  }

  function testDefaultCommit_RawInsert_1xOuter() {
    $cid = NULL;
    $test = $this;

    $transactionalFunction_outermost = function () use (&$cid, $test) {
      $tx = new CRM_Core_Transaction();
      $r = CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contact(first_name,last_name) VALUES ('ff', 'll')");
      $cid = mysql_insert_id();
      $test->assertContactsExist(array($cid), TRUE);

      // End of outermost $tx; COMMIT will execute ASAP
    };

    $transactionalFunction_outermost();
    $test->assertContactsExist(array($cid), TRUE);
  }

  function testDefaultCommit_BaoCreate_1xOuter() {
    $cid = NULL;
    $test = $this;

    $transactionalFunction_outermost = function () use (&$cid, $test) {
      $tx = new CRM_Core_Transaction();
      $params = array(
        'contact_type' => 'Individual',
        'first_name' => 'FF',
        'last_name' => 'LL',
      );
      $r = CRM_Contact_BAO_Contact::create($params);
      $cid = $r->id;
      $test->assertContactsExist(array($cid), TRUE);

      // End of outermost $tx; COMMIT will execute ASAP
    };

    $transactionalFunction_outermost();
    $test->assertContactsExist(array($cid), TRUE);
  }

  function testRollback_RawInsert_1xOuter() {
    $cid = NULL;
    $test = $this;

    $transactionalFunction_outermost = function() use (&$cid, $test) {
      $tx = new CRM_Core_Transaction();

      $r = CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contact(first_name,last_name) VALUES ('ff', 'll')");
      $cid = mysql_insert_id();

      $test->assertContactsExist(array($cid), TRUE);

      $tx->rollback(); // Mark ROLLBACK, but don't execute yet

      // End of outermost $tx; ROLLBACK will execute ASAP
    };

    $transactionalFunction_outermost();
    $test->assertContactsExist(array($cid), FALSE);
  }

  /**
   * Test in which an outer function ($transactionalFunction_outermost) makes multiple calls
   * to inner functions ($transactionalFunction_inner) but then rollsback the entire set.
   */
  function testRollback_RawInsert_2xInner() {
    $cids = array();
    $test = $this;

    $transactionalFunction_inner = function() use (&$cids, $test) {
      $tx = new CRM_Core_Transaction();

      $r = CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contact(first_name,last_name) VALUES ('ff', 'll')");
      $cid = mysql_insert_id();
      $cids[] = $cid;

      $test->assertContactsExist($cids, TRUE);

      // End of inner $tx; neither COMMIT nor ROLLBACK b/c another $tx remains
    };

    $transactionalFunction_outermost = function() use (&$cids, $test, $transactionalFunction_inner) {
      $tx = new CRM_Core_Transaction();

      $transactionalFunction_inner();
      $transactionalFunction_inner();

      $tx->rollback(); // Mark ROLLBACK, but don't execute yet

      $test->assertContactsExist($cids, TRUE); // not yet rolled back

      // End of outermost $tx; ROLLBACK will execute ASAP
    };

    $transactionalFunction_outermost();
    $test->assertContactsExist($cids, FALSE);
  }

  function testRollback_BaoCreate_1xOuter() {
    $cid = NULL;
    $test = $this;

    $transactionalFunction_outermost = function() use (&$cid, $test) {
      $tx = new CRM_Core_Transaction();

      $params = array(
        'contact_type' => 'Individual',
        'first_name' => 'F',
        'last_name' => 'L',
      );
      $r = CRM_Contact_BAO_Contact::create($params);
      $cid = $r->id;

      $test->assertContactsExist(array($cid), TRUE);

      $tx->rollback(); // Mark ROLLBACK, but don't execute yet

      // End of outermost $tx; ROLLBACK will execute ASAP
    };

    $transactionalFunction_outermost();

    // No outstanding $tx -- ROLLBACK should be done
    $test->assertContactsExist(array($cid), FALSE);
  }

  /**
   * Test in which an outer function ($transactionalFunction_outermost) makes multiple calls
   * to inner functions ($transactionalFunction_inner) but then rollsback the entire set.
   */
  function testRollback_BaoCreate_2xInner() {
    $cids = array();
    $test = $this;

    $transactionalFunction_inner = function() use (&$cids, $test) {
      $tx = new CRM_Core_Transaction();

      $params = array(
        'contact_type' => 'Individual',
        'first_name' => 'F',
        'last_name' => 'L',
      );
      $r = CRM_Contact_BAO_Contact::create($params);
      $cid = $r->id;
      $cids[] = $cid;

      $test->assertContactsExist($cids, TRUE);

      // end of inner $tx; neither COMMIT nor ROLLBACK b/c it's inner
    };

    $transactionalFunction_outermost = function() use (&$cids, $test, $transactionalFunction_inner) {
      $tx = new CRM_Core_Transaction();

      $transactionalFunction_inner();
      $transactionalFunction_inner();

      $tx->rollback(); // Mark ROLLBACK, but don't execute yet

      $test->assertContactsExist($cids, TRUE); // not yet rolled back

      // End of outermost $tx; ROLLBACK will execute ASAP
    };

    $transactionalFunction_outermost();

    // No outstanding $tx -- ROLLBACK should be done
    $test->assertContactsExist($cids, FALSE);
  }

  public function assertContactsExist($cids, $exist = TRUE) {
    foreach ($cids as $cid) {
      $this->assertTrue(is_numeric($cid));
      $this->assertDBQuery($exist ? 1 : 0, 'SELECT count(*) FROM civicrm_contact WHERE id = %1', array(
        1 => array($cid, 'Integer'),
      ));
    }
  }
}