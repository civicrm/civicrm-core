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
 * Class CRM_Core_Payment_ProcessorFormTest
 * @group headless
 */
class CRM_Core_Payment_ProcessorFormTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  protected $standardProfile;

  /**
   * @var array
   */
  protected $customProfile;

  /**
   * @var int
   */
  protected $standardProcessorTypeID;

  /**
   * @var int
   */
  protected $customProcessorTypeID;

  /**
   * @var int
   */
  protected $standardProcessorID;

  /**
   * @var int
   */
  protected $customProcessorID;

  public function setUp(): void {
    parent::setUp();

    $this->createStandardBillingProfile();
    $this->createCustomBillingProfile();

    $this->standardProcessorTypeID = $this->paymentProcessorTypeCreate([
      'class_name' => 'PaymentProcessorWithStandardBillingRequirements',
      'name' => 'StandardBillingType',
    ]);

    $this->customProcessorTypeID = $this->paymentProcessorTypeCreate([
      'class_name' => 'PaymentProcessorWithCustomBillingRequirements',
      'name' => 'CustomBillingType',
    ]);

    $this->standardProcessorID = $this->paymentProcessorCreate([
      'name' => 'StandardBilling',
      'class_name' => 'PaymentProcessorWithStandardBillingRequirements',
      'payment_processor_type_id' => $this->standardProcessorTypeID,
      'is_test' => 0,
    ]);

    $this->customProcessorID = $this->paymentProcessorCreate([
      'name' => 'CustomBilling',
      'class_name' => 'PaymentProcessorWithCustomBillingRequirements',
      'payment_processor_type_id' => $this->customProcessorTypeID,
      'is_test' => 0,
    ]);
  }

  public function tearDown(): void {
    $this->callAPISuccess('PaymentProcessor', 'delete', [
      'id' => $this->standardProcessorID,
    ]);

    $this->callAPISuccess('PaymentProcessor', 'delete', [
      'id' => $this->customProcessorID,
    ]);

    $this->callAPISuccess('PaymentProcessorType', 'delete', [
      'id' => $this->standardProcessorTypeID,
    ]);

    $this->callAPISuccess('PaymentProcessorType', 'delete', [
      'id' => $this->customProcessorTypeID,
    ]);

    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function createStandardBillingProfile(): void {
    $this->createTestableBillingProfile('standard', TRUE);
  }

  public function createCustomBillingProfile(): void {
    $this->createTestableBillingProfile('custom', FALSE);
  }

  public function createTestableBillingProfile($name, $withState): void {
    $billingID = CRM_Core_BAO_LocationType::getBilling();

    $this->ids['UFGroup']["{$name}_billing"] = $this->callAPISuccess('UFGroup', 'create', [
      'group_type' => 'Contact',
      'title' => "Billing fields: $name",
      'name' => "{$name}_billing",
    ])['id'];

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
      'field_name' => 'first_name',
      'is_required' => TRUE,
    ]);

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
      'field_name' => 'last_name',
      'is_required' => TRUE,
    ]);

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
      'field_name' => 'street_address',
      'is_required' => TRUE,
    ]);

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
      'field_name' => 'city',
      'location_type_id' => $billingID,
      'is_required' => TRUE,
    ]);

    if ($withState) {
      $this->callAPISuccess('UFField', 'create', [
        'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
        'field_name' => 'state_province',
        'location_type_id' => $billingID,
        'is_required' => TRUE,
      ]);
    }

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
      'field_name' => 'postal_code',
      'location_type_id' => $billingID,
      'is_required' => TRUE,
    ]);

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $this->ids['UFGroup']["{$name}_billing"],
      'field_name' => 'country',
      'location_type_id' => $billingID,
      'is_required' => TRUE,
    ]);
  }

  /**
   * Checks a payment processor with either the standard,
   * or the custom profile and returns a boolean that
   * indicates whether the billing block can be hidden, or not.
   */
  public function checkPaymentProcessorWithProfile($processorClass, $case): bool {
    $profileID = $this->ids['UFGroup'][$case . '_billing'];
    $processor = new $processorClass();

    $missingBillingFields = [];

    $fields = array_column(
      $this->callAPISuccess('UFField', 'get', ['uf_group_id' => $profileID])['values'],
      'field_name'
    );

    $fields = array_map(function($field) {
      if (!isset($field['location_type_id'])) {
        return $field;
      }
      return $field . '-' . $field['location_type_id'];
    }, $fields);

    $canBeHidden = FALSE;
    foreach ((array) $fields as $field) {
      $canBeHidden = CRM_Core_BAO_UFField::assignAddressField(
        $field,
        $missingBillingFields,
        ['uf_group_id' => $profileID],
        array_keys($processor->getBillingAddressFields())
      );

      if (!$canBeHidden) {
        break;
      }
    }

    return $canBeHidden;
  }

  /**
   * Checks that, if a payment processor declares the standard
   * billing fields as needed, they must be considered mandatory.
   */
  public function testPaymentProcessorWithStandardBillingRequirements(): void {
    $canBeHiddenWithTheStandardProfile = $this->checkPaymentProcessorWithProfile(
      'PaymentProcessorWithStandardBillingRequirements',
      'standard'
    );

    $canBeHiddenWithTheCustomProfile = $this->checkPaymentProcessorWithProfile(
      'PaymentProcessorWithStandardBillingRequirements',
      'custom'
    );

    $this->assertEquals(TRUE, $canBeHiddenWithTheStandardProfile);
    $this->assertEquals(FALSE, $canBeHiddenWithTheCustomProfile);
  }

  /**
   * Checks that, if the payment processor doesn't declare a field
   * as needed, the field shouldn't be considered mandatory.
   */
  public function testPaymentProcessorWithCustomRequirements(): void {
    $canBeHiddenWithTheCustomProfile = $this->checkPaymentProcessorWithProfile(
      "PaymentProcessorWithCustomBillingRequirements",
      "custom"
    );

    $this->assertEquals(TRUE, $canBeHiddenWithTheCustomProfile);
  }

}

class PaymentProcessorWithStandardBillingRequirements extends CRM_Core_Payment {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->_paymentProcessor = [
      'payment_type' => 0,
      'billing_mode' => 0,
      'id' => 0,
      'url_recur' => '',
      'is_recur' => 0,
    ];
  }

  /**
   * again, `checkConfig` is abstract in CRM_Core_Payment, so we are forced to implement it
   */
  public function checkConfig() {
  }

}

class PaymentProcessorWithCustomBillingRequirements extends CRM_Core_Payment {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->_paymentProcessor = [
      'payment_type' => 0,
      'billing_mode' => 0,
      'id' => 0,
      'url_recur' => '',
      'is_recur' => 0,
    ];
  }

  /**
   * again, `checkConfig` is abstract in CRM_Core_Payment, so we are forced to implement it
   */
  public function checkConfig() {
  }

  public function getBillingAddressFields($billingLocationID = NULL) {
    // Note that it intentionally misses the state_province field
    return [
      'first_name' => 'billing_first_name',
      'middle_name' => 'billing_middle_name',
      'last_name' => 'billing_last_name',
      'street_address' => "billing_street_address-{$billingLocationID}",
      'city' => "billing_city-{$billingLocationID}",
      'country' => "billing_country_id-{$billingLocationID}",
      'postal_code' => "billing_postal_code-{$billingLocationID}",
    ];
  }

}
