<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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

}
