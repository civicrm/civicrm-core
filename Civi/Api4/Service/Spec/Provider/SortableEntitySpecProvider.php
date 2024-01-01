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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class SortableEntitySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Adds fields to expose the join created by:
   * @see \Civi\Api4\Event\Subscriber\SortableEntitySchemaMapSubscriber
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $entityName = $spec->getEntity();
    $field = (new FieldSpec("previous", $entityName, 'Integer'))
      ->setTitle(ts('Previous %1', [1 => CoreUtil::getInfoItem('title', $entityName)]))
      ->setColumnName('id')
      ->setFkEntity($entityName)
      ->setDescription(ts('Item that comes before this one in the sorted group'))
      ->setSqlRenderer(['\Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
    $spec->addFieldSpec($field);

    $field = (new FieldSpec("next", $entityName, 'Integer'))
      ->setTitle(ts('Next %1', [1 => CoreUtil::getInfoItem('title', $entityName)]))
      ->setColumnName('id')
      ->setFkEntity($entityName)
      ->setDescription(ts('Item that comes after this one in the sorted group'))
      ->setSqlRenderer(['\Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return CoreUtil::isType($entity, 'SortableEntity');
  }

}
