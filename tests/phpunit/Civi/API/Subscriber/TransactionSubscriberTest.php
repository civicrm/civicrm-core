<?php
namespace Civi\API\Subscriber;

/**
 */
class TransactionSubscriberTest extends \CiviUnitTestCase {

  /**
   * Get transaction options.
   *
   * @return array
   */
  public function transactionOptions() {
    $r = [];
    // $r[] = array(string $entity, string $action, array $params, bool $isTransactional, bool $isForceRollback, bool $isNested);

    $r[] = [3, 'Widget', 'get', [], FALSE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'create', [], TRUE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'delete', [], TRUE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'submit', [], TRUE, FALSE, FALSE];

    $r[] = [3, 'Widget', 'get', ['is_transactional' => TRUE], TRUE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'get', ['is_transactional' => FALSE], FALSE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'get', ['is_transactional' => 'nest'], TRUE, FALSE, TRUE];

    $r[] = [3, 'Widget', 'create', ['is_transactional' => TRUE], TRUE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'create', ['is_transactional' => FALSE], FALSE, FALSE, FALSE];
    $r[] = [3, 'Widget', 'create', ['is_transactional' => 'nest'], TRUE, FALSE, TRUE];

    $r[] = [3, 'Widget', 'create', ['options' => ['force_rollback' => TRUE]], TRUE, TRUE, TRUE];
    $r[] = [3, 'Widget', 'create', ['options' => ['force_rollback' => FALSE]], TRUE, FALSE, FALSE];

    $r[] = [
      3,
      'Widget',
      'create',
      ['is_transactional' => TRUE, 'options' => ['force_rollback' => TRUE]],
      TRUE,
      TRUE,
      TRUE,
    ];
    $r[] = [
      3,
      'Widget',
      'create',
      ['is_transactional' => TRUE, 'options' => ['force_rollback' => FALSE]],
      TRUE,
      FALSE,
      FALSE,
    ];
    $r[] = [
      3,
      'Widget',
      'create',
      ['is_transactional' => FALSE, 'options' => ['force_rollback' => TRUE]],
      TRUE,
      TRUE,
      TRUE,
    ];
    $r[] = [
      3,
      'Widget',
      'create',
      ['is_transactional' => FALSE, 'options' => ['force_rollback' => FALSE]],
      FALSE,
      FALSE,
      FALSE,
    ];

    //  $r[] = array(4, 'Widget', 'get', array(), FALSE, FALSE, FALSE);
    //  $r[] = array(4, 'Widget', 'create', array(), TRUE, FALSE, FALSE);
    //
    //  $r[] = array(4, 'Widget', 'create', array('is_transactional' => TRUE), TRUE, FALSE, FALSE);
    //  $r[] = array(4, 'Widget', 'create', array('is_transactional' => FALSE), FALSE, FALSE, FALSE);
    //  $r[] = array(4, 'Widget', 'create', array('is_transactional' => 'nest'), TRUE, FALSE, TRUE);
    //
    //  $r[] = array(4, 'Widget', 'create', array('options' => array('force_rollback' => TRUE)), TRUE, TRUE, TRUE);
    //  $r[] = array(4, 'Widget', 'create', array('options' => array('force_rollback' => FALSE)), TRUE, FALSE, FALSE);

    return $r;
  }

  /**
   * Ensure that API parameters "is_transactional" and "force_rollback" are parsed correctly.
   *
   * @dataProvider transactionOptions
   *
   * @param $version
   * @param $entity
   * @param $action
   * @param array $params
   * @param bool $isTransactional
   * @param bool $isForceRollback
   * @param bool $isNested
   *
   * @throws \API_Exception
   */
  public function testTransactionOptions($version, $entity, $action, $params, $isTransactional, $isForceRollback, $isNested) {
    $txs = new TransactionSubscriber();
    $apiProvider = NULL;

    $params['version'] = $version;
    $apiRequest = \Civi\API\Request::create($entity, $action, $params, []);

    $this->assertEquals($isTransactional, $txs->isTransactional($apiProvider, $apiRequest), 'check isTransactional');
    $this->assertEquals($isForceRollback, $txs->isForceRollback($apiProvider, $apiRequest), 'check isForceRollback');
    $this->assertEquals($isNested, $txs->isNested($apiProvider, $apiRequest), 'check isNested');
  }

  public function testForceRollback() {
    $result = $this->callAPISuccess('contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Me',
      'last_name' => 'Myself',
      'options' => [
        'force_rollback' => TRUE,
      ],
    ]);
    $this->assertTrue(is_numeric($result['id']));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_contact WHERE id = %1', [
      1 => [$result['id'], 'Integer'],
    ]);
  }

}
