<?php

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Custom_Import_Parser_Api extends CRM_Custom_Import_Parser {

  protected $_entity = '';
  protected $_fields = array();
  protected $_requiredFields = array();
  protected $_dateFields = array();
  protected $_multipleCustomData = '';

  /**
   * Params for the current entity being prepared for the api.
   * @var array
   */
  protected $_params = array();

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
    $this->_fields = array_merge(array(
        'do_not_import' => array('title' => ts('- do not import -')),
        'contact_id' => array('title' => ts('Contact ID')),
        'external_identifier' => array('title' => ts('External Identifier')),
      ), $importableFields);
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
    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($this->_params + array('contact_type' => $contactType), $errorMessage, $this->_contactSubType, NULL);

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
      $importRecordParams = array(
        $statusFieldName => 'INVALID',
        "${statusFieldName}Msg" => "Invalid (Error Code: $response)",
      );
      return $response;
    }

    $this->_updateWithId = FALSE;
    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);

    $params = $this->getActiveFieldParams();
    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    $formatted = array(
      'contact_type' => $contactType,
    );
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');

    if (isset($this->_params['external_identifier']) && !isset($this->_params['contact_id'])) {
      $checkCid = new CRM_Contact_DAO_Contact();
      $checkCid->external_identifier = $this->_params['external_identifier'];
      $checkCid->find(TRUE);
      $formatted['id'] = $checkCid->id;
    }
    else {
      $formatted['id'] = $this->_params['contact_id'];
    }
    $setDateFields = array_intersect_key($this->_params, array_flip($this->_dateFields));

    $this->formatCommonData($this->_params, $formatted, $formatted);
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
   * Format Date params.
   *
   * Although the api will accept any strtotime valid string CiviCRM accepts at least one date format
   * not supported by strtotime so we should run this through a conversion
   */
  public function formatDateParams() {
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    $setDateFields = array_intersect_key($this->_params, array_flip($this->_dateFields));

    foreach ($setDateFields as $key => $value) {
      CRM_Utils_Date::convertToDefaultDate($this->_params, $dateType, $key);
      $this->_params[$key] = CRM_Utils_Date::processDate($this->_params[$key]);
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
    $importableFields = array();
    $params = array('custom_group_id' => $id);
    $allFields = civicrm_api3('custom_field', 'get', $params);
    $fields = $allFields['values'];
    foreach ($fields as $id => $values) {
      $datatype = CRM_Utils_Array::value('data_type', $values);
      if ($datatype == 'File') {
        continue;
      }
      /* generate the key for the fields array */
      $key = "custom_$id";
      $regexp = preg_replace('/[.,;:!?]/', '', CRM_Utils_Array::value(0, $values));
      $importableFields[$key] = array(
        'name' => $key,
        'title' => CRM_Utils_Array::value('label', $values),
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/',
        'import' => 1,
        'custom_field_id' => $id,
        'options_per_line' => CRM_Utils_Array::value('options_per_line', $values),
        'data_type' => CRM_Utils_Array::value('data_type', $values),
        'html_type' => CRM_Utils_Array::value('html_type', $values),
        'is_search_range' => CRM_Utils_Array::value('is_search_range', $values),
      );
      if (CRM_Utils_Array::value('html_type', $values) == 'Select Date') {
        $importableFields[$key]['date_format'] = CRM_Utils_Array::value('date_format', $values);
        $importableFields[$key]['time_format'] = CRM_Utils_Array::value('time_format', $values);
        $this->_dateFields[] = $key;
      }
    }
    return $importableFields;
  }

}
