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
      'Select Date' => 'Date',
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
        if ($field['input_type'] == 'Date') {
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
          // Autocomplete-select
          if ($field['input_type'] === 'EntityRef') {
            $field['entity_reference'] = [
              'entity' => 'OptionValue',
              'key' => 'value',
            ];
            $field['input_attrs']['filter']['option_group_id'] = $customField['option_group_id'];
          }
          // Options for Select, Radio, Checkbox
          else {
            $field['pseudoconstant'] = [
              'option_group_id' => $customField['option_group_id'],
            ];
          }
        }
        $customFields[$fieldName] = $field;
      }
    }
    return $customFields;
  }

}
