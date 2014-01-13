<?php
class CRM_Custom_Import_Parser_Api extends CRM_Custom_Import_Parser_BaseClass {
  protected $_entity = '';
  protected $_fields = array();
  protected $_requiredFields = array();
  protected $_dateFields = array();
  protected $_multipleCustomData = '';
  /**
   * Params for the current entity being prepared for the api
   * @var array
   */
  protected $_params = array();

  function setFields() {
    $customGroupID = $this->_multipleCustomData;//$this->get('multipleCustomData');
    $importableFields = $this->getGroupFieldsForImport($customGroupID, $this);
    $this->_fields = array_merge(array('do_not_import' => array('title' => ts('- do not import -')), 'contact_id' => array('title' => ts('Contact ID'))), $importableFields);
  }

  /**
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * It is called from both the preview & the import actions
   * (non-PHPdoc)
   * @see CRM_Custom_Import_Parser_BaseClass::summary()
   */
  function summary(&$values) {
   $erroneousField = NULL;
   $response      = $this->setActiveFieldValues($values, $erroneousField);
   $errorRequired = FALSE;
   $missingField = '';
   $this->_params = &$this->getActiveFieldParams();

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
   $this->isErrorInCustomData($this->_params , $errorMessage, $contactType, NULL);

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
   * handle the values in import mode
   *
   * @param int $onDuplicate the code for what action to take on duplicates
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function import($onDuplicate, &$values) {
    $response = $this->summary($values);
   
    $this->_params = $this->getActiveFieldParams();
    $this->formatDateParams($this->_params);
    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    $formatted = array(
      'contact_type' => $contactType,
    );

    $formatted['id'] = $this->_params['contact_id'];

    $this->formatParams($this->_params, $formatted);
    $this->_params['skipRecentView'] = TRUE;
    $this->_params['check_permissions'] = TRUE;
    $this->_params['entity_id'] = $this->_params['contact_id'];
    try{
      civicrm_api3('custom_value', 'create', $this->_params);
    }
    catch(CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      array_unshift($values, $error);
      return CRM_Import_Parser::ERROR;
    }
  }

  /**
   * Format Date params
   *
   * Although the api will accept any strtotime valid string CiviCRM accepts at least one date format
   * not supported by strtotime so we should run this through a conversion
   * @param unknown $params
   */
  function formatDateParams() {
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    $setDateFields = array_intersect_key($this->_params, array_flip($this->_dateFields));
    
    foreach ($setDateFields as $key => $value) {
      CRM_Utils_Date::convertToDefaultDate($this->_params, $dateType, $key);
      $this->_params[$key] = CRM_Utils_Date::processDate($this->_params[$key]);
    }
  }

  function formatParams($params, $formatted) {
    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    $customFields = CRM_Core_BAO_CustomField::getFields($contactType, FALSE, FALSE);
    $addressCustomFields = CRM_Core_BAO_CustomField::getFields('Address');
    $customFields = $customFields + $addressCustomFields;
    foreach ($params as $key => $field) {
      if (!isset($field) || empty($field)){
        unset($params[$key]);
        continue;
      }

      if (is_array($field)) {
        $isAddressCustomField = FALSE;
        foreach ($field as $value) {
          $break = FALSE;
          if (is_array($value)) {
            foreach ($value as $name => $testForEmpty) {
              if ($addressCustomFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
                $isAddressCustomField = TRUE;
                break;
              }
              // check if $value does not contain IM provider or phoneType
              if (($name !== 'phone_type_id' || $name !== 'provider_id') && ($testForEmpty === '' || $testForEmpty == NULL)) {
                $break = TRUE;
                break;
              }
            }
          }
          else {
            $break = TRUE;
          }

          if (!$break) {
            require_once 'CRM/Utils/DeprecatedUtils.php';
            _civicrm_api3_deprecated_add_formatted_param($value, $formatted);
          }
        }
        if (!$isAddressCustomField) {
          continue;
        }
      }

      $formatValues = array(
        $key => $field,
      );

      if ($key == 'id' && isset($field)) {
        $formatted[$key] = $field;
      }
      require_once 'CRM/Utils/DeprecatedUtils.php';
      _civicrm_api3_deprecated_add_formatted_param($formatValues, $formatted);

      //Handling Custom Data
      // note: Address custom fields will be handled separately inside _civicrm_api3_deprecated_add_formatted_param
      if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) &&
          array_key_exists($customFieldID, $customFields) &&
          !array_key_exists($customFieldID, $addressCustomFields)) {

        $extends = CRM_Utils_Array::value('extends', $customFields[$customFieldID]);
        $htmlType = CRM_Utils_Array::value( 'html_type', $customFields[$customFieldID] );
        switch ( $htmlType ) {
        case 'CheckBox':
        case 'AdvMulti-Select':
        case 'Multi-Select':

          if ( CRM_Utils_Array::value( $key, $formatted ) && CRM_Utils_Array::value( $key, $params ) ) {
            $mulValues       = explode( ',', $formatted[$key] );
            $customOption    = CRM_Core_BAO_CustomOption::getCustomOption( $customFieldID, true );
            $formatted[$key] = array( );
            $params[$key]    = array( );
            foreach ( $mulValues as $v1 ) {
              foreach ( $customOption as $v2 ) {
                if ( ( strtolower( $v2['label'] ) == strtolower( trim( $v1 ) ) ) ||
                     ( strtolower( $v2['value'] ) == strtolower( trim( $v1 ) ) ) ) {
                  if ( $htmlType == 'CheckBox' ) {
                    $params[$key] = $formatted[$key][$v2['value']] = 1;
                  } else {
                    $params[$key] = $formatted[$key][] = $v2['value'];
                  }
                }
              }
            }
          }
          break;
        }
      }
    }

    if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) && array_key_exists($customFieldID, $customFields) &&
      !array_key_exists($customFieldID, $addressCustomFields)) {
      _civicrm_api3_custom_format_params($params, $formatted, $extends);
    }
  }

  /**
   * Set import entity
   * @param string $entity
   */
  function setEntity($entity) {
    $this->_entity = $entity;
    $this->_multipleCustomData = $entity;
  }

  /**
   * Return the field ids and names (with groups) for import purpose.
   *
   * @param int      $id     Custom group ID
   *
   * @return array   $importableFields
   *
   * @access public
   * @static
   */
   function getGroupFieldsForImport( $id ) {
    $importableFields = array();
    $params = array('custom_group_id' => $id);
    $allFields = civicrm_api3('custom_field', 'get', $params);
    $fields = $allFields['values'];
    foreach ($fields as $id => $values) {
      $datatype = CRM_Utils_Array::value('data_type', $values);
      if ( $datatype == 'File' ) {
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