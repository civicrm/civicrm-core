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
use Civi\BAO\Import;
use Civi\Core\Service\AutoService;
use CRM_Core_BAO_UserJob;

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
    $columns = Import::getFieldsForTable($tableName);
    $action = $spec->getAction();
    // CheckPermissions does not reach us here - so we will have to rely on earlier permission filters.
    $userJobID = substr($spec->getEntity(), (strpos($spec->getEntity(), '_') + 1));
    $userJob = UserJob::get(FALSE)->addWhere('id', '=', $userJobID)->addSelect('metadata', 'job_type', 'created_id')->execute()->first();

    foreach ($columns as $column) {
      $isInternalField = strpos($column['name'], '_') === 0;
      $exists = $isInternalField && $spec->getFieldByName($column['name']);
      if ($exists) {
        continue;
      }
      $field = new FieldSpec($column['name'], $spec->getEntity(), 'String');
      $field->setTitle(ts('Import field') . ':' . $column['label']);
      $field->setLabel($column['label']);
      $field->setType('Field');
      $field->setReadonly($isInternalField);
      $field->setDescription(ts('Data being imported into the field.'));
      $field->setColumnName($column['name']);
      if ($column['name'] === '_entity_id') {
        $jobTypes = CRM_Core_BAO_UserJob::getTypes();
        foreach ($jobTypes as $jobType) {
          if ($userJob['job_type'] === $jobType['id'] && $jobType['entity']) {
            $field->setFkEntity($jobType['entity']);
            $field->setInputType('EntityRef');
          }
        }
      }
      $spec->addFieldSpec($field);
    }

  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action): bool {
    return strpos($entity, 'Import_') === 0;
  }

}
