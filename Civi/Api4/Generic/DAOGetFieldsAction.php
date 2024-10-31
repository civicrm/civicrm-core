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

use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;

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
    // Force-set values supplied by entity definition
    // e.g. if this is a ContactType pseudo-entity, set `contact_type` value which is used by the following:
    // @see \Civi\Api4\Service\Spec\Provider\ContactGetSpecProvider
    // @see \Civi\Api4\Service\Spec\SpecGatherer::addDAOFields
    $presetValues = CoreUtil::getInfoItem($this->getEntityName(), 'where') ?? [];
    foreach ($presetValues as $presetField => $presetValue) {
      $this->addValue($presetField, $presetValue);
    }
    /** @var \Civi\Api4\Service\Spec\SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    $this->formatValues();
    $fields = $gatherer->getAllFields($this->getEntityName(), $this->getAction(), $this->values, $this->checkPermissions);
    if ($this->loadOptions) {
      $this->loadFieldOptions($fields, $fieldsToGet ?: array_keys($fields));
    }
    foreach ($fieldsToGet ?? [] as $fieldName) {
      if (empty($fields[$fieldName]) && str_contains($fieldName, '.')) {
        $fkField = $this->getFkFieldSpec($fieldName, $fields);
        if ($fkField) {
          $fkField['name'] = $fieldName;
          $fields[] = $fkField;
        }
      }
    }
    return $fields;
  }

  protected function loadFieldOptions(array &$fields, array $fieldsToGet) {
    foreach ($fieldsToGet as $fieldName) {
      if (!empty($fields[$fieldName]['options_callback'])) {
        $fields[$fieldName]['options'] = $fields[$fieldName]['options_callback']($fields[$fieldName], $this->values, $this->loadOptions, $this->checkPermissions, $fields[$fieldName]['options_callback_params'] ?? NULL);
      }
    }
  }

  /**
   * @param string $fieldName
   * @param array $fields
   * @return array|null
   * @throws \CRM_Core_Exception
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

  /**
   * Special handling for pseudoconstant replacements.
   *
   * Normally this would involve calling getFields... but this IS getFields.
   *
   * @throws \CRM_Core_Exception
   */
  private function formatValues() {
    foreach (array_keys($this->values) as $key) {
      if (strpos($key, ':')) {
        if (isset($this->values[$key]) && $this->values[$key] !== '') {
          [$fieldName, $suffix] = explode(':', $key);
          if (!isset($this->values[$fieldName])) {
            $options = FormattingUtil::getPseudoconstantList(['name' => $fieldName, 'entity' => $this->getEntityName()], $key, $this->values);
            $this->values[$fieldName] = FormattingUtil::replacePseudoconstant($options, $this->values[$key], TRUE);
          }
        }
        unset($this->values[$key]);
      }
    }
  }

  public function fields() {
    $fields = parent::fields();
    $fields[] = [
      'name' => 'fk_column',
      'data_type' => 'String',
      'description' => 'Name of fk_entity column this field references.',
    ];
    $fields[] = [
      'name' => 'dfk_entities',
      'description' => 'List of possible entity types this field could be referencing.',
      'data_type' => 'Array',
    ];
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
