<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the functionality for batch profile update
 */
class CRM_Contact_Form_Task_Batch extends CRM_Contact_Form_Task {

  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * maximum contacts that should be allowed to update
   *
   */
  protected $_maxContacts = 100;

  /**
   * maximum profile fields that will be displayed
   *
   */
  protected $_maxFields = 9;

  /**
   * variable to store redirect path
   *
   */
  protected $_userContext;

  /**
   * when not to reset sort_name
   */
  protected $_preserveDefault = TRUE;
  
  /**
   * the internal QF names for the state/country/county fields
   */
   
  protected $_stateCountryCountyFields = array(); 

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    /*
     * initialize the task and row fields
     */
    parent::preProcess();
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      CRM_Core_Error::fatal('ufGroupId is missing');
    }
    $this->_title = ts('Batch Update') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    CRM_Utils_System::setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = array('File', 'Autocomplete-Select');
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }
    }

    //FIX ME: phone ext field is added at the end and it gets removed because of below code
    //$this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Update Contact(s)'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );


    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_contactIds);

    // if below fields are missing we should not reset sort name / display name
    // CRM-6794
    $preserveDefaultsArray = array(
      'first_name', 'last_name', 'middle_name',
      'organization_name',
      'household_name',
    );
    
    $stateCountryMap = array();

    foreach ($this->_contactIds as $contactId) {
      $profileFields = $this->_fields;
      CRM_Core_BAO_Address::checkContactSharedAddressFields($profileFields, $contactId);
      foreach ($profileFields as $name => $field) {
      
        // Link state to country, county to state per location per contact
        list($prefixName, $index) = CRM_Utils_System::explode('-', $name, 2);
        if ($prefixName == 'state_province' || $prefixName == 'country' || $prefixName == 'county') {
          $stateCountryMap["$index-$contactId"][$prefixName] = "field_{$contactId}_{$field['name']}";
          $this->_stateCountryCountyFields["$index-$contactId"][$prefixName] = "field[{$contactId}][{$field['name']}]";
        }
       
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contactId);

        if (in_array($field['name'], $preserveDefaultsArray)) {
          $this->_preserveDefault = FALSE;
        }
      }
    }
    
    CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);
    


    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_BatchUpdateProfile_next') {
      CRM_Core_Session::setStatus(ts("File or Autocomplete Select type field(s) in the selected profile are not supported for Batch Update and have been excluded."), ts('Some Fields Excluded'), 'info');
    }

    $this->addDefaultButtons(ts('Update Contacts'));
    $this->addFormRule(array('CRM_Contact_Form_Task_Batch', 'formRule'));
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    $defaults = $sortName = array();
    foreach ($this->_contactIds as $contactId) {
      $details[$contactId] = array();

      //build sortname
      $sortName[$contactId] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactId,
        'sort_name'
      );

      CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $this->_fields, $defaults, FALSE);
    }

    $this->assign('sortName', $sortName);
    
    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults, $this->_stateCountryCountyFields); 

    return $defaults;
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields) {
    $errors = array();
    $externalIdentifiers = array();
    foreach ($fields['field'] as $componentId => $field) {
      foreach ($field as $fieldName => $fieldValue) {
        if ($fieldName == 'external_identifier') {
          if (in_array($fieldValue, $externalIdentifiers)) {
            $errors["field[$componentId][external_identifier]"] = ts('Duplicate value for External Identifier.');
          }
          else {
            $externalIdentifiers[$componentId] = $fieldValue;
          }
        }
      }
    }

    return $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->exportValues();

    $ufGroupId         = $this->get('ufGroupId');
    $notify            = NULL;
    $inValidSubtypeCnt = 0;
    //send profile notification email if 'notify' field is set
    $notify = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $ufGroupId, 'notify');
    foreach ($params['field'] as $key => $value) {

      //CRM-5521
      //validate subtype before updating
      if (CRM_Utils_Array::value('contact_sub_type', $value) && !CRM_Contact_BAO_ContactType::isAllowEdit($key)) {
        unset($value['contact_sub_type']);
        $inValidSubtypeCnt++;
      }

      $value['preserveDBName'] = $this->_preserveDefault;

      //parse street address, CRM-7768
      self::parseStreetAddress($value, $this);

      CRM_Contact_BAO_Contact::createProfileContact($value, $this->_fields, $key, NULL, $ufGroupId);
      if ($notify) {
        $values = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($ufGroupId, $key, NULL);
        CRM_Core_BAO_UFGroup::commonSendMail($key, $values);
      }
    }

    CRM_Core_Session::setStatus('', ts("Updates Saved"), 'success');
    if ($inValidSubtypeCnt) {
      CRM_Core_Session::setStatus(ts('Contact SubType field of 1 contact has not been updated.', array('plural' => 'Contact SubType field of %count contacts has not been updated.', 'count' => $inValidSubtypeCnt)), ts('Invalid Subtype'));
    }
  }
  //end of function

  function parseStreetAddress(&$contactValues, &$form) {
    if (!is_array($contactValues) ||
      !is_array($form->_fields)
    ) {
      return;
    }

    static $parseAddress;
    $addressFldKey = 'street_address';
    if (!isset($parseAddress)) {
      $parseAddress = FALSE;
      foreach ($form->_fields as $key => $fld) {
        if (strpos($key, $addressFldKey) !== FALSE) {
          $parseAddress = CRM_Utils_Array::value('street_address_parsing',
            CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
              'address_options'
            ),
            FALSE
          );
          break;
        }
      }
    }

    if (!$parseAddress) {
      return;
    }

    $allParseValues = array();
    foreach ($contactValues as $key => $value) {
      if (strpos($key, $addressFldKey) !== FALSE) {
        $locTypeId = substr($key, strlen($addressFldKey) + 1);

        // parse address field.
        $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($value);

        //street address consider to be parsed properly,
        //If we get street_name and street_number.
        if (!CRM_Utils_Array::value('street_name', $parsedFields) ||
          !CRM_Utils_Array::value('street_number', $parsedFields)
        ) {
          $parsedFields = array_fill_keys(array_keys($parsedFields), '');
        }

        //merge parse values.
        foreach ($parsedFields as $fldKey => $parseVal) {
          if ($locTypeId) {
            $fldKey .= "-{$locTypeId}";
          }
          $allParseValues[$fldKey] = $parseVal;
        }
      }
    }

    //finally merge all parse values
    if (!empty($allParseValues)) {
      $contactValues += $allParseValues;
    }
  }
}

