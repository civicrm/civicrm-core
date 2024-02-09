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

use Civi\Api4\Membership;

/**
 * Class api_v3_JobProcessMembershipTest
 * @group headless
 */
class api_v3_JobProcessMembershipTest extends CiviUnitTestCase {

  /**
   * Caches some reference dates
   *
   * @var string
   */
  private $yesterday;

  /**
   * @var string
   */
  private $tomorrow;

  public function setUp(): void {
    parent::setUp();
    $this->yesterday = date('Y-m-d', time() - 60 * 60 * 24);
    $this->tomorrow = date('Y-m-d', time() + 60 * 60 * 24);
    $this->membershipTypeCreate(['name' => 'General'], 'General');
    $this->membershipTypeCreate(['name' => 'Old'], 'Old');
  }

  public function tearDown(): void {
    $this->restoreMembershipTypes();
    parent::tearDown();
  }

  /**
   * Creates a membership that is expired but that should be ignored
   * by the process as it is in `deceased` status.
   */
  public function createDeceasedMembershipThatShouldBeExpired(): int {
    $contactId = $this->individualCreate(['is_deceased' => FALSE]);
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->yesterday,
      'end_date' => $this->yesterday,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => 'Deceased',
    ]);

    return $membershipId;
  }

  /**
   * Creates a membership that is grace and should be updated to expired.
   */
  public function createGraceMembershipThatShouldBeExpired(): int {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->yesterday,
      'end_date' => $this->yesterday,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => 'Deceased',
    ]);

    return $membershipId;
  }

  /**
   * Creates a test membership in `grace` status that should be
   * in `current` status but that won't be updated unless the process
   * is explicitly told not to exclude tests.
   */
  public function createTestMembershipThatShouldBeCurrent(): int {
    $contactId = $this->individualCreate();
    $membershipID = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->yesterday,
      'end_date' => $this->tomorrow,
      'is_test' => TRUE,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipID,
      'status_id' => 'Grace',
    ]);

    return $membershipID;
  }

  /**
   * Creates a grace membership that should be in `current` status
   * that should be fixed even when the process is executed with
   * the default parameters.
   */
  public function createGraceMembershipThatShouldBeCurrent(): int {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->yesterday,
      'end_date' => $this->tomorrow,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => 'Grace',
    ]);

    return $membershipId;
  }

  /**
   * Creates a pending membership that should be in `current` status
   * that won't be fixed unless the process is executed
   * with an explicit `exclude_membership_status_ids` list that
   * doesn't include it.
   */
  public function createPendingMembershipThatShouldBeCurrent(): int {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->yesterday,
      'end_date' => $this->tomorrow,
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => 'Pending',
    ]);

    return $membershipId;
  }

  /**
   * Creates a membership that uses an inactive membership type
   * and should be in `current` status.
   */
  public function createOldMembershipThatShouldBeCurrent(): int {
    $contactId = $this->individualCreate();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId,
      'start_date' => $this->yesterday,
      'end_date' => $this->tomorrow,
      'membership_type_id' => 'Old',
    ]);

    $this->callAPISuccess('Membership', 'create', [
      'id' => $membershipId,
      'status_id' => 'Grace',
    ]);

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->ids['MembershipType']['Old'],
      'is_active' => FALSE,
    ]);

    return $membershipId;
  }

  /**
   * Returns the name of the status of a membership given its id.
   *
   * @param int $membershipID
   *
   * @return string
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getMembershipStatus(int $membershipID): string {
    return (string) Membership::get()->addWhere('id', '=', $membershipID)->addSelect('status_id:name')->execute()->first()['status_id:name'];
  }

  /**
   * Test that by default test memberships are excluded.
   */
  public function testByDefaultTestsAreExcluded(): void {
    $testId = $this->createTestMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', []);

    $this->assertEquals('Grace', $this->getMembershipStatus($testId));
  }

  /**
   * Test that by default memberships of inactive types are excluded.
   */
  public function testByDefaultInactiveAreExcluded(): void {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();
    $this->callAPISuccess('job', 'process_membership', []);
    $this->assertEquals('Grace', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that by default grace memberships are considered.
   */
  public function testByDefaultGraceIsConsidered(): void {
    $graceID = $this->createGraceMembershipThatShouldBeCurrent();
    $this->callAPISuccess('job', 'process_membership', []);
    $this->assertEquals('Current', $this->getMembershipStatus($graceID));
  }

  /**
   * Test that by default pending memberships are excluded.
   *
   * The pending status is still excluded as it's in the
   * exclude_membership_status_ids list by default.
   */
  public function testByDefaultPendingIsExcluded(): void {
    $pendingID = $this->createPendingMembershipThatShouldBeCurrent();
    $this->callAPISuccess('job', 'process_membership', []);
    $this->assertEquals('Pending', $this->getMembershipStatus($pendingID));
  }

  /**
   * Test that by default memberships of type deceased are excluded.
   */
  public function testByDefaultDeceasedIsExcluded(): void {
    $deceasedID = $this->createDeceasedMembershipThatShouldBeExpired();
    $this->callAPISuccess('job', 'process_membership', []);
    $this->assertEquals('Deceased', $this->getMembershipStatus($deceasedID));
  }

  /**
   * Test that when including test memberships,
   * pending memberships are excluded.
   *
   * The pending status is still excluded as it's in the
   * exclude_membership_status_ids list by default.
   */
  public function testIncludingTestMembershipsExcludesPending(): void {
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
  public function testIncludingTestMembershipsConsidersGrace(): void {
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
  public function testIncludingTestMembershipsIgnoresInactive(): void {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_test_memberships' => FALSE,
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that when including test memberships,
   * actually includes test memberships.
   */
  public function testIncludingTestMembershipsActuallyIncludesThem(): void {
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
  public function testIncludingTestMembershipsStillIgnoresDeceased(): void {
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
  public function testIncludingInactiveMembershipTypesStillExcludesPending(): void {
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
  public function testIncludingInactiveMembershipTypesConsidersGrace(): void {
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
  public function testIncludingInactiveMembershipTypesConsidersInactive(): void {
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
  public function testIncludingInactiveMembershipTypesStillIgnoresTests(): void {
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
  public function testMembershipTypeDeceasedIsExcluded(): void {
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
  public function testSpecifyingTheStatusIdsToExcludeStillExcludesDeceased(): void {
    $deceasedId = $this->createDeceasedMembershipThatShouldBeExpired();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled'),
      ],
    ]);

    $this->assertEquals('Deceased', $this->getMembershipStatus($deceasedId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * test memberships are still ignored.
   */
  public function testSpecifyingTheStatusIdsToExcludeStillExcludesTests(): void {
    $testId = $this->createTestMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled'),
      ],
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($testId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * memberships of disabled membership types are still ignored.
   */
  public function testSpecifyingTheStatusIdsToExcludeStillExcludesInactive(): void {
    $oldId = $this->createOldMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled'),
      ],
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($oldId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * grace memberships are considered by default.
   */
  public function testSpecifyingTheStatusIdsToExcludeGraceIsIncludedByDefault(): void {
    $graceId = $this->createGraceMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled'),
      ],
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($graceId));
  }

  /**
   * Test that when explicitly setting the status ids to exclude,
   * if the specified list doesn't include pending, then pending
   * memberships are considered.
   */
  public function testSpecifyingTheStatusIdsToExcludePendingIsExcludedByDefault(): void {
    $pendingId = $this->createPendingMembershipThatShouldBeCurrent();

    $this->callAPISuccess('job', 'process_membership', [
      'exclude_membership_status_ids' => [
        CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled'),
      ],
    ]);

    $this->assertEquals('Current', $this->getMembershipStatus($pendingId));
  }

  /**
   * Test that we can explicitly exclude multiple settings by providing a list
   * of comma separated values (for the Scheduled Job config or command line)
   */
  public function testSpecifyingMultipleCommaSeparatedStatusIDs(): void {
    $graceId = $this->createGraceMembershipThatShouldBeExpired();

    $exclude = [];
    $exclude[] = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Grace');
    $exclude[] = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending');

    $this->callAPISuccess('job', 'process_membership', [
      // This is on purpose to make sure we test the comma-separated syntax
      'exclude_membership_status_ids' => implode(',', $exclude),
    ]);

    $this->assertEquals('Grace', $this->getMembershipStatus($graceId));
  }

}
