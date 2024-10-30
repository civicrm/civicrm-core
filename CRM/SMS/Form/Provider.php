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
 * SMS Form.
 */
class CRM_SMS_Form_Provider extends CRM_Core_Form {
  protected $_id = NULL;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  public function preProcess() {

    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this);

    $this->setPageTitle(ts('SMS Provider'));

    if ($this->_id) {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/sms/provider/edit',
        "reset=1&action=update&id={$this->_id}",
        FALSE, NULL, FALSE
      );
    }
    else {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/sms/provider/edit',
        "reset=1&action=add",
        FALSE, NULL, FALSE
      );
    }

    $this->assign('refreshURL', $refreshURL);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->addButtons([
      [
        'type' => 'next',
        'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_SMS_DAO_SmsProvider');

    $providerNames = CRM_Core_OptionGroup::values('sms_provider_name', FALSE, FALSE, FALSE, NULL, 'label');
    $apiTypes = CRM_Core_OptionGroup::values('sms_api_type', FALSE, FALSE, FALSE, NULL, 'label');

    $this->add('select', 'name', ts('Name'), $providerNames, TRUE, ['placeholder' => TRUE]);

    $this->add('text', 'title', ts('Title'),
      $attributes['title'], TRUE
    );

    $this->addRule('title', ts('This Title already exists in Database.'), 'objectExists', [
      'CRM_SMS_DAO_SmsProvider',
      $this->_id,
    ]);

    $this->add('text', 'username', ts('Username'),
      $attributes['username'], TRUE
    );

    $this->add('password', 'password', ts('Password'),
      $attributes['password'], TRUE
    );

    $this->add('select', 'api_type', ts('API Type'), $apiTypes, TRUE);

    $this->add('text', 'api_url', ts('API Url'), $attributes['api_url'], TRUE);

    $this->add('textarea', 'api_params', ts('API Parameters'),
      ['cols' => 50, 'rows' => 6], TRUE
    );

    $this->add('checkbox', 'is_active', ts('Is this provider active?'));

    $this->add('checkbox', 'is_default', ts('Is this a default provider?'));
  }

  /**
   * Set the default values of various form elements.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    $name = CRM_Utils_Request::retrieve('key', 'String', $this, FALSE, NULL);
    if ($name) {
      $defaults['name'] = $name;
      $provider = CRM_SMS_Provider::singleton(['provider' => $name]);
      $defaults['api_url'] = $provider->_apiURL ?? '';
    }

    if (!$this->_id) {
      $defaults['is_active'] = $defaults['is_default'] = 1;
      return $defaults;
    }

    $dao = new CRM_SMS_DAO_SmsProvider();
    $dao->id = $this->_id;

    if ($name) {
      $dao->name = $name;
    }

    if (!$dao->find(TRUE)) {
      return $defaults;
    }

    CRM_Core_DAO::storeValues($dao, $defaults);

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {

    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_SMS_BAO_SmsProvider::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Provider has been deleted.'), ts('Deleted'), 'success');
      return;
    }

    $recData = $values = $this->controller->exportValues($this->_name);
    $recData['is_active'] ??= 0;
    $recData['is_default'] ??= 0;

    if ($this->_action && (CRM_Core_Action::UPDATE || CRM_Core_Action::ADD)) {
      if ($this->_id) {
        $recData['id'] = $this->_id;
      }
      civicrm_api3('SmsProvider', 'create', $recData);
    }
  }

}
