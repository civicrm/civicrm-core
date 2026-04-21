<?php

namespace Civi\Contribute;

use Civi\Checkout\CheckoutOptionInterface;
use Civi\Checkout\CheckoutSession;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test Contribution handling in Afform
 *
 * @group headless
 */
class CheckoutTest extends TestCase implements HeadlessInterface {

  protected $afformContributionSettingBackup;

  protected $contactId;

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
      // TODO: if paypal moved to civicrm-core, then include and test interaction
      // ->install(['paypal'])
      ->apply();
  }

  public function setUp(): void {
    $this->afformContributionSettingBackup = \Civi::settings()->get('contribute_enable_afform_contributions');
    \Civi::settings()->set('contribute_enable_afform_contributions', TRUE);

    $this->contactId = \Civi\Api4\Contact::create(FALSE)
      ->addValue('first_name', 'Test')
      ->addValue('last_name', 'Contact')
      ->execute()
      ->single()['id'];
  }

  public function tearDown(): void {
    \Civi::settings()->set('contribute_enable_afform_contributions', $this->afformContributionSettingBackup);

    \Civi\Api4\Contribution::delete(FALSE)
      ->addWhere('contact_id', '=', $this->contactId)
      ->execute();

    \Civi\Api4\Contact::delete(FALSE)
      ->addWhere('id', '=', $this->contactId)
      ->setUseTrash(FALSE)
      ->execute();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCheckoutOptionGathering(): void {
    $options = \Civi::service('civi.checkout')->getOptions();

    // we should have at least the PayLater option
    $this->assertEquals(TRUE, !empty($options));

    // the pay later option should be a checkout option interface
    foreach ($options as $name => $option) {

      // all options should have name keys
      $this->assertEquals(TRUE, is_string($name));
      // all options should implement CheckoutOptionInterface
      $this->assertEquals(TRUE, is_a($option, CheckoutOptionInterface::class));
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCheckoutSession(): void {
    $contribution = $this->createPendingContribution();

    // note: pay_later option should always be available
    $checkoutSession = new CheckoutSession($contribution['id'], 'pay_later');
    $checkoutSession->setSuccessUrl("https://thank.you");

    // check the session is pending
    $this->assertEquals(CheckoutSession::STATUS_PENDING, $checkoutSession->getStatus());

    $checkoutSession->startCheckout();

    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contribution['id'])
      ->addSelect('contribution_status_id:name', 'is_pay_later')
      ->execute()
      ->first();

    // check the contribution is updated
    $this->assertEquals('Pending', $contribution['contribution_status_id:name']);
    $this->assertEquals(TRUE, $contribution['is_pay_later']);

    // check the session is successful
    $this->assertEquals(CheckoutSession::STATUS_SUCCESS, $checkoutSession->getStatus());
    // check the session tells us to go to the thank you page now
    $this->assertEquals("https://thank.you", $checkoutSession->getNextUrl());

    // we should be able to tokenise the session
    $token = $checkoutSession->tokenise();

    // and then call continueCheckout api
    $result = \Civi\Api4\Contribution::continueCheckout(FALSE)
      ->setToken($token)
      ->execute();

    // and get the status back
    $this->assertEquals($result['redirect'], $checkoutSession->getNextUrl());
  }

  protected function createPendingContribution(): array {
    return \Civi\Api4\Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->contactId,
        'financial_type_id' => 1,
      ])
      ->addLineItem([
        'unit_price' => 10,
        'qty' => 1,
      ])
      ->execute()
      ->first();
  }

}
