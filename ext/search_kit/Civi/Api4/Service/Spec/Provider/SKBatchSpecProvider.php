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
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;
use CRM_Search_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class SKBatchSpecProvider extends AutoService implements SpecProviderInterface {

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function modifySpec(RequestSpec $spec): void {
    $tableName = $spec->getEntityTableName();

    $select = \CRM_Utils_SQL_Select::from('civicrm_user_job')
      ->where('job_type = "search_batch_import"')
      ->where('metadata LIKE \'%"table_name":"' . $tableName . '"%\'')
      ->where('metadata LIKE \'%"saved_search":"%\'')
      ->where('metadata LIKE \'%"search_display":"%\'')
      ->where('metadata LIKE \'%"column_specs":%\'')
      ->select('metadata');

    $userJobs = \CRM_Core_DAO::executeQuery($select->toSQL(), [], TRUE, NULL, FALSE, FALSE)->fetchAll();
    if (!$userJobs) {
      return;
    }

    $metadata = json_decode($userJobs[0]['metadata'], TRUE);

    // By default, CiviImport just sets every field to a text input (@see ImportSpecProvider)
    // To facilitate batch-entry in SearchKit, set more specifics.
    // `column_specs` are set by SearchDisplay::createBatch
    foreach ($metadata['DataSource']['column_specs'] as $name => $column) {
      // Depending on loading-order, spec may have already been defined by ImportSpecProvider
      $field = $spec->getFieldByName($name) ?: new FieldSpec($name, $spec->getEntity());
      $field->setDataType($column['data_type']);
      $field->setTitle(ts('Import field') . ': ' . $column['label']);
      $field->setLabel($column['label']);
      $field->setType('Field');
      $field->setColumnName($column['name']);
      if (!empty($column['options'])) {
        $field->setSuffixes($column['suffixes']);
        if (is_array($column['options'])) {
          $field->setOptions($column['options']);
        }
        else {
          $field->setOptionsCallback([__CLASS__, 'getOptionsForBatchEntityField'], $column);
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action): bool {
    return str_starts_with($entity, 'Import_');
  }

  /**
   * Callback function retrieve options from original field.
   *
   * @param array $field
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @param array $params
   * @return array|false
   */
  public static function getOptionsForBatchEntityField($field, $values, $returnFormat, $checkPermissions, $params) {
    return civicrm_api4($params['original_field_entity'], 'getFields', [
      'where' => [['name', '=', $params['original_field_name']]],
      'loadOptions' => $returnFormat,
      'checkPermissions' => FALSE,
    ])->first()['options'];
  }

}
