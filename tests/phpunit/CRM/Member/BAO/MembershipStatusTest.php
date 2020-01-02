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
    $params = [
      'name' => 'pending',
      'is_active' => 1,
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);

    $result = $this->assertDBNotNull('CRM_Member_BAO_MembershipStatus', $membershipStatus->id,
      'name', 'id',
      'Database check on updated membership status record.'
    );
    $this->assertEquals($result, 'pending', 'Verify membership status is_active.');
  }

  public function testRetrieve() {

    $params = [
      'name' => 'testStatus',
      'is_active' => 1,
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $defaults = [];
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'testStatus', 'Verify membership status name.');
    CRM_Member_BAO_MembershipStatus::del($membershipStatus->id);
  }

  public function testPseudoConstantflush() {
    $params = [
      'name' => 'testStatus',
      'is_active' => 1,
    ];
    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $defaults = [];
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'testStatus', 'Verify membership status name.');
    $updateParams = [
      'id' => $membershipStatus->id,
      'name' => 'testStatus',
      'label' => 'Changed Status',
      'is_active' => 1,
    ];
    $membershipStatus2 = CRM_Member_BAO_MembershipStatus::add($updateParams);
    $result = CRM_Member_PseudoConstant::membershipStatus($membershipStatus->id, NULL, 'label', FALSE, FALSE);
    $this->assertEquals($result, 'Changed Status', 'Verify updated membership status label From PseudoConstant.');
    CRM_Member_BAO_MembershipStatus::del($membershipStatus->id);
  }

  public function testSetIsActive() {

    $params = [
      'name' => 'pending',
      'is_active' => 1,
    ];

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
    $params = [
      'name' => 'pending',
      'is_active' => 1,
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatus($membershipStatus->id);
    $this->assertEquals($result['name'], 'pending', 'Verify membership status name.');
  }

  public function testDel() {
    $params = [
      'name' => 'testStatus',
      'is_active' => 1,
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    CRM_Member_BAO_MembershipStatus::del($membershipStatus->id);
    $defaults = [];
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify membership status record deletion.');
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testExpiredDisabled() {
    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'Expired',
      'api.MembershipStatus.create' => ['label' => 'Expiiiired'],
    ]);

    // Calling it 'Expiiiired' is OK.
    $this->callAPISuccess('job', 'process_membership', []);

    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'Expired',
      'api.MembershipStatus.create' => ['is_active' => 0],
    ]);

    // Disabling 'Expired' is OK.
    $this->callAPISuccess('job', 'process_membership', []);

    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'Expired',
      'api.MembershipStatus.delete' => [],
    ]);

    // Deleting 'Expired' is OK.
    $this->callAPISuccess('job', 'process_membership', []);

    // Put things back like normal
    $this->callAPISuccess('MembershipStatus', 'create', [
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
    $params = [
      'name' => 'Current',
      'is_active' => 1,
      'start_event' => 'start_date',
      'end_event' => 'end_date',
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $toDate = date('Ymd');

    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($toDate, $toDate, $toDate, 'today', TRUE, NULL, $params);
    $this->assertEquals($result['name'], 'Current', 'Verify membership status record.');
  }

  public function testgetMembershipStatusCurrent() {
    $params = [
      'name' => 'Current',
      'is_active' => 1,
      'is_current_member' => 1,
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent();

    $this->assertEquals(empty($result), FALSE, 'Verify membership status records is_current_member.');
  }

}
