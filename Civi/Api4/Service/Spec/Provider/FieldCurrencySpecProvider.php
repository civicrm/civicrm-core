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

/**
 * @service
 * @internal
 */
class FieldCurrencySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Generic create spec function to set sensible defaults for any entity with a "currency" field.
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $currencyField = $spec->getFieldByName('currency');
    if ($currencyField && $currencyField->isRequired()) {
      $currencyField->setRequired(FALSE)->setDefaultValue(\CRM_Core_Config::singleton()->defaultCurrency);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'create';
  }

}
