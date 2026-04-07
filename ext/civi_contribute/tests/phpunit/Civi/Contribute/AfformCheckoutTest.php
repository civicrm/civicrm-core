<?php

namespace Civi\Contribute;

use Civi\Api4\Afform;
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
    \Civi::dispatcher()->addListener('civi.checkout.options', function ($e) {
      $e->options['test_checkout_option'] = new TestCheckoutOption();
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
