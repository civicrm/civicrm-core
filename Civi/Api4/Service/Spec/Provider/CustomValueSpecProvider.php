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
    $idField->setNullable(FALSE);
    $idField->setTitle(ts('Custom Value ID'));
    $idField->setReadonly(TRUE);
    $idField->setNullable(FALSE);
    $spec->addFieldSpec($idField);

    // Check which entity this group extends
    $groupName = CoreUtil::getCustomGroupName($spec->getEntity());
    $baseEntity = \CRM_Core_BAO_CustomGroup::getEntityForGroup($groupName);
    // Lookup base entity info using DAO methods not CoreUtil to avoid early-bootstrap issues
    $baseEntityDao = \CRM_Core_DAO_AllCoreTables::getDAONameForEntity($baseEntity);
    $baseEntityTitle = $baseEntityDao ? $baseEntityDao::getEntityTitle() : $baseEntity;

    $entityField = new FieldSpec('entity_id', $spec->getEntity(), 'Integer');
    $entityField->setType('Field');
    $entityField->setColumnName('entity_id');
    $entityField->setTitle(ts('Entity ID'));
    $entityField->setLabel($baseEntityTitle);
    $entityField->setRequired($action === 'create');
    $entityField->setFkEntity($baseEntity);
    $entityField->setReadonly(TRUE);
    $entityField->setNullable(FALSE);
    $entityField->setInputType('EntityRef');
    $spec->addFieldSpec($entityField);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return str_starts_with($entity, 'Custom_');
  }

}
