<?php

use Civi\Api4\CustomField;

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Custom_Import_Parser_Api extends CRM_Import_Parser {

  protected $_fields = [];
  protected $_multipleCustomData = '';

  /**
   * Get information about the provided job.
   *
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'custom_field_import' => [
        'id' => 'custom_field_import',
        'name' => 'custom_field_import',
        'label' => ts('Multiple Value Custom Field Import'),
        'entity' => 'Contact',
        'url' => 'civicrm/import/custom',
      ],
    ];
  }

  /**
   * Main import function.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import($values) {
    $rowNumber = (int) $values[array_key_last($values)];
    try {
      $params = $this->getMappedRow($values);
      $formatted = [];
      foreach ($params as $key => $value) {
        if ($value !== '') {
          $formatted[$key] = $value;
        }
      }

      if (isset($params['external_identifier']) && !isset($params['contact_id'])) {
        $checkCid = new CRM_Contact_DAO_Contact();
        $checkCid->external_identifier = $params['external_identifier'];
        $checkCid->find(TRUE);
        $formatted['id'] = $checkCid->id;
      }
      else {
        $formatted['id'] = $params['contact_id'];
      }

      $this->formatCommonData($params, $formatted);
      foreach ($formatted['custom'] as $key => $val) {
        $params['custom_' . $key] = $val[-1]['value'];
      }
      $params['skipRecentView'] = TRUE;
      $params['check_permissions'] = TRUE;
      $params['entity_id'] = $formatted['id'];
      civicrm_api3('custom_value', 'create', $params);
      $this->setImportStatus($rowNumber, 'IMPORTED', '', $formatted['id']);
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage(), $formatted['id']);
    }
  }

  /**
   * Set the import metadata.
   */
  public function setFieldMetadata(): void {
    if (!$this->importableFieldsMetadata) {
      $customGroupID = $this->getSubmittedValue('multipleCustomData');
      $importableFields = $this->getGroupFieldsForImport($customGroupID);
      $this->importableFieldsMetadata = array_merge([
        'do_not_import' => ['title' => ts('- do not import -')],
        'contact_id' => ['title' => ts('Contact ID'), 'name' => 'contact_id', 'type' => CRM_Utils_Type::T_INT, 'options' => FALSE, 'headerPattern' => '/contact?|id$/i'],
        'external_identifier' => ['title' => ts('External Identifier'), 'name' => 'external_identifier', 'type' => CRM_Utils_Type::T_STRING, 'options' => FALSE, 'headerPattern' => '/external\s?id/i'],
      ], $importableFields);
    }
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  public function getRequiredFields(): array {
    return [['contact_id'], ['external_identifier']];
  }

  /**
   * Adapted from CRM_Contact_Import_Parser_Contact::formatCommonData
   *
   * TODO: Is this function even necessary? All values get passed to the api anyway.
   *
   * @param array $params
   *   Contain record values.
   * @param array $formatted
   *   Array of formatted data.
   */
  private function formatCommonData($params, &$formatted) {

    $customFields = CRM_Core_BAO_CustomField::getFields(NULL);

    //now format custom data.
    foreach ($params as $key => $field) {

      if ($key == 'id' && isset($field)) {
        $formatted[$key] = $field;
      }

      //Handling Custom Data
      if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) &&
        array_key_exists($customFieldID, $customFields)
      ) {

        $extends = $customFields[$customFieldID]['extends'] ?? NULL;
        $htmlType = $customFields[$customFieldID]['html_type'] ?? NULL;
        $dataType = $customFields[$customFieldID]['data_type'] ?? NULL;
        $serialized = CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]);

        if (!$serialized && in_array($htmlType, ['Select', 'Radio', 'Autocomplete-Select']) && in_array($dataType, ['String', 'Int'])) {
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          foreach ($customOption as $customValue) {
            $val = $customValue['value'] ?? NULL;
            $label = strtolower($customValue['label'] ?? '');
            $value = strtolower(trim($formatted[$key]));
            if (($value == $label) || ($value == strtolower($val))) {
              $params[$key] = $formatted[$key] = $val;
            }
          }
        }
        elseif ($serialized && !empty($formatted[$key]) && !empty($params[$key])) {
          $mulValues = explode(',', $formatted[$key]);
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          $formatted[$key] = [];
          $params[$key] = [];
          foreach ($mulValues as $v1) {
            foreach ($customOption as $v2) {
              if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                (strtolower($v2['value']) == strtolower(trim($v1)))
              ) {
                if ($htmlType === 'CheckBox') {
                  $params[$key][$v2['value']] = $formatted[$key][$v2['value']] = 1;
                }
                else {
                  $params[$key][] = $formatted[$key][] = $v2['value'];
                }
              }
            }
          }
        }
      }
    }

    if (!empty($key) && ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) && array_key_exists($customFieldID, $customFields)) {
      // @todo calling api functions directly is not supported
      _civicrm_api3_custom_format_params($params, $formatted, $extends);
    }
  }

  /**
   * Return the field ids and names (with groups) for import purpose.
   *
   * @param int $customGroupID
   *   Custom group ID.
   *
   * @return array
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  private function getGroupFieldsForImport(int $customGroupID): array {
    $importableFields = [];
    $fields = (array) CustomField::get(FALSE)
      ->addSelect('*', 'custom_group_id.is_multiple', 'custom_group_id.name', 'custom_group_id.extends')
      ->addWhere('custom_group_id', '=', $customGroupID)->execute();

    foreach ($fields as $values) {
      $datatype = $values['data_type'] ?? NULL;
      if ($datatype === 'File') {
        continue;
      }
      /* generate the key for the fields array */
      $key = 'custom_' . $values['id'];
      $regexp = preg_replace('/[.,;:!?]/', '', $values['label']);
      $importableFields[$key] = [
        'name' => $key,
        'title' => $values['label'] ?? NULL,
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/i',
        'import' => 1,
        'custom_field_id' => $values['id'],
        'options_per_line' => $values['options_per_line'],
        'data_type' => $values['data_type'],
        'html_type' => $values['html_type'],
        'type' => CRM_Core_BAO_CustomField::dataToType()[$values['data_type']],
        'is_search_range' => $values['is_search_range'],
        'date_format' => $values['date_format'],
        'time_format' => $values['time_format'],
        'extends' => $values['custom_group_id.extends'],
        'custom_group_id' => $customGroupID,
        'custom_group_id.name' => $values['custom_group_id.name'],
        'is_multiple' => $values['custom_group_id.is_multiple'],
      ];
    }
    return $importableFields;
  }

}
