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
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Price_BAO_PriceSetTest extends CiviUnitTestCase {

  /**
   * Sets up the fixtures.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture.
   */
  protected function tearDown() {
  }

  /**
   * Test the correct amount level is returned for an event which is not presented as a price set event.
   *
   * (these are denoted as 'quickConfig' in the code - but quickConfig is only supposed to refer to the
   * configuration interface - there should be no different post process.
   */
  public function testGetAmountLevelTextAmount() {
    $priceSetID = $this->eventPriceSetCreate(9);
    $priceSet = CRM_Price_BAO_PriceSet::getCachedPriceSetDetail($priceSetID);
    $field = reset($priceSet['fields']);
    $params = ['priceSetId' => $priceSetID, 'price_' . $field['id'] => 1];
    $amountLevel = CRM_Price_BAO_PriceSet::getAmountLevelText($params);
    $this->assertEquals(CRM_Core_DAO::VALUE_SEPARATOR . 'Price Field - 1' . CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel);
    $priceFieldValue = $this->callAPISuccess('pricefieldvalue', 'getsingle', ['price_field_id' => $field['id']]);
    $this->callAPISuccess('PriceFieldValue', 'delete', ['id' => $priceFieldValue['id']]);
    $this->callAPISuccess('PriceField', 'delete', ['id' => $field['id']]);
    $this->callAPISuccess('PriceSet', 'delete', ['id' => $priceSetID]);
  }

  /**
   * CRM-20237 Test that Copied price set does not generate long name and unneded information
   */
  public function testCopyPriceSet() {
    $priceSetID = $this->eventPriceSetCreate(9);
    $oldPriceSetInfo = $this->callAPISuccess('PriceSet', 'getsingle', ['id' => $priceSetID]);
    $newPriceSet = CRM_Price_BAO_PriceSet::copy($priceSetID);
    $this->assertEquals(substr($oldPriceSetInfo['name'], 0, 20) . 'price_set_' . $newPriceSet->id, $newPriceSet->name);
    $this->assertEquals($oldPriceSetInfo['title'] . ' [Copy id ' . $newPriceSet->id . ']', $newPriceSet->title);
    $new2PriceSet = CRM_Price_BAO_PriceSet::copy($newPriceSet->id);
    $this->assertEquals(substr($newPriceSet->name, 0, 20) . 'price_set_' . $new2PriceSet->id, $new2PriceSet->name);
    $this->assertEquals($oldPriceSetInfo['title'] . ' [Copy id ' . $new2PriceSet->id . ']', $new2PriceSet->title);
    $oldPriceField = $this->callAPISuccess('priceField', 'getsingle', ['price_set_id' => $priceSetID]);
    $oldPriceFieldValue = $this->callAPISuccess('priceFieldValue', 'getsingle', ['price_field_id' => $oldPriceField['id']]);
    $this->callAPISuccess('PriceFieldValue', 'delete', ['id' => $oldPriceFieldValue['id']]);
    $this->callAPISuccess('PriceField', 'delete', ['id' => $oldPriceField['id']]);
    $this->callAPISuccess('PriceSet', 'delete', ['id' => $priceSetID]);
    $newPriceField = $this->callAPISuccess('PriceField', 'getsingle', ['price_set_id' => $newPriceSet->id]);
    $newPriceFieldValue = $this->callAPISuccess('PriceFieldValue', 'getsingle', ['price_field_id' => $newPriceField['id']]);
    $this->callAPISuccess('PriceFieldValue', 'delete', ['id' => $newPriceFieldValue['id']]);
    $this->callAPISuccess('PriceField', 'delete', ['id' => $newPriceField['id']]);
    $this->callAPISuccess('PriceSet', 'delete', ['id' => $newPriceSet->id]);
    $new2PriceField = $this->callAPISuccess('PriceField', 'getsingle', ['price_set_id' => $new2PriceSet->id]);
    $new2PriceFieldValue = $this->callAPISuccess('PriceFieldValue', 'getsingle', ['price_field_id' => $new2PriceField['id']]);
    $this->callAPISuccess('PriceFieldValue', 'delete', ['id' => $new2PriceFieldValue['id']]);
    $this->callAPISuccess('PriceField', 'delete', ['id' => $new2PriceField['id']]);
    $this->callAPISuccess('PriceSet', 'delete', ['id' => $new2PriceSet->id]);
  }

  /**
   * Test CRM_Price_BAO_PriceSet::getMembershipCount() that return correct number of
   *   membership type occurances against it's corresponding member orgaisation
   */
  public function testGetMembershipCount() {
    // create two organisations
    $organization1 = $this->organizationCreate();
    $organization2 = $this->organizationCreate();

    // create three membership type where first two belong to same organisation
    $membershipType1 = $this->membershipTypeCreate([
      'name' => 'Membership Type 1',
      'member_of_contact_id' => $organization1,
    ]);
    $membershipType2 = $this->membershipTypeCreate([
      'name' => 'Membership Type 2',
      'member_of_contact_id' => $organization1,
    ]);
    $membershipType3 = $this->membershipTypeCreate([
      'name' => 'Membership Type 3',
      'member_of_contact_id' => $organization2,
    ]);

    $priceDetails = CRM_Price_BAO_PriceSet::getSetDetail(CRM_Core_DAO::getFieldValue(
      'CRM_Price_DAO_PriceSet',
      'default_membership_type_amount',
      'id', 'name'
    ));
    // fetch price field value IDs in array('membership_type_id' => 'price_field_value_id') format
    $priceFieldValueIDs = [];
    foreach ($priceDetails as $priceFields) {
      foreach ($priceFields['fields'] as $priceField) {
        foreach ($priceField['options'] as $id => $priceFieldValue) {
          if (in_array($priceFieldValue['membership_type_id'], [$membershipType1, $membershipType2, $membershipType3])) {
            $priceFieldValueIDs[$priceFieldValue['membership_type_id']] = $id;
          }
        }
      }
    }

    // CASE 1: when two price field value IDs of membership type that belong to same organization, are chosen
    $sameOrgPriceFieldIDs = implode(', ', [
      $priceFieldValueIDs[$membershipType1],
      $priceFieldValueIDs[$membershipType2],
    ]);
    $occurences = CRM_Price_BAO_PriceSet::getMembershipCount($sameOrgPriceFieldIDs);
    // total number of membership type occurences of same organisation is one
    $this->assertEquals(1, count($occurences));
    $this->assertTrue(array_key_exists($organization1, $occurences));
    // assert that two membership types were chosen from same organisation
    $this->assertEquals(2, $occurences[$organization1]);

    // CASE 2: when two price field value IDs of membership type that belong to different organizations, are chosen
    $differentOrgPriceFieldIDs = implode(', ', [
      $priceFieldValueIDs[$membershipType1],
      $priceFieldValueIDs[$membershipType3],
    ]);
    $occurences = CRM_Price_BAO_PriceSet::getMembershipCount($differentOrgPriceFieldIDs);
    // total number of membership type occurences of different organisation is two
    $this->assertEquals(2, count($occurences));
    $this->assertTrue(array_key_exists($organization1, $occurences));
    $this->assertTrue(array_key_exists($organization2, $occurences));
    // assert that two membership types were chosen from different organisation
    $this->assertEquals(1, $occurences[$organization1]);
    $this->assertEquals(1, $occurences[$organization2]);
  }

}
