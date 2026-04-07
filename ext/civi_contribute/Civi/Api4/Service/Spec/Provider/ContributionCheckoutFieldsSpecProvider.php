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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Service\Spec\FieldSpec;

use CRM_Contribute_ExtensionUtil as E;

/**
 * Class ContributionCheckoutFieldsSpecProvider
 *
 * @package Civi\Api4\Service\Spec\Provider
 * @service
 * @internal
 */
class ContributionCheckoutFieldsSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Note: using create here doesn't block using these fields with an existing contribution
   * on afform because afform bases field options on 'create'
   *
   * @inheritDoc
   */
  public function applies($entity, $action) {
    if (!\Civi::settings()->get('contribute_enable_afform_contributions')) {
      return FALSE;
    }
    return ($action === 'create' && $entity === 'Contribution');
  }

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $this->addCheckoutOptionField($spec);
    $this->addCheckoutParamsField($spec);
    $this->addRecurPeriodField($spec);
  }

  /**
   * Add selector for checkout option
   */
  protected function addCheckoutOptionField(RequestSpec $spec): void {
    $checkoutOption = new FieldSpec('checkout_option', $spec->getEntity(), 'String');
    $checkoutOption->setLabel(E::ts('Checkout Option'))
      ->setDescription(E::ts('Checkout option to use for Contribution payment.'))
      ->setInputType('Select')
      ->setOptionsCallback([__CLASS__, 'getCheckoutOptions'])
      ->setType('Extra');

    $spec->addFieldSpec($checkoutOption);
  }

  /**
   * Add pseudofield to collect additional parameters for checkout
   */
  protected function addCheckoutParamsField(RequestSpec $spec): void {
    $checkoutParams = new FieldSpec('checkout_params', $spec->getEntity(), 'String');
    $checkoutParams->setLabel(E::ts('Checkout Details'))
      ->setInputType('CheckoutBlock')
      ->setSerialize('JSON')
      ->setType('Extra');

    $spec->addFieldSpec($checkoutParams);
  }

  /**
   * Add field to set if contribution should recur.
   *
   * NOTE: this is intended to be a flat field. In the future it could
   * be hookable to provide custom options like Quarterly / Every 2 months / Every 35 days
   * but given those use cases are very much the exception rather the rule,
   * I am deliberately avoiding having period_unit and period_qty fields
   * which cause confusion for users and developers alike
   */
  protected function addRecurPeriodField(RequestSpec $spec): void {
    $recurPeriod = new FieldSpec('recur_period', $spec->getEntity(), 'String');
    $recurPeriod->setLabel(E::ts('Recur Period'))
      ->setInputType('Select')
      ->setDefaultValue(NULL)
      ->setOptions([
        '' => E::ts('Does not recur'),
        'monthly' => E::ts('Every month'),
        'yearly' => E::ts('Every year'),
      ])
      ->setType('Extra');

    $spec->addFieldSpec($recurPeriod);
  }

  public static function getCheckoutOptions(): array {
    $fieldOptions = [];

    $checkoutOptions = \Civi::service('civi.checkout')->getOptions();

    foreach ($checkoutOptions as $name => $option) {

      $fieldOptions[] = [
        'id' => $name,
        'name' => $name,
        'label' => $option->getFrontendLabel(),
        //'description' => $option['description'],
        'icon' => 'fa-money',
      ];
    }

    return $fieldOptions;
  }

}
