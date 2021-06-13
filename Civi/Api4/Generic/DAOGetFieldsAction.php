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

namespace Civi\Api4\Generic;

/**
 * @inheritDoc
 * @method bool getIncludeCustom()
 */
class DAOGetFieldsAction extends BasicGetFieldsAction {

  /**
   * Get fields for a DAO-based entity.
   *
   * @return array
   */
  protected function getRecords() {
    $fieldsToGet = $this->_itemsToGet('name');
    $typesToGet = $this->_itemsToGet('type');
    /** @var \Civi\Api4\Service\Spec\SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    $includeCustom = TRUE;
    if ($typesToGet) {
      $includeCustom = in_array('Custom', $typesToGet, TRUE);
    }
    elseif ($fieldsToGet) {
      // Any fields name with a dot in it is either custom or an implicit join
      $includeCustom = strpos(implode('', $fieldsToGet), '.') !== FALSE;
    }
    $spec = $gatherer->getSpec($this->getEntityName(), $this->getAction(), $includeCustom, $this->values);
    $fields = $this->specToArray($spec->getFields($fieldsToGet));
    foreach ($fieldsToGet ?? [] as $fieldName) {
      if (empty($fields[$fieldName]) && strpos($fieldName, '.') !== FALSE) {
        $fkField = $this->getFkFieldSpec($fieldName, $fields);
        if ($fkField) {
          $fkField['name'] = $fieldName;
          $fields[] = $fkField;
        }
      }
    }
    return $fields;
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec[] $fields
   *
   * @return array
   */
  protected function specToArray($fields) {
    $fieldArray = [];

    foreach ($fields as $field) {
      if ($this->loadOptions) {
        $field->getOptions($this->values, $this->loadOptions, $this->checkPermissions);
      }
      $fieldArray[$field->getName()] = $field->toArray();
    }

    return $fieldArray;
  }

  /**
   * @param string $fieldName
   * @param array $fields
   * @return array|null
   * @throws \API_Exception
   */
  private function getFkFieldSpec($fieldName, $fields) {
    $fieldPath = explode('.', $fieldName);
    // Search for the first segment alone plus the first and second
    // No field in the schema contains more than one dot in its name.
    $searchPaths = [$fieldPath[0], $fieldPath[0] . '.' . $fieldPath[1]];
    $fkFieldName = array_intersect($searchPaths, array_keys($fields))[0] ?? NULL;
    if ($fkFieldName && !empty($fields[$fkFieldName]['fk_entity'])) {
      $newFieldName = substr($fieldName, 1 + strlen($fkFieldName));
      return civicrm_api4($fields[$fkFieldName]['fk_entity'], 'getFields', [
        'checkPermissions' => $this->checkPermissions,
        'where' => [['name', '=', $newFieldName]],
        'loadOptions' => $this->loadOptions,
        'action' => $this->action,
      ])->first();
    }
  }

  public function fields() {
    $fields = parent::fields();
    $fields[] = [
      'name' => 'help_pre',
      'data_type' => 'String',
    ];
    $fields[] = [
      'name' => 'help_post',
      'data_type' => 'String',
    ];
    $fields[] = [
      'name' => 'column_name',
      'data_type' => 'String',
    ];
    $fields[] = [
      'name' => 'custom_field_id',
      'data_type' => 'Integer',
    ];
    $fields[] = [
      'name' => 'custom_group_id',
      'data_type' => 'Integer',
    ];
    $fields[] = [
      'name' => 'sql_filters',
      'data_type' => 'Array',
      '@internal' => TRUE,
    ];
    $fields[] = [
      'name' => 'sql_renderer',
      'data_type' => 'Array',
      '@internal' => TRUE,
    ];
    return $fields;
  }

}
