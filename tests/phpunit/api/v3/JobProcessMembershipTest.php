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
 * Specific tests for the `process_membership` job.
 *
 * @link https://github.com/civicrm/civicrm-core/pull/16298
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class api_v3_JobProcessMembershipTest
 * @group headless
 */
class api_v3_JobProcessMembershipTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public $DBResetRequired = FALSE;
  public $_entity = 'Job';

  /**
   * Caches membership status names in a key, value array
   *
   * @var array
   */
  public $_statuses;

  /**
   * Caches membership types in a key, value array
   *
   * @var array
   */
  public $_types;

  /**
   * Caches some reference dates
   *
   * @var string
   */
  public $_yesterday;

  /**
   * @var string
   */
  public $_today;

  /**
   * @var string
   */
  public $_tomorrow;

  public function setUp(): void {
    parent::setUp();
    $this->loadReferenceDates();
    $this->loadMembershipStatuses();
    $this->loadMembershipTypes();
  }

  public function loadMembershipStatuses() {
    $statuses = civicrm_api3('MembershipStatus', 'get', ['options' => ['limit' => 0]])['values'];
    $this->_statuses = array_map(
      function($status) {
        return $status['name'];
      },
      $statuses
    );
  }

  public function loadMembershipTypes() {
    $this->membershipTypeCreate(['name' => 'General']);
    $this->membershipTypeCreate(['name' => 'Old']);
    $types = civicrm_api3('MembershipType', 'get', ['options' => ['limit' => 0]])['values'];
    $this->_types = array_map(
      function($type) {
        return $type['name'];
      },
      $types
    );
  }

  public function loadReferenceDates() {
    $this->_yesterday = date('Y-m-d', time() - 60 * 60 * 24);
    $this->_today = date('Y-m-d');
    $this->_tomorrow = date('Y-m-d', time() + 60 * 60 * 24);
  }

  public function tearDown(): void {
    parent::tearDown();

    // For each case, the `old` membershipt type must start as
    // active, so we can assign it (we'll disabled it after
    // assigning it)
    $this->callAPISuccess('MembershipType', 'create', [
      'id' => array_search('Old', $this->_types),
      'is_active' => TRUE,
    ]);
  }

  /**
   * Creates a membership that is expired but that should be ignored
   * by the process as it is in `deceased` status.
   */
  public function createDeceasedMembershipThatShouldBeExpired() {
    $contactId = $this->individualCreate(['is_deceased' => FALSE]);
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->_yesterday,
      'end_date' => $this->_yesterday,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => array_search('Deceased', $this->_statuses),
    ]);

    return $membershipId;
  }

  /**
   * Creates a test membership in `grace` status that should be
   * in `current` status but that won't be updated unless the process
   * is explicitly told not to exclude tests.
   */
  public function createTestMembershipThatShouldBeCurrent() {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->_yesterday,
      'end_date' => $this->_tomorrow,
      'is_test' => TRUE,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => array_search('Grace', $this->_statuses),
    ]);

    return $membershipId;
  }

  /**
   * Creates a grace membership that should be in `current` status
   * that should be fixed even when the process is executed with
   * the default parameters.
   */
  public function createGraceMembershipThatShouldBeCurrent() {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->_yesterday,
      'end_date' => $this->_tomorrow,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => array_search('Grace', $this->_statuses),
    ]);

    return $membershipId;
  }

  /**
   * Creates a pending membership that should be in `current` status
   * that won't be fixed unless the process is executed
   * with an explicit `exclude_membership_status_ids` list that
   * doesn't include it.
   */
  public function createPendingMembershipThatShouldBeCurrent() {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->_yesterday,
      'end_date' => $this->_tomorrow,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => array_search('Pending', $this->_statuses),
    ]);

    return $membershipId;
  }

  /**
   * Creates a membership that uses an inactive membership type
   * and should be in `current` status.
   */
  public function createOldMembershipThatShouldBeCurrent() {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->_yesterday,
      'end_date' => $this->_tomorrow,
      'membership_type_id' => array_search('Old', $this->_types),
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => array_search('Grace', $this->_statuses),
    ]);

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => array_search('Old', $this->_types),
      'is_active' => FALSE,
    ]);

    return $membershipId;
  }

  /**
   * Returns the name of the status of a membership given its id.
   */
  public function getMembershipStatus($membershipId) {
    $membership = $this->callAPISuccess('Membership', 'getsingle', ['id' => $membershipId]);
    $statusId = $membership['status_id'];
    return $this->_statuses[$statusId];
  }

  /**
   * Test that by default test memberships are excluded.
   */
  public function testByDefaultTestsAreExcluded() {
    $testId = $this->createTestMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', []);

    $this->assertEquals('Grace', $this->getMembershipStatus($testId));
  }

  /**
   * Test that by default memberships of inactive types are excluded.
   */
  public function testByDefaultInactiveAreExcluded() {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', []);

    $this->assertEquals('Grace', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that by default grace memberships are considered.
   */
  public function testByDefaultGraceIsConsidered() {
    $graceId = $this->createGraceMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', []);

    $this->assertEquals('Current', $this->getMembershipStatus($graceId));
  }

  /**
   * Test that by default pending memberships are excluded.
   *
   * The pending status is still excluded as it's in the
   * exclude_membership_status_ids list by default.
   */
  public function testByDefaultPendingIsExcluded() {
    $pendingId = $this->createPendingMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', []);

    $this->assertEquals('Pending', $this->getMembershipStatus($pendingId));
  }

  /**
   * Test that by default memberships of type deceased are excluded.
   */
  public function testByDefaultDeceasedIsExcluded() {
    $deceasedId = $this->createDeceasedMembershipThatShouldBeExpired();

    $this->callAPISuccess('job', 'process_membership', []);

    $this->assertEquals('Deceased', $this->getMembershipStatus($deceasedId));
  }

  /**
   * Test that when including test memberships,
   * pending memberships are excluded.
   *
   * The pending status is still excluded as it's in the
   * exclude_membership_status_ids list by default.
   */
  public function testIncludingTestMembershipsExcludesPending() {
    $pendingId = $this->createPendingMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_test_memberships' => FALSE,
    ]);

    $this->assertEquals('Pending', $this->getMembershipStatus($pendingId));
  }

  /**
   * Test that when including test memberships,
   * grace memberships are considered.
   */
  public function testIncludingTestMembershipsConsidersGrace() {
    $graceId = $this->createGraceMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_test_memberships' => FALSE,
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($graceId));
  }

  /**
   * Test that when including test memberships,
   * memberships of inactive types are still ignored.
   */
  public function testIncludingTestMembershipsIgnoresInactive() {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_test_memberships' => FALSE,
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that when including test memberships,
   * acually includes test memberships.
   */
  public function testIncludingTestMembershipsActuallyIncludesThem() {
    $testId = $this->createTestMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_test_memberships' => FALSE,
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($testId));
  }

  /**
   * Test that when including test memberships,
   * memberships of type deceased are still ignored.
   */
  public function testIncludingTestMembershipsStillIgnoresDeceased() {
    $deceasedId = $this->createDeceasedMembershipThatShouldBeExpired();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_test_memberships' => FALSE,
    ]);

    $this->assertEquals('Deceased', $this->getMembershipStatus($deceasedId));
  }

  /**
   * Test that when including inactive membership types,
   * pending memberships are considered.
   *
   * The pending status is still excluded as it's in the
   * exclude_membership_status_ids list by default.
   */
  public function testIncludingInactiveMembershipTypesStillExcludesPending() {
    $pendingId = $this->createPendingMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'only_active_membership_types' => FALSE,
    ]);

    $this->assertEquals('Pending', $this->getMembershipStatus($pendingId));
  }

  /**
   * Test that when including inactive membership types,
   * grace memberships are considered.
   */
  public function testIncludingInactiveMembershipTypesConsidersGrace() {
    $graceId = $this->createGraceMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'only_active_membership_types' => FALSE,
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($graceId));
  }

  /**
   * Test that when including inactive membership types,
   * memberships of disabled membership types are considered.
   */
  public function testIncludingInactiveMembershipTypesConsidersInactive() {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'only_active_membership_types' => FALSE,
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that when including inactive membership types,
   * test memberships are still ignored.
   */
  public function testIncludingInactiveMembershipTypesStillIgnoresTests() {
    $testId = $this->createTestMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'only_active_membership_types' => FALSE,
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($testId));
  }

  /**
   * Test that when including inactive membership types,
   * memberships of type deceased are still ignored.
   */
  public function testMembershipTypeDeceasedIsExcluded() {
    $deceasedId = $this->createDeceasedMembershipThatShouldBeExpired();

    $this->callAPISuccess('job', 'process_membership', [
      'only_active_membership_types' => FALSE,
    ]);

    $this->assertEquals('Deceased', $this->getMembershipStatus($deceasedId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * memberships in deceased status are still ignored.
   */
  public function testSpecifyingTheStatusIdsToExcludeStillExcludesDeceased() {
    $deceasedId = $this->createDeceasedMembershipThatShouldBeExpired();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        array_search('Cancelled', $this->_statuses),
      ],
    ]);

    $this->assertEquals('Deceased', $this->getMembershipStatus($deceasedId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * test memberships are still ignored.
   */
  public function testSpecifyingTheStatusIdsToExcludeStillExcludesTests() {
    $testId = $this->createTestMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        array_search('Cancelled', $this->_statuses),
      ],
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($testId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * memberships of disabled membership types are still ignored.
   */
  public function testSpecifyingTheStatusIdsToExcludeStillExcludesInactive() {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        array_search('Cancelled', $this->_statuses),
      ],
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * grace memberships are considered by default.
   */
  public function testSpecifyingTheStatusIdsToExcludeGraceIsIncludedByDefault() {
    $graceId = $this->createGraceMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        array_search('Cancelled', $this->_statuses),
      ],
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($graceId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * if the specified list doesn't include pending, then pending
   * memberships are considered.
   */
  public function testSpecifyingTheStatusIdsToExcludePendingIsExcludedByDefault() {
    $pendingId = $this->createPendingMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        array_search('Cancelled', $this->_statuses),
      ],
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($pendingId));
  }

}
