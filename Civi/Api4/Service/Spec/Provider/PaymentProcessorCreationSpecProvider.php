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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class PaymentProcessorCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * This runs for both create and get actions
   *
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    // Billing mode is copied across from the payment processor type field in the BAO::create function.
    $spec->getFieldByName('billing_mode')->setRequired(FALSE);

    $financial_account_id = new FieldSpec('financial_account_id', 'PaymentProcessor', 'Integer');
    $financial_account_id
      ->setTitle('Financial Account ID')
      ->setDescription('The financial account that this payment processor is linked to')
      ->setRequired(FALSE)
      ->setDefaultValue(\CRM_Financial_BAO_PaymentProcessor::getDefaultFinancialAccountID())
      ->setFkEntity('FinancialAccount');
    $spec->addFieldSpec($financial_account_id);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'PaymentProcessor' && in_array($action, ['create']);
  }

}
