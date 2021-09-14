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


namespace api\v4\Entity;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ExampleDataTest extends UnitTestCase {

  /**
   * Basic canary test fetching a specific example.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGet() {
    $file = \Civi::paths()->getPath('[civicrm.root]/Civi/WorkflowMessage/GenericWorkflowMessage/Alex.ex.php');
    $name = 'workflow/generic/Alex';

    $this->assertTrue(file_exists($file), "Expect find canary file ($file)");

    $get = \Civi\Api4\ExampleData::get()
      ->addWhere('name', '=', $name)
      ->execute()
      ->single();
    $this->assertEquals($name, $get['name']);
    $this->assertTrue(!isset($get['data']), 'Default "get" should not return "data"');
    $this->assertTrue(!isset($get['asserts']), 'Default "get" should not return "asserts"');

    $get = \Civi\Api4\ExampleData::get()
      ->addWhere('name', 'LIKE', 'workflow/generic/%')
      ->execute();
    $this->assertTrue($get->count() > 0);
    foreach ($get as $gotten) {
      $this->assertStringStartsWith('workflow/generic/', $gotten['name']);
    }

    $get = \Civi\Api4\ExampleData::get()
      ->addWhere('name', '=', $name)
      ->addSelect('workflow', 'data')
      ->execute()
      ->single();
    $this->assertEquals($name, $get['name']);
    $this->assertEquals(100, $get['data']['modelProps']['contact']['contact_id']);
  }

}
