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

  public $DBResetRequired = FALSE;

  function setUp() {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
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
        $this->callAPISuccess($entity, 'delete', array('id' => $id));
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
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetContributionRecur() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $getParams = array(
      'amount' => '500',
    );
    $result = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
  }

  public function testDeleteContributionRecur() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = array('id' => $result['id']);
    $result = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array(
      ));
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testGetFieldsContributionRecur() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }
}

