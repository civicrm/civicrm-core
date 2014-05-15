<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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


/**
 *  Test APIv3 civicrm_grant* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Grant
 */

class api_v3_GrantTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $ids = array();
  protected $_entity = 'Grant';

  public $DBResetRequired = FALSE;

  function setUp() {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
           'contact_id' => $this->ids['contact'][0],
      'application_received_date' => 'now',
      'decision_date' => 'next Monday',
      'amount_total' => '500',
      'status_id' => 1,
      'rationale' => 'Just Because',
      'currency' => 'USD',
      'grant_type_id' => 1,
    );
  }

  function tearDown() {
    foreach ($this->ids as $entity => $entities) {
      foreach ($entities as $id) {
        $this->callAPISuccess($entity, 'delete', array( 'id' => $id));
      }
    }
  }

  public function testCreateGrant() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetGrant() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->ids['grant'][0] = $result['id'];
    $result = $this->callAPIAndDocument($this->_entity, 'get', array('rationale' => 'Just Because'), __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
  }

  public function testDeleteGrant() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'delete', array('id' => $result['id']), __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array(
           ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }
  /*
 * This is a test to check if setting fields one at a time alters other fields
 * Issues Hit so far =
 * 1) Currency keeps getting reset to USD -  BUT this may be the only enabled currency
 *  - in which case it is valid
 * 2)
 */

  public function testCreateAutoGrant() {
    $entityName = $this->_entity;
    $baoString  = 'CRM_Grant_BAO_Grant';
    $fields     = $this->callAPISuccess($entityName, 'getfields', array(
               'action' => 'create',
      )
    );

    $fields = $fields['values'];
    $return = array_keys($fields);
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject($baoString, array('currency' => 'USD'), 2, 0);
    $getentities = $this->callAPISuccess($entityName, 'get', array(
               'sequential' => 1,
        'return' => $return,
      ));

    // lets use first rather than assume only one exists
    $entity = $getentities['values'][0];
    $entity2 = $getentities['values'][1];
    foreach ($fields as $field => $specs) {
      if ($field == 'currency' || $field == 'id') {
        continue;
      }
      switch ($specs['type']) {
        case CRM_Utils_Type::T_DATE:
        case CRM_Utils_Type::T_TIMESTAMP:
          $entity[$field] = '2012-05-20';
          break;

        case CRM_Utils_Type::T_STRING:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_EMAIL:
          $entity[$field] = 'New String';
          break;

        case CRM_Utils_Type::T_INT:
          // probably created with a 1
          $entity[$field] = 2;
          if (!empty($specs['FKClassName'])) {
            $entity[$field] = empty($entity2[$field]) ? $entity2[$specs]['uniqueName'] : $entity2[$field];
          }
          break;

        case CRM_Utils_Type::T_BOOLEAN:
          // probably created with a 1
          $entity[$field] = 0;
          break;

        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_MONEY:
          $entity[$field] = 222;
          break;

        case CRM_Utils_Type::T_URL:
          $entity[$field] = 'warm.beer.com';
      }
      $updateParams = array(
               'id' => $entity['id'],
         $field => $entity[$field],
        'debug' => 1,
      );
      $update = $this->callAPISuccess($entityName, 'create', $updateParams);
      $this->assertAPISuccess($update, "setting $field to {$entity[$field]} in line " . __LINE__);
      $checkParams = array(
        'id' => $entity['id'],
        'sequential' => 1,
      );
      $checkEntity = $this->callAPISuccess($entityName, 'getsingle', $checkParams);
      $this->assertAPIArrayComparison((array) $entity, $checkEntity);
    }
    $baoObj->deleteTestObjects($baoString);
    $baoObj->free();
  }
}

