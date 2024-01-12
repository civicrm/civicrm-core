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

use Civi\Api4\Query\Api4SelectQuery;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class OptionValueJoinTest extends Api4TestBase {

  public function testCommunicationMethodJoin(): void {
    $this->createTestRecord('Contact', [
      'preferred_communication_method' => 1,
    ]);

    $api = \Civi\API\Request::create('Contact', 'get', [
      'version' => 4,
      'checkPermissions' => FALSE,
      'select' => ['first_name', 'preferred_communication_method:label'],
      'where' => [['preferred_communication_method', 'IS NOT NULL']],
    ]);
    $query = new Api4SelectQuery($api);
    $results = $query->run();
    $first = array_shift($results);
    $keys = array_keys($first);
    sort($keys);
    $this->assertEquals(['first_name', 'id', 'preferred_communication_method:label'], $keys);
    $firstPreferredMethod = array_shift($first['preferred_communication_method:label']);

    $this->assertEquals(
      'Phone',
      $firstPreferredMethod
    );
  }

}
