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
 * Class api_v3_MembershipStatusTest
 * @group headless
 */
class api_v3_MembershipStatusTest extends CiviUnitTestCase {

  protected $_contactID;
  protected $_membershipTypeID;
  protected $_membershipStatusID;

  protected $_apiversion = 3;

  public function setUp(): void {
    parent::setUp();
    $this->_contactID = $this->individualCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate(['member_of_contact_id' => $this->_contactID]);
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');

    CRM_Member_PseudoConstant::membershipType($this->_membershipTypeID, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
  }

  /**
   * Cleanup after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  ///////////////// civicrm_membership_status_get methods

  /**
   * Test civicrm_membership_status_get with empty params.
   */
  public function testGetEmptyParams() {
    $result = $this->callAPISuccess('membership_status', 'get', []);
    // It should be 8 statuses, 7 default from mysql_data
    // plus one test status added in setUp
    $this->assertEquals(8, $result['count']);
  }

  /**
   * Test civicrm_membership_status_get. Success expected.
   */
  public function testGet() {
    $params = [
      'name' => 'test status',
    ];
    $result = $this->callAPIAndDocument('membership_status', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$this->_membershipStatusID]['name'], "test status", "In line " . __LINE__);
  }

  /**
   * Test civicrm_membership_status_get. Success expected.
   */
  public function testGetLimit() {
    $result = $this->callAPISuccess('membership_status', 'get', []);
    $this->assertGreaterThan(1, $result['count'], "Check more than one exists In line " . __LINE__);
    $params['option.limit'] = 1;
    $result = $this->callAPISuccess('membership_status', 'get', $params);
    $this->assertEquals(1, $result['count'], "Check only 1 retrieved " . __LINE__);
  }

  public function testCreateDuplicateName(): void {
    $params = ['name' => 'name'];
    $this->callAPISuccess('MembershipStatus', 'create', $params);
    $this->callAPIFailure('MembershipStatus', 'create', $params,
      'A membership status with this name already exists.'
    );
  }

  public function testCreateWithMissingRequired(): void {
    $params = ['title' => 'Does not make sense'];
    $this->callAPIFailure('MembershipStatus', 'create', $params, 'Mandatory key(s) missing from params array: name');
  }

  public function testCreate() {
    $params = [
      'name' => 'test membership status',
    ];
    $result = $this->callAPIAndDocument('membership_status', 'create', $params, __FUNCTION__, __FILE__);

    $this->assertNotNull($result['id']);
    $this->membershipStatusDelete($result['id']);
  }

  public function testUpdate() {
    $params = [
      'name' => 'test membership status',
    ];
    $result = $this->callAPISuccess('membership_status', 'create', $params);
    $id = $result['id'];
    $result = $this->callAPISuccess('membership_status', 'get', $params);
    $this->assertEquals('test membership status', $result['values'][$id]['name']);
    $newParams = [
      'id' => $id,
      'name' => 'renamed',
    ];
    $this->callAPISuccess('MembershipStatus', 'create', $newParams);
    $result = $this->callAPISuccess('MembershipStatus', 'get', ['id' => $id]);
    $this->assertEquals('renamed', $result['values'][$id]['name']);
  }

  ///////////////// civicrm_membership_status_delete methods

  /**
   * Attempt (and fail) to delete membership status without an parameters.
   */
  public function testDeleteEmptyParams(): void {
    $this->callAPIFailure('membership_status', 'delete', []);
  }

  public function testDeleteWithMissingRequired() {
    $params = ['title' => 'Does not make sense'];
    $result = $this->callAPIFailure('membership_status', 'delete', $params);
  }

  public function testDelete() {
    $membershipID = $this->membershipStatusCreate();
    $params = [
      'id' => $membershipID,
    ];
    $result = $this->callAPISuccess('membership_status', 'delete', $params);
  }

  /**
   * Test that after checking the person as 'Deceased', the Membership is also 'Deceased' both through inline and normal edit.
   */
  public function testDeceasedMembershipInline() {
    $contactID = $this->individualCreate();
    $params = [
      'contact_id' => $contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'status_id' => $this->_membershipStatusID,
    ];
    $this->callApiSuccess('membership', 'create', $params);
    $this->callApiSuccess('contact', 'create', ['id' => $contactID, 'is_deceased' => 1]);
    $membership = $this->callApiSuccessGetSingle('membership', ['contact_id' => $contactID]);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Deceased'), $membership['status_id']);
  }

  /**
   * Test that trying to delete membership status while membership still exists creates error.
   */
  public function testDeleteWithMembershipError(): void {
    $membershipStatusID = $this->membershipStatusCreate();
    $this->_contactID = $this->individualCreate();
    $this->_entity = 'membership';
    $params = [
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $membershipStatusID,
    ];

    $result = $this->callAPISuccess('Membership', 'create', $params);
    $membershipID = $result['id'];

    $params = [
      'id' => $membershipStatusID,
    ];
    $this->callAPIFailure('MembershipStatus', 'delete', $params);

    $this->callAPISuccess('Membership', 'Delete', [
      'id' => $membershipID,
    ]);
    $this->callAPISuccess('membership_status', 'delete', $params);
  }

}
