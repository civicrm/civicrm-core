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
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class HierarchicalEntitySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Generic create spec function applies to all SortableEntity types.
   * Disables required 'weight' field because that's auto-managed.
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('_depth', $spec->getEntity(), 'Integer');
    $field->setLabel(ts('Depth'))
      ->setTitle(ts('Depth'))
      ->setColumnName('id')
      ->setInputType('Number')
      ->setDescription(ts('Depth in the nested hierarchy'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'getDepth']);
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'get' && CoreUtil::isType($entity, 'HierarchicalEntity');
  }

  /**
   * Generate SQL for _depth field
   * @param array $field
   * @return string
   */
  public static function getDepth(array $field): string {
    return "0";
  }

}
