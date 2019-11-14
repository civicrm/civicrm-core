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
 * Class api_v3_MembershipTypeTest
 * @group headless
 */
class api_v3_MembershipTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_apiversion;
  protected $_entity = 'MembershipType';

  /**
   * Set up for tests.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_apiversion = 3;
    $this->_contactID = $this->organizationCreate();
  }

  /**
   * Get the membership without providing an ID.
   *
   * This should return an empty array but not an error.
   */
  public function testGetWithoutId() {
    $params = [
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'financial_type_id' => 1,
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'visibility' => 'public',
    ];

    $membershipType = $this->callAPISuccess('membership_type', 'get', $params);
    $this->assertEquals($membershipType['count'], 0);
  }

  /**
   * Test get works.
   */
  public function testGet() {
    $id = $this->membershipTypeCreate(['member_of_contact_id' => $this->_contactID]);

    $params = [
      'id' => $id,
    ];
    $membershipType = $this->callAPIAndDocument('membership_type', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($membershipType['values'][$id]['name'], 'General');
    $this->assertEquals($membershipType['values'][$id]['member_of_contact_id'], $this->_contactID);
    $this->assertEquals($membershipType['values'][$id]['financial_type_id'], $this->getFinancialTypeId('Member Dues'));
    $this->assertEquals($membershipType['values'][$id]['duration_unit'], 'year');
    $this->assertEquals($membershipType['values'][$id]['duration_interval'], '1');
    $this->assertEquals($membershipType['values'][$id]['period_type'], 'rolling');
    $this->membershipTypeDelete($params);
  }

  /**
   * Test create with missing mandatory field.
   */
  public function testCreateWithoutMemberOfContactId() {
    $params = [
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    ];

    $this->callAPIFailure('membership_type', 'create', $params, 'Mandatory key(s) missing from params array: member_of_contact_id');
  }

  /**
   * Test successful create.
   */
  public function testCreate() {
    $params = [
      'name' => '40+ Membership',
      'description' => 'people above 40 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    ];

    $membershipType = $this->callAPIAndDocument('membership_type', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($membershipType['values']);
    $this->membershipTypeDelete(['id' => $membershipType['id']]);
  }

  /**
   * Domain ID can be intuited..
   * DomainID is now optional on API, check that it gets set correctly and that the domain_id is not overwritten when not specified in create.
   */
  public function testCreateWithoutDomainId() {
    $params = [
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'minimum_fee' => '1200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    ];

    $membershipType = $this->callAPISuccess('membership_type', 'create', $params);
    $domainID = $this->callAPISuccessGetValue('MembershipType', ['return' => 'domain_id', 'id' => $membershipType['id']]);
    $this->assertEquals(CRM_Core_Config::domainID(), $domainID);

    $this->callAPISuccess('membership_type', 'create', ['domain_id' => 2, 'id' => $membershipType['id']]);
    $domainID = $this->callAPISuccessGetValue('MembershipType', ['return' => 'domain_id', 'id' => $membershipType['id']]);
    $this->assertEquals(2, $domainID);

    $this->callAPISuccess('membership_type', 'create', ['id' => $membershipType['id'], 'description' => 'Cool member']);
    $domainID = $this->callAPISuccessGetValue('MembershipType', ['return' => 'domain_id', 'id' => $membershipType['id']]);
    $this->assertEquals(2, $domainID);

  }

  /**
   *  CRM-20010 Tests period_type is required for MemberType create
   */
  public function testMemberTypePeriodiTypeRequired() {
    $this->callAPIFailure('MembershipType', 'create', [
      'domain_id' => "Default Domain Name",
      'member_of_contact_id' => 1,
      'financial_type_id' => "Member Dues",
      'duration_unit' => "month",
      'duration_interval' => 1,
      'name' => "Standard Member",
      'minimum_fee' => 100,
    ]);
  }

  /**
   * Test update.
   */
  public function testUpdate() {
    $id = $this->membershipTypeCreate(['member_of_contact_id' => $this->_contactID, 'financial_type_id' => 2]);
    $newMemberOrgParams = [
      'organization_name' => 'New membership organisation',
      'contact_type' => 'Organization',
      'visibility' => 1,
    ];

    $params = [
      'id' => $id,
      'name' => 'Updated General',
      'member_of_contact_id' => $this->organizationCreate($newMemberOrgParams),
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'fixed',
      'domain_id' => 1,
    ];

    $this->callAPISuccess('membership_type', 'update', $params);

    $this->getAndCheck($params, $id, $this->_entity);
  }

  /**
   * Test successful delete.
   */
  public function testDelete() {
    $membershipTypeID = $this->membershipTypeCreate(['member_of_contact_id' => $this->organizationCreate()]);
    $params = [
      'id' => $membershipTypeID,
    ];

    $this->callAPIAndDocument('membership_type', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Delete test that could do with a decent comment block.
   *
   * I can't skim this & understand it so if anyone does explain it here.
   */
  public function testDeleteRelationshipTypesUsedByMembershipType() {
    $rel1 = $this->relationshipTypeCreate([
      'name_a_b' => 'abcde',
      'name_b_a' => 'abcde',
    ]);
    $rel2 = $this->relationshipTypeCreate([
      'name_a_b' => 'fghij',
      'name_b_a' => 'fghij',
    ]);
    $rel3 = $this->relationshipTypeCreate([
      'name_a_b' => 'lkmno',
      'name_b_a' => 'lkmno',
    ]);
    $id = $this->membershipTypeCreate([
      'member_of_contact_id' => $this->_contactID,
      'relationship_type_id' => [$rel1, $rel2, $rel3],
      'relationship_direction' => ['a_b', 'a_b', 'b_a'],
    ]);

    $this->callAPISuccess('RelationshipType', 'delete', ['id' => $rel2]);
    $newValues = $this->callAPISuccess('MembershipType', 'getsingle', ['id' => $id]);
    $this->assertEquals([$rel1, $rel3], $newValues['relationship_type_id']);
    $this->assertEquals(['a_b', 'b_a'], $newValues['relationship_direction']);

    $this->callAPISuccess('RelationshipType', 'delete', ['id' => $rel1]);
    $newValues = $this->callAPISuccess('MembershipType', 'getsingle', ['id' => $id]);
    $this->assertEquals([$rel3], $newValues['relationship_type_id']);
    $this->assertEquals(['b_a'], $newValues['relationship_direction']);

    $this->callAPISuccess('RelationshipType', 'delete', ['id' => $rel3]);
    $newValues = $this->callAPISuccess('MembershipType', 'getsingle', ['id' => $id]);
    $this->assertTrue(empty($newValues['relationship_type_id']));
    $this->assertTrue(empty($newValues['relationship_direction']));
  }

  /**
   * Test that membership type getlist returns an array of enabled membership types.
   */
  public function testMembershipTypeGetList() {
    $this->membershipTypeCreate();
    $this->membershipTypeCreate(['name' => 'cheap-skates']);
    $this->membershipTypeCreate(['name' => 'disabled cheap-skates', 'is_active' => 0]);
    $result = $this->callAPISuccess('MembershipType', 'getlist', []);
    $this->assertEquals(2, $result['count']);
    $this->assertEquals('cheap-skates', $result['values'][0]['label']);
    $this->assertEquals('General', $result['values'][1]['label']);
  }

  /**
   * Test priceField values are correctly created for membership type
   * selected in contribution pages.
   */
  public function testEnableMembershipTypeOnContributionPage() {
    $memType = [];
    $memType[1] = $this->membershipTypeCreate(['member_of_contact_id' => $this->_contactID, 'minimum_fee' => 100]);
    $priceSet = $this->callAPISuccess('price_set', 'create', [
      'title' => "test priceset",
      'name' => "test_priceset",
      'extends' => "CiviMember",
      'is_quick_config' => 1,
      'financial_type_id' => "Member Dues",
    ]);
    $priceSet = $priceSet['id'];
    $field = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $priceSet,
      'name' => 'membership_amount',
      'label' => 'Membership Amount',
      'html_type' => 'Radio',
    ]);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'name' => 'membership_amount',
      'label' => 'Membership Amount',
      'amount' => 100,
      'financial_type_id' => 'Donation',
      'format.only_id' => TRUE,
      'membership_type_id' => $memType[1],
      'price_field_id' => $field['id'],
    ]);

    $memType[2] = $this->membershipTypeCreate(['member_of_contact_id' => $this->_contactID, 'minimum_fee' => 200]);
    $fieldParams = [
      'id' => $field['id'],
      'label' => 'Membership Amount',
      'html_type' => 'Radio',
    ];
    foreach ($memType as $rowCount => $type) {
      $membetype = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($type);
      $fieldParams['option_id'] = [1 => $priceFieldValue['id']];
      $fieldParams['option_label'][$rowCount] = CRM_Utils_Array::value('name', $membetype);
      $fieldParams['option_amount'][$rowCount] = CRM_Utils_Array::value('minimum_fee', $membetype, 0);
      $fieldParams['option_weight'][$rowCount] = CRM_Utils_Array::value('weight', $membetype);
      $fieldParams['option_description'][$rowCount] = CRM_Utils_Array::value('description', $membetype);
      $fieldParams['option_financial_type_id'][$rowCount] = CRM_Utils_Array::value('financial_type_id', $membetype);
      $fieldParams['membership_type_id'][$rowCount] = $type;
    }
    $priceField = CRM_Price_BAO_PriceField::create($fieldParams);
    $this->assertEquals($priceField->id, $fieldParams['id']);

    //Update membership type name and visibility
    $updateParams = [
      'id' => $memType[1],
      'name' => 'General - Edited',
      'visibility' => 'Admin',
      'financial_type_id' => 1,
      'minimum_fee' => 300,
      'description' => 'Test edit description',
    ];
    $this->callAPISuccess('membership_type', 'create', $updateParams);
    $priceFieldValue = $this->callAPISuccess('PriceFieldValue', 'get', [
      'sequential' => 1,
      'membership_type_id' => $memType[1],
    ]);
    //Verify if membership type updates are copied to pricefield value.
    foreach ($priceFieldValue['values'] as $key => $value) {
      $setId = $this->callAPISuccessGetValue('PriceField', ['return' => "price_set_id", 'id' => $value['price_field_id']]);
      if ($setId == $priceSet) {
        $this->assertEquals($value['label'], $updateParams['name']);
        $this->assertEquals($value['description'], $updateParams['description']);
        $this->assertEquals((int) $value['amount'], $updateParams['minimum_fee']);
        $this->assertEquals($value['financial_type_id'], $updateParams['financial_type_id']);
        $this->assertEquals($value['visibility_id'], CRM_Price_BAO_PriceField::getVisibilityOptionID(strtolower($updateParams['visibility'])));
      }
    }

    foreach ($memType as $type) {
      $this->callAPISuccess('membership_type', 'delete', ['id' => $type]);
    }

  }

}
