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


/**
 *  Test APIv3 civicrm_contribute_recur* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contribution
 */


class api_v3_ContributionRecurTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $ids = array();
  protected $_entity = 'contribution_recur';
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = FALSE; function setUp() {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
      'version' => 3,
      'contact_id' => $this->ids['contact'][0],
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'day',
    );
  }

  function tearDown() {
    foreach ($this->ids as $entity => $entities) {
      foreach ($entities as $id) {
        civicrm_api($entity, 'delete', array('version' => $this->_apiversion, 'id' => $id));
      }
    }
    $tablesToTruncate = array(
      'civicrm_financial_type',
      'civicrm_contribution',
      'civicrm_contribution_recur',
      'civicrm_membership',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  public function testCreateContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $getParams = array(
      'version' => $this->_apiversion,
      'amount' => '500',
    );
    $result = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
  }

  public function testDeleteContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $deleteParams = array('version' => 3, 'id' => $result['id']);
    $result = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->documentMe($deleteParams, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array(
      'version' => 3,
      ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }

  public function testGetFieldsContributionRecur() {
    $result = civicrm_api($this->_entity, 'getfields', array('version' => 3, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }
}

