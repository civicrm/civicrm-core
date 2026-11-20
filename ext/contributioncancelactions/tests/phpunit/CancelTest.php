<?php

use Civi\Api4\Activity;
use Civi\Api4\Contribution;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\EntityTrait;
use Civi\Test\FormTrait;
use Civi\Test\LookupHelperTrait;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Contact;
use Civi\Api4\Membership;
use Civi\Api4\MembershipType;
use Civi\Api4\Order;
use Civi\Api4\RelationshipType;
use Civi\Api4\Relationship;
use Civi\Api4\Event;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\Participant;
use PHPUnit\Framework\TestCase;
use Civi\Test\ContactTestTrait;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CancelTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use ContactTestTrait;
  use EntityTrait;
  use FormTrait;
  use LookupHelperTrait;

  /**
   * The setupHeadless function runs at the start of each test case, right
   * before the headless environment reboots.
   *
   * It should perform any necessary steps required for putting the database
   * in a consistent baseline -- such as loading schema and extensions.
   *
   * The utility `\Civi\Test::headless()` provides a number of helper functions
   * for managing this setup, and it includes optimizations to avoid redundant
   * setup work.
   *
   * @throws \CRM_Extension_Exception_ParseException
   * @see \Civi\Test
   */
  public function setUpHeadless(): CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test that a cancel from paypal pro results in an order being cancelled.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testPaypalProCancel(): void {
    $this->createContact();
    $this->createMembershipType();
    Relationship::create()->setValues([
      'contact_id_a' => $this->ids['contact'][0],
      'contact_id_b' => Contact::create()->setValues(['first_name' => 'Bugs', 'last_name' => 'Bunny'])->execute()->first()['id'],
      'relationship_type_id:name' => 'AB',
    ])->execute();

    $this->createMembershipOrder();

    $this->assertCount(2, Membership::get(FALSE)->execute());

    $ipn = new CRM_Core_Payment_PayPalProIPN([
      'rp_invoice_id' => http_build_query([
        'b' => $this->ids['Contribution'][0],
        'm' => 'contribute',
        'i' => 'zyx',
        'c' => $this->ids['contact'][0],
      ]),
      'mc_gross' => 200,
      'payment_status' => 'Refunded',
      'processor_id' => $this->createPaymentProcessor(),
    ]);
    $ipn->main();
    $this->assertCount(1, Contribution::get(FALSE)->addWhere('contribution_status_id:name', '=', 'Cancelled')->execute());
    $this->assertCount(2, Membership::get(FALSE)->addWhere('status_id:name', '=', 'Cancelled')->execute());
  }

  /**
   * Create an order with more than one membership.
   *
   */
  protected function createMembershipOrder(): void {
    $priceFieldID = PriceField::get(FALSE)
      ->addSelect('id')
      ->addWhere('label', '=', 'Membership Amount')
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->first()['id'];
    $generalPriceFieldValueID = PriceFieldValue::get(FALSE)
      ->addSelect('id')
      ->addWhere('label', '=', 'General')
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->first()['id'];

    $this->ids['Contribution'][0] = Order::create(FALSE)
      ->setContributionValues([
        'financial_type_id:name' => 'Member Dues',
        'contact_id' => $this->ids['contact'][0],
        'is_test' => FALSE,
        'payment_instrument_id:name' => 'Credit Card',
        'receive_date' => '2019-07-25 07:34:23',
        'invoice_id' => 'zyx',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_membership',
        'entity_id.contact_id' => $this->ids['contact'][0],
        'entity_id.source' => 'Payment',
        'entity_id.membership_type_id' => $this->ids['MembershipType']['General'],
        'label' => 'General',
        'qty' => 1,
        'unit_price' => 200,
        'line_total' => 200,
        'financial_type_id' => $this->getFinancialTypeID('Donation'),
        'price_field_id' => $priceFieldID,
        'price_field_value_id' => $generalPriceFieldValueID,
      ])
      ->execute()->single()['id'];
  }

  /**
   * Create the general membership type.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createMembershipType(): void {
    $this->ids['MembershipType']['General'] = MembershipType::create()->setValues([
      'name' => 'General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => 1,
      'domain_id' => 1,
      'financial_type_id:name' => 'Member Dues',
      'relationship_type_id' => RelationshipType::create(FALSE)->setValues(['name_a_b' => 'AB', 'name_b_a' => 'BA'])->execute()->first()['id'],
      'relationship_direction' => 'a_b',
      'is_active' => 1,
      'sequential' => 1,
      'visibility' => 'Public',
    ])->execute()->first()['id'];
  }

  /**
   * Create a payment processor.
   *
   * @param string $processorType
   *   Name of the payment processor type.
   *
   * @return int
   */
  public function createPaymentProcessor(string $processorType = 'PayPal'): int {
    return $this->createTestEntity('PaymentProcessor', [
      'name' => 'demo',
      'title' => 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id:name' => $processorType,
      'is_active' => TRUE,
      'is_default' => FALSE,
      'is_test' => TRUE,
      'user_name' => 'sunil._1183377782_biz_api1.webaccess.co.in',
      'password' => '1183377788',
      'signature' => 'APixCoQ-Zsaj-u3IH7mD5Do-7HUqA9loGnLSzsZga9Zr-aNmaJa3WGPH',
      'url_site' => 'https://www.sandbox.paypal.com/',
      'url_api' => 'https://api-3t.sandbox.paypal.com/',
      'url_button' => 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',
      'class_name' => 'Payment_PayPalImpl',
      'billing_mode' => 3,
      'financial_account_id' => $this->getFinancialAccountID('Payment Processor Account'),
      // Credit card = 1 so can pass 'by accident'.
      'payment_instrument_id:name' => 'Debit Card',
    ], $processorType)['id'];
  }

  /**
   * Test that a cancel from PayPal pro results in an order being cancelled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaypalStandardCancel(): void {
    $this->createContact();
    $orderID = $this->createEventOrder();
    $ipn = new CRM_Core_Payment_PayPalIPN([
      'mc_gross' => 200,
      'contactID' => $this->ids['contact'][0],
      'contributionID' => $orderID,
      'module' => 'event',
      'invoice' => 123,
      'eventID' => $this->ids['Event'][0],
      'participantID' => Participant::get()->addWhere('event_id', '=', $this->ids['Event'][0])->addSelect('id')->execute()->first()['id'],
      'payment_status' => 'Refunded',
      'processor_id' => $this->createPaymentProcessor('PayPal_Standard'),
    ]);
    $ipn->main();
    Contribution::get(FALSE)->addWhere('contribution_status_id:name', '=', 'Cancelled')->execute()->single();
    $this->assertCount(1, Participant::get(FALSE)->addWhere('status_id:name', '=', 'Cancelled')->execute());
  }

  /**
   * Test fail order api.
   *
   * @throws CRM_Core_Exception
   */
  public function testCancelOrderWithParticipantFailed(): void {
    $status = 'Failed';
    $this->createAndUpdateContribution($status);
  }

  /**
   * Test cancel order api.
   *
   * @throws CRM_Core_Exception
   */
  public function testCancelOrderWithParticipantCancelled(): void {
    $this->markTestIncomplete('For unknown reasons this failed if run after the cancelled variation of this test');
    $this->createAndUpdateContribution('Cancelled');
  }

  /**
   * Test cancelling a contribution with a membership on the contribution edit
   * form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancelFromContributionForm(): void {
    $this->createContact();
    $this->createMembershipType();
    $this->createMembershipOrder();
    $this->createLoggedInUser();
    $formValues = [
      'contact_id' => $this->ids['contact'][0],
      'financial_type_id' => $this->getFinancialTypeID('Donation'),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled'),
    ];
    $this->getTestForm('CRM_Contribute_Form_Contribution', $formValues, [
      'action' => 'update',
      'id' => $this->ids['Contribution'][0],
    ])->processForm();

    $contribution = Contribution::get()
      ->addWhere('id', '=', $this->ids['Contribution'][0])
      ->addSelect('contribution_status_id:name')
      ->execute()->first();
    $this->assertSame('Cancelled', $contribution['contribution_status_id:name']);
    $membership = Membership::get(FALSE)
      ->addSelect('id', 'status_id:name', 'is_override')
      ->execute()->single();
    $this->assertSame('Cancelled', $membership['status_id:name']);
    $this->assertTrue($membership['is_override']);
    $membershipSignupActivity = Activity::get()
      ->addSelect('subject', 'source_record_id', 'status_id')
      ->addWhere('activity_type_id:name', '=', 'Membership Signup')
      ->execute();
    $this->assertCount(1, $membershipSignupActivity);
    $this->assertSame($membership['id'], $membershipSignupActivity->first()['source_record_id']);
    $this->assertSame('General - Payment - Status: Pending', $membershipSignupActivity->first()['subject']);
    $activity = Activity::get()
      ->addSelect('subject', 'source_record_id', 'status_id')
      ->addWhere('activity_type_id:name', '=', 'Change Membership Status')
      ->execute();
    $this->assertCount(1, $activity);
    $this->assertSame('Status changed from Pending to Cancelled', $activity->first()['subject']);
  }

  /**
   * Get the event ID.
   *
   * @return int
   */
  protected function getEventID(): int {
    return $this->ids['Event'][0];
  }

  /**
   * Create an event and an order for a participant in that event.
   *
   * @return int
   * @throws CRM_Core_Exception
   */
  protected function createEventOrder(): int {
    $this->ids['Event'][0] = (int) Event::create()->setValues(['title' => 'Event', 'start_date' => 'tomorrow', 'event_type_id:name' => 'Workshop'])->execute()->first()['id'];
    return Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->ids['contact'][0],
        'financial_type_id:name' => 'Donation',
        'invoice_id' => '123',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id.contact_id' => $this->ids['contact'][0],
        'entity_id.event_id' => $this->ids['Event'][0],
        'entity_id.status_id' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Pending from incomplete transaction'),
        'line_total' => 5,
        'unit_price' => 5,
        'qty' => 1,
        'financial_type_id' => $this->getFinancialTypeID('Donation'),
        'price_field_id' => PriceField::get(FALSE)->addSelect('id')->addWhere('name', '=', 'contribution_amount')->execute()->first()['id'],
      ])
      ->execute()->single()['id'];
  }

  /**
   * Create a contact for use in the test.
   *
   * @throws CRM_Core_Exception
   */
  protected function createContact(): void {
    $this->ids['contact'][0] = Civi\Api4\Contact::create()->setValues(['first_name' => 'Brer', 'last_name' => 'Rabbit'])->execute()->first()['id'];
  }

  /**
   * @param string $status
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function createAndUpdateContribution(string $status): void {
    $this->createContact();
    $orderID = $this->createEventOrder();
    $participantID = Participant::get()
      ->addSelect('id')
      ->execute()
      ->first()['id'];
    $additionalParticipantID = Participant::create()->setValues([
      'event_id' => $this->getEventID(),
      'contact_id' => $this->individualCreate(),
      'registered_by_id' => $participantID,
      'status_id:name' => 'Pending from incomplete transaction',
    ])->execute()->first()['id'];
    Contribution::update(FALSE)
      ->setValues(['contribution_status_id:name' => $status])
      ->addWhere('id', '=', $orderID)
      ->execute();
    Contribution::get(FALSE)->addWhere('contribution_status_id:name', '=', $status)->execute()->single();
    $this->assertCount(2, Participant::get(FALSE)->addWhere('status_id:name', '=', 'Cancelled')->execute());

    $cancelledActivities = Activity::get(FALSE)
      ->addWhere('activity_type_id:name', '=', 'Event Registration')
      ->addWhere('subject', 'LIKE', '%Cancelled%')
      ->addWhere('source_record_id', 'IN', [$participantID, $additionalParticipantID])
      ->execute();
    $this->assertCount(2, $cancelledActivities);
  }

}
