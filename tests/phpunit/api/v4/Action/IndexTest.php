<?php

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

}
