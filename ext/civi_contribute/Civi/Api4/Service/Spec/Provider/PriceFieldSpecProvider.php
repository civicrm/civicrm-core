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
use Civi\Contribute\Utils\PriceFieldUtils;

use CRM_Contribute_ExtensionUtil as E;

/**
 * Class PriceFieldSpecProvider
 *
 * @package Civi\Api4\Service\Spec\Provider
 * @service
 * @internal
 */
class PriceFieldSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $fields = PriceFieldUtils::getPriceFieldsForEntity($spec->getEntity());

    foreach ($fields as $field) {
      $fieldSpec = new FieldSpec($field['name'], $spec->getEntity(), $field['data_type']);
      $fieldSpec->setLabel(E::ts("Contribution Amount: %1", [1 => $field['label']]))
        ->setInputType($field['input_type'])
        ->setType('Extra');

      if ($field['options'] ?? NULL) {
        $fieldSpec->setOptions($field['options']);
      }

      $spec->addFieldSpec($fieldSpec);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    if (!\Civi::settings()->get('contribute_enable_afform_contributions')) {
      return FALSE;
    }
    return ($action === 'create' &&
    ($entity === 'Contribution' || in_array($entity, PriceFieldUtils::getEnabledEntities())));
  }

}
