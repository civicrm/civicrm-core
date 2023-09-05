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
class CustomValueSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    $idField = new FieldSpec('id', $spec->getEntity(), 'Integer');
    $idField->setType('Field');
    $idField->setInputType('Number');
    $idField->setColumnName('id');
    $idField->setNullable('false');
    $idField->setTitle(ts('Custom Value ID'));
    $idField->setReadonly(TRUE);
    $idField->setNullable(FALSE);
    $spec->addFieldSpec($idField);

    $entityField = new FieldSpec('entity_id', $spec->getEntity(), 'Integer');
    $entityField->setType('Field');
    $entityField->setColumnName('entity_id');
    $entityField->setTitle(ts('Entity ID'));
    $entityField->setLabel(ts('Contact'));
    $entityField->setRequired($action === 'create');
    $entityField->setFkEntity('Contact');
    $entityField->setReadonly(TRUE);
    $entityField->setNullable(FALSE);
    $entityField->setInputType('EntityRef');
    $spec->addFieldSpec($entityField);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return strstr($entity, 'Custom_');
  }

}
