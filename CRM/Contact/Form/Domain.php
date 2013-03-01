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
 * This class is to build the form for adding Group
 */
class CRM_Contact_Form_Domain extends CRM_Core_Form {

  /**
   * the group id, used when editing a group
   *
   * @var int
   */
  protected $_id;

  /**
   * the contact_id of domain
   *
   * @var int
   */
  protected $_contactId;

  /**
   * default from email address option value id.
   *
   * @var int
   */
  protected $_fromEmailId = NULL;

  /**
   * how many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  CONST LOCATION_BLOCKS = 1; 

  function preProcess() {
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

  /*
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   * @return None
   */
  function setDefaultValues() {
    $defaults  = array();
    $params    = array();
    $locParams = array();

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
      $defaults = CRM_Core_BAO_Location::getValues($locParams);

      $config = CRM_Core_Config::singleton();
      if (!isset($defaults['address'][1]['country_id'])) {
        $defaults['address'][1]['country_id'] = $config->defaultContactCountry;
      }
      
      if (!isset($defaults['address'][1]['state_province_id'])) {
        $defaults['address'][1]['state_province_id'] = $config->defaultContactStateProvince;
      }

      if (!empty($defaults['address'])) {
        foreach ($defaults['address'] as $key => $value) {
          CRM_Contact_Form_Edit_Address::fixStateSelect($this,
            "address[$key][country_id]",
            "address[$key][state_province_id]",
            "address[$key][county_id]",
            CRM_Utils_Array::value('country_id', $value,
              $config->defaultContactCountry
            ),
            CRM_Utils_Array::value('state_province_id', $value,
              $config->defaultContactStateProvince
            )
          );
        }
      }
    }
    $defaults = array_merge($defaults, $domainDefaults);
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    $this->add('text', 'name', ts('Organization Name'), array('size' => 25), TRUE);
    $this->add('text', 'description', ts('Description'), array('size' => 25));

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
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Domain', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = array();
    // check for state/country mapping
    CRM_Contact_Form_Edit_Address::formRule($fields, $errors);

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
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = array();
    $params = $this->exportValues();
    $params['entity_id'] = $this->_id;
    $params['entity_table'] = CRM_Core_BAO_Domain::getTableName();
    $domain = CRM_Core_BAO_Domain::edit($params, $this->_id);

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    $location = array();
    $params['address'][1]['location_type_id'] = $defaultLocationType->id;
    $params['phone'][1]['location_type_id'] = $defaultLocationType->id;
    $params['email'][1]['location_type_id'] = $defaultLocationType->id;
    $params += array('contact_id' => $this->_contactId);
    $contactParams = array (
      'sort_name'    => $domain->name,
      'display_name' => $domain->name,
      'legal_name'   => $domain->name,
      'organization_name' => $domain->name,
      'contact_id' => $this->_contactId,              
    );
    CRM_Contact_BAO_Contact::add($contactParams);
    $location = CRM_Core_BAO_Location::create($params, TRUE);


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

