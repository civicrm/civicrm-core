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
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactID = $this->organizationCreate();
    $activity = $this->activityCreate();
    $this->_activityID = $activity['id'];
    CRM_Core_PseudoConstant::flush();
    $this->_params = [
      'contact_id' => $this->_contactID,
      'activity_id' => $this->_activityID,
      'record_type_id' => 2,
    ];
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateActivityContact($version) {
    $this->_apiversion = $version;

    $result = $this->callAPIAndDocument('activity_contact', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);

    $this->callAPISuccess('activity_contact', 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteActivityContact($version) {
    $this->_apiversion = $version;
    //create one
    $create = $this->callAPISuccess('activity_contact', 'create', $this->_params);

    $result = $this->callAPIAndDocument('activity_contact', 'delete', ['id' => $create['id']], __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('activity_contact', 'get', [
      'id' => $create['id'],
    ]);
    $this->assertEquals(0, $get['count'], 'ActivityContact not successfully deleted');
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivitiesByContact($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('ActivityContact', 'Get', ['contact_id' => $this->_contactID]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivitiesByActivity($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('ActivityContact', 'Get', ['activity_id' => $this->_activityID]);
  }

  /**
   * Test civicrm_activity_contact_get with empty params.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetEmptyParams($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('ActivityContact', 'Get', []);
  }

  /**
   * Test civicrm_activity_contact_get with wrong params.
   * FIXME: Api4
   */
  public function testGetWrongParams() {
    $this->callAPIFailure('ActivityContact', 'Get', ['contact_id' => 'abc']);
    $this->callAPIFailure('ActivityContact', 'Get', ['activity_id' => 'abc']);
    $this->callAPIFailure('ActivityContact', 'Get', ['record_type_id' => 'abc']);
  }

}
