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
