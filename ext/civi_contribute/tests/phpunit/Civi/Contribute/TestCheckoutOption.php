<?php

namespace Civi\Contribute;

use Civi\Checkout\AfformCheckoutOptionInterface;
use Civi\Checkout\CheckoutOption;
use Civi\Checkout\CheckoutSession;

class TestCheckoutOption extends CheckoutOption implements AfformCheckoutOptionInterface {

  public function getLabel(): string {
    return 'TEST';
  }

  public function startCheckout(CheckoutSession $session): void {
    $session->setPendingUrl("https://now.go.to/{$session->getContributionId()}");
    $session->setResponseItem('test_session_token', $session->tokenise());
  }

  public function validate($e): void {
    $submittedValues = $e->getSubmittedValues();

    $contributionAmount = $submittedValues['Contribution1'][0]['fields']['default_contribution_amount.contribution_amount'];
    $currency = $submittedValues['Contribution1'][0]['fields']['currency'];

    if ($currency === 'USD' && $contributionAmount > 10) {
      $e->setError('No payments over 10 USD');
    }
  }

  public function getAfformModule(): ?string {
    return NULL;
  }

  public function getAfformSettings(bool $testMode): array {
    return [];
  }

}
