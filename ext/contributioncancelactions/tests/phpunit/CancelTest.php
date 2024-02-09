<?php

use Civi\Api4\Activity;
use Civi\Api4\Contribution;
use Civi\Test\Api3TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\FormTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Contact;
use Civi\Api4\MembershipType;
use Civi\Api4\RelationshipType;
use Civi\Api4\Relationship;
use Civi\Api4\Event;
use Civi\Api4\PriceField;
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

  use Api3TestTrait;
  use ContactTestTrait;
  use FormTrait;

  /**
   * Created ids.
   *
   * @var array
   */
  protected $ids = [];

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
      'relationship_type_id' => RelationshipType::get()->addWhere('name_a_b', '=', 'AB')->execute()->first()['id'],
    ])->execute();

    $this->createMembershipOrder();

    $memberships = $this->callAPISuccess('Membership', 'get')['values'];
    $this->assertCount(2, $memberships);

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
    $this->callAPISuccessGetCount('Contribution', ['contribution_status_id' => 'Cancelled'], 1);
    $this->callAPISuccessGetCount('Membership', ['status_id' => 'Cancelled'], 2);
  }

  /**
   * Create an order with more than one membership.
   *
   */
  protected function createMembershipOrder(): void {
    $priceFieldID = $this->callAPISuccessGetValue('price_field', [
      'return' => 'id',
      'label' => 'Membership Amount',
      'options' => ['limit' => 1, 'sort' => 'id DESC'],
    ]);
    $generalPriceFieldValueID = $this->callAPISuccessGetValue('price_field_value', [
      'return' => 'id',
      'label' => 'General',
      'options' => ['limit' => 1, 'sort' => 'id DESC'],
    ]);

    $orderID = $this->callAPISuccess('Order', 'create', [
      'financial_type_id' => 'Member Dues',
      'contact_id' => $this->ids['contact'][0],
      'is_test' => 0,
      'payment_instrument_id' => 'Credit card',
      'receive_date' => '2019-07-25 07:34:23',
      'invoice_id' => 'zyx',
      'line_items' => [
        [
          'params' => [
            'contact_id' => $this->ids['contact'][0],
            'source' => 'Payment',
            'membership_type_id' => 'General',
            // This is interim needed while we improve the BAO - if the test passes without it it can go!
            'skipStatusCal' => TRUE,
          ],
          'line_item' => [
            [
              'label' => 'General',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 1,
              'entity_table' => 'civicrm_membership',
              'price_field_id' => $priceFieldID,
              'price_field_value_id' => $generalPriceFieldValueID,
            ],
          ],
        ],
      ],
    ])['id'];
    $this->ids['Contribution'][0] = $orderID;
  }

  /**
   * Create the general membership type.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createMembershipType(): void {
    MembershipType::create()->setValues([
      'name' => 'General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => 1,
      'domain_id' => 1,
      'financial_type_id' => 2,
      'relationship_type_id' => RelationshipType::create(FALSE)->setValues(['name_a_b' => 'AB', 'name_b_a' => 'BA'])->execute()->first()['id'],
      'relationship_direction' => 'a_b',
      'is_active' => 1,
      'sequential' => 1,
      'visibility' => 'Public',
    ])->execute();
  }

  /**
   * Create a payment processor.
   *
   * @param array $params
   *
   * @return int
   */
  public function createPaymentProcessor(array $params = []): int {
    $params = array_merge([
      'title' => $params['name'] ?? 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id' => 'PayPal',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 1,
      'user_name' => 'sunil._1183377782_biz_api1.webaccess.co.in',
      'password' => '1183377788',
      'signature' => 'APixCoQ-Zsaj-u3IH7mD5Do-7HUqA9loGnLSzsZga9Zr-aNmaJa3WGPH',
      'url_site' => 'https://www.sandbox.paypal.com/',
      'url_api' => 'https://api-3t.sandbox.paypal.com/',
      'url_button' => 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',
      'class_name' => 'Payment_PayPalImpl',
      'billing_mode' => 3,
      'financial_type_id' => 1,
      'financial_account_id' => 12,
      // Credit card = 1 so can pass 'by accident'.
      'payment_instrument_id' => 'Debit Card',
    ], $params);
    if (!is_numeric($params['payment_processor_type_id'])) {
      // really the api should handle this through getoptions but it's not exactly api call so lets just sort it
      //here
      $params['payment_processor_type_id'] = $this->callAPISuccess('payment_processor_type', 'getvalue', [
        'name' => $params['payment_processor_type_id'],
        'return' => 'id',
      ], 'integer');
    }
    $result = $this->callAPISuccess('payment_processor', 'create', $params);
    return (int) $result['id'];
  }

  /**
   * Test that a cancel from paypal pro results in an order being cancelled.
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
      'processor_id' => $this->createPaymentProcessor(['payment_processor_type_id' => 'PayPal_Standard']),
    ]);
    $ipn->main();
    $this->callAPISuccessGetSingle('Contribution', ['contribution_status_id' => 'Cancelled']);
    $this->callAPISuccessGetCount('Participant', ['status_id' => 'Cancelled'], 1);
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
    $status = 'Cancelled';
    $this->createAndUpdateContribution($status);
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
      'financial_type_id' => 1,
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
    $this->assertEquals('Cancelled', $contribution['contribution_status_id:name']);
    $membership = $this->callAPISuccessGetSingle('Membership', []);
    $this->assertEquals('Cancelled', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']));
    $this->assertEquals(TRUE, $membership['is_override']);
    $membershipSignupActivity = Activity::get()
      ->addSelect('subject', 'source_record_id', 'status_id')
      ->addWhere('activity_type_id:name', '=', 'Membership Signup')
      ->execute();
    $this->assertCount(1, $membershipSignupActivity);
    $this->assertEquals($membership['id'], $membershipSignupActivity->first()['source_record_id']);
    $this->assertEquals('General - Payment - Status: Pending', $membershipSignupActivity->first()['subject']);
    $activity = Activity::get()
      ->addSelect('subject', 'source_record_id', 'status_id')
      ->addWhere('activity_type_id:name', '=', 'Change Membership Status')
      ->execute();
    $this->assertCount(1, $activity);
    $this->assertEquals('Status changed from Pending to Cancelled', $activity->first()['subject']);
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
    $order = $this->callAPISuccess('Order', 'create', [
      'contact_id' => $this->ids['contact'][0],
      'financial_type_id' => 'Donation',
      'invoice_id' => 123,
      'line_items' => [
        [
          'line_item' => [
            [
              'line_total' => 5,
              'qty' => 1,
              'financial_type_id' => 1,
              'entity_table' => 'civicrm_participant',
              'price_field_id' => PriceField::get()->addSelect('id')->addWhere('name', '=', 'contribution_amount')->execute()->first()['id'],
            ],
          ],
          'params' => [
            'contact_id' => $this->ids['contact'][0],
            'event_id' => $this->ids['Event'][0],
          ],
        ],
      ],
    ]);
    return (int) $order['id'];
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
    if ($status === 'Cancelled') {
      $this->callAPISuccess('Order', 'cancel', ['contribution_id' => $orderID]);
    }
    else {
      Contribution::update()
        ->setValues(['contribution_status_id:name' => $status])
        ->addWhere('id', '=', $orderID)
        ->execute();
    }
    $this->callAPISuccess('Order', 'get', ['contribution_id' => $orderID]);
    $this->callAPISuccessGetSingle('Contribution', ['contribution_status_id' => $status]);
    $this->callAPISuccessGetCount('Participant', ['status_id' => 'Cancelled'], 2);

    $cancelledActivatesCount = civicrm_api3('Activity', 'get', [
      'sequential' => 1,
      'activity_type_id' => 'Event Registration',
      'subject' => ['LIKE' => '%Cancelled%'],
      'source_record_id' => ['IN' => [$participantID, $additionalParticipantID]],
    ]);

    $this->assertEquals(2, $cancelledActivatesCount['count']);
  }

}
