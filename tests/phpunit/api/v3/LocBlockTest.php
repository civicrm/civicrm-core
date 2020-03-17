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
 * Class api_v3_LocBlockTest
 *
 * @group headless
 */
class api_v3_LocBlockTest extends CiviUnitTestCase {

  protected $_apiversion = 3;

  protected $_entity = 'loc_block';

  /**
   * Set up.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test creating location block.
   */
  public function testCreateLocBlock() {
    $email = $this->callAPISuccess('email', 'create', [
      'contact_id' => 'null',
      'email' => 'test@loc.block',
    ]);
    $phone = $this->callAPISuccess('phone', 'create', [
      'contact_id' => 'null',
      'location_type_id' => 1,
      'phone' => '1234567',
    ]);
    $address = $this->callAPISuccess('address', 'create', [
      'contact_id' => 'null',
      'location_type_id' => 1,
      'street_address' => '1234567',
    ]);
    $params = [
      'address_id' => $address['id'],
      'phone_id' => $phone['id'],
      'email_id' => $email['id'],
    ];
    $description = 'Create locBlock with existing entities';
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, $description);
    $id = $result['id'];
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$id]['id']);
    $this->getAndCheck($params, $id, $this->_entity);
  }

  /**
   * Test creating location block entities.
   */
  public function testCreateLocBlockEntities() {
    $params = [
      'email' => [
        'location_type_id' => 1,
        'email' => 'test2@loc.block',
      ],
      'phone' => [
        'location_type_id' => 1,
        'phone' => '987654321',
      ],
      'phone_2' => [
        'location_type_id' => 1,
        'phone' => '456-7890',
      ],
      'address' => [
        'location_type_id' => 1,
        'street_address' => '987654321',
      ],
    ];
    $description = "Create entities and locBlock in 1 api call.";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, $description, 'CreateEntities');
    $id = $result['id'];
    $this->assertEquals(1, $result['count']);

    // Now check our results using the return param 'all'.
    $getParams = [
      'id' => $id,
      'return' => 'all',
    ];
    // Can't use callAPISuccess with getsingle.
    $result = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__, 'Get entities and location block in 1 api call');
    $result = array_pop($result['values']);
    $this->assertNotNull($result['email_id']);
    $this->assertNotNull($result['phone_id']);
    $this->assertNotNull($result['phone_2_id']);
    $this->assertNotNull($result['address_id']);
    $this->assertEquals($params['email']['email'], $result['email']['email']);
    $this->assertEquals($params['phone_2']['phone'], $result['phone_2']['phone']);
    $this->assertEquals($params['address']['street_address'], $result['address']['street_address']);

    $this->callAPISuccess($this->_entity, 'delete', ['id' => $id]);
  }

}
