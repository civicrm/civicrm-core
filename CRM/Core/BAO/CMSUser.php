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
 * This file contains functions for synchronizing cms users with CiviCRM contacts.
 */

/**
 * Class CRM_Core_BAO_CMSUser
 */
class CRM_Core_BAO_CMSUser {

  /**
   * Create CMS user using Profile.
   *
   * @param array $params
   * @param string $mailParam
   *   Name of the param which contains the email address.
   *   Because. Right. OK. That's what it is.
   *
   * @return int
   *   contact id that has been created
   */
  public static function create(&$params, $mailParam) {
    $config = CRM_Core_Config::singleton();

    $ufID = $config->userSystem->createUser($params, $mailParam);

    // Create UF Match if we have contactID unless we're Standalone
    // since in Standalone uf_match is the same table as User.
    if (
      CIVICRM_UF !== 'Standalone'
      && $ufID !== FALSE
      && isset($params['contactID'])
    ) {
      // create the UF Match record
      $ufmatch['uf_id'] = $ufID;
      $ufmatch['contact_id'] = $params['contactID'];
      $ufmatch['uf_name'] = $params[$mailParam];
      CRM_Core_BAO_UFMatch::create($ufmatch);
    }

    return $ufID;
  }

  /**
   * Create Form for CMS user using Profile.
   *
   * @param CRM_Core_Form $form
   * @param int $gid
   *   Id of group of profile.
   * @param bool $emailPresent
   *   True if the profile field has email(primary).
   * @param \const|int $action
   *
   * @return FALSE|void
   *   WTF
   *
   */
  public static function buildForm(&$form, $gid, $emailPresent, $action = CRM_Core_Action::NONE) {
    $config = CRM_Core_Config::singleton();
    $showCMS = FALSE;

    if (!$config->userSystem->isUserRegistrationPermitted()) {
      // Do not build form if CMS is not configured to allow creating users.
      $form->assign('showCMS', $showCMS);
      return FALSE;
    }

    if ($gid) {
      $isCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'is_cms_user');
    }

    // $cms is true when there is email(primary location) is set in the profile field.
    $userID = CRM_Core_Session::singleton()->get('userID');
    $showUserRegistration = FALSE;
    if ($action) {
      $showUserRegistration = TRUE;
    }
    elseif (!$action && !$userID) {
      $showUserRegistration = TRUE;
    }

    if ($isCMSUser && $emailPresent) {
      if ($showUserRegistration) {
        if ($isCMSUser != 2) {
          $extra = [
            'onclick' => "return showHideByValue('cms_create_account','','details','block','radio',false );",
          ];
          $form->addElement('checkbox', 'cms_create_account', ts('Create an account?'), NULL, $extra);
          $required = FALSE;
        }
        else {
          $form->add('hidden', 'cms_create_account', 1);
          $required = TRUE;
        }

        $form->assign('isCMS', $required);
        if (!$userID || $action & CRM_Core_Action::PREVIEW || $action & CRM_Core_Action::PROFILE) {
          $form->add('text', 'cms_name', ts('Username'), NULL, $required);
          if ($config->userSystem->isPasswordUserGenerated()) {
            $form->add('password', 'cms_pass', ts('Password'));
            $form->add('password', 'cms_confirm_pass', ts('Confirm Password'));
          }

          $form->addFormRule(['CRM_Core_BAO_CMSUser', 'formRule'], $form);
        }
        $showCMS = TRUE;
      }
    }

    $destination = $config->userSystem->getLoginDestination($form);
    $loginURL = $config->userSystem->getLoginURL($destination);
    $form->assign('loginURL', $loginURL);
    $form->assign('showCMS', $showCMS);
  }

  /**
   * Checks that there is a valid username & email
   * optionally checks password is present & matches DB & gets the CMS to validate
   *
   * @param array $fields
   *   Posted values of form.
   * @param array $files
   *   Uploaded files if any.
   * @param CRM_Core_Form $form
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $form) {
    if (empty($fields['cms_create_account'])) {
      return TRUE;
    }

    $config = CRM_Core_Config::singleton();

    $errors = [];

    $emailName = $config->userSystem->getEmailFieldName($form, $fields);

    $params = [
      'name' => $fields['cms_name'],
      'mail' => isset($fields[$emailName]) ? $fields[$emailName] : '',
      'pass' => isset($fields['cms_pass']) ? $fields['cms_pass'] : '',
    ];

    // Verify the password.
    if ($config->userSystem->isPasswordUserGenerated()) {
      $config->userSystem->verifyPassword($params, $errors);
    }

    // Set generic errors messages.
    if ($emailName == '') {
      $errors['_qf_default'] = ts('Could not find an email address.');
    }

    if (empty($params['name'])) {
      $errors['cms_name'] = ts('Please specify a username.');
    }

    if (empty($params['mail'])) {
      $errors[$emailName] = ts('Please specify a valid email address.');
    }

    if ($config->userSystem->isPasswordUserGenerated()) {
      if (empty($fields['cms_pass']) ||
        empty($fields['cms_confirm_pass'])
      ) {
        $errors['cms_pass'] = ts('Please enter a password.');
      }
      if ($fields['cms_pass'] != $fields['cms_confirm_pass']) {
        $errors['cms_pass'] = ts('Password and Confirm Password values are not the same.');
      }
    }

    $config->userSystem->checkUserNameEmailExists($params, $errors, $emailName);

    return (!empty($errors)) ? $errors : TRUE;
  }

}
