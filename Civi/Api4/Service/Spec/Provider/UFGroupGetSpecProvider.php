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
class UFGroupGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('group_type_label', $spec->getEntity(), 'Array');
    $field->setLabel(ts('Group Type Label'))
      ->setTitle(ts('Group Type Label'))
      ->setColumnName('group_type')
      ->setDescription(ts('Formatted group type labels.'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->addOutputFormatter([__CLASS__, 'formatGroupTypeLabel']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies(string $entity, string $action): bool {
    return $entity === 'UFGroup' && $action === 'get';
  }

  /**
   * Format group_type string into an array of human-readable labels.
   *
   * @param mixed $value
   */
  public static function formatGroupTypeLabel(&$value): void {
    if ($value) {
      $extracted = \CRM_Core_BAO_UFGroup::extractGroupTypes($value);
      $value = \CRM_Core_BAO_UFGroup::formatGroupTypeLabels($extracted);
    }
    else {
      $value = [];
    }
  }

}
