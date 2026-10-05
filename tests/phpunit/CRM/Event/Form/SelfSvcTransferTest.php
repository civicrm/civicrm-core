<?php

use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialItem;
use Civi\Api4\LineItem;
use Civi\Api4\Order;
use Civi\Api4\Participant;
use Civi\Api4\Payment;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_SelfSvcTransferTest extends CiviUnitTestCase {

  /**
   * Test cancellation.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancel(): void {
    $this->participantCreate(['status_id.name' => 'Registered']);
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new@example.org']);
    $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'email' => 'new@example.org',
    ], [
      'pid' => $this->ids['Participant']['default'],
      'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->ids['Contact']['individual_0']),
      'is_backoffice' => 1,
    ])->processForm();

    $this->assertMailSentContainingHeaderString('Registration Confirmation - Annual CiviCRM meet - Mr. Anthony', 0);
    $this->assertMailSentContainingString('<p>Dear Anthony,</p>    <p>Your Event Registration has been Transferred to Anthony Anderson.</p>', 1);
    $this->assertMailSentContainingString('anthony_anderson@civicrm.org', 1);
    $this->assertMailSentContainingString('123', 1);
    $this->assertMailSentContainingString('fixme.domainemail@example.org', 1);
  }

  /**
   * Test transferring a participant whose line items have no linked contribution
   * (e.g. a payment-suppressed waitlist registration) does not fatal.
   *
   * https://lab.civicrm.org/dev/core/-/work_items/6691
   *
   * @throws \CRM_Core_Exception
   */
  public function testTransferUnlinkedLineItem(): void {
    $participantID = $this->participantCreate(['status_id.name' => 'Registered']);
    $this->createTestEntity('LineItem', [
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participantID,
      'qty' => 1,
      'unit_price' => 0,
      'line_total' => 0,
    ]);
    $this->individualCreate(['email' => 'new@example.org']);
    $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'email' => 'new@example.org',
    ], [
      'pid' => $this->ids['Participant']['default'],
      'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->ids['Contact']['individual_0']),
      'is_backoffice' => 1,
    ])->processForm();

    $this->assertMailSentContainingString('Your Event Registration has been Transferred', 1);
  }

  /**
   * Test transferring a participant whose line item IS linked to a contribution
   * moves the financial item to the new participant's contact correctly.
   *
   * https://lab.civicrm.org/dev/core/-/work_items/6691
   *
   * @throws \CRM_Core_Exception
   */
  public function testTransferLinkedLineItem(): void {
    $event = $this->eventCreatePaid();
    $fromContactID = $this->individualCreate();
    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $fromContactID,
        'financial_type_id:name' => 'Event Fee',
        'contribution_status_id:name' => 'Completed',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id.event_id' => $event['id'],
        'entity_id.contact_id' => $fromContactID,
        'entity_id.status_id:name' => 'Registered',
        'entity_id.role_id' => 1,
        'entity_id.register_date' => date('Y-m-d'),
        'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student_early'],
        'qty' => 1,
      ])
      ->execute()->single();

    Payment::create(FALSE)
      ->setNotificationForCompleteOrder(FALSE)
      ->addValue('contribution_id', $contribution['id'])
      ->addValue('total_amount', 50)
      ->execute();
    $fromLineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();
    $participantID = $fromLineItem['entity_id'];

    $this->individualCreate(['email' => 'new@example.org'], 'to_contact');
    $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'email' => 'new@example.org',
    ], [
      'pid' => $participantID,
      'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($fromContactID),
      'is_backoffice' => 1,
    ])->processForm();

    $toParticipant = Participant::get(FALSE)
      ->addWhere('event_id', '=', $event['id'])
      ->addWhere('id', '!=', $participantID)
      ->execute()->single();
    $toLineItem = LineItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_participant')
      ->addWhere('entity_id', '=', $toParticipant['id'])
      ->execute()->single();
    $this->assertEquals($contribution['id'], $toLineItem['contribution_id']);

    $toFinancialItem = FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', '=', $toLineItem['id'])
      ->execute()->single();
    $this->assertEquals($this->ids['Contact']['to_contact'], $toFinancialItem['contact_id']);
    $this->assertEquals(50, $toFinancialItem['amount']);

    $trxnAmounts = EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_financial_item')
      ->addSelect('amount')
      ->execute()
      ->column('amount');
    $this->assertEquals(50, array_sum($trxnAmounts));
  }

  /**
   * Test Transfer as anonymous
   *
   * @throws \CRM_Core_Exception
   */
  public function testTransferAnonymous(): void {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $event = $this->eventCreateUnpaid(['start_date' => date('Ymd', strtotime('+2 month')), 'end_date' => date('Ymd', strtotime('+2 month')), 'registration_end_date' => date('Ymd', strtotime('+1 month')), 'allow_selfcancelxfer' => 1]);
    $this->participantCreate(['status_id.name' => 'Registered', 'event_id' => $event['id'], 'contact_id' => $this->individualCreate()]);
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new2@example.org'], 'to_contact');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'first_name' => 'test',
      'last_name' => 'selftransfer',
      'email' => 'new2@example.org',
    ], [
      'pid' => $this->ids['Participant']['default'],
      'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->ids['Contact']['individual_0']),
      'is_backoffice' => 0,
    ])->processForm();
    $this->assertEquals('Registration Transferred', CRM_Core_Session::singleton()->getStatus()[1]['title']);
  }

}
