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
  public static function transactionOptions() {
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
   * @throws \CRM_Core_Exception
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

  public function testForceRollback(): void {
    $result = $this->callAPIV3Success('contact', 'create', [
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

  public function testRollbackOnError(): void {
    $this->hookClass->setHook('civicrm_post', function ($op, $objectName) {
      if ($objectName === 'Individual') {
        throw new \TypeError('Ruh-roh');
      }
    });
    try {
      civicrm_api3('Contact', 'create', [
        'contact_type' => 'Individual',
        'first_name' => 'Me',
        'last_name' => 'Myself',
      ]);
      $this->fail('The error should reach the caller');
    }
    catch (\TypeError $e) {
    }
    $this->assertDBQuery(0, "SELECT count(*) FROM civicrm_contact WHERE first_name = 'Me' AND last_name = 'Myself'");
  }

  public function testGenericReplaceRollsBackOnError(): void {
    $contactID = $this->individualCreate();
    $this->hookClass->setHook('civicrm_post', function ($op, $objectName, $objectId, $objectRef) {
      if ($op === 'create' && $objectName === 'Email' && $objectRef->email === 'second@example.org') {
        throw new \TypeError('Ruh-roh');
      }
    });
    try {
      civicrm_api3('Email', 'replace', [
        'contact_id' => $contactID,
        'values' => [['email' => 'first@example.org'], ['email' => 'second@example.org']],
      ]);
      $this->fail('The error should reach the caller');
    }
    catch (\TypeError $e) {
    }
    $this->assertDBQuery(0, "SELECT COUNT(*) FROM civicrm_email WHERE email IN ('first@example.org', 'second@example.org')");
  }

  /**
   * A PHP error while replacing tags rolls back the tags already added.
   */
  public function testEntityTagReplaceRollsBackOnError(): void {
    $contactID = $this->individualCreate();
    $keptTagID = $this->tagCreate(['name' => 'Kept'])['id'];
    $newTagID = $this->tagCreate(['name' => 'New'])['id'];
    $this->callAPISuccess('EntityTag', 'create', ['entity_id' => $contactID, 'tag_id' => $keptTagID]);
    $this->hookClass->setHook('civicrm_pre', function ($op, $objectName) {
      if ($op === 'delete' && $objectName === 'EntityTag') {
        throw new \TypeError('Ruh-roh');
      }
    });
    $this->callAPIFailure('EntityTag', 'replace', ['entity_id' => $contactID, 'tag_id' => [$newTagID]], 'Ruh-roh');
    $this->assertDBQuery($keptTagID, 'SELECT GROUP_CONCAT(tag_id) FROM civicrm_entity_tag WHERE entity_table = %1 AND entity_id = %2', [
      1 => ['civicrm_contact', 'String'],
      2 => [$contactID, 'Integer'],
    ]);
  }

}
