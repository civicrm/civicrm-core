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
 *  Test APIv3 civicrm_activity_contact* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Activity
 * @group headless
 */
class api_v3_ActivityContactTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_activityID;
  protected $_params;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactID = $this->organizationCreate();
    $activity = $this->activityCreate();
    $this->_activityID = $activity['id'];
    CRM_Core_PseudoConstant::flush();
    $this->_params = array(
      'contact_id' => $this->_contactID,
      'activity_id' => $this->_activityID,
      'record_type_id' => 2,
    );
  }

  public function testCreateActivityContact() {

    $result = $this->callAPIAndDocument('activity_contact', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);

    $this->callAPISuccess('activity_contact', 'delete', array('id' => $result['id']));
  }

  public function testDeleteActivityContact() {
    //create one
    $create = $this->callAPISuccess('activity_contact', 'create', $this->_params);

    $result = $this->callAPIAndDocument('activity_contact', 'delete', array('id' => $create['id']), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('activity_contact', 'get', array(
      'id' => $create['id'],
    ));
    $this->assertEquals(0, $get['count'], 'ActivityContact not successfully deleted');
  }

  /**
   *
   */
  public function testGetActivitiesByContact() {
    $this->callAPISuccess('ActivityContact', 'Get', array('contact_id' => $this->_contactID));
  }

  public function testGetActivitiesByActivity() {
    $this->callAPISuccess('ActivityContact', 'Get', array('activity_id' => $this->_activityID));
  }

  /**
   * Test civicrm_activity_contact_get with empty params.
   */
  public function testGetEmptyParams() {
    $this->callAPISuccess('ActivityContact', 'Get', array());
  }

  /**
   * Test civicrm_activity_contact_get with wrong params.
   */
  public function testGetWrongParams() {
    $this->callAPIFailure('ActivityContact', 'Get', array('contact_id' => 'abc'));
    $this->callAPIFailure('ActivityContact', 'Get', array('activity_id' => 'abc'));
    $this->callAPIFailure('ActivityContact', 'Get', array('record_type_id' => 'abc'));
  }

}
