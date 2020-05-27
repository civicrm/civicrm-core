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


namespace api\v4\Action;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class IndexTest extends UnitTestCase {

  public function testIndex() {
    // Results indexed by name
    $resultByName = civicrm_api4('Activity', 'getActions', [], 'name');
    $this->assertInstanceOf('Civi\Api4\Generic\Result', $resultByName);
    $this->assertEquals('get', $resultByName['get']['name']);

    // Get result at index 0
    $firstResult = civicrm_api4('Activity', 'getActions', [], 0);
    $this->assertInstanceOf('Civi\Api4\Generic\Result', $firstResult);
    $this->assertArrayHasKey('name', $firstResult);

    $this->assertEquals($resultByName->first(), (array) $firstResult);
  }

  public function testBadIndexInt() {
    $error = '';
    try {
      civicrm_api4('Activity', 'getActions', [], 99);
    }
    catch (\API_Exception $e) {
      $error = $e->getMessage();
    }
    $this->assertContains('not found', $error);
  }

  public function testBadIndexString() {
    $error = '';
    try {
      civicrm_api4('Activity', 'getActions', [], 'xyz');
    }
    catch (\API_Exception $e) {
      $error = $e->getMessage();
    }
    $this->assertContains('not found', $error);
  }

  public function testIndexWithSelect() {
    $result = civicrm_api4('Activity', 'getFields', ['select' => ['title'], 'where' => [['name', '=', 'subject']]], 'name');
    $this->assertEquals(['subject' => ['title' => 'Subject']], (array) $result);
  }

  public function testArrayIndex() {
    // Non-associative
    $result = civicrm_api4('Activity', 'getFields', ['where' => [['name', '=', 'subject']]], ['name' => 'title']);
    $this->assertEquals(['subject' => 'Subject'], (array) $result);

    // Associative
    $result = civicrm_api4('Activity', 'getFields', ['where' => [['name', '=', 'subject']]], ['title']);
    $this->assertEquals(['Subject'], (array) $result);
  }

}
