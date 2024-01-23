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
class SKEntitySpecProvider extends AutoService implements SpecProviderInterface {

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function modifySpec(RequestSpec $spec): void {
    $entityName = $spec->getEntity();
    foreach (_getSearchKitEntityDisplays() as $entityDisplay) {
      if ($entityDisplay['entityName'] !== $entityName) {
        continue;
      }
      // Primary key field
      $field = new FieldSpec('_row', $entityName, 'Int');
      $field->setTitle(E::ts('Row'));
      $field->setLabel(E::ts('Row'));
      $field->setType('Field');
      $field->setDescription('Search result row number');
      $field->setColumnName('_row');
      $spec->addFieldSpec($field);

      foreach ($entityDisplay['settings']['columns'] as $column) {
        $field = new FieldSpec($column['spec']['name'], $entityName, $column['spec']['data_type']);
        $field->setTitle($column['label']);
        $field->setLabel($column['label']);
        $field->setType('Field');
        $field->setFkEntity($column['spec']['fk_entity']);
        $field->setColumnName($column['spec']['name']);
        $field->setSuffixes($column['spec']['suffixes']);
        if (!empty($column['spec']['options'])) {
          if (is_array($column['spec']['options'])) {
            $field->setOptions($column['spec']['options']);
          }
          else {
            $field->setOptionsCallback([__CLASS__, 'getOptionsForSKEntityField'], $column['spec']);
          }
        }
        $spec->addFieldSpec($field);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action): bool {
    return strpos($entity, 'SK_') === 0;
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
  public static function getOptionsForSKEntityField($field, $values, $returnFormat, $checkPermissions, $params) {
    return civicrm_api4($params['original_field_entity'], 'getFields', [
      'where' => [['name', '=', $params['original_field_name']]],
      'loadOptions' => $returnFormat,
      'checkPermissions' => FALSE,
    ])->first()['options'];
  }

}
