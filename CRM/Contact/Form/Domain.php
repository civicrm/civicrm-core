<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
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
  protected $_locationDefaults = [];

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
    $defaults = [];
    $params = [];

    if (isset($this->_id)) {
      $params['id'] = $this->_id;
      CRM_Core_BAO_Domain::retrieve($params, $domainDefaults);
      $this->_contactId = $domainDefaults['contact_id'];

      unset($params['id']);
      $locParams = ['contact_id' => $domainDefaults['contact_id']];
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
    $this->addField('name', ['label' => ts('Organization Name')], TRUE);
    $this->addField('description', ['label' => ts('Description'), 'size' => 30]);

    //build location blocks.
    CRM_Contact_Form_Location::buildQuickForm($this);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'subName' => 'view',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
    $this->assign('emailDomain', TRUE);
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Contact_Form_Domain', 'formRule']);
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
      $errors = [];
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

    $params += ['contact_id' => $this->_contactId];
    $contactParams = [
      'sort_name' => $domain->name,
      'display_name' => $domain->name,
      'legal_name' => $domain->name,
      'organization_name' => $domain->name,
      'contact_id' => $this->_contactId,
      'contact_type' => 'Organization',
    ];

    if ($this->_contactId) {
      $contactParams['contact_sub_type'] = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId);
    }

    CRM_Contact_BAO_Contact::add($contactParams);
    CRM_Core_BAO_Location::create($params, TRUE);

    CRM_Core_BAO_Domain::edit($params, $this->_id);

    CRM_Core_Session::setStatus(ts("Domain information for '%1' has been saved.", [1 => $domain->name]), ts('Saved'), 'success');
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }

}
