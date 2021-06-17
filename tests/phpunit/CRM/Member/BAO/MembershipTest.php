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
 * Class CRM_Member_BAO_MembershipTest
 * @group headless
 */
class CRM_Member_BAO_MembershipTest extends CiviUnitTestCase {

  private $_contactID;
  private $_membershipStatusID;
  private $_membershipTypeID;

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->_membershipTypeID = $this->membershipTypeCreate();
    // add a random number to avoid silly conflicts with old data
    $this->_membershipStatusID = $this->membershipStatusCreate('test status' . random_int(1, 1000));
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function tearDown(): void {
    $this->_membershipStatusID = $this->_membershipTypeID = NULL;
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Create membership type using given organization id.
   *
   * @param $organizationId
   * @param bool $withRelationship
   * @param int $maxRelated
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  private function createMembershipType($organizationId, $withRelationship = FALSE, $maxRelated = 0) {
    $membershipType = $this->callAPISuccess('MembershipType', 'create', [
      //Default domain ID
      'domain_id' => 1,
      'member_of_contact_id' => $organizationId,
      'financial_type_id' => "Member Dues",
      'duration_unit' => "year",
      'duration_interval' => 1,
      'period_type' => "rolling",
      'name' => 'Organization Membership Type',
      'relationship_type_id' => ($withRelationship) ? 5 : NULL,
      'relationship_direction' => ($withRelationship) ? 'b_a' : NULL,
      'max_related' => $maxRelated ?: NULL,
    ]);

    return $membershipType["values"][$membershipType["id"]];
  }

  /**
   * Get count of related memberships by parent membership id.
   *
   * @param $membershipId
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  private function getRelatedMembershipsCount($membershipId) {
    return $this->callAPISuccess("Membership", "getcount", [
      'owner_membership_id' => $membershipId,
    ]);
  }

  /**
   * Test to delete related membership when type of parent membership is changed which does not have relation type associated.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testDeleteRelatedMembershipsOnParentTypeChanged(): void {

    $contactId = $this->individualCreate();
    $membershipOrganizationId = $this->organizationCreate();
    $organizationId = $this->organizationCreate();

    // Create relationship between organization and individual contact
    $this->callAPISuccess('Relationship', 'create', [
      // Employer of relationship
      'relationship_type_id' => 5,
      'contact_id_a'         => $contactId,
      'contact_id_b'         => $organizationId,
      'is_active'            => 1,
    ]);

    // Create two membership types one with relationship and one without.
    $membershipTypeWithRelationship = $this->createMembershipType($membershipOrganizationId, TRUE);
    $membershipTypeWithoutRelationship = $this->createMembershipType($membershipOrganizationId);

    // Creating membership of organisation
    $membership = $this->callAPISuccess("Membership", "create", [
      'membership_type_id' => $membershipTypeWithRelationship["id"],
      'contact_id'         => $organizationId,
      'status_id'          => $this->_membershipStatusID,
    ]);

    $membership = $membership['values'][$membership["id"]];

    // Check count of related memberships. It should be one for individual contact.
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(1, $relatedMembershipsCount, 'Related membership count should be 1.');

    // Update membership by changing it's type. New membership type is without relationship.
    $membership["membership_type_id"] = $membershipTypeWithoutRelationship["id"];
    $this->callAPISuccess("Membership", "create", $membership);

    // Check count of related memberships again. It should be zero as we changed the membership type.
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(0, $relatedMembershipsCount, 'Related membership count should be 0.');

    // Clean up: Delete membership
    $this->membershipDelete($membership["id"]);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCreate(): void {

    [$contactId, $membershipId] = $this->setupMembership();

    // Now call create() to modify an existing Membership
    $params = [
      'id' => $membershipId,
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];
    $this->callAPISuccess('Membership', 'create', $params);

    $membershipTypeId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId,
      'membership_type_id', 'contact_id',
      'Database check on updated membership record.'
    );
    $this->assertEquals($membershipTypeId, $this->_membershipTypeID, 'Verify membership type id is fetched.');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGetValues(): void {
    //        $this->markTestSkipped( 'causes mysterious exit, needs fixing!' );
    //  Calculate membership dates based on the current date
    $now = time();
    $year_from_now = $now + (365 * 24 * 60 * 60);
    $last_month = $now - (30 * 24 * 60 * 60);
    $year_from_last_month = $last_month + (365 * 24 * 60 * 60);

    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'end_date' => date('Ymd', $year_from_now),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membershipId1 = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', $last_month),
      'start_date' => date('Ymd', $last_month),
      'end_date' => date('Ymd', $year_from_last_month),
      'source' => 'Source123',
      'is_override' => 0,
      'status_id' => $this->_membershipStatusID,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membershipId2 = $this->assertDBNotNull('CRM_Member_BAO_Membership', 'source123', 'id',
      'source', 'Database check for created membership.'
    );

    $membership = ['contact_id' => $contactId];
    $membershipValues = [];
    CRM_Member_BAO_Membership::getValues($membership, $membershipValues, TRUE);

    $this->assertEquals($membershipValues[$membershipId1]['membership_id'], $membershipId1, 'Verify membership record 1 is fetched.');

    $this->assertEquals($membershipValues[$membershipId2]['membership_id'], $membershipId2, 'Verify membership record 2 is fetched.');

    $this->membershipDelete($membershipId1);
    $this->membershipDelete($membershipId2);
    $this->contactDelete($contactId);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testRetrieve(): void {
    [$contactId, $membershipId] = $this->setupMembership();
    $params = ['id' => $membershipId];
    $values = [];
    CRM_Member_BAO_Membership::retrieve($params, $values);
    $this->assertEquals($values['id'], $membershipId, 'Verify membership record is retrieved.');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testActiveMembers(): void {
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membershipId1 = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $params = ['id' => $membershipId1];
    $values1 = [];
    CRM_Member_BAO_Membership::retrieve($params, $values1);
    $membership = [$membershipId1 => $values1];

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'PaySource',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membershipId2 = $this->assertDBNotNull('CRM_Member_BAO_Membership', 'PaySource', 'id',
      'source', 'Database check for created membership.'
    );

    $params = ['id' => $membershipId2];
    $values2 = [];
    CRM_Member_BAO_Membership::retrieve($params, $values2);
    $membership[$membershipId2] = $values2;

    $activeMembers = CRM_Member_BAO_Membership::activeMembers($membership);
    $inActiveMembers = CRM_Member_BAO_Membership::activeMembers($membership, 'inactive');

    $this->assertEquals($activeMembers[$membershipId1]['id'], $membership[$membershipId1]['id'], 'Verify active membership record is retrieved.');
    $this->assertEquals($activeMembers[$membershipId2]['id'], $membership[$membershipId2]['id'], 'Verify active membership record is retrieved.');

    $this->assertCount(0, $inActiveMembers, 'Verify No inactive membership record is retrieved.');

    $this->membershipDelete($membershipId1);
    $this->membershipDelete($membershipId2);
    $this->contactDelete($contactId);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testDeleteMembership(): void {
    [$contactId, $membershipId] = $this->setupMembership();
    CRM_Member_BAO_Membership::del($membershipId);

    $this->assertDBNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for deleted membership.'
    );
    $this->assertDBNull('CRM_Price_BAO_LineItem', $membershipId, 'id',
      'entity_id', 'Database check for deleted line item.'
    );
    $this->contactDelete($contactId);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGetContactMembership(): void {
    [$contactId, $membershipId] = $this->setupMembership();
    $membership = CRM_Member_BAO_Membership::getContactMembership($contactId, $this->_membershipTypeID, FALSE);

    $this->assertEquals($membership['id'], $membershipId, 'Verify membership record is retrieved.');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Get the contribution.
   * page id from the membership record
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGetContributionPageId(): void {
    [$contactId, $membershipId] = $this->setupMembership();
    $membership[$membershipId]['renewPageId'] = CRM_Member_BAO_Membership::getContributionPageId($membershipId);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Get membership joins/renewals
   * for a specified membership
   * type.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGetMembershipStarts(): void {
    [$contactId, $membershipId] = $this->setupMembership();
    $yearStart = date('Y') . '0101';
    $currentDate = date('Ymd');
    CRM_Member_BAO_Membership::getMembershipStarts($this->_membershipTypeID, $yearStart, $currentDate);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Get a count of membership for a specified membership type,
   * optionally for a specified date.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGetMembershipCount(): void {
    [$contactId, $membershipId] = $this->setupMembership();
    $currentDate = date('Ymd');
    $test = 0;
    CRM_Member_BAO_Membership::getMembershipCount($this->_membershipTypeID, $currentDate, $test);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Checkup sort name function.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSortName(): void {
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $membership = $this->callAPISuccess('Membership', 'create', $params);

    $this->assertEquals('Anderson, Anthony', CRM_Member_BAO_Membership::sortName($membership['id']));

    $this->membershipDelete($membership['id']);
    $this->contactDelete($contactId);
  }

  /**
   * Delete related memberships.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testDeleteRelatedMemberships(): void {
    [$contactId, $membershipId] = $this->setupMembership();

    CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Renew membership with change in membership type.
   *
   * @fixme Note that this test fails when today is August 29 2019 (and maybe
   *   other years?): Verify correct end date is calculated after membership
   *   renewal Failed asserting that two strings are equal.
   *   Expected-'2021-03-01' Actual+'2021-02-28'
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testRenewMembership(): void {
    $contactId = $this->individualCreate();
    $joinDate = $startDate = date("Ymd", strtotime(date("Ymd") . " -6 month"));
    $endDate = date("Ymd", strtotime($joinDate . " +1 year -1 day"));
    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => $joinDate,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $contactId]);

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $membership['id'],
      'id',
      'membership_id',
      'Database checked on membership log record.'
    );

    // this is a test and we dont want qfKey generation / validation
    // easier to suppress it, than change core code
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;

    [$MembershipRenew] = CRM_Contribute_Form_Contribution_Confirm::unitTestAccessTolegacyProcessMembership(
      $contactId,
      $this->_membershipTypeID);
    $endDate = date("Y-m-d", strtotime($membership['end_date'] . " +1 year"));

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $MembershipRenew->id,
      'id',
      'membership_id',
      'Database checked on membership log record.'
    );
    $this->assertEquals($this->_membershipTypeID, $MembershipRenew->membership_type_id, 'Verify membership type is changed during renewal.');
    $this->assertEquals($endDate, $MembershipRenew->end_date, 'Verify correct end date is calculated after membership renewal');

    $this->membershipDelete($membership['id']);
    $this->contactDelete($contactId);
  }

  /**
   * Renew stale membership.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testStaleMembership(): void {
    $statusId = 3;
    $contactId = $this->individualCreate();
    $joinDate = $startDate = date("Ymd", strtotime(date("Ymd") . " -1 year -15 days"));
    $endDate = date('Ymd', strtotime($joinDate . " +1 year -1 day"));
    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => $joinDate,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'source' => 'Payment',
      'status_id' => $statusId,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membership = $this->callAPISuccessGetSingle('Membership', [
      'contact_id' => $contactId,
      'start_date' => $startDate,
      'join_date' => $joinDate,
      'end_date' => $endDate,
    ]);

    $this->assertEquals($membership['status_id'], $statusId, 'Verify correct status id is calculated.');
    $this->assertEquals($membership['membership_type_id'], $this->_membershipTypeID);

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $membership['id'],
      'id',
      'membership_id',
      'Database checked on membership log record.'
    );

    [$MembershipRenew] = CRM_Contribute_Form_Contribution_Confirm::unitTestAccessTolegacyProcessMembership(
      $contactId,
      $this->_membershipTypeID,
      1
    );

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $MembershipRenew->id,
      'id',
      'membership_id',
      'Database checked on membership log record.'
    );

    $this->membershipDelete($membership['id']);
    $this->contactDelete($contactId);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testUpdateAllMembershipStatusConvertExpiredOverriddenStatusToNormal(): void {
    $params = [
      'contact_id' => $this->individualCreate(),
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'end_date' => date('Ymd', strtotime('+1 year')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_override_end_date' => date('Ymd', strtotime('-1 day')),
      'status_id' => $this->_membershipStatusID,
    ];

    $createdMembershipID = $this->callAPISuccess('Membership', 'create', $params)['id'];

    civicrm_api3('Job', 'process_membership');

    $membershipAfterProcess = civicrm_api3('Membership', 'get', [
      'sequential' => 1,
      'id' => $createdMembershipID,
      'return' => ['id', 'is_override', 'status_override_end_date'],
    ])['values'][0];

    $this->assertEquals($createdMembershipID, $membershipAfterProcess['id']);
    $this->assertArrayNotHasKey('is_override', $membershipAfterProcess);
    $this->assertArrayNotHasKey('status_override_end_date', $membershipAfterProcess);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testUpdateAllMembershipStatusHandleOverriddenWithEndOverrideDateEqualTodayAsExpired(): void {
    $params = [
      'contact_id' => $this->individualCreate(),
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'end_date' => date('Ymd', strtotime('+1 year')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_override_end_date' => date('Ymd'),
      'status_id' => $this->_membershipStatusID,
    ];

    $createdMembershipID = $this->callAPISuccess('Membership', 'create', $params)['id'];

    civicrm_api3('Job', 'process_membership');

    $membershipAfterProcess = civicrm_api3('Membership', 'get', [
      'sequential' => 1,
      'id' => $createdMembershipID,
      'return' => ['id', 'is_override', 'status_override_end_date'],
    ])['values'][0];

    $this->assertEquals($createdMembershipID, $membershipAfterProcess['id']);
    $this->assertArrayNotHasKey('is_override', $membershipAfterProcess);
    $this->assertArrayNotHasKey('status_override_end_date', $membershipAfterProcess);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testUpdateAllMembershipStatusDoesNotConvertOverriddenMembershipWithoutEndOverrideDateToNormal(): void {
    $params = [
      'contact_id' => $this->individualCreate(),
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'end_date' => date('Ymd', strtotime('+1 year')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $createdMembershipID = $this->callAPISuccess('Membership', 'create', $params)['id'];

    $this->callAPISuccess('Job', 'process_membership');

    $membershipAfterProcess = civicrm_api3('Membership', 'get', [
      'sequential' => 1,
      'id' => $createdMembershipID,
      'return' => ['id', 'is_override', 'status_override_end_date'],
    ])['values'][0];

    $this->assertEquals($createdMembershipID, $membershipAfterProcess['id']);
    $this->assertEquals(1, $membershipAfterProcess['is_override']);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipPaymentForSingleContributionMultipleMembership(): void {
    $membershipTypeID1 = $this->membershipTypeCreate(['name' => 'Parent']);
    $membershipTypeID2 = $this->membershipTypeCreate(['name' => 'Child']);
    $financialTypeId = $this->getFinancialTypeId('Member Dues');
    $priceSet = $this->callAPISuccess('price_set', 'create', [
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => $financialTypeId,
      'title' => 'Family Membership',
    ]);
    $priceSetID = $priceSet['id'];
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $priceSetID,
      'label' => 'Memberships',
      'html_type' => 'Radio',
    ]);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Parent',
      'amount' => 100,
      'financial_type_id' => $financialTypeId,
      'membership_type_id' => $membershipTypeID1,
      'membership_num_terms' => 1,
    ]);
    $priceFieldValueId = [1 => $priceFieldValue['id']];
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Child',
      'amount' => 50,
      'financial_type_id' => $financialTypeId,
      'membership_type_id' => $membershipTypeID2,
      'membership_num_terms' => 1,
    ]);
    $priceFieldValueId[2] = $priceFieldValue['id'];
    $parentContactId = $this->individualCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', [
      'contact_id' => $parentContactId,
      'amount' => 150,
      'frequency_unit' => 'day',
      'frequency_interval' => 1,
      'installments' => 2,
      'start_date' => 'yesterday',
      'create_date' => 'yesterday',
      'modified_date' => 'yesterday',
      'cancel_date' => NULL,
      'end_date' => '+ 2 weeks',
      'processor_id' => '643411460836',
      'trxn_id' => 'e0d0808e26f3e661c6c18eb7c039d363',
      'invoice_id' => 'e0d0808e26f3e661c6c18eb7c039d363',
      'contribution_status_id' => 'In Progress',
      'cycle_day' => 1,
      'next_sched_contribution_date' => '+ 1 week',
      'auto_renew' => 0,
      'currency' => 'USD',
      'payment_processor_id' => $this->paymentProcessorCreate(),
      'financial_type_id' => $financialTypeId,
      'payment_instrument_id' => 'Credit Card',
    ]);

    $contribution = $this->callAPISuccess('Order', 'create', [
      'total_amount' => 150,
      'contribution_recur_id' => $contributionRecur['id'],
      'currency' => 'USD',
      'contact_id' => $parentContactId,
      'financial_type_id' => $financialTypeId,
      'contribution_status_id' => 'Pending',
      'is_recur' => TRUE,
      'api.Payment.create' => ['total_amount' => 150],
      'line_items' => [
        [
          'line_item' => [
            [
              'price_field_id' => $priceField['id'],
              'price_field_value_id' => $priceFieldValueId[1],
              'label' => 'Parent',
              'membership_type_id' => $membershipTypeID1,
              'qty' => 1,
              'unit_price' => 100,
              'line_total' => 100,
              'financial_type_id' => $financialTypeId,
              'entity_table' => 'civicrm_membership',
            ],
          ],
          'params' => [
            'contact_id' => $this->individualCreate(),
            'membership_type_id' => $membershipTypeID2,
            'contribution_recur_id' => $contributionRecur['id'],
            'join_date' => date('Ymd'),
            'start_date' => date('Ymd'),
            'end_date' => date('Ymd', strtotime('+1 year')),
            'source' => 'Payment',
          ],
        ],
        [
          'line_item' => [
            [
              'price_field_id' => $priceField['id'],
              'price_field_value_id' => $priceFieldValueId[2],
              'label' => 'Child',
              'qty' => 1,
              'unit_price' => 50,
              'line_total' => 50,
              'membership_type_id' => $membershipTypeID2,
              'financial_type_id' => $financialTypeId,
              'entity_table' => 'civicrm_membership',
            ],
          ],
          'params' => [
            'contact_id' => $this->individualCreate(),
            'membership_type_id' => $membershipTypeID2,
            'contribution_recur_id' => $contributionRecur['id'],
            'join_date' => date('Ymd'),
            'start_date' => date('Ymd'),
            'end_date' => date('Ymd', strtotime('+1 year')),
            'source' => 'Payment',
          ],
        ],
      ],
    ]);

    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $contribution['id'],
      'contribution_status_id' => 'Completed',
    ]);
    $contributions = $this->callAPISuccess('Contribution', 'get', ['sequential' => 1])['values'];
    $this->assertCount(2, $contributions);
    $this->assertEquals('Debit Card', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $contributions[1]['payment_instrument_id']));
    $this->validateAllPayments();
    $this->validateAllContributions();
  }

  /**
   * Test the buildMembershipTypeValues function.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testBuildMembershipTypeValues(): void {
    $this->restoreMembershipTypes();
    $form = new CRM_Core_Form();
    $values = CRM_Member_BAO_Membership::buildMembershipTypeValues($form);
    $this->assertEquals([
      'id' => '1',
      'minimum_fee' => '100.000000000',
      'name' => 'General',
      'is_active' => '1',
      'description' => 'Regular annual membership.',
      'financial_type_id' => '2',
      'auto_renew' => '0',
      'member_of_contact_id' => $values[1]['member_of_contact_id'],
      'relationship_type_id' => [7],
      'relationship_direction' => ['b_a'],
      'max_related' => NULL,
      'duration_unit' => 'year',
      'duration_interval' => '2',
      'domain_id' => '1',
      'period_type' => 'rolling',
      'visibility' => 'Public',
      'weight' => '1',
      'tax_rate' => 0.0,
      'minimum_fee_with_tax' => 100.0,
    ], $values[1]);
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function setupMembership(): array {
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $this->callAPISuccess('Membership', 'create', $params);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );
    return [$contactId, $membershipId];
  }

  /**
   * Test done to verify bug dev/core#1854 remains fixed.
   *
   * Under certain special circumstances, updating a membership that had related
   * memberships and a maximum related value, resulted in some related
   * memberships being deleted, even though the maximum value was not reached.
   *
   * The problem presented itself when a membership is found for the nth contact
   * related to the organization, the nth+1 contact didn't have a membership,
   * and the nth+b contact does have a membership, where b > 1.
   *
   * This test builds that scenario and checks updating the status of the
   * membership does not cause the deletion of memberships.
   *
   * https://lab.civicrm.org/dev/core/-/issues/1854
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testMembershipUpdateDoesNotDeleteRelatedMembershipsByMistake(): void {
    $membershipOrganizationId = $this->organizationCreate();
    $employerId = $this->organizationCreate();

    // Create membership type with relationship.
    $membershipTypeWithRelationship = $this->createMembershipType($membershipOrganizationId, TRUE, 2);

    // Creating membership for employer.
    $membership = $this->callAPISuccess("Membership", "create", [
      'membership_type_id' => $membershipTypeWithRelationship["id"],
      'contact_id'         => $employerId,
      'status_id'          => $this->_membershipStatusID,
    ]);
    $membership = $membership['values'][$membership["id"]];
    $this->assertEquals(0, $this->getRelatedMembershipsCount($membership["id"]), 'Related membership count should be 0.');

    // Create relationship between organization and individual contacts.
    $employees = $this->createContacts(5);
    foreach ($employees as $contactID) {
      $this->callAPISuccess('Relationship', 'create', [
        'relationship_type_id' => 5,
        'contact_id_a'         => $contactID,
        'contact_id_b'         => $employerId,
        'is_active'            => 1,
      ]);
    }
    $this->deleteRelatedMemberships($membership["id"]);
    $this->assertEquals(0, $this->getRelatedMembershipsCount($membership["id"]), 'Related membership count should be 0.');

    // Create related memberships for first and last contact.
    $relatedMembership1 = $this->createRelatedMembershipForContact($employees[0], $membership);
    $relatedMembership2 = $this->createRelatedMembershipForContact($employees[4], $membership);
    $this->assertEquals(2, $this->getRelatedMembershipsCount($membership["id"]), 'Related membership count should be 2.');

    // Reset statics.
    unset(Civi::$statics[CRM_Member_BAO_Membership::class]['related_contacts']);

    // Update membership by changing its status.
    $otherStatusID = $this->membershipStatusCreate('another status ' . random_int(1, 1000));
    $membership["status_id"] = $otherStatusID;
    $this->callAPISuccess("Membership", "create", $membership);

    // Assert nothing has changed.
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(2, $relatedMembershipsCount, "Related membership count should still be 2, but found $relatedMembershipsCount");
    $this->assertMembershipExists($relatedMembership1['id']);
    $this->assertMembershipExists($relatedMembership2['id']);
  }

  public function testRelatedMembershipWithContactReferenceCustomField() {
    $relatedContactId = $this->individualCreate();
    $customContactId = $this->individualCreate();
    $membershipOrganizationId = $this->organizationCreate();
    $organizationId = $this->organizationCreate();

    $membershipTypeWithRelationship = $this->createMembershipType($membershipOrganizationId, TRUE);
    $customGroup = $this->customGroupCreate(['extends' => 'Membership']);
    $customField = $this->customFieldCreate([
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'default_value' => '',
    ]);

    // Creating membership of organisation
    $membership = $this->callAPISuccess("Membership", "create", [
      'membership_type_id' => $membershipTypeWithRelationship["id"],
      'contact_id'         => $organizationId,
      'status_id'          => $this->_membershipStatusID,
      'custom_' . $customField['id'] => $customContactId,
    ]);

    $membership = $membership['values'][$membership["id"]];

    // Create relationship between organization and individual contact
    $relationship = $this->callAPISuccess('Relationship', 'create', [
      // Employer of relationship
      'relationship_type_id' => 5,
      'contact_id_a'         => $relatedContactId,
      'contact_id_b'         => $organizationId,
      'is_active'            => 1,
    ]);

    // Check count of related memberships. It should be one for individual contact.
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(1, $relatedMembershipsCount, 'Related membership count should be 1.');

    $relatedMembership = $this->callAPISuccess("Membership", "getsingle", [
      'owner_membership_id' => $membership["id"],
    ]);

    $this->assertEquals($customContactId, $relatedMembership['custom_' . $customField['id'] . '_id']);

    $this->callAPISuccess('Relationship', 'delete', ['id' => $relationship['id']]);
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(0, $relatedMembershipsCount, 'Related membership count should be 0.');
  }

  /**
   * Creates the given amount of contacts.
   *
   * @param int $count
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function createContacts(int $count): array {
    $contacts = [];
    for ($i = 0; $i < $count; $i++) {
      $contacts[] = $this->individualCreate();
    }

    return $contacts;
  }

  /**
   * Deletes related memberships for given parent membership ID.
   *
   * @param int $parentMembershipID
   *
   * @throws \CRM_Core_Exception
   */
  private function deleteRelatedMemberships(int $parentMembershipID): void {
    $this->callAPISuccess('Membership', 'get', [
      'owner_membership_id' => $parentMembershipID,
      'api.Membership.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   * Creates a related membership for the given contact ID.
   *
   * @param int $contactID
   * @param array $parentMembership
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function createRelatedMembershipForContact(int $contactID, array $parentMembership): array {
    return $this->callAPISuccess('Membership', 'create', [
      'membership_type_id' => $parentMembership['membership_type_id'],
      'contact_id'         => $contactID,
      'status_id'          => $this->_membershipStatusID,
      'owner_membership_id' => $parentMembership['id'],
    ]);
  }

  /**
   * Checks the given membership ID can be found.
   *
   * @param int $membershipID
   *
   * @throws \CRM_Core_Exception
   */
  private function assertMembershipExists(int $membershipID): void {
    $membership = $this->callAPISuccess("Membership", "get", [
      'id' => $membershipID,
    ]);
    $this->assertEquals(1, $membership['count']);
    $this->assertEquals($membershipID, $membership['id']);
  }

}
