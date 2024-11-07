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

namespace Civi\Schema;

use Civi\Core\Resolver;

abstract class EntityMetadataBase implements EntityMetadataInterface {

  /**
   * @var string
   */
  protected string $entityName;

  public function __construct(string $entityName) {
    $this->entityName = $entityName;
  }

  /**
   * @return array{name: string, table: string, class: string, module: string, getInfo: callable, getPaths: callable, getIndices: callable, getFields: callable, metaProvider: callable, storageProvider: callable}
   */
  protected function getEntity(): array {
    return EntityRepository::getEntity($this->entityName);
  }

  public function getField(string $fieldName): ?array {
    $field = $this->getFields()[$fieldName] ?? NULL;
    if (!$field && str_contains($fieldName, '.')) {
      [$customGroupName] = explode('.', $fieldName);
      $field = $this->getCustomFields(['name' => $customGroupName])[$fieldName] ?? NULL;
    }
    return $field;
  }

  public function getOptions(string $fieldName, array $values = [], bool $includeDisabled = FALSE, bool $checkPermissions = FALSE, ?int $userId = NULL): ?array {
    $field = $this->getField($fieldName);
    $options = NULL;
    $hookParams = [
      'entity' => $this->entityName,
      'context' => 'full',
      'values' => $values,
      'include_disabled' => $includeDisabled,
      'check_permissions' => $checkPermissions,
      'user_id' => $userId,
    ];
    $field['pseudoconstant']['condition'] = (array) ($field['pseudoconstant']['condition'] ?? []);
    if (!empty($field['pseudoconstant']['condition_provider'])) {
      $this->getConditionFromProvider($fieldName, $field, $hookParams);
    }
    if (!empty($field['pseudoconstant']['option_group_name'])) {
      $this->getOptionGroupParams($field);
    }
    if (!empty($field['pseudoconstant']['callback'])) {
      $callbackValues = call_user_func(Resolver::singleton()->get($field['pseudoconstant']['callback']), $fieldName, $hookParams);
      $options = self::formatOptionValues($callbackValues);
    }
    elseif (!empty($field['pseudoconstant']['table'])) {
      $options = self::getSqlOptions($field, $includeDisabled);
    }
    elseif (\CRM_Utils_Schema::getDataType($field) === 'Boolean') {
      $options = self::formatOptionValues(\CRM_Core_SelectValues::boolean());
    }
    $preHookOptions = $options;
    // Allow hooks to alter or overwrite the option list
    \CRM_Utils_Hook::fieldOptions($this->entityName, $fieldName, $options, $hookParams);
    // If options were altered via hook, re-normalize the format
    if ($preHookOptions !== $options && is_array($options)) {
      $options = self::formatOptionValues($options);
    }
    return isset($options) ? array_values($options) : NULL;
  }

  private function getConditionFromProvider(string $fieldName, array &$field, array $hookParams) {
    $fragment = \CRM_Utils_SQL_Select::fragment();
    $callback = Resolver::singleton()->get($field['pseudoconstant']['condition_provider']);
    $callback($fieldName, $fragment, $hookParams);
    foreach ($fragment->getWhere() as $condition) {
      $field['pseudoconstant']['condition'][] = $condition;
    }
    unset($field['pseudoconstant']['condition_provider']);
  }

  private function getOptionGroupParams(array &$field) {
    $groupName = $field['pseudoconstant']['option_group_name'];
    $groupId = (int) \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $groupName, 'id', 'name');

    $field['pseudoconstant']['table'] = 'civicrm_option_value';
    $field['pseudoconstant']['condition'][] = "option_group_id = $groupId";

    // Set default selectors (allowing for overrides)
    $field['pseudoconstant'] += ['key_column' => 'value'];

