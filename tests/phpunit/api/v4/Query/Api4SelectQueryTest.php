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


namespace api\v4\Query;

use Civi\API\Request;
use Civi\Api4\Query\Api4SelectQuery;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class Api4SelectQueryTest extends Api4TestBase {

  public function testManyToOneJoin(): void {
    $contact = $this->createTestRecord('Contact', [
      'first_name' => uniqid(),
      'last_name' => uniqid(),
    ]);
    $phone = $this->createTestRecord('Phone', [
      'contact_id' => $contact['id'],
      'phone' => uniqid(),
    ]);

    $phoneNum = $phone['phone'];

    $api = Request::create('Phone', 'get', [
      'version' => 4,
      'checkPermissions' => FALSE,
      'select' => ['id', 'phone', 'contact_id.display_name', 'contact_id.first_name'],
      'where' => [['phone', '=', $phoneNum]],
    ]);
    $query = new Api4SelectQuery($api);
    $results = $query->run();

    $this->assertCount(1, $results);
    $firstResult = array_shift($results);
    $this->assertEquals($contact['display_name'], $firstResult['contact_id.display_name']);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function testAggregateNoGroupBy(): void {
    $api = Request::create('Pledge', 'get', [
      'version' => 4,
      'checkPermissions' => FALSE,
      'select' => ['SUM(amount) AS SUM_amount'],
    ]);
    $query = new Api4SelectQuery($api);
    $this->assertLike(
      'SELECT SUM(`a`.`amount`) AS `SUM_amount` FROM `civicrm_pledge` a',
      $query->getSql()
    );
  }

  /**
   * Ensures autoJoinFK() does not fatal on an unresolvable base table.
   *
   * When the base table cannot be resolved to a string (e.g. getFrom()
   * returns NULL because the entity's table mapping is unavailable),
   * autoJoinFK() must bail out gracefully rather than pass NULL to the
   * strictly-typed Joiner::getPath() and trigger an uncaught \TypeError. The
   * select clause silently ignores unknown fields, so this path never throws.
   */
  public function testAutoJoinWithUnresolvedBaseTableDoesNotThrow(): void {
    $api = Request::create('Contact', 'get', [
      'version' => 4,
      'checkPermissions' => FALSE,
      'select' => ['id'],
    ]);
    // A query whose base table cannot be resolved to a string. The constructor
    // still resolves the (real) Contact table, so construction succeeds; only
    // the later getFrom() lookup inside autoJoinFK() returns NULL.
    $query = new class($api) extends Api4SelectQuery {

      /**
       * {@inheritdoc}
       */
      public function getFrom() {
        return NULL;
      }

      /**
       * Exposes the protected autoJoinFK() so the test can invoke it directly.
       *
       * @param string $key
       *   The field path to auto-join.
       */
      public function callAutoJoin($key) {
        $this->autoJoinFK($key);
      }

    };

    $fieldSpecBefore = $query->apiFieldSpec;

    // Before the fix this threw a \TypeError (Argument #1 ($baseTable) must
    // be of type string, null given) that escaped the \CRM_Core_Exception
    // handling below.
    $query->callAutoJoin('some_fk_field.some_column');

    // The unresolvable join is silently skipped, leaving the field spec
    // untouched - not fatalled partway through.
    $this->assertEquals($fieldSpecBefore, $query->apiFieldSpec);
  }

  /**
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function testInvalidSort(): void {
    $api = Request::create('Contact', 'get', [
      'version' => 4,
      'checkPermissions' => FALSE,
      'select' => ['id', 'display_name'],
      'where' => [['first_name', '=', 'phoney']],
      'orderBy' => ['first_name' => 'sleep(1)'],
    ]);
    $query = new Api4SelectQuery($api);
    try {
      $query->run();
      $this->fail('An Exception Should have been raised');
    }
    catch (\CRM_Core_Exception $e) {
    }

    $api = Request::create('Contact', 'get', [
      'version' => 4,
      'checkPermissions' => FALSE,
      'select' => ['id', 'display_name'],
      'where' => [['first_name', '=', 'phoney']],
      'orderBy' => ['sleep(1)' => 'ASC'],
    ]);
    $query = new Api4SelectQuery($api);
    try {
      $query->run();
      $this->fail('An Exception Should have been raised');
    }
    catch (\CRM_Core_Exception $e) {
    }
  }

}
