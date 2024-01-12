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

use Civi\Api4\MembershipBlock;
use Civi\Api4\MembershipType;
use Civi\Test\ContributionPageTestTrait;

/**
 * Class CRM_Member_BAO_MembershipTypeTest
 * @group headless
 */
class CRM_Member_BAO_MembershipTypeTest extends CiviUnitTestCase {

  use ContributionPageTestTrait;

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();

    //create relationship
    $params = [
      'name_a_b' => 'Relation 1',
      'name_b_a' => 'Relation 2',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
    $this->ids['RelationshipType'][0] = $this->relationshipTypeCreate($params);
    $this->ids['Contact']['organization'] = $this->organizationCreate();
    $this->ids['Contact']['individual'] = $this->individualCreate();
    $this->ids['MembershipStatus']['test'] = $this->membershipStatusCreate('test status');
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->relationshipTypeDelete($this->ids['RelationshipType'][0]);
    $this->membershipStatusDelete($this->ids['MembershipStatus']['test']);
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test add.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAdd(): void {
    $params = [
      'name' => 'test type',
      'domain_id' => 1,
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
    ];

    MembershipType::create()->setValues($params)->execute();

    $membership = $this->assertDBNotNull('CRM_Member_BAO_MembershipType', $this->ids['Contact']['organization'],
      'name', 'member_of_contact_id',
      'Database check on updated membership record.'
    );
    $this->assertEquals('test type', $membership, 'Verify membership type name.');
  }

  /**
   * Test retrieve().
   *
   * @throws \CRM_Core_Exception
   */
  public function testRetrieve(): void {
    $params = [
      'name' => 'General',
      'description' => NULL,
      'domain_id' => 1,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
    ];
    MembershipType::create()->setValues($params)->execute();

    $params = ['name' => 'General'];
    $default = [];
    $result = CRM_Member_BAO_MembershipType::retrieve($params, $default);
    $this->assertEquals('General', $result->name, 'Verify membership type name.');
  }

  /**
   * Test delete.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDelete(): void {
    $membershipTypeID = $this->createGeneralMembershipType();
    MembershipType::delete()->addWhere('id', '=', $membershipTypeID)->execute();
    $this->assertCount(0, MembershipType::get()->addWhere('id', '=', $membershipTypeID)->execute());
  }

  /**
   * Test convertDayFormat.
   *
   * @throws \CRM_Core_Exception
   */
  public function testConvertDayFormat(): void {
    $params = [
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'fixed_period_start_day' => 1213,
      'fixed_period_rollover_day' => 1214,
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    $membershipTypeID = MembershipType::create()->setValues($params)->execute()->first()['id'];
    $membershipType[$membershipTypeID] = $params;

    CRM_Member_BAO_MembershipType::convertDayFormat($membershipType);
    $this->assertEquals('Dec 14', $membershipType[$membershipTypeID]['fixed_period_rollover_day'], 'Verify memberFixed Period Rollover Day.');
  }

  /**
   * Test getMembershipTypes.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetMembershipTypes(): void {
    $params = [
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    $membershipTypeID = MembershipType::create()->setValues($params)->execute()->first()['id'];
    $result = CRM_Member_BAO_MembershipType::getMembershipTypes();
    $this->assertEquals('General', $result[$membershipTypeID], 'Verify membership types.');
  }

  /**
   * check function getMembershipTypeDetails( )
   *
   */
  public function testGetMembershipTypeDetails(): void {
    $membershipTypeID = $this->createGeneralMembershipType();
    $result = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membershipTypeID);

    $this->assertEquals('General', $result['name'], 'Verify membership type details.');
    $this->assertEquals('year', $result['duration_unit'], 'Verify membership types details.');
  }

  /**
   * Test getDatesForMembershipType.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetDatesForMembershipType(): void {
    $params = [
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    $membershipTypeID = MembershipType::create()->setValues($params)->execute()->first()['id'];

    $membershipDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membershipTypeID);
    $this->assertEquals($membershipDates['start_date'], date('Ymd'), 'Verify membership types details.');
  }

  /**
   * Test getRenewalDatesForMembershipType.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetRenewalDatesForMembershipType(): void {
    $params = [
      'name' => 'General',
      'domain_id' => 1,
      'description' => NULL,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    $membershipTypeID = MembershipType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'contact_id' => $this->ids['Contact']['individual'],
      'membership_type_id' => $membershipTypeID,
      'join_date' => '20060121000000',
      'start_date' => '20060121000000',
      'end_date' => '20070120000000',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->ids['MembershipStatus']['test'],
    ];

    $membership = $this->callAPISuccess('Membership', 'create', $params);

    $membershipRenewDates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership['id']);

    $this->assertEquals('20060121', $membershipRenewDates['start_date'], 'Verify membership renewal start date.');
    $this->assertEquals('20080120', $membershipRenewDates['end_date'], 'Verify membership renewal end date.');

    $this->membershipDelete($membership['id']);
    $this->membershipTypeDelete(['id' => $membershipTypeID]);
  }

  /**
   * Test getMembershipTypesByOrg.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetMembershipTypesByOrg(): void {
    $params = [
      'name' => 'General',
      'description' => NULL,
      'domain_id' => 1,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    MembershipType::create()->setValues($params)->execute();

    $result = $this->callAPISuccess('MembershipType', 'get', [
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'options' => [
        'limit' => 0,
      ],
    ])['values'];
    $this->assertEquals(FALSE, empty($result), 'Verify membership types for organization.');

    $result = $this->callAPISuccess('MembershipType', 'get', [
      'member_of_contact_id' => 501,
      'options' => [
        'limit' => 0,
      ],
    ])['values'];
    $this->assertEquals(TRUE, empty($result), 'Verify membership types for organization.');
  }

  /**
   * Create a general membership type.
   *
   * @return int
   */
  private function createGeneralMembershipType(): int {
    $params = [
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_interval' => 1,
      'financial_type_id:name' => 'Donation',
      'relationship_type_id' => $this->ids['RelationshipType'][0],
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    try {
      return MembershipType::create()
        ->setValues($params)
        ->execute()
        ->first()['id'];
    }
    catch (CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
      return 0;
    }
  }

  /**
   * Test that when renewal settings are modified membership blocks are updated.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRenewModification(): void {
    $this->contributionPageQuickConfigCreate();
    $autoRenew = MembershipBlock::get(FALSE)->execute()->first()['membership_types'];
    $this->assertEquals(1, reset($autoRenew));

    // Disable auto-renew on membership type - block should update to 0 (no auto-renew).
    MembershipType::update()
      ->addWhere('name', '=', 'General')
      ->setValues(['auto_renew' => 0])->execute();
    $autoRenew = MembershipBlock::get(FALSE)->execute()->first()['membership_types'];
    $this->assertEquals(0, reset($autoRenew));

    // Force auto-renew on membership type - block should update to 2 (force auto-renew).
    MembershipType::update()->addWhere('name', '=', 'General')
      ->setValues(['auto_renew' => 2])->execute();
    $autoRenew = MembershipBlock::get(FALSE)->execute()->first()['membership_types'];
    $this->assertEquals(2, reset($autoRenew));

    // Make auto-renew optional on membership type - block should stay at 2 (force autorenew).
    // If the membership type is optional if can be made more or less restrictive at
    // the block level.
    MembershipType::update()->addWhere('name', '=', 'General')
      ->setValues(['auto_renew' => 1])->execute();
    $autoRenew = MembershipBlock::get(FALSE)->execute()->first()['membership_types'];
    $this->assertEquals(2, reset($autoRenew));
  }

}
