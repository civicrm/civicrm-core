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
 * $Id$
 *
 */


namespace api\v4\Query;

use Civi\Api4\Query\Api4SelectQuery;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class Api4SelectQueryTest extends UnitTestCase {

  public function setUpHeadless() {
    $relatedTables = [
      'civicrm_address',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_openid',
      'civicrm_im',
      'civicrm_website',
      'civicrm_activity',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $this->loadDataSet('DefaultDataSet');
    $displayNameFormat = '{contact.first_name}{ }{contact.last_name}';
    \Civi::settings()->set('display_name_format', $displayNameFormat);

    return parent::setUpHeadless();
  }

  public function testWithSingleWhereJoin() {
    $phoneNum = $this->getReference('test_phone_1')['phone'];

    $api = \Civi\API\Request::create('Contact', 'get', ['version' => 4, 'checkPermissions' => FALSE]);
    $query = new Api4SelectQuery($api);
    $query->where[] = ['phones.phone', '=', $phoneNum];
    $results = $query->run();

    $this->assertCount(1, $results);
  }

  public function testOneToManyJoin() {
    $phoneNum = $this->getReference('test_phone_1')['phone'];

    $api = \Civi\API\Request::create('Contact', 'get', ['version' => 4, 'checkPermissions' => FALSE]);
    $query = new Api4SelectQuery($api);
    $query->select[] = 'id';
    $query->select[] = 'first_name';
    $query->select[] = 'phones.phone';
    $query->where[] = ['phones.phone', '=', $phoneNum];
    $results = $query->run();

    $this->assertCount(1, $results);
    $firstResult = array_shift($results);
    $this->assertArrayHasKey('phones', $firstResult);
    $firstPhone = array_shift($firstResult['phones']);
    $this->assertEquals($phoneNum, $firstPhone['phone']);
  }

  public function testManyToOneJoin() {
    $phoneNum = $this->getReference('test_phone_1')['phone'];
    $contact = $this->getReference('test_contact_1');

    $api = \Civi\API\Request::create('Phone', 'get', ['version' => 4, 'checkPermissions' => FALSE]);
    $query = new Api4SelectQuery($api);
    $query->select[] = 'id';
    $query->select[] = 'phone';
    $query->select[] = 'contact.display_name';
    $query->select[] = 'contact.first_name';
    $query->where[] = ['phone', '=', $phoneNum];
    $results = $query->run();

    $this->assertCount(1, $results);
    $firstResult = array_shift($results);
    $this->assertEquals($contact['display_name'], $firstResult['contact.display_name']);
  }

  public function testOneToManyMultipleJoin() {
    $api = \Civi\API\Request::create('Contact', 'get', ['version' => 4, 'checkPermissions' => FALSE]);
    $query = new Api4SelectQuery($api);
    $query->select[] = 'id';
    $query->select[] = 'first_name';
    $query->select[] = 'phones.phone';
    $query->where[] = ['first_name', '=', 'Phoney'];
    $results = $query->run();
    $result = array_pop($results);

    $this->assertEquals('Phoney', $result['first_name']);
    $this->assertCount(2, $result['phones']);
  }

  public function testInvaidSort() {
    $api = \Civi\API\Request::create('Contact', 'get', ['version' => 4, 'checkPermissions' => FALSE]);
    $query = new Api4SelectQuery($api);
    $query->select[] = 'id';
    $query->select[] = 'first_name';
    $query->select[] = 'phones.phone';
    $query->where[] = ['first_name', '=', 'Phoney'];
    $query->orderBy = ['first_name' => 'sleep(1)'];
    try {
      $results = $query->run();
      $this->fail('An Exception Should have been raised');
    }
    catch (\API_Exception $e) {
    }
    $query->orderBy = ['sleep(1)', 'ASC'];
    try {
      $results = $query->run();
      $this->fail('An Exception Should have been raised');
    }
    catch (\API_Exception $e) {
    }
  }

}
