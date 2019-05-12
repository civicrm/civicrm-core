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
 * This class handles mail account settings.
 *
 */
class CRM_Admin_Form_MailSettings extends CRM_Admin_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Mail Account'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    //get the attributes.
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_MailSettings');

    //build setting form
    $this->add('text', 'name', ts('Name'), $attributes['name'], TRUE);

    $this->add('text', 'domain', ts('Email Domain'), $attributes['domain'], TRUE);
    $this->addRule('domain', ts('Email domain must use a valid internet domain format (e.g. \'example.org\').'), 'domain');

    $this->add('text', 'localpart', ts('Localpart'), $attributes['localpart']);

    $this->add('text', 'return_path', ts('Return-Path'), $attributes['return_path']);
    $this->addRule('return_path', ts('Return-Path must use a valid email address format.'), 'email');

    $this->add('select', 'protocol',
      ts('Protocol'),
      ['' => ts('- select -')] + CRM_Core_PseudoConstant::get('CRM_Core_DAO_MailSettings', 'protocol'),
      TRUE
    );

    $this->add('text', 'server', ts('Server'), $attributes['server']);

    $this->add('text', 'username', ts('Username'), ['autocomplete' => 'off']);

    $this->add('password', 'password', ts('Password'), ['autocomplete' => 'off']);

    $this->add('text', 'source', ts('Source'), $attributes['source']);

    $this->add('checkbox', 'is_ssl', ts('Use SSL?'));

    $usedfor = [
      1 => ts('Bounce Processing'),
      0 => ts('Email-to-Activity Processing'),
    ];
    $this->add('select', 'is_default', ts('Used For?'), $usedfor);
    $this->addField('activity_status', ['placeholder' => FALSE]);
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Admin_Form_MailSettings', 'formRule']);
  }

  public function getDefaultEntity() {
    return 'MailSettings';
  }

  /**
   * Add local and global form rules.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // Set activity status to "Completed" by default.
    if ($this->_action != CRM_Core_Action::DELETE &&
      (!$this->_id || !CRM_Core_DAO::getFieldValue('CRM_Core_BAO_MailSettings', $this->_id, 'activity_status'))
    ) {
      $defaults['activity_status'] = 'Completed';
    }

    return $defaults;
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
    $errors = [];
    // Check for default from email address and organization (domain) name. Force them to change it.
    if ($fields['domain'] == 'EXAMPLE.ORG') {
      $errors['domain'] = ts('Please enter a valid domain for this mailbox account (the part after @).');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_MailSettings::deleteMailSettings($this->_id);
      CRM_Core_Session::setStatus("", ts('Mail Setting Deleted.'), "success");
      return;
    }

    //get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    //form fields.
    $fields = [
      'name',
      'domain',
      'localpart',
      'server',
      'return_path',
      'protocol',
      'port',
      'username',
      'password',
      'source',
      'is_ssl',
      'is_default',
      'activity_status',
    ];

    $params = [];
    foreach ($fields as $f) {
      if (in_array($f, [
        'is_default',
        'is_ssl',
      ])) {
        $params[$f] = CRM_Utils_Array::value($f, $formValues, FALSE);
      }
      else {
        $params[$f] = CRM_Utils_Array::value($f, $formValues);
      }
    }

    $params['domain_id'] = CRM_Core_Config::domainID();

    // assign id only in update mode
    $status = ts('Your New  Email Settings have been saved.');
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
      $status = ts('Your Email Settings have been updated.');
    }

    $mailSettings = CRM_Core_BAO_MailSettings::create($params);

    if ($mailSettings->id) {
      CRM_Core_Session::setStatus($status, ts("Saved"), "success");
    }
    else {
      CRM_Core_Session::setStatus("", ts('Changes Not Saved.'), "info");
    }
  }

}
