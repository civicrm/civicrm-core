<?php

namespace Civi\Contribute;

use Civi\Api4\Afform;
use Civi\Api4\Contribution;
use Civi\Api4\PaymentProcessor;
use Civi\Checkout\CheckoutSession;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test Afform - Checkout integration
 *
 * @group headless
 */
class AfformCheckoutTest extends TestCase implements HeadlessInterface {

  protected $afformContributionSettingBackup;

  protected TestCheckoutOption $checkoutOption;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->install(['org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    $this->afformContributionSettingBackup = \Civi::settings()->get('contribute_enable_afform_contributions');
    \Civi::settings()->set('contribute_enable_afform_contributions', TRUE);

    // add a listener with our test checkout option
    // (payment integrations should do similar with a real CheckoutOptionInterface implemenation)
    $this->checkoutOption = new TestCheckoutOption();
    \Civi::dispatcher()->addListener('civi.checkout.options', function ($e) {
      $e->options['test_checkout_option'] = $this->checkoutOption;
    });

    $layout = <<<HTML
    <af-form ctrl="afform">
      <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
      <af-entity type="Contribution" name="Contribution1" label="Contribution 1" data="{contact_id: 'Individual1', financial_type_id: 1, currency: 'USD'}" actions="{create: true, update: false}" security="FBAC" />
      <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
        <div class="af-container">
          <af-field name="first_name" />
          <af-field name="last_name" />
        </div>
      </fieldset>
      <fieldset af-fieldset="Contribution1" class="af-container" af-title="Contribution 1">
        <div class="af-container">
          <!-- standard field for Contribution -->
          <af-field name="source" />
          <!-- price field for Contribution -->
          <af-field name="default_contribution_amount.contribution_amount" />
          <af-field name="checkout_option" />
          <af-field name="recur_period" />
        </div>
      </fieldset>
    </af-form>
    HTML;

    Afform::save(FALSE)
      ->addRecord([
        'name' => 'testAfformCheckout',
        'layout' => $layout,
        'title' => 'Afform Checkout Test',
      ])
      ->setLayoutFormat('html')
      ->execute();

  }

  public function tearDown(): void {
    // \Civi\Api4\Afform::delete(FALSE)->addWhere('name', '=', 'testAfformCheckout')->execute();

    \Civi::settings()->set('contribute_enable_afform_contributions', $this->afformContributionSettingBackup);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testStartCheckout(): void {
    $response = Afform::submit(FALSE)
      ->setName('testAfformCheckout')
      ->setValues([
        'Individual1' => [
          [
            'fields' => [
              'first_name' => 'Test',
              'last_name' => 'Contact',
            ],
          ],
        ],
        'Contribution1' => [
          [
            'fields' => [
              'source' => 'testContributionCreate',
              // free text input
              'default_contribution_amount.contribution_amount' => 5,
              'checkout_option' => 'test_checkout_option',
            ],
          ],
        ],
      ])
      ->execute()
      ->single();

    // our test CheckoutOption just spits back
    $token = $response['test_session_token'];

    // should be able to restore the CheckoutSession from the token
    $session = CheckoutSession::restoreFromToken($token);

    // we set the pending url in startCheckout
    // the session should be pending so that should be our nextUrl
    $nextUrl = $session->getNextUrl();
    $this->assertEquals(TRUE, \str_starts_with($nextUrl, 'https://now.go.to'));
  }

  /**
   * recur_period on the submitted Contribution should produce a ContributionRecur
   * as part of the same Order, linked to the Contribution and sharing its values.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRecurringContributionCreate(): void {
    $this->checkoutOption->paymentProcessorID = $this->getPaymentProcessorID();

    $response = Afform::submit(FALSE)
      ->setName('testAfformCheckout')
      ->setValues([
        'Individual1' => [
          [
            'fields' => [
              'first_name' => 'Test',
              'last_name' => 'Recur',
            ],
          ],
        ],
        'Contribution1' => [
          [
            'fields' => [
              'source' => 'testRecurringContributionCreate',
              'default_contribution_amount.contribution_amount' => 5,
              'checkout_option' => 'test_checkout_option',
              'recur_period' => 'monthly',
            ],
          ],
        ],
      ])
      ->execute();

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $response->single()['Contribution1'][0]['id'])
      ->addSelect(
        'contact_id',
        'total_amount',
        'currency',
        'is_test',
        'financial_type_id',
        'contribution_recur_id',
        'contribution_recur_id.contact_id',
        'contribution_recur_id.amount',
        'contribution_recur_id.currency',
        'contribution_recur_id.is_test',
        'contribution_recur_id.financial_type_id',
        'contribution_recur_id.frequency_unit',
        'contribution_recur_id.frequency_interval',
        'contribution_recur_id.next_sched_contribution_date',
        'contribution_recur_id.payment_processor_id'
      )
      ->execute()
      ->single();

    // The Order created both records and linked them.
    $this->assertNotEmpty($contribution['contribution_recur_id']);
    $this->assertEquals($contribution['contact_id'], $contribution['contribution_recur_id.contact_id']);
    $this->assertEquals($contribution['total_amount'], $contribution['contribution_recur_id.amount']);
    $this->assertEquals($contribution['currency'], $contribution['contribution_recur_id.currency']);
    $this->assertEquals($contribution['is_test'], $contribution['contribution_recur_id.is_test']);
    $this->assertEquals($contribution['financial_type_id'], $contribution['contribution_recur_id.financial_type_id']);

    // recur_period 'monthly' unpacks to a monthly schedule with a next date.
    $this->assertEquals('month', $contribution['contribution_recur_id.frequency_unit']);
    $this->assertEquals(1, $contribution['contribution_recur_id.frequency_interval']);
    $this->assertNotEmpty($contribution['contribution_recur_id.next_sched_contribution_date']);

    // The processor comes from the selected checkout option.
    $this->assertEquals($this->checkoutOption->paymentProcessorID, $contribution['contribution_recur_id.payment_processor_id']);
  }

  /**
   * Without recur_period the same form should create a one-off Contribution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNonRecurringContributionCreate(): void {
    $response = Afform::submit(FALSE)
      ->setName('testAfformCheckout')
      ->setValues([
        'Individual1' => [
          [
            'fields' => [
              'first_name' => 'Test',
              'last_name' => 'OneOff',
            ],
          ],
        ],
        'Contribution1' => [
          [
            'fields' => [
              'source' => 'testNonRecurringContributionCreate',
              'default_contribution_amount.contribution_amount' => 5,
              'checkout_option' => 'test_checkout_option',
            ],
          ],
        ],
      ])
      ->execute();

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $response->single()['Contribution1'][0]['id'])
      ->addSelect('contribution_recur_id')
      ->execute()
      ->single();
    $this->assertEmpty($contribution['contribution_recur_id']);
  }

  private function getPaymentProcessorID(): int {
    $existing = PaymentProcessor::get(FALSE)
      ->addWhere('name', '=', 'test_processor')
      ->addSelect('id')
      ->execute()
      ->first();
    if ($existing) {
      return $existing['id'];
    }
    return PaymentProcessor::create(FALSE)
      ->addValue('name', 'test_processor')
      ->addValue('title', 'Test Processor')
      ->addValue('frontend_title', 'Test Processor')
      ->addValue('payment_processor_type_id:name', 'Dummy')
      ->addValue('class_name', 'Payment_Dummy')
      ->addValue('domain_id', \CRM_Core_Config::domainID())
      ->addValue('billing_mode', 1)
      ->addValue('is_active', TRUE)
      ->addValue('is_recur', TRUE)
      ->execute()
      ->single()['id'];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCheckoutOptionValidate(): void {
    try {
      $response = Afform::submit(FALSE)
        ->setName('testAfformCheckout')
        ->setValues([
          'Individual1' => [
            [
              'fields' => [
                'first_name' => 'Test',
                'last_name' => 'Contact',
              ],
            ],
          ],
          'Contribution1' => [
            [
              'fields' => [
                'source' => 'testContributionCreate',
                'default_contribution_amount.contribution_amount' => 50,
                'checkout_option' => 'test_checkout_option',
              ],
            ],
          ],
        ])
        ->execute();

      $this->fail('Afform::validate should have failed');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals(TRUE, \str_contains($e->getMessage(), 'No payments over 10 USD'));
    }

  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testInvalidCheckoutOption(): void {
    try {
      $response = Afform::submit(FALSE)
        ->setName('testAfformCheckout')
        ->setValues([
          'Individual1' => [
            [
              'fields' => [
                'first_name' => 'Test',
                'last_name' => 'Contact',
              ],
            ],
          ],
          'Contribution1' => [
            [
              'fields' => [
                'source' => 'testContributionCreate',
                'default_contribution_amount.contribution_amount' => 5,
                'checkout_option' => 'invalid_checkout_option',
              ],
            ],
          ],
        ])
        ->execute();

      $this->fail('Afform::submit should have failed because we passed an invalid checkout option');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals(TRUE, \str_contains($e->getMessage(), 'No CheckoutOption found with name: invalid_checkout_option'));
    }

  }

}
