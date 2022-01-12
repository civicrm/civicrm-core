<?php

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Custom_Import_Parser_Api extends CRM_Custom_Import_Parser {

  protected $_entity = '';
  protected $_fields = [];
  protected $_requiredFields = [];
  protected $_dateFields = [];
  protected $_multipleCustomData = '';

  /**
   * Params for the current entity being prepared for the api.
   * @var array
   */
  protected $_params = [];

  /**
   * Class constructor.
   *
   * @param array $mapperKeys
   * @param null $mapperLocType
   * @param null $mapperPhoneType
   */
  public function __construct(&$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
  }

  public function setFields() {
    $customGroupID = $this->_multipleCustomData;
    $importableFields = $this->getGroupFieldsForImport($customGroupID, $this);
    $this->_fields = array_merge([
      'do_not_import' => ['title' => ts('- do not import -')],
      'contact_id' => ['title' => ts('Contact ID')],
      'external_identifier' => ['title' => ts('External Identifier')],
    ], $importableFields);
  }

  /**
   * The initializer code, called before the processing
   *
   * @return void
   */
  public function init() {
    $this->setFields();
    $fields = $this->_fields;
    $hasLocationType = FALSE;

    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern'], $hasLocationType);
    }
    $this->setActiveFields($this->_mapperKeys);
  }

  /**
   * Handle the values in mapField mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   */
  public function mapField(&$values) {
    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in preview mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   *   It is called from both the preview & the import actions
   *
   * @see CRM_Custom_Import_Parser_BaseClass::summary()
   */
  public function summary(&$values) {
    $erroneousField = NULL;
    $response = $this->setActiveFieldValues($values, $erroneousField);
    $errorRequired = FALSE;
    $missingField = '';
    $this->_params = &$this->getActiveFieldParams();

    $formatted = $this->_params;
    $this->_updateWithId = FALSE;
    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);

    $this->_params = $this->getActiveFieldParams();
    foreach ($this->_requiredFields as $requiredField) {
      if (empty($this->_params[$requiredField])) {
        $errorRequired = TRUE;
        $missingField .= ' ' . $requiredField;
        CRM_Contact_Import_Parser_Contact::addToErrorMsg($this->_entity, $requiredField);
      }
    }

    if ($errorRequired) {
      array_unshift($values, ts('Missing required field(s) :') . $missingField);
      return CRM_Import_Parser::ERROR;
    }

    $errorMessage = NULL;

    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($this->_params + ['contact_type' => $contactType], $errorMessage, $this->_contactSubType, NULL);

    // pseudoconstants
    if ($errorMessage) {
      $tempMsg = "Invalid value for field(s) : $errorMessage";
      array_unshift($values, $tempMsg);
      $errorMessage = NULL;
      return CRM_Import_Parser::ERROR;
    }
    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in import mode.
   *
   * @param int $onDuplicate
   *   The code for what action to take on duplicates.
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function import($onDuplicate, &$values) {
    $response = $this->summary($values);
    if ($response != CRM_Import_Parser::VALID) {
      return $response;
    }

    $this->_updateWithId = FALSE;
    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);

    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    $formatted = [
      'contact_type' => $contactType,
    ];

    if (isset($this->_params['external_identifier']) && !isset($this->_params['contact_id'])) {
      $checkCid = new CRM_Contact_DAO_Contact();
      $checkCid->external_identifier = $this->_params['external_identifier'];
      $checkCid->find(TRUE);
      $formatted['id'] = $checkCid->id;
    }
    else {
      $formatted['id'] = $this->_params['contact_id'];
    }

    $this->formatCommonData($this->_params, $formatted);
    foreach ($formatted['custom'] as $key => $val) {
      $this->_params['custom_' . $key] = $val[-1]['value'];
    }
    $this->_params['skipRecentView'] = TRUE;
    $this->_params['check_permissions'] = TRUE;
    $this->_params['entity_id'] = $formatted['id'];
    try {
      civicrm_api3('custom_value', 'create', $this->_params);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      array_unshift($values, $error);
      return CRM_Import_Parser::ERROR;
    }
  }

  /**
   * Adapted from CRM_Contact_Import_Parser::formatCommonData
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

    //format date first
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");
    foreach ($params as $key => $val) {
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
      if ($customFieldID) {
        //we should not update Date to null, CRM-4062
        if ($val && ($customFields[$customFieldID]['data_type'] == 'Date')) {
          //CRM-21267
          CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
        }
        elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
          if (empty($val) && !is_numeric($val)) {
            //retain earlier value when Import mode is `Fill`
            unset($params[$key]);
          }
          else {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
      }
    }

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
                if ($htmlType == 'CheckBox') {
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
   * Set import entity.
   * @param string $entity
   */
  public function setEntity($entity) {
    $this->_entity = $entity;
    $this->_multipleCustomData = $entity;
  }

  /**
   * The initializer code, called before the processing
   *
   * @return void
   */
  public function fini() {
  }

  /**
   * Return the field ids and names (with groups) for import purpose.
   *
   * @param int $id
   *   Custom group ID.
   *
   * @return array
   *
   */
  public function getGroupFieldsForImport($id) {
    $importableFields = [];
    $params = ['custom_group_id' => $id];
    $allFields = civicrm_api3('custom_field', 'get', $params);
    $fields = $allFields['values'];
    foreach ($fields as $id => $values) {
      $datatype = $values['data_type'] ?? NULL;
      if ($datatype == 'File') {
        continue;
      }
      /* generate the key for the fields array */
      $key = "custom_$id";
      $regexp = preg_replace('/[.,;:!?]/', '', CRM_Utils_Array::value(0, $values));
      $importableFields[$key] = [
        'name' => $key,
        'title' => $values['label'] ?? NULL,
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/',
        'import' => 1,
        'custom_field_id' => $id,
        'options_per_line' => $values['options_per_line'] ?? NULL,
        'data_type' => $values['data_type'] ?? NULL,
        'html_type' => $values['html_type'] ?? NULL,
        'is_search_range' => $values['is_search_range'] ?? NULL,
      ];
      if (CRM_Utils_Array::value('html_type', $values) == 'Select Date') {
        $importableFields[$key]['date_format'] = $values['date_format'] ?? NULL;
        $importableFields[$key]['time_format'] = $values['time_format'] ?? NULL;
        $this->_dateFields[] = $key;
      }
    }
    return $importableFields;
  }

}
