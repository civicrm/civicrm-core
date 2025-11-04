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

namespace Civi\Api4\Import;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\UserJob;
use Civi\Api4\Utils\CoreUtil;
use Civi\BAO\Import;
use Civi\Core\Service\AutoService;
use CRM_Core_BAO_UserJob;
use CRM_Civiimport_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class ImportSpecProvider extends AutoService implements SpecProviderInterface {

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function modifySpec(RequestSpec $spec): void {
    $tableName = $spec->getEntityTableName();
    try {
      $columns = Import::getFieldsForTable($tableName);
    }
    catch (\CRM_Core_Exception $e) {
      // The api metadata may retain the expectation that this entity exists after the
      // table is deleted - & hence we get an error.
      return;
    }
    // Common fields
    $field = new FieldSpec('_id', $spec->getEntity(), 'Int');
    $field->setTitle(E::ts('Import row ID'));
    $field->setType('Field');
    $field->setInputType('Number');
    $field->setReadonly(TRUE);
    $field->setNullable(FALSE);
    $field->setColumnName('_id');
    $spec->addFieldSpec($field);

    $field = new FieldSpec('_status', $spec->getEntity(), 'String');
    $field->setTitle(E::ts('Import row status'));
    $field->setType('Field');
    $field->setInputType('Text');
    $field->setReadonly(TRUE);
    $field->setNullable(FALSE);
    $field->setColumnName('_status');
    $spec->addFieldSpec($field);

    $field = new FieldSpec('_status_message', $spec->getEntity(), 'String');
    $field->setTitle(E::ts('Import row message'));
    $field->setType('Field');
    $field->setInputType('Text');
    $field->setReadonly(TRUE);
    $field->setNullable(TRUE);
    $field->setColumnName('_status_message');
    $spec->addFieldSpec($field);

    [, $userJobID] = explode('_', $spec->getEntity(), 2);

    $userJobType = $this->getJobType($spec);
    $parser = new $userJobType['class']();
    $parser->setUserJobID($userJobID);

    foreach ($columns as $column) {
      $isInternalField = str_starts_with($column['name'], '_');
      $exists = $isInternalField && $spec->getFieldByName($column['name']);
      if ($exists) {
        continue;
      }
      $field = new FieldSpec($column['name'], $spec->getEntity(), 'String');
      $field->setTitle(ts('Import field') . ': ' . $column['label']);
      $field->setLabel($column['label']);
      $field->setType('Field');
      $field->setDataType($column['data_type']);
      $field->setReadonly($isInternalField);
      $field->setDescription(ts('Data being imported into the field.'));
      $field->setColumnName($column['name']);
      if ($column['name'] === '_entity_id') {
        try {
          $baseEntity = $parser->getBaseEntity();
          $field->setFkEntity($baseEntity);
          $field->setInputType('EntityRef');
          $field->setInputAttrs([
            'label' => CoreUtil::getInfoItem($baseEntity, 'title'),
          ]);
        }
        catch (\CRM_Core_Exception $e) {
          // Search display may have been deleted
        }
      }
      $spec->addFieldSpec($field);
    }

  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action): bool {
    return str_starts_with($entity, 'Import_');
  }

  /**
   * Get the user job type detail.
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getJobType(RequestSpec $spec): array {
    // CheckPermissions does not reach us here - so we will have to rely on earlier permission filters.
    [, $userJobID] = explode('_', $spec->getEntity(), 2);
    $userJob = UserJob::get(FALSE)
      ->addWhere('id', '=', $userJobID)
      ->addSelect('metadata', 'job_type', 'created_id')
      ->execute()
      ->first();
    return CRM_Core_BAO_UserJob::getType($userJob['job_type']);
  }

}
