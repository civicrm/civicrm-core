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
 * Class CRM_Member_BAO_MembershipStatusTest
 * @group headless
 */
class CRM_Member_BAO_MembershipStatusTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check function add()
   */
  public function testAdd() {
    $params = array(
      'name' => 'pending',
      'is_active' => 1,
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);

    $result = $this->assertDBNotNull('CRM_Member_BAO_MembershipStatus', $membershipStatus->id,
      'name', 'id',
      'Database check on updated membership status record.'
    );
    $this->assertEquals($result, 'pending', 'Verify membership status is_active.');
  }

  public function testRetrieve() {

    $params = array(
      'name' => 'testStatus',
      'is_active' => 1,
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $defaults = array();
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'testStatus', 'Verify membership status name.');
    CRM_Member_BAO_MembershipStatus::del($membershipStatus->id);
  }

  public function testPseudoConstantflush() {
    $params = array(
      'name' => 'testStatus',
      'is_active' => 1,
    );
    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $defaults = array();
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'testStatus', 'Verify membership status name.');
    $updateParams = array(
      'id' => $membershipStatus->id,
      'name' => 'testStatus',
      'label' => 'Changed Status',
      'is_active' => 1,
    );
    $membershipStatus2 = CRM_Member_BAO_MembershipStatus::add($updateParams);
    $result = CRM_Member_PseudoConstant::membershipStatus($membershipStatus->id, NULL, 'label', FALSE, FALSE);
    $this->assertEquals($result, 'Changed Status', 'Verify updated membership status label From PseudoConstant.');
    CRM_Member_BAO_MembershipStatus::del($membershipStatus->id);
  }

  public function testSetIsActive() {

    $params = array(
      'name' => 'pending',
      'is_active' => 1,
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $result = CRM_Member_BAO_MembershipStatus::setIsActive($membershipStatus->id, 0);
    $this->assertEquals($result, TRUE, 'Verify membership status record updation.');

    $isActive = $this->assertDBNotNull('CRM_Member_BAO_MembershipStatus', $membershipStatus->id,
      'is_active', 'id',
      'Database check on updated membership status record.'
    );
    $this->assertEquals($isActive, 0, 'Verify membership status is_active.');
  }

  public function testGetMembershipStatus() {
    $params = array(
      'name' => 'pending',
      'is_active' => 1,
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatus($membershipStatus->id);
    $this->assertEquals($result['name'], 'pending', 'Verify membership status name.');
  }

  public function testDel() {
    $params = array(
      'name' => 'testStatus',
      'is_active' => 1,
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    CRM_Member_BAO_MembershipStatus::del($membershipStatus->id);
    $defaults = array();
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify membership status record deletion.');
  }

  public function testExpiredDisabled() {
    $result = civicrm_api3('MembershipStatus', 'get', [
      'name' => "Expired",
      'api.MembershipStatus.create' => ['label' => 'Expiiiired'],
    ]);

    // Calling it 'Expiiiired' is OK.
    $result = $this->callAPISuccess('job', 'process_membership', []);

    $result = civicrm_api3('MembershipStatus', 'get', [
      'name' => "Expired",
      'api.MembershipStatus.create' => ['is_active' => 0],
    ]);

    // Disabling 'Expired' is OK.
    $result = $this->callAPISuccess('job', 'process_membership', []);

    $result = civicrm_api3('MembershipStatus', 'get', [
      'name' => "Expired",
      'api.MembershipStatus.delete' => [],
    ]);

    // Deleting 'Expired' is OK.
    $result = $this->callAPISuccess('job', 'process_membership', []);

    // Put things back like normal
    $result = civicrm_api3('MembershipStatus', 'create', [
      'name' => 'Expired',
      'label' => 'Expired',
      'start_event' => 'end_date',
      'start_event_adjust_unit' => 'month',
      'start_event_adjust_interval' => 1,
      'is_current_member' => 0,
      'is_admin' => 0,
      'weight' => 4,
      'is_default' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    ]);

  }

  public function testGetMembershipStatusByDate() {
    $params = array(
      'name' => 'Current',
      'is_active' => 1,
      'start_event' => 'start_date',
      'end_event' => 'end_date',
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $toDate = date('Ymd');

    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($toDate, $toDate, $toDate, 'today', TRUE, NULL, $params);
    $this->assertEquals($result['name'], 'Current', 'Verify membership status record.');
  }

  public function testgetMembershipStatusCurrent() {
    $params = array(
      'name' => 'Current',
      'is_active' => 1,
      'is_current_member' => 1,
    );

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent();

    $this->assertEquals(empty($result), FALSE, 'Verify membership status records is_current_member.');
  }

}
