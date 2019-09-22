<?php

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
