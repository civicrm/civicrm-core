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

use Civi\Api4\PriceField;
use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;

/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Contribution_MainTest extends CiviUnitTestCase {

  use FormTrait;

  /**
   * The id of the contribution page's payment processor.
   *
   * @var int
   */
  private $paymentProcessorId;

  /**
   * Clean up DB.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Given a membership type ID, return the price field value.
   */
  private function getPriceFieldValue($membershipTypeID): int {
    return (int) $this->callAPISuccessGetSingle('PriceFieldValue', ['membership_type_id' => $membershipTypeID, 'return' => 'id', 'price_field_id' => $this->ids['PriceField']['membership']])['id'];
  }

  /**
   * Establish a standard list of submit params to more accurately test the submission.
   */
  private function getSubmitParams(): array {
    return [
      'id' => $this->ids['ContributionPage']['test'],
      'amount' => 80,
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => $this->paymentProcessorId,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
      'auto_renew' => 1,
      'priceSetId' => $this->ids['PriceSet']['membership'],
    ];
  }

  /**
   * Test that the membership is set to recurring if the membership type is always autorenew.
   */
  public function testSetRecurFunction(): void {
    $this->membershipTypeCreate(['auto_renew' => 2, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->postProcess();
    $this->assertIsRecur(1);
  }

  /**
   * Test that the membership is set to recurring if the membership type is optionally autorenew and is_recur is true.
   */
  public function testSetRecurFunctionOptionalYes(): void {
    $this->membershipTypeCreate(['auto_renew' => 1, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->postProcess();
    $this->assertIsRecur(1);
  }

  /**
   * Test that the membership is not set to recurring if the membership type is optionally autorenew and is_recur is false.
   */
  public function testSetRecurFunctionOptionalNo(): void {
    $this->membershipTypeCreate(['auto_renew' => 1, 'minimum_fee' => 80]);
    $form = $this->getContributionForm(['auto_renew' => 0]);
    $form->postProcess();
    $this->assertIsRecur(0);
  }

  /**
   * Test that the membership doesn't have an "is_recur" key if the membership type can never autorenew.
   */
  public function testSetRecurFunctionNotAvailable(): void {
    $this->membershipTypeCreate(['auto_renew' => 0, 'minimum_fee' => 80]);
    $form = $this->getContributionForm();
    $form->postProcess();
    $this->assertIsRecur(NULL);
  }

  /**
   * Get a contribution form object for testing.
   *
   * @param array $submittedValues
   * @param array $params
   * @param int $formState
   *
   * @return \Civi\Test\FormWrapper
   */
  protected function getContributionForm(array $submittedValues = [], array $params = [], int $formState = FormWrapper::VALIDATED): FormWrapper {
    try {
      $this->ids['PriceSet']['membership'] = $params['priceSetID'] ?? $this->callAPISuccessGetValue('PriceSet', [
        'name' => 'default_membership_type_amount',
        'return' => 'id',
      ]);
      $this->ids['PriceField']['membership'] = PriceField::get(FALSE)->addWhere('price_set_id', '=', $this->ids['PriceSet']['membership'])->execute()->first()['id'];

      $paymentProcessor = $submittedValues['payment_processor_id'] = $this->paymentProcessorCreate([
        'payment_processor_type_id' => 'Dummy',
        'is_test' => 0,
      ]);
      $this->paymentProcessorId = $paymentProcessor;
      $this->supportPreApproval();

      $contributionPageParams = (array_merge($params, [
        'currency' => 'NZD',
        'goal_amount' => 6000,
        'is_pay_later' => 0,
        'is_monetary' => 1,
        'pay_later_text' => 'Front up',
        'pay_later_receipt' => 'Ta',
        'is_email_receipt' => 1,
        'payment_processor' => [$paymentProcessor],
        'amount_block_is_active' => 1,
      ]));
      $contributionPage = $this->contributionPageCreate($contributionPageParams);
      CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPage['id'], $this->ids['PriceSet']['membership']);

      $submittedValues = array_merge($this->getSubmitParams(), [
        'price_' . $this->ids['PriceField']['membership'] => $this->getPriceFieldValue($this->ids['MembershipType']['test']),
      ], $submittedValues);
      $submittedValues['id'] = $_REQUEST['id'] = (int) $contributionPage['id'];
      $form = $this->getTestForm('CRM_Contribute_Form_Contribution_Main', $submittedValues);
      $form->processForm($formState);
      return $form;
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('Failed to prepare form' . $e->getMessage());
    }
  }

  /**
   * Test expired priceset are not returned from buildPriceSet() Function.
   */
  public function testExpiredPriceSet(): void {
    $priceSetParams1 = [
      'name' => 'priceset',
      'title' => 'Priceset with Multiple Terms',
      'is_active' => 1,
      'extends' => 3,
      'financial_type_id' => 2,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];
    $priceSet = $this->createTestEntity('PriceSet', $priceSetParams1);

    // Create valid price field.
    $params = [
      'price_set_id' => $this->ids['PriceSet']['default'],
      'name' => 'test_valid_price_field',
      'label' => 'test valid price field',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField1 = $this->createTestEntity('PriceField', $params);

    // Create expired price field.
    $params = [
      'price_set_id' => $priceSet['id'],
      'name' => 'test_expired_price_field',
      'label' => 'test expired price field',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
      'expire_on' => date('Y-m-d', strtotime('-1 days')),
    ];
    $priceField2 = $this->createTestEntity('PriceField', $params, 'expired');

    //Create price options.
    $this->membershipTypeCreate(['member_of_contact_id' => $this->organizationCreate()]);
    foreach ([$priceField1, $priceField2] as $priceField) {
      $priceFieldValueParams = [
        'price_field_id' => $priceField['id'],
        'name' => 'rye grass',
        'membership_type_id' => $this->ids['MembershipType']['test'],
        'label' => 'juicy and healthy',
        'amount' => 1,
        'membership_num_terms' => 2,
        'financial_type_id' => 1,
      ];
      $this->callAPISuccess('PriceFieldValue', 'create', $priceFieldValueParams);
    }

    $form = $this->getContributionForm([], ['priceSetID' => $priceSet['id']]);
    $allFields = $form->getPriceFieldMetadata();
    $this->assertCount(1, $allFields);
    $field = current($allFields);
    $expectedValues = [
      'name' => 'test_valid_price_field',
      'label' => 'test valid price field',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'weight' => TRUE,
      'is_display_amounts' => TRUE,
      'options_per_line' => 1,
      'is_active' => TRUE,
      'active_on' => NULL,
      'expire_on' => NULL,
      'javascript' => NULL,
      'visibility_id:name' => 'public',
      'visibility_id' => 1,
      'is_required' => TRUE,
    ];
    foreach ($expectedValues as $key => $value) {
      $this->assertEquals($value, $field[$key]);
    }

    $option = reset($field['options']);
    $expectedValues = [
      'name' => 'rye grass',
      'label' => 'juicy and healthy',
      'amount' => 1,
      'weight' => 1,
      'membership_type_id' => $this->ids['MembershipType']['test'],
      'membership_num_terms' => 2,
      'is_default' => FALSE,
      'is_active' => TRUE,
      'financial_type_id' => '1',
      'non_deductible_amount' => 0,
      'visibility_id' => '1',
    ];
    foreach ($expectedValues as $key => $value) {
      $this->assertEquals($value, $option[$key]);
    }

  }

  /**
   * @param int|null $expected
   */
  protected function assertIsRecur(?int $expected): void {
    $isRecur = \Civi::$statics['CRM_Core_Payment_Dummy']['doPreApproval']['is_recur'] ?? NULL;
    $this->assertEquals($expected, $isRecur);
  }

  /**
   * Set the dummy processor to support preApproval.
   *
   * @throws \CRM_Core_Exception
   */
  protected function supportPreApproval(): void {
    $processors = CRM_Financial_BAO_PaymentProcessor::getAllPaymentProcessors('live');
    $cacheKey = 'CRM_Financial_BAO_Payment_Processor_live__' . CRM_Core_Config::domainID();
    foreach ($processors as $processor) {
      if ($processor['payment_processor_type'] === 'Dummy') {
        $processor['object']->setSupports(['PreApproval' => TRUE]);
      }
    }
    CRM_Utils_Cache::singleton()->set($cacheKey, $processors);
  }

}
