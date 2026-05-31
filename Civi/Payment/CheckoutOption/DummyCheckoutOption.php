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

namespace Civi\Payment\CheckoutOption;

use Civi\Afform\Event\AfformValidateEvent;
use Civi\Checkout\AfformCheckoutOptionInterface;
use Civi\Checkout\CheckoutOption;
use Civi\Checkout\CheckoutSession;

/**
 * CheckoutOption for the Dummy payment processor.
 *
 * For testing FormBuilder payment flows. Provides a simple checkout
 * widget that immediately processes a payment on confirmation.
 */
class DummyCheckoutOption extends CheckoutOption implements AfformCheckoutOptionInterface {

  protected array $liveConnection;
  protected array $testConnection;

  public function __construct(array $liveConnection, array $testConnection) {
    $this->liveConnection = $liveConnection;
    $this->testConnection = $testConnection;
  }

  protected function getConnectionDetails(bool $testMode = FALSE): array {
    return $testMode ? $this->testConnection : $this->liveConnection;
  }

  public function getLabel(): string {
    return $this->getConnectionDetails()['title'];
  }

  public function getFrontendLabel(): string {
    return $this->getConnectionDetails()['frontend_title'];
  }

  public function getPaymentProcessorId(bool $testMode = FALSE): ?int {
    return $this->getConnectionDetails($testMode)['id'];
  }

  public function getAfformModule(): ?string {
    return 'afDummy';
  }

  public function getAfformSettings(bool $testMode): array {
    return [
      'template' => '~/afDummy/dummy_checkout.html',
      'fields' => $this->getBillingFields(),
    ];
  }

  private function getBillingFields(): array {
    $countryOptions = array_map(
      fn($id, $label) => ['id' => $id, 'label' => $label],
      array_keys(\CRM_Core_PseudoConstant::country()),
      array_values(\CRM_Core_PseudoConstant::country())
    );

    return [
      ['name' => 'credit_card_number', 'htmlType' => 'text', 'title' => ts('Card Number'), 'is_required' => TRUE],
      ['name' => 'credit_card_exp_date', 'htmlType' => 'date', 'title' => ts('Expiry Date'), 'is_required' => TRUE],
      ['name' => 'cvv2', 'htmlType' => 'text', 'title' => ts('Security Code'), 'is_required' => TRUE],
      ['name' => 'billing_first_name', 'htmlType' => 'text', 'title' => ts('First Name'), 'is_required' => TRUE],
      ['name' => 'billing_last_name', 'htmlType' => 'text', 'title' => ts('Last Name'), 'is_required' => TRUE],
      ['name' => 'billing_street_address', 'htmlType' => 'text', 'title' => ts('Street Address'), 'is_required' => TRUE],
      ['name' => 'billing_city', 'htmlType' => 'text', 'title' => ts('City'), 'is_required' => TRUE],
      ['name' => 'billing_postal_code', 'htmlType' => 'text', 'title' => ts('Postal Code'), 'is_required' => TRUE],
      ['name' => 'billing_country_id-billing', 'htmlType' => 'select', 'title' => ts('Country'), 'is_required' => TRUE, 'options' => $countryOptions],
      ['name' => 'billing_state_province_id-billing', 'htmlType' => 'chainSelect', 'title' => ts('State/Province'), 'is_required' => FALSE],
    ];
  }

  public function validate(AfformValidateEvent $event): void {
  }

  /**
   * Provide the landing URL to the checkout widget so it can redirect
   * back to complete the checkout after the user confirms.
   */
  public function startCheckout(CheckoutSession $session): void {
    $session->setResponseItem('dummy_checkout', [
      'landing_url' => $session->getLandingUrl(),
    ]);
    $session->setResponseItem('message', FALSE);
  }

  /**
   * Called when the user returns from the checkout widget.
   * Generates a transaction ID (mirroring CRM_Core_Payment_Dummy) and records the payment.
   */
  public function continueCheckout(CheckoutSession $session): void {
    $mode = $session->isTestMode() ? 'test' : 'live';
    $last = \CRM_Core_DAO::singleValueQuery("SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE '{$mode}_%'") ?? '';
    $last = str_replace($mode, '', $last);
    $trxnId = $mode . '_' . ((int) $last + 1) . '_' . uniqid();

    $session
      ->setTransactionId($trxnId)
      ->success()
      ->createPayment();
  }

}
