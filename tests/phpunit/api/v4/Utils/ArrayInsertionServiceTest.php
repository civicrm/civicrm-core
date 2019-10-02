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


namespace api\v4\Utils;

use Civi\Api4\Utils\ArrayInsertionUtil;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ArrayInsertionServiceTest extends UnitTestCase {

  public function testInsertWillWork() {
    $arr = [];
    $path = ['foo' => FALSE, 'bar' => FALSE];
    $inserter = new ArrayInsertionUtil();
    $inserter::insert($arr, $path, ['LALA']);

    $expected = [
      'foo' => [
        'bar' => 'LALA',
      ],
    ];

    $this->assertEquals($expected, $arr);
  }

  public function testInsertionOfContactEmailLocation() {
    $contacts = [
      [
        'id' => 1,
        'first_name' => 'Jim',
      ],
      [
        'id' => 2,
        'first_name' => 'Karen',
      ],
    ];
    $emails = [
      [
        'email' => 'jim@jim.com',
        'id' => 2,
        '_parent_id' => 1,
      ],
    ];
    $locationTypes = [
      [
        'name' => 'Home',
        'id' => 3,
        '_parent_id' => 2,
      ],
    ];

    $emailPath = ['emails' => TRUE];
    $locationPath = ['emails' => TRUE, 'location' => FALSE];
    $inserter = new ArrayInsertionUtil();

    foreach ($contacts as &$contact) {
      $inserter::insert($contact, $emailPath, $emails);
      $inserter::insert($contact, $locationPath, $locationTypes);
    }

    $locationType = $contacts[0]['emails'][0]['location']['name'];
    $this->assertEquals('Home', $locationType);
  }

}
