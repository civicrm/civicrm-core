<?php

namespace api\v4\Entity;

use Civi\Api4\Route;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class RouteTest extends UnitTestCase {

  public function testGet() {
    $result = Route::get()->addWhere('path', '=', 'civicrm/admin')->execute();
    $this->assertEquals(1, $result->count());

    $result = Route::get()->addWhere('path', 'LIKE', 'civicrm/admin/%')->execute();
    $this->assertGreaterThan(10, $result->count());
  }

}
