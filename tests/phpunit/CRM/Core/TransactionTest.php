<?php

/**
 * Class CRM_Core_TransactionTest
 * @group headless
 */
class CRM_Core_TransactionTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  private $callbackLog;

  /**
   * @var array (int $idx => int $contactId) list of contact IDs that have been created (in order of creation)
   *
   * Note that ID this is all IDs created by the test-case -- even if the creation was subsequently rolled back
   */
  private $cids;

  public function setUp() {
    parent::setUp();
    $this->quickCleanup(array('civicrm_contact', 'civicrm_activity'));
    $this->callbackLog = array();
    $this->cids = array();
  }

  /**
   * @return array
   */
  public function dataCreateStyle() {
    return array(
      array('sql-insert'),
      array('bao-create'),
    );
  }

  /**
   * @return array
   */
  public function dataCreateAndCommitStyles() {
    return array(
      array('sql-insert', 'implicit-commit'),
      array('sql-insert', 'explicit-commit'),
      array('bao-create', 'implicit-commit'),
      array('bao-create', 'explicit-commit'),
    );
  }

  /**
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testBasicCommit($createStyle, $commitStyle) {
    $this->createContactWithTransaction('reuse-tx', $createStyle, $commitStyle);
    $this->assertCount(1, $this->cids);
    $this->assertContactsExistByOffset(array(0 => TRUE));
  }

  /**
   * @dataProvider dataCreateStyle
   * @param $createStyle
   */
  public function testBasicRollback($createStyle) {
    $this->createContactWithTransaction('reuse-tx', $createStyle, 'rollback');
    $this->assertCount(1, $this->cids);
    $this->assertContactsExistByOffset(array(0 => FALSE));
  }

  /**
   * Test in which an outer function makes multiple calls to inner functions.
   * but then rolls back the entire set.
   *
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testBatchRollback($createStyle, $commitStyle) {
    $this->runBatch(
      'reuse-tx',
      array(
        // cid 0
        array('reuse-tx', $createStyle, $commitStyle),
        // cid 1
        array('reuse-tx', $createStyle, $commitStyle),
      ),
      array(0 => TRUE, 1 => TRUE),
      'rollback'
    );
    $this->assertCount(2, $this->cids);
    $this->assertContactsExistByOffset(array(0 => FALSE, 1 => FALSE));
  }

  /**
   * Test in which runBatch makes multiple calls to
   * createContactWithTransaction using a mix of rollback/commit.
   * createContactWithTransaction use nesting (savepoints), so the
   * batch is able to commit.
   *
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testMixedBatchCommit_nesting($createStyle, $commitStyle) {
    $this->runBatch(
      'reuse-tx',
      array(
        // cid 0
        array('nest-tx', $createStyle, $commitStyle),
        // cid 1
        array('nest-tx', $createStyle, 'rollback'),
        // cid 2
        array('nest-tx', $createStyle, $commitStyle),
      ),
      array(0 => TRUE, 1 => FALSE, 2 => TRUE),
      $commitStyle
    );
    $this->assertCount(3, $this->cids);
    $this->assertContactsExistByOffset(array(0 => TRUE, 1 => FALSE, 2 => TRUE));
  }

  /**
   * Test in which runBatch makes multiple calls to
   * createContactWithTransaction using a mix of rollback/commit.
   * createContactWithTransaction reuses the main transaction,
   * so the overall batch must rollback.
   *
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testMixedBatchCommit_reuse($createStyle, $commitStyle) {
    $this->runBatch(
      'reuse-tx',
      array(
        // cid 0
        array('reuse-tx', $createStyle, $commitStyle),
        // cid 1
        array('reuse-tx', $createStyle, 'rollback'),
        // cid 2
        array('reuse-tx', $createStyle, $commitStyle),
      ),
      array(0 => TRUE, 1 => TRUE, 2 => TRUE),
      $commitStyle
    );
    $this->assertCount(3, $this->cids);
    $this->assertContactsExistByOffset(array(0 => FALSE, 1 => FALSE, 2 => FALSE));
  }

  /**
   * Test in which runBatch makes multiple calls to
   * createContactWithTransaction using a mix of rollback/commit.
   * The overall batch is rolled back.
   *
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testMixedBatchRollback_nesting($createStyle, $commitStyle) {
    $this->assertFalse(CRM_Core_Transaction::isActive());
    $this->runBatch(
      'reuse-tx',
      array(
        // cid 0
        array('nest-tx', $createStyle, $commitStyle),
        // cid 1
        array('nest-tx', $createStyle, 'rollback'),
        // cid 2
        array('nest-tx', $createStyle, $commitStyle),
      ),
      array(0 => TRUE, 1 => FALSE, 2 => TRUE),
      'rollback'
    );
    $this->assertFalse(CRM_Core_Transaction::isActive());
    $this->assertCount(3, $this->cids);
    $this->assertContactsExistByOffset(array(0 => FALSE, 1 => FALSE, 2 => FALSE));
  }

  public function testIsActive() {
    $this->assertEquals(FALSE, CRM_Core_Transaction::isActive());
    $this->assertEquals(TRUE, CRM_Core_Transaction::willCommit());
    $tx = new CRM_Core_Transaction();
    $this->assertEquals(TRUE, CRM_Core_Transaction::isActive());
    $this->assertEquals(TRUE, CRM_Core_Transaction::willCommit());
    $tx = NULL;
    $this->assertEquals(FALSE, CRM_Core_Transaction::isActive());
    $this->assertEquals(TRUE, CRM_Core_Transaction::willCommit());
  }

  public function testIsActive_rollback() {
    $this->assertEquals(FALSE, CRM_Core_Transaction::isActive());
    $this->assertEquals(TRUE, CRM_Core_Transaction::willCommit());

    $tx = new CRM_Core_Transaction();
    $this->assertEquals(TRUE, CRM_Core_Transaction::isActive());
    $this->assertEquals(TRUE, CRM_Core_Transaction::willCommit());

    $tx->rollback();
    $this->assertEquals(TRUE, CRM_Core_Transaction::isActive());
    $this->assertEquals(FALSE, CRM_Core_Transaction::willCommit());

    $tx = NULL;
    $this->assertEquals(FALSE, CRM_Core_Transaction::isActive());
    $this->assertEquals(TRUE, CRM_Core_Transaction::willCommit());
  }

  public function testCallback_commit() {
    $tx = new CRM_Core_Transaction();

    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_PRE_COMMIT, array($this, '_preCommit'), array(
      'qwe',
      'rty',
    ));
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, array($this, '_postCommit'), array(
      'uio',
      'p[]',
    ));
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_PRE_ROLLBACK, array(
      $this,
      '_preRollback',
    ), array('asd', 'fgh'));
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_ROLLBACK, array(
      $this,
      '_postRollback',
    ), array('jkl', ';'));

    CRM_Core_DAO::executeQuery('UPDATE civicrm_contact SET id = 100 WHERE id = 100');

    $this->assertEquals(array(), $this->callbackLog);
    $tx = NULL;
    $this->assertEquals(array('_preCommit', 'qwe', 'rty'), $this->callbackLog[0]);
    $this->assertEquals(array('_postCommit', 'uio', 'p[]'), $this->callbackLog[1]);
  }

  public function testCallback_rollback() {
    $tx = new CRM_Core_Transaction();

    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_PRE_COMMIT, array($this, '_preCommit'), array(
      'ewq',
      'ytr',
    ));
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, array($this, '_postCommit'), array(
      'oiu',
      '][p',
    ));
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_PRE_ROLLBACK, array(
      $this,
      '_preRollback',
    ), array('dsa', 'hgf'));
    CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_ROLLBACK, array(
      $this,
      '_postRollback',
    ), array('lkj', ';'));

    CRM_Core_DAO::executeQuery('UPDATE civicrm_contact SET id = 100 WHERE id = 100');
    $tx->rollback();

    $this->assertEquals(array(), $this->callbackLog);
    $tx = NULL;
    $this->assertEquals(array('_preRollback', 'dsa', 'hgf'), $this->callbackLog[0]);
    $this->assertEquals(array('_postRollback', 'lkj', ';'), $this->callbackLog[1]);
  }

  /**
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testRun_ok($createStyle, $commitStyle) {
    $test = $this;
    CRM_Core_Transaction::create(TRUE)->run(function ($tx) use (&$test, $createStyle, $commitStyle) {
      $test->createContactWithTransaction('nest-tx', $createStyle, $commitStyle);
      $test->assertContactsExistByOffset(array(0 => TRUE));
    });
    $this->assertContactsExistByOffset(array(0 => TRUE));
  }

  /**
   * @param string $createStyle
   *   'sql-insert'|'bao-create'.
   * @param string $commitStyle
   *   'implicit-commit'|'explicit-commit'.
   * @dataProvider dataCreateAndCommitStyles
   */
  public function testRun_exception($createStyle, $commitStyle) {
    $tx = new CRM_Core_Transaction();
    $test = $this;
    // Exception
    $e = NULL;
    try {
      CRM_Core_Transaction::create(TRUE)->run(function ($tx) use (&$test, $createStyle, $commitStyle) {
        $test->createContactWithTransaction('nest-tx', $createStyle, $commitStyle);
        $test->assertContactsExistByOffset(array(0 => TRUE));
        throw new Exception("Ruh-roh");
      });
    }
    catch (Exception $ex) {
      $e = $ex;
      if (get_class($e) != 'Exception' || $e->getMessage() != 'Ruh-roh') {
        throw $e;
      }
    }
    $this->assertTrue($e instanceof Exception);
    $this->assertContactsExistByOffset(array(0 => FALSE));
  }

  /**
   * @param $cids
   * @param bool $exist
   */
  public function assertContactsExist($cids, $exist = TRUE) {
    foreach ($cids as $cid) {
      $this->assertTrue(is_numeric($cid));
      $this->assertDBQuery($exist ? 1 : 0, 'SELECT count(*) FROM civicrm_contact WHERE id = %1', array(
        1 => array($cid, 'Integer'),
      ));
    }
  }

  /**
   * @param array $existsByOffset
   *   Array(int $cidOffset => bool $expectExists).
   * @param int $generalOffset
   */
  public function assertContactsExistByOffset($existsByOffset, $generalOffset = 0) {
    foreach ($existsByOffset as $offset => $expectExists) {
      $this->assertTrue(isset($this->cids[$generalOffset + $offset]), "Find cid at offset($generalOffset + $offset)");
      $cid = $this->cids[$generalOffset + $offset];
      $this->assertTrue(is_numeric($cid));
      $this->assertDBQuery($expectExists ? 1 : 0, 'SELECT count(*) FROM civicrm_contact WHERE id = %1', array(
        1 => array($cid, 'Integer'),
      ), "Check contact at offset($generalOffset + $offset)");
    }
  }

  /**
   * Use SQL to INSERT a contact and assert success. Perform
   * work within a transaction.
   *
   * @param string $nesting
   *   'reuse-tx'|'nest-tx' how to construct transaction.
   * @param string $insert
   *   'sql-insert'|'bao-create' how to add the example record.
   * @param string $outcome
   *   'rollback'|'implicit-commit'|'explicit-commit' how to finish transaction.
   * @return int
   *   cid
   */
  public function createContactWithTransaction($nesting, $insert, $outcome) {
    if ($nesting != 'reuse-tx' && $nesting != 'nest-tx') {
      throw new RuntimeException('Bad test data: reuse=' . $nesting);
    }
    if ($insert != 'sql-insert' && $insert != 'bao-create') {
      throw new RuntimeException('Bad test data: insert=' . $insert);
    }
    if ($outcome != 'rollback' && $outcome != 'implicit-commit' && $outcome != 'explicit-commit') {
      throw new RuntimeException('Bad test data: outcome=' . $outcome);
    }

    $tx = new CRM_Core_Transaction($nesting === 'nest-tx');

    if ($insert == 'sql-insert') {
      $r = CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contact(first_name,last_name) VALUES ('ff', 'll')");
      $cid = $r->getConnection()->lastInsertId();
    }
    elseif ($insert == 'bao-create') {
      $params = array(
        'contact_type' => 'Individual',
        'first_name' => 'FF',
        'last_name' => 'LL',
      );
      $r = CRM_Contact_BAO_Contact::create($params);
      $cid = $r->id;
    }

    $this->cids[] = $cid;

    $this->assertContactsExist(array($cid), TRUE);

    if ($outcome == 'rollback') {
      $tx->rollback();
    }
    elseif ($outcome == 'explicit-commit') {
      $tx->commit();
    } // else: implicit-commit

    return $cid;
  }

  /**
   * Perform a series of operations within smaller transactions.
   *
   * @param string $nesting
   *   'reuse-tx'|'nest-tx' how to construct transaction.
   * @param array $callbacks
   *   See createContactWithTransaction.
   * @param array $existsByOffset
   *   See assertContactsMix.
   * @param string $outcome
   *   'rollback'|'implicit-commit'|'explicit-commit' how to finish transaction.
   * @return void
   */
  public function runBatch($nesting, $callbacks, $existsByOffset, $outcome) {
    if ($nesting != 'reuse-tx' && $nesting != 'nest-tx') {
      throw new RuntimeException('Bad test data: nesting=' . $nesting);
    }
    if ($outcome != 'rollback' && $outcome != 'implicit-commit' && $outcome != 'explicit-commit') {
      throw new RuntimeException('Bad test data: outcome=' . $nesting);
    }

    $tx = new CRM_Core_Transaction($nesting === 'reuse-tx');

    $generalOffset = count($this->cids);
    foreach ($callbacks as $callback) {
      list ($cbNesting, $cbInsert, $cbOutcome) = $callback;
      $this->createContactWithTransaction($cbNesting, $cbInsert, $cbOutcome);
    }

    $this->assertContactsExistByOffset($existsByOffset, $generalOffset);

    if ($outcome == 'rollback') {
      $tx->rollback();
    }
    elseif ($outcome == 'explicit-commit') {
      $tx->commit();
    } // else: implicit-commit
  }

  /**
   * @param $arg1
   * @param $arg2
   */
  public function _preCommit($arg1, $arg2) {
    $this->callbackLog[] = array('_preCommit', $arg1, $arg2);
  }

  /**
   * @param $arg1
   * @param $arg2
   */
  public function _postCommit($arg1, $arg2) {
    $this->callbackLog[] = array('_postCommit', $arg1, $arg2);
  }

  /**
   * @param $arg1
   * @param $arg2
   */
  public function _preRollback($arg1, $arg2) {
    $this->callbackLog[] = array('_preRollback', $arg1, $arg2);
  }

  /**
   * @param $arg1
   * @param $arg2
   */
  public function _postRollback($arg1, $arg2) {
    $this->callbackLog[] = array('_postRollback', $arg1, $arg2);
  }

}
