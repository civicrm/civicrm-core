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

use Civi\Api4\Contribution;
use Civi\Api4\LineItem;
use Civi\Api4\Membership;
use Civi\Api4\Order;
use Civi\Api4\Participant;
use Civi\Api4\PriceSet;
use Civi\Test\EventTestTrait;

/**
 * Class CRM_Financial_BAO_OrderTest
 *
 * @group headless
 */
class CRM_Financial_BAO_OrderTest extends CiviUnitTestCase {
  use EventTestTrait;

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateOrderParticipantAndDonation(): void {
    $this->eventCreatePaid();
    $this->individualCreate();
    Order::create()
      ->setContributionValues([
        'contact_id' => $this->ids['Contact']['individual_0'],
        'financial_type_id' => 1,
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id.event_id' => $this->getEventID(),
        'entity_id.contact_id' => $this->ids['Contact']['individual_0'],
        'financial_type_id' => 3,
        'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student_early'],
      ])
      ->execute();
    $contribution = Contribution::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['individual_0'])
      ->execute()->single();
    $this->assertEquals(50, $contribution['total_amount']);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();
    $this->assertEquals('civicrm_participant', $lineItem['entity_table']);
    $participant = Participant::get()
      ->addWhere('id', '=', $lineItem['entity_id'])
      ->execute()->single();
    $this->assertEquals($this->ids['Contact']['individual_0'], $participant['contact_id']);

  }

  /**
   * Test create order api for membership
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateOrderForMembership(): void {
    $this->setUpMembershipPriceSet();
    $contribution = Order::create()
      ->setContributionValues([
        'contact_id' => $this->individualCreate(),
        'receive_date' => '2010-01-20',
        'financial_type_id:name' => 'Member Dues',
      ])
      ->addLineItem([
        'price_field_value_id' => $this->ids['PriceFieldValue']['membership_first'],
        // Because the price field value relates to a membership type
        // the entity_id is understood to be a membership ID.
        // All provided values prefixed by entity_id will be passed to
        // the membership.create api.
        'entity_id.join_date' => '2006-01-21',
        'entity_id.start_date' => '2006-01-21',
        'entity_id.end_date' => '2006-12-21',
        'entity_id.source' => 'Payment',
      ])
      ->execute()->first();

    $lineItem = LineItem::get()
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);

    // The line item links the membership to the contribution.
    $this->assertEquals(1, $lineItem['membership_num_terms']);
    $this->assertEquals(100, $lineItem['unit_price']);
    $this->assertEquals(100, $lineItem['line_total']);
    $this->assertEquals(1, $lineItem['qty']);

    $membership = Membership::get()
      ->addWhere('id', '=', $lineItem['entity_id'])
      ->execute()->single();
    $this->assertEquals('2006-12-21', $membership['end_date']);
    // A membership payment should have been created for legacy compatibility.
    $this->callAPISuccessGetSingle('MembershipPayment', ['membership_id' => $lineItem['entity_id'], 'contribution_id' => $contribution['id']]);
  }

  /**
   * Test creating an order containing items from 2 price sets plus an ad hoc amount.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateMixedPriceSetOrder(): void {
    $this->setUpMembershipPriceSet();
    $this->setUpGenericPriceSet();

    Order::create()
      ->setContributionValues([
        'contact_id' => $this->individualCreate(),
        'receive_date' => '2010-01-20',
        'financial_type_id:name' => 'Member Dues',
      ])
      ->addLineItem([
        // As this price field value has a membership type ID attached
        // a membership will be created in response to this.
        // All other values will be loaded from the price field value,
        // with the contact_id defaulting to that of the contribution (above).
        'price_field_value_id' => $this->ids['PriceFieldValue']['membership_first'],
      ])
      ->addLineItem([
        // The amount, financial type will come from the price field value record.
        // Note this price field value is not in the same price set as the above one.
        'price_field_value_id' => $this->ids['PriceFieldValue']['hundred'],
      ])
      ->addLineItem([
        // This will assume the order financial type & quantity = 1,
        // The price field value will be that of the default line item
        // (generally ID = 1) which is the 'generic' one.
        // Note that it is not part of either the above price sets.
        'line_total' => 500,
        'description' => 'Some extra dosh',
      ])
      ->execute()->first();
    $contribution = Contribution::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['individual_0'])
      ->execute()->single();
    $this->assertEquals(700, $contribution['total_amount']);
    $lineItem = (array) LineItem::get()
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addSelect('*', 'price_field_value_id.*', 'price_field_id.*')
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(3, $lineItem);
    $firstItem = array_pop($lineItem);
    $secondItem = array_pop($lineItem);
    $thirdItem = array_pop($lineItem);
    $this->assertEquals($this->getDefaultPriceSetID(), $firstItem['price_field_id.price_set_id']);
    $this->assertEquals($this->ids['PriceSet']['generic'], $secondItem['price_field_id.price_set_id']);
    $this->assertEquals($this->ids['PriceSet']['membership'], $thirdItem['price_field_id.price_set_id']);
  }

  public function setUpGenericPriceSet(): void {
    $this->createTestEntity('PriceSet', [
      'name' => 'generic_price_set',
      'title' => 'generic price set',
      'is_quick_config' => TRUE,
      'extends' => 2,
    ], 'generic')['id'];

    $this->createTestEntity('PriceField', [
      'price_set_id:name' => 'generic_price_set',
      'name' => 'generic_price_set',
      'label' => 'generic_price_set',
      'html_type' => 'Radio',
    ], 'generic');
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id.name' => 'generic_price_set',
      'name' => 'hundred',
      'label' => 'Hundred',
      'amount' => 100,
      'financial_type_id:name' => 'Donation',
    ], 'hundred');
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id.name' => 'generic_price_set',
      'name' => 'Thousand',
      'label' => 'Thousand',
      'amount' => 1000,
      'financial_type_id:name' => 'Donation',
    ], 'thousand');
  }

  /**
   *
   */
  public function setUpMembershipPriceSet(): void {
    $this->membershipTypeCreate(['name' => 'First'], 'first');
    $this->membershipTypeCreate(['name' => 'Second'], 'second');
    $this->createTestEntity('PriceSet', [
      'name' => 'price_set',
      'title' => 'membership price set',
      'is_quick_config' => TRUE,
      'extends' => 2,
    ], 'membership')['id'];

    $this->createTestEntity('PriceField', [
      'price_set_id:name' => 'price_set',
      'name' => 'membership_first',
      'label' => 'Membership First',
      'html_type' => 'Radio',
    ], 'membership_first');
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id.name' => 'membership_first',
      'name' => 'membership_first',
      'label' => 'Membership Type',
      'amount' => 100,
      'financial_type_id:name' => 'Member Dues',
      'membership_type_id.name' => 'First',
    ], 'membership_first');
  }

  /**
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getDefaultPriceSetID(): int {
    return PriceSet::get(FALSE)
      ->addWhere('name', '=', 'default_contribution_amount')
      ->execute()
      ->first()['id'];
  }

}
