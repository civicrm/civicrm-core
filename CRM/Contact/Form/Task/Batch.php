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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality for batch profile update.
 */
class CRM_Contact_Form_Task_Batch extends CRM_Contact_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum contacts that should be allowed to update.
   * @var int
   */
  protected $_maxContacts = 100;

  /**
   * Maximum profile fields that will be displayed.
   * @var int
   */
  protected $_maxFields = 9;

  /**
   * Variable to store redirect path.
   * @var string
   */
  protected $_userContext;

  /**
   * When not to reset sort_name.
   * @var bool
   */
  protected $_preserveDefault = TRUE;

  /**
   * Array of fields within the selected profile group
   *
   * @var array
   */
  protected $_fields;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      CRM_Core_Error::statusBounce(ts('ufGroupId is missing'));
    }
    $this->_title = ts('Update multiple contacts') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    $this->setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = ['File'];
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

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Update Contact(s)'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_contactIds);

    // if below fields are missing we should not reset sort name / display name
    // CRM-6794
    $preserveDefaultsArray = [
      'first_name',
      'last_name',
      'middle_name',
      'organization_name',
      'prefix_id',
      'suffix_id',
      'household_name',
    ];

    foreach ($this->_contactIds as $contactId) {
      $profileFields = $this->_fields;
      CRM_Core_BAO_Address::checkContactSharedAddressFields($profileFields, $contactId);
      foreach ($profileFields as $name => $field) {
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contactId);

        if (in_array($field['name'], $preserveDefaultsArray)) {
          $this->_preserveDefault = FALSE;
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_BatchUpdateProfile_next') {
      CRM_Core_Session::setStatus(ts("File type field(s) in the selected profile are not supported for Update multiple contacts."), ts('Some Fields Excluded'), 'info');
    }

    $this->addDefaultButtons(ts('Update Contacts'));
    $this->addFormRule(['CRM_Contact_Form_Task_Batch', 'formRule']);
  }

  /**
   * Set default values for the form.
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return NULL;
    }

    $defaults = $sortName = [];
    foreach ($this->_contactIds as $contactId) {
      $details[$contactId] = [];

      //build sortname
      $sortName[$contactId] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactId,
        'sort_name'
      );

      CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $this->_fields, $defaults, FALSE);
    }

    $this->assign('sortName', $sortName);

    return $defaults;
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields) {
    $errors = [];
    $externalIdentifiers = [];
    foreach ($fields['field'] as $componentId => $field) {
      foreach ($field as $fieldName => $fieldValue) {
        if ($fieldName == 'external_identifier') {
          if (in_array($fieldValue, $externalIdentifiers)) {
            $errors["field[$componentId][external_identifier]"] = ts('Duplicate value for External ID.');
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
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // @todo extract submit functions &
    // extend CRM_Event_Form_Task_BatchTest::testSubmit with a data provider to test
    // handling of custom data, specifically checkbox fields.
    $ufGroupId = $this->get('ufGroupId');
    $notify = NULL;
    $inValidSubtypeCnt = 0;
    //send profile notification email if 'notify' field is set
    $notify = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $ufGroupId, 'notify');
    foreach ($params['field'] as $key => $value) {

      //CRM-5521
      //validate subtype before updating
      if (!empty($value['contact_sub_type']) && !CRM_Contact_BAO_ContactType::isAllowEdit($key)) {
        unset($value['contact_sub_type']);
        $inValidSubtypeCnt++;
      }

      $value['preserveDBName'] = $this->_preserveDefault;

      //parse street address, CRM-7768
      self::parseStreetAddress($value, $this);

      CRM_Contact_BAO_Contact::createProfileContact($value, $this->_fields, $key, NULL, $ufGroupId, NULL, TRUE);
      if ($notify) {
        $values = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($ufGroupId, $key, NULL);
        CRM_Core_BAO_UFGroup::commonSendMail($key, $values);
      }
    }

    CRM_Core_Session::setStatus('', ts("Updates Saved"), 'success');
    if ($inValidSubtypeCnt) {
      CRM_Core_Session::setStatus(ts('Contact Subtype field of 1 contact has not been updated.', [
        'plural' => 'Contact Subtype field of %count contacts has not been updated.',
        'count' => $inValidSubtypeCnt,
      ]), ts('Invalid Subtype'));
    }
  }

  /**
   * Parse street address.
   *
   * @param array $contactValues
   *   Contact values.
   * @param self $form
   *   Form object.
   */
  public static function parseStreetAddress(&$contactValues, &$form) {
    if (!is_array($contactValues) || !is_array($form->_fields)) {
      return;
    }

    static $parseAddress;
    $addressFldKey = 'street_address';
    if (!isset($parseAddress)) {
      $parseAddress = FALSE;
      foreach ($form->_fields as $key => $fld) {
        if (str_contains($key, $addressFldKey)) {
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

    $allParseValues = [];
    foreach ($contactValues as $key => $value) {
      if (str_contains($key, $addressFldKey)) {
        $locTypeId = substr($key, strlen($addressFldKey) + 1);

        // parse address field.
        $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($value);

        //street address consider to be parsed properly,
        //If we get street_name and street_number.
        if (empty($parsedFields['street_name']) || empty($parsedFields['street_number'])) {
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
