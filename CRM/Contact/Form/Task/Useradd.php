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
 * This class generates form components generic to useradd.
 */
class CRM_Contact_Form_Task_Useradd extends CRM_Core_Form {

  /**
   * The contact id, used when adding user
   *
   * @var int
   */
  protected $_contactId;

  /**
   * Contact.display_name of contact for whom we are adding user
   *
   * @var int
   */
  public $_displayName;

  /**
   * Primary email of contact for whom we are adding user.
   *
   * @var int
   */
  public $_email;

  public function preProcess() {
    $params = $defaults = $ids = [];

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $params['id'] = $params['contact_id'] = $this->_contactId;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, $ids);
    $this->_displayName = $contact->display_name;
    $this->_email = $contact->email;
    $this->setTitle(ts('Create User Record for %1', [1 => $this->_displayName]));
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults['contactID'] = $this->_contactId;
    $defaults['name'] = $this->_displayName;
    if (!empty($this->_email)) {
      $defaults['email'] = $this->_email[1]['email'];
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $element = $this->add('text', 'name', ts('Full Name'), ['class' => 'huge']);
    $element->freeze();
    $this->add('text', 'cms_name', ts('Username'), ['class' => 'huge']);
    $this->addRule('cms_name', ts('Username is required'), 'required');

    // WordPress may or may not require setting passwords via magic link, depending on its configuration.
    // For other CMS, output the password fields
    if ($config->userSystem->showPasswordFieldWhenAdminCreatesUser()) {
      $this->add('password', 'cms_pass', ts('Password'), ['class' => 'huge']);
      $this->add('password', 'cms_confirm_pass', ts('Confirm Password'), ['class' => 'huge']);
      $this->addRule('cms_pass', ts('Password is required'), 'required');
      $this->addFormRule(['CRM_Contact_Form_Task_Useradd', 'passwordMatch']);
    }

    $this->add('text', 'email', ts('Email'), ['class' => 'huge'])->freeze();
    $this->addRule('email', ts('Email is required'), 'required');
    $this->add('hidden', 'contactID');

    //add a rule to check username uniqueness
    $this->addFormRule(['CRM_Contact_Form_Task_Useradd', 'usernameRule']);

    $this->addButtons(
      [
        [
          'type' => 'next',
          'name' => ts('Add'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
    $this->setDefaults($this->setDefaultValues());
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if (CRM_Core_BAO_CMSUser::create($params, 'email') === FALSE) {
      CRM_Core_Error::statusBounce(ts('Error creating CMS user account.'));
    }
    else {
      CRM_Core_Session::setStatus(ts('User Added'), '', 'success');
    }
  }

  /**
   * Validation Rule.
   *
   * @param array $params
   *
   * @return array|bool
   */
  public static function usernameRule($params) {
    $config = CRM_Core_Config::singleton();
    $errors = [];
    $check_params = [
      'name' => $params['cms_name'],
      'mail' => $params['email'],
    ];
    $config->userSystem->checkUserNameEmailExists($check_params, $errors);

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Validation Rule.
   *
   * @param array $params
   *
   * @return array|bool
   */
  public static function passwordMatch($params) {
    if ($params['cms_pass'] !== $params['cms_confirm_pass']) {
      return ['cms_pass' => ts('Password mismatch')];
    }
    return TRUE;
  }

}
