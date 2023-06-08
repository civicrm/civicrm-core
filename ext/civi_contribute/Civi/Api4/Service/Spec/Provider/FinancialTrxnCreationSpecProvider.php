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
class FinancialTrxnCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Modify the api spec.
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->getFieldByName('to_financial_account_id')->setRequired(TRUE);
    $field = new FieldSpec('entity_id', 'FinancialTrxn', 'Integer');
    $field->setRequired(TRUE);
    $field->setTitle(ts('Related entity (eg. contribution) id'));
    $spec->addFieldSpec($field);
    $spec->getFieldByName('status_id')->setDefaultValue(1);
    $spec->getFieldByName('total_amount')->setRequired(TRUE);
  }

  /**
   * Specify the entity & action it applies to.
   *
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return $entity === 'FinancialTrxn' && $action === 'create';
  }

}
