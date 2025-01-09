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
    $needsCleanup = !in_array('Civi\Test\TransactionalInterface', $implements, TRUE) ||
      // Creating custom groups or custom fields breaks transactions & requires cleanup
      array_intersect(['CustomField', 'CustomGroup'], array_column($this->testRecords, 0));
    if ($needsCleanup) {
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

}
