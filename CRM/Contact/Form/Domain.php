<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class is to build the form for adding Group.
 */
class CRM_Contact_Form_Domain extends CRM_Core_Form {

  /**
   * The group id, used when editing a group
   *
   * @var int
   */
  protected $_id;

  /**
   * The contact_id of domain.
   *
   * @var int
   */
  protected $_contactId;

  /**
   * Default from email address option value id.
   *
   * @var int
   */
  protected $_fromEmailId = NULL;

  /**
   * Default location type fields.
   *
   * @var array
   */
  protected $_locationDefaults = array();

  /**
   * How many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  const LOCATION_BLOCKS = 1;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Domain';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  public function preProcess() {
    CRM_Utils_System::setTitle(ts('Organization Address and Contact Info'));
    $breadCrumbPath = CRM_Utils_System::url('civicrm/admin', 'reset=1');
    CRM_Utils_System::appendBreadCrumb(ts('Administer CiviCRM'), $breadCrumbPath);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    $this->_id = CRM_Core_Config::domainID();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'view'
    );
    //location blocks.
    $location = new CRM_Contact_Form_Location();
    $location->preProcess($this);
  }

  /**
   * This virtual function is used to set the default values of.
   * various form elements
   *
   * @return array
   *   reference to the array of default values
   *
   */
  public function setDefaultValues() {
    $defaults = array();
    $params = array();

    if (isset($this->_id)) {
      $params['id'] = $this->_id;
      CRM_Core_BAO_Domain::retrieve($params, $domainDefaults);
      $this->_contactId = $domainDefaults['contact_id'];
      //get the default domain from email address. fix CRM-3552
      $optionValues = array();
      $grpParams['name'] = 'from_email_address';
      CRM_Core_OptionValue::getValues($grpParams, $optionValues);
      foreach ($optionValues as $Id => $value) {
        if ($value['is_default'] && $value['is_active']) {
          $this->_fromEmailId = $Id;
          $list = explode('"', $value['label']);
          $domainDefaults['email_name'] = CRM_Utils_Array::value(1, $list);
          $domainDefaults['email_address'] = CRM_Utils_Mail::pluckEmailFromHeader($value['label']);
          break;
        }
      }

      unset($params['id']);
      $locParams = array('contact_id' => $domainDefaults['contact_id']);
      $this->_locationDefaults = $defaults = CRM_Core_BAO_Location::getValues($locParams);

      $config = CRM_Core_Config::singleton();
      if (!isset($defaults['address'][1]['country_id'])) {
        $defaults['address'][1]['country_id'] = $config->defaultContactCountry;
      }

      if (!isset($defaults['address'][1]['state_province_id'])) {
        $defaults['address'][1]['state_province_id'] = $config->defaultContactStateProvince;
      }

    }
    $defaults = array_merge($defaults, $domainDefaults);
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addField('name', array('label' => ts('Organization Name')), TRUE);
    $this->addField('description', array('label' => ts('Description'), 'size' => 30));
    $this->add('text', 'email_name', ts('FROM Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), TRUE);
    $this->add('text', 'email_address', ts('FROM Email Address'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), TRUE);
    $this->addRule('email_address', ts('Domain Email Address must use a valid email address format (e.g. \'info@example.org\').'), 'email');

    //build location blocks.
    CRM_Contact_Form_Location::buildQuickForm($this);

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => ts('Save'),
        'subName' => 'view',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
    $this->assign('emailDomain', TRUE);
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Domain', 'formRule'));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    // check for state/country mapping
    $errors = CRM_Contact_Form_Edit_Address::formRule($fields, CRM_Core_DAO::$_nullArray, CRM_Core_DAO::$_nullObject);
    // $errors === TRUE means no errors from above formRule excution,
    // so declaring $errors to array for further processing
    if ($errors === TRUE) {
      $errors = array();
    }

    //fix for CRM-3552,
    //as we use "fromName"<emailaddresss> format for domain email.
    if (strpos($fields['email_name'], '"') !== FALSE) {
      $errors['email_name'] = ts('Double quotes are not allow in from name.');
    }

    // Check for default from email address and organization (domain) name. Force them to change it.
    if ($fields['email_address'] == 'info@EXAMPLE.ORG') {
      $errors['email_address'] = ts('Please enter a valid default FROM email address for system-generated emails.');
    }
    if ($fields['name'] == 'Default Domain Name') {
      $errors['name'] = ts('Please enter the name of the organization or entity which owns this CiviCRM site.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form when submitted.
   */
  public function postProcess() {
    $params = $this->exportValues();
    $params['entity_id'] = $this->_id;
    $params['entity_table'] = CRM_Core_BAO_Domain::getTableName();
    $domain = CRM_Core_BAO_Domain::edit($params, $this->_id);

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    if (isset($this->_locationDefaults['address'][1]['location_type_id'])) {
      $params['address'][1]['location_type_id'] = $this->_locationDefaults['address'][1]['location_type_id'];
    }
    else {
      $params['address'][1]['location_type_id'] = $defaultLocationType->id;
    }

    if (isset($this->_locationDefaults['phone'][1]['location_type_id'])) {
      $params['phone'][1]['location_type_id'] = $this->_locationDefaults['phone'][1]['location_type_id'];
    }
    else {
      $params['phone'][1]['location_type_id'] = $defaultLocationType->id;
    }

    if (isset($this->_locationDefaults['email'][1]['location_type_id'])) {
      $params['email'][1]['location_type_id'] = $this->_locationDefaults['email'][1]['location_type_id'];
    }
    else {
      $params['email'][1]['location_type_id'] = $defaultLocationType->id;
    }

    $params += array('contact_id' => $this->_contactId);
    $contactParams = array(
      'sort_name' => $domain->name,
      'display_name' => $domain->name,
      'legal_name' => $domain->name,
      'organization_name' => $domain->name,
      'contact_id' => $this->_contactId,
      'contact_type' => 'Organization',
    );

    if ($this->_contactId) {
      $contactParams['contact_sub_type'] = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId);
    }

    CRM_Contact_BAO_Contact::add($contactParams);
    CRM_Core_BAO_Location::create($params, TRUE);

    CRM_Core_BAO_Domain::edit($params, $this->_id);

    //set domain from email address, CRM-3552
    $emailName = '"' . $params['email_name'] . '" <' . $params['email_address'] . '>';

    $emailParams = array(
      'label' => $emailName,
      'description' => $params['description'],
      'is_active' => 1,
      'is_default' => 1,
    );

    $groupParams = array('name' => 'from_email_address');

    //get the option value wt.
    if ($this->_fromEmailId) {
      $action = $this->_action;
      $emailParams['weight'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_fromEmailId, 'weight');
    }
    else {
      //add from email address.
      $action = CRM_Core_Action::ADD;
      $grpId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'from_email_address', 'id', 'name');
      $fieldValues = array('option_group_id' => $grpId);
      $emailParams['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);
    }

    //reset default within domain.
    $emailParams['reset_default_for'] = array('domain_id' => CRM_Core_Config::domainID());

    CRM_Core_OptionValue::addOptionValue($emailParams, $groupParams, $action, $this->_fromEmailId);

    CRM_Core_Session::setStatus(ts("Domain information for '%1' has been saved.", array(1 => $domain->name)), ts('Saved'), 'success');
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }

}
