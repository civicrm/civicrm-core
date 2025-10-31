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
 * This class is to build the form for adding Group.
 */
class CRM_Contact_Form_Domain extends CRM_Core_Form {
  use CRM_Contact_Form_Edit_PhoneBlockTrait;
  use CRM_Contact_Form_Edit_EmailBlockTrait;

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
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity(): string {
    return 'Domain';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext(): string {
    return 'create';
  }

  public function preProcess() {
    $this->setTitle(ts('Organization Address and Contact Info'));
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    $this->_id = CRM_Core_Config::domainID();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'view'
    );
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
      $this->_locationDefaults['address'] = $defaults['address'] = CRM_Core_BAO_Address::getValues($locParams);
      $this->_locationDefaults['phone'] = $defaults['phone'] = $this->getExistingPhonesReIndexed();
      $this->_locationDefaults['email'] = $defaults['email'] = $this->getExistingEmailsReIndexed();
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
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addField('name', ['label' => ts('Organization Name')], TRUE);
    $this->addField('description', ['label' => ts('Description'), 'size' => 30]);

    //build location blocks.
    $this->assign('addressSequence', CRM_Core_BAO_Address::addressSequence());
    CRM_Contact_Form_Edit_Address::buildQuickForm($this, 1);

    //Email box
    $this->addField("email[1][email]", [
      'entity' => 'email',
      'aria-label' => ts('Email 1'),
      'label' => ts('Email 1'),
    ]);
    $this->addRule("email[1][email]", ts('Email is not valid.'), 'email');
    $this->addPhoneBlockFields(1);

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
    $errors = CRM_Contact_Form_Edit_Address::formRule($fields);
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
    $params = $this->getSubmittedValues();
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

  public function getContactID() {
    return CRM_Core_BAO_Domain::getDomain()->contact_id;
  }

}