    // Guard against sql errors if this (relatively new) column hasn't been added yet by the upgrader
    if (version_compare(\CRM_Core_BAO_Domain::version(), '5.49', '>')) {
      $optionValueFieldsStr = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $groupId, 'option_value_fields');
    }
    $optionValueFields = empty($optionValueFieldsStr) ? ['name', 'label', 'description'] : explode(',', $optionValueFieldsStr);
    foreach ($optionValueFields as $optionValueField) {
      $field['pseudoconstant'] += ["{$optionValueField}_column" => $optionValueField];
    }

    // Filter for domain-specific groups
    if (\CRM_Core_OptionGroup::isDomainOptionGroup($groupName)) {
      $field['pseudoconstant']['condition'][] = 'domain_id = ' . \CRM_Core_Config::domainID();
    }
  }

  private function formatOptionValues(array $optionValues): array {
    foreach ($optionValues as $id => $optionValue) {
      if (!is_array($optionValue)) {
        // Convert scalar values to array format
        $optionValues[$id] = [
          'id' => $id,
          'name' => $id,
          'label' => $optionValue,
        ];
      }
      else {
        // Ensure each option has a name and label
        $optionValues[$id]['name'] ??= $optionValue['id'];
        $optionValues[$id]['label'] ??= $optionValue['name'];
      }
    }
    return $optionValues;
  }

  private function getSqlOptions(array $field, bool $includeDisabled = FALSE): array {
    $pseudoconstant = $field['pseudoconstant'];
    $cacheKey = 'EntityMetadataGetSqlOptions' . \CRM_Core_Config::domainID() . '_' . \CRM_Core_I18n::getLocale() . md5(json_encode($pseudoconstant));
    $entity = \Civi::table($pseudoconstant['table']);
    $cache = \Civi::cache('metadata');
    $options = $cache->get($cacheKey);
    if (!isset($options)) {
      $options = [];
      $fields = $entity->getSupportedFields();
      $select = \CRM_Utils_SQL_Select::from($pseudoconstant['table']);
      $idCol = $pseudoconstant['key_column'] ?? $entity->getMeta('primary_key');
      $pseudoconstant['name_column'] ??= (isset($fields['name']) ? 'name' : $idCol);
      $select->select(["$idCol AS id"]);
      foreach (array_keys(\CRM_Core_SelectValues::optionAttributes()) as $prop) {
        if (isset($pseudoconstant["{$prop}_column"], $fields[$pseudoconstant["{$prop}_column"]])) {
          $propColumn = $pseudoconstant["{$prop}_column"];
          $select->select("$propColumn AS $prop");
        }
      }
      // Select is_active for filtering
      if (isset($fields['is_active'])) {
        $select->select('is_active');
      }
      // Also component_id for filtering (this is legacy, the new way for extensions to add options is via hook)
      if (isset($fields['component_id'])) {
        $select->select('component_id');
      }
      // Order by: prefer order_column; or else 'weight' column; or else lobel_column; or as a last resort, $idCol
      $orderColumns = [$pseudoconstant['order_column'] ?? NULL, 'weight', $pseudoconstant['label_column'] ?? NULL, $idCol];
      foreach ($orderColumns as $orderColumn) {
        if (isset($fields[$orderColumn])) {
          $select->orderBy($orderColumn);
          break;
        }
      }
      // Filter on domain, but only if field is required
      if (!empty($fields['domain_id']['required'])) {
        $select->where('domain_id = #dom', ['#dom' => \CRM_Core_Config::domainID()]);
      }
      if (!empty($pseudoconstant['condition'])) {
        $select->where($pseudoconstant['condition']);
      }
      $result = $select->execute()->fetchAll();
      foreach ($result as $option) {
        if (\CRM_Utils_Schema::getDataType($fields[$idCol]) === 'Integer' || \CRM_Utils_Schema::getDataType($field) === 'Integer') {
          $option['id'] = (int) $option['id'];
        }
        $options[$option['id']] = $option;
      }
      $cache->set($cacheKey, $options);
    }
    // Filter out disabled options
    if (!$includeDisabled) {
      foreach ($options as $id => $option) {
        if ((isset($option['is_active']) && !$option['is_active']) || (!empty($option['component_id']) && !\CRM_Core_Component::isIdEnabled($option['component_id']))) {
          unset($options[$id]);
        }
      }
    }
    return $options;
  }

  /**
   * Retrieves the custom fields associated with the entity, in the same format as returned by `getFields()`
   *
   * @param array $customGroupFilters
   *   Optional. Additional filters to apply when retrieving custom groups. Defaults to an empty array.
   * @return array[]
   *   The array keys are formatted as "customGroup.fieldName", and the array values are associative arrays containing the field details.
   */
  public function getCustomFields(array $customGroupFilters = []): array {
    $customFields = [];
    $customGroupFilters += ['extends' => $this->entityName, 'is_active' => TRUE];
    $inputTypeMap = [
      'Link' => 'Url',
      'Autocomplete-Select' => 'EntityRef',
    ];
    foreach (\CRM_Core_BAO_CustomGroup::getAll($customGroupFilters) as $customGroup) {
      foreach ($customGroup['fields'] as $customField) {
        $fieldName = $customGroup['name'] . '.' . $customField['name'];
        $field = [
          'title' => $customField['label'],
          'sql_type' => \CRM_Core_BAO_CustomValueTable::fieldToSQLType($customField['data_type'], $customField['text_length']),
          'data_type' => \CRM_Core_BAO_CustomField::getDataTypeString($customField),
          'input_type' => $inputTypeMap[$customField['html_type']] ?? $customField['html_type'],
          'input_attrs' => [
            'label' => $customGroup['title'] . ': ' . $customField['label'],
          ],
          'default' => $customField['default_value'],
          'help_pre' => $customField['help_pre'],
          'help_post' => $customField['help_post'],
          'required' => !empty($customField['is_required']),
          'usage' => ['export', 'duplicate_matching', 'token'],
          'readonly' => !empty($customField['is_view']),
          'serialize' => $customField['serialize'] ?: NULL,
          'custom_field_id' => $customField['id'],
          'table_name' => $customGroup['table_name'],
          'column_name' => $customField['column_name'],
        ];
        if (empty($customField['is_view'])) {
          $field['usage'][] = 'import';
        }
        if ($field['input_type'] == 'Text' && $customField['text_length']) {
          $field['input_attrs']['maxlength'] = (int) $customField['text_length'];
        }
        if ($field['input_type'] == 'TextArea') {
          $field['input_attrs']['rows'] = (int) ($customField['note_rows'] ?? 4);
          $field['input_attrs']['cols'] = (int) ($customField['note_columns'] ?? 60);
        }
        // Date/time settings
        if ($field['input_type'] == 'Select Date') {
          $field['input_attrs']['time'] = empty($customField['time_format']) ? FALSE : ($customField['time_format'] == 1 ? 12 : 24);
          $field['input_attrs']['date'] = $customField['date_format'];
          $field['input_attrs']['start_date_years'] = isset($customField['start_date_years']) ? (int) $customField['start_date_years'] : NULL;
          $field['input_attrs']['end_date_years'] = isset($customField['end_date_years']) ? (int) $customField['end_date_years'] : NULL;
        }
        // Number input for numeric fields
        if ($field['input_type'] === 'Text' && in_array($customField['data_type'], ['Int', 'Float'], TRUE)) {
          $field['input_type'] = 'Number';
          // Todo: make 'step' configurable for the custom field
          $field['input_attrs']['step'] = $customField['data_type'] === 'Int' ? 1 : .01;
        }
        // Unserialize filters from url-arg-style string
        if (!empty($customField['filter'])) {
          $customGroupFilters = explode('&', $customField['filter']);
          foreach ($customGroupFilters as $filter) {
            if (str_contains($filter, '=')) {
              [$filterKey, $filterValue] = explode('=', $filter, 2);
              // Convert legacy ContactRef filter to EntityRef format
              if ($customField['data_type'] === 'ContactReference') {
                $filterKey = $filterKey === 'group' ? 'groups' : $filterKey;
                if ($filterKey === 'action') {
                  continue;
                }
              }
              $field['input_attrs']['filter'][$filterKey] = $filterValue;
            }
          }
        }
        // Re-use options from address entity
        if ($customField['data_type'] === 'StateProvince' || $customField['data_type'] === 'Country') {
          $addressFieldName = \CRM_Utils_String::convertStringToSnakeCase($customField['data_type']) . '_id';
          $addressField = \Civi::entity('Address')->getField($addressFieldName);
          $field['pseudoconstant'] = $addressField['pseudoconstant'];
        }
        // Set FK for EntityRef, ContactRef & File fields
        if ($customField['fk_entity'] || $customField['data_type'] === 'ContactReference' || $customField['data_type'] === 'File') {
          $onDelete = empty($customField['fk_entity_on_delete']) ? 'SET NULL' : strtoupper(str_replace('_', ' ', $customField['fk_entity_on_delete']));
          $field['entity_reference'] = [
            'entity' => $customField['fk_entity'] ?? str_replace('Reference', '', $customField['data_type']),
            'key' => 'id',
            'on_delete' => $onDelete,
          ];
        }
        if ($customField['option_group_id']) {
          // Options for Select, Radio, Checkbox
          $field['pseudoconstant'] = [
            'option_group_name' => \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $customField['option_group_id']),
          ];
          // Autocomplete-select
          if ($field['input_type'] === 'EntityRef') {
            $field['entity_reference'] = [
              'entity' => 'OptionValue',
              'key' => 'value',
            ];
            $field['input_attrs']['filter']['option_group_id'] = $customField['option_group_id'];
            // Retain option list but don't prefetch since the widget is autocomplete
            $field['pseudoconstant']['prefetch'] = 'disabled';
          }
        }
        $customFields[$fieldName] = $field;
      }
    }
    return $customFields;
  }

}
