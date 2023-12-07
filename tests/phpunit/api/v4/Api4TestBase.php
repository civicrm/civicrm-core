<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4;

use Civi\Api4\UFMatch;
use Civi\Test;
use Civi\Test\Api4TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class Api4TestBase extends TestCase implements HeadlessInterface {

  use Api4TestTrait;

  /**
   * @see CiviUnitTestCase
   *
   * @param string $name
   * @param array $data
   * @param string $dataName
   */
  public function __construct($name = NULL, array $data = [], $dataName = '') {
    parent::__construct($name, $data, $dataName);
    error_reporting(E_ALL);
  }

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()->apply();
  }

  /**
   * Post test cleanup.
   */
  public function tearDown(): void {
    $implements = class_implements($this);
    // If not created in a transaction, test records must be deleted
    if (!in_array('Civi\Test\TransactionalInterface', $implements, TRUE)) {
      $this->deleteTestRecords();
    }
  }

  /**
   * Quick clean by emptying tables created for the test.
   *
   * @param array{tablesToTruncate: array} $params
   */
  public function cleanup(array $params): void {
    $params += [
      'tablesToTruncate' => [],
    ];
    \CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
    foreach ($params['tablesToTruncate'] as $table) {
      \Civi::log()->info('truncating: ' . $table);
      $sql = "TRUNCATE TABLE $table";
      \CRM_Core_DAO::executeQuery($sql);
    }
    \CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
  }

  /**
   * Emulate a logged in user since certain functions use that.
   * value to store a record in the DB (like activity)
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-8180
   *
   * @return int
   *   Contact ID of the created user.
   * @throws \CRM_Core_Exception
   */
  public function createLoggedInUser(): int {
    $contactID = $this->createTestRecord('Individual')['id'];
    UFMatch::delete(FALSE)->addWhere('uf_id', '=', 6)->execute();
    $this->createTestRecord('UFMatch', [
      'contact_id' => $contactID,
      'uf_name' => 'superman',
      'uf_id' => 6,
    ]);

    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $contactID);
    return $contactID;
  }

  public function userLogout() {
    \CRM_Core_Session::singleton()->reset();
    UFMatch::delete(FALSE)
      ->addWhere('uf_name', '=', 'superman')
      ->execute();
  }

}
