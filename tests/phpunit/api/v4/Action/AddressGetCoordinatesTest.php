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


namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Address;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AddressGetCoordinatesTest extends Api4TestBase implements TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    \Civi\Api4\Setting::set()
      ->addValue('geoProvider', 'TestProvider')
      ->execute();
  }

  public function tearDown(): void {
    parent::tearDown();
    \Civi\Api4\Setting::revert()
      ->addSelect('geoProvider')
      ->execute();
  }

  public function testGetCoordinatesWhiteHouse(): void {
    $coordinates = Address::getCoordinates()->setAddress(\CRM_Utils_Geocode_TestProvider::ADDRESS)->execute()->first();
    $this->assertEquals(\CRM_Utils_Geocode_TestProvider::GEO_CODE_1, $coordinates['geo_code_1']);
    $this->assertEquals(\CRM_Utils_Geocode_TestProvider::GEO_CODE_2, $coordinates['geo_code_2']);
  }

  public function testGetCoordinatesNoAddress(): void {
    $coorindates = Address::getCoordinates()->setAddress('Does not exist, Washington, DC, USA')->execute()->first();
    $this->assertEmpty($coorindates);
  }

}
