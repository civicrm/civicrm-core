<?php

namespace Civi\Checkout;

use Civi\Afform\Event\AfformValidateEvent;
use CRM_Contribute_ExtensionUtil as E;

class PayLater extends CheckoutOption implements AfformCheckoutOptionInterface {

  public function getLabel(): string {
    return E::ts('Pay Later');
  }

  public function startCheckout(CheckoutSession $session): void {
    // FIXME: Contribution.update crashes if we dont pass
    // back the financial type id
    $financialTypeId = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $session->getContributionId())
      ->addSelect('financial_type_id')
      ->execute()->single()['financial_type_id'];

    \Civi\Api4\Contribution::update(FALSE)
      ->addWhere('id', '=', $session->getContributionId())
      ->addValue('is_pay_later', TRUE)
      ->addValue('financial_type_id', $financialTypeId)
      ->execute();

    $session->success();
  }

  public function validate(AfformValidateEvent $event): void {
    // no validation requirements for Pay Later
  }

  public function getAfformModule(): ?string {
    return NULL;
  }

  public function getAfformSettings(bool $testMode): array {
    return [
      // TODO: add a setting to make this user configurable
      'description' => E::ts('No payment will be taken now.'),
    ];
  }

}
