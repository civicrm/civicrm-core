<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_LocBlockTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_entity = 'loc_block';
  public $_eNoticeCompliant = TRUE;
  public function setUp() {
    parent::setUp();
  }

  function tearDown() {
  }

  public function testCreateLocBlock() {
    $email = $this->callAPISuccess('email', 'create', array(
      'contact_id' => 'null',
      'location_type_id' => 1,
      'email' => 'test@loc.block',
    ));
    $phone = $this->callAPISuccess('phone', 'create', array(
      'contact_id' => 'null',
      'location_type_id' => 1,
      'phone' => '1234567',
    ));
    $address = $this->callAPISuccess('address', 'create', array(
      'contact_id' => 'null',
      'location_type_id' => 1,
      'street_address' => '1234567',
    ));
    $params = array(
      'address_id' => $address['id'],
      'phone_id' => $phone['id'],
      'email_id' => $email['id'],
    );
    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $id = $result['id'];
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$id]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($params, $id, $this->_entity);
  }

  public function testCreateLocBlockEntities() {
    $params = array(
      'email' => array(
        'location_type_id' => 1,
        'email' => 'test2@loc.block',
      ),
      'phone' => array(
        'location_type_id' => 1,
        'phone' => '987654321',
      ),
      'phone_2' => array(
        'location_type_id' => 1,
        'phone' => '456-7890',
      ),
      'address' => array(
        'location_type_id' => 1,
        'street_address' => '987654321',
      ),
    );
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, 'Create entities and location block in 1 api call', NULL, 'createEntities');
    $id = $result['id'];
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);

    // Now check our results using the return param 'all'
    $getParams = array(
      'version' => $this->_apiversion,
      'id' => $id,
      'return' => 'all'
    );
    // Can't use callAPISuccess with getsingle
    $result = civicrm_api($this->_entity, 'getsingle', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__, 'Get entities and location block in 1 api call', NULL, 'get');
    $this->assertNotNull($result['email_id'], 'In line ' . __LINE__);
    $this->assertNotNull($result['phone_id'], 'In line ' . __LINE__);
    $this->assertNotNull($result['phone_2_id'], 'In line ' . __LINE__);
    $this->assertNotNull($result['address_id'], 'In line ' . __LINE__);
    $this->assertEquals($params['email']['email'], $result['email']['email'],  'In line ' . __LINE__);
    $this->assertEquals($params['phone_2']['phone'], $result['phone_2']['phone'],  'In line ' . __LINE__);
    $this->assertEquals($params['address']['street_address'], $result['address']['street_address'],  'In line ' . __LINE__);
    // Delete block
    $result = $this->callAPISuccess($this->_entity, 'delete', array('id' => $id));
  }

  public static function setUpBeforeClass() {
      // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass(){
  }
}

