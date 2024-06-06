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
use Civi\Api4\Order;
use Civi\Api4\Participant;
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

}
