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
    $this->assertEquals(
      'SELECT SUM(`a`.`amount`) AS `SUM_amount`
FROM civicrm_pledge a',
      trim($query->getSql())
    );
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
