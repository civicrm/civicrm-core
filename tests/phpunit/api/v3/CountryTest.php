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
 *  Test APIv3 civicrm_country* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_CountryTest extends CiviUnitTestCase {
  protected $_params;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = [
      'name' => 'Made Up Land',
      'iso_code' => 'ZZ',
      'region_id' => 1,
    ];
  }

  /**
   * @dataProvider versionThreeAndFour
   */
  public function testCreateCountry(): void {

    $result = $this->callAPISuccess('country', 'create', $this->_params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);

    $this->callAPISuccess('country', 'delete', ['id' => $result['id']]);
  }

  /**
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteCountry(): void {
    //create one
    $create = $this->callAPISuccess('country', 'create', $this->_params);

    $result = $this->callAPISuccess('country', 'delete', ['id' => $create['id']]);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('country', 'get', [
      'id' => $create['id'],
    ]);
    $this->assertEquals(0, $get['count'], 'Country not successfully deleted');
  }

  /**
   * Test civicrm_phone_get with empty params.
   * @dataProvider versionThreeAndFour
   */
  public function testGetEmptyParams(): void {
    $result = $this->callAPISuccess('Country', 'Get', []);
  }

  /**
   * Test civicrm_phone_get with wrong params.
   * @dataProvider versionThreeAndFour
   */
  public function testGetWrongParams(): void {
    $this->callAPIFailure('Country', 'Get', ['id' => 'abc']);
  }

  /**
   * Test civicrm_phone_get - success expected.
   * @dataProvider versionThreeAndFour
   */
  public function testGet(): void {
    $country = $this->callAPISuccess('Country', 'create', $this->_params);
    $params = [
      'iso_code' => $this->_params['iso_code'],
    ];
    $result = $this->callAPISuccess('Country', 'Get', $params);
    $this->assertEquals($country['values'][$country['id']]['name'], $result['values'][$country['id']]['name']);
    $this->assertEquals($country['values'][$country['id']]['iso_code'], $result['values'][$country['id']]['iso_code']);
  }

  ///////////////// civicrm_country_create methods

  /**
   * If a new country is created and it is created again it should not create a second one.
   * We check on the iso code (there should be only one iso code
   * @dataProvider versionThreeAndFour
   */
  public function testCreateDuplicateFail(): void {
    $params = $this->_params;
    unset($params['id']);
    $this->callAPISuccess('country', 'create', $params);
    $this->callAPIFailure('country', 'create', $params);
    $check = $this->callAPISuccess('country', 'getcount', [
      'iso_code' => $params['iso_code'],
    ]);
    $this->assertEquals(1, $check);
  }

  /**
   * Test that the list of states is in the correct format when chaining
   * and using sequential.
   */
  public function testCountryStateChainSequential(): void {
    // first without specifying
    $result = $this->callAPISuccess('Country', 'getsingle', [
      'iso_code' => 'US',
      'api.Address.getoptions' => [
        'field' => 'state_province_id',
        'country_id' => '$value.id',
      ],
    ]);
    $this->assertSame(['key' => 1000, 'value' => 'Alabama'], $result['api.Address.getoptions']['values'][0]);
    $this->assertSame(['key' => 1001, 'value' => 'Alaska'], $result['api.Address.getoptions']['values'][1]);
    $this->assertSame(['key' => 1049, 'value' => 'Wyoming'], $result['api.Address.getoptions']['values'][59]);

    // now specifying sequential
    $result = $this->callAPISuccess('Country', 'getsingle', [
      'iso_code' => 'US',
      'api.Address.getoptions' => [
        'field' => 'state_province_id',
        'country_id' => '$value.id',
        'sequential' => 1,
      ],
    ]);
    $this->assertSame(['key' => 1000, 'value' => 'Alabama'], $result['api.Address.getoptions']['values'][0]);
    $this->assertSame(['key' => 1001, 'value' => 'Alaska'], $result['api.Address.getoptions']['values'][1]);
    $this->assertSame(['key' => 1049, 'value' => 'Wyoming'], $result['api.Address.getoptions']['values'][59]);

    // now specifying keyed
    $result = $this->callAPISuccess('Country', 'getsingle', [
      'iso_code' => 'US',
      'api.Address.getoptions' => [
        'field' => 'state_province_id',
        'country_id' => '$value.id',
        'sequential' => 0,
      ],
    ]);
    $this->assertSame('Alabama', $result['api.Address.getoptions']['values'][1000]);
    $this->assertSame('Alaska', $result['api.Address.getoptions']['values'][1001]);
    $this->assertSame('Wyoming', $result['api.Address.getoptions']['values'][1049]);
  }

}
