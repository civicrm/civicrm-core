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
   * @param string $mail
   *   Email id for cms user.
   *
   * @return int
   *   contact id that has been created
   */
  public static function create(&$params, $mail) {
    $config = CRM_Core_Config::singleton();

    $ufID = $config->userSystem->createUser($params, $mail);

    //if contact doesn't already exist create UF Match
    if ($ufID !== FALSE &&
      isset($params['contactID'])
    ) {
      // create the UF Match record
      $ufmatch['uf_id'] = $ufID;
      $ufmatch['contact_id'] = $params['contactID'];
      $ufmatch['uf_name'] = $params[$mail];
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

    $isDrupal = $config->userSystem->is_drupal;
    $isJoomla = ucfirst($config->userFramework) == 'Joomla' ? TRUE : FALSE;
    $isWordPress = $config->userFramework == 'WordPress' ? TRUE : FALSE;

    //if CMS is configured for not to allow creating new CMS user,
    //don't build the form,Fixed for CRM-4036
    if ($isJoomla) {
      $userParams = JComponentHelper::getParams('com_users');
      if (!$userParams->get('allowUserRegistration')) {
        return FALSE;
      }
    }
    elseif ($isDrupal && !variable_get('user_register', TRUE)) {
      return FALSE;
    }
    elseif ($isWordPress && !get_option('users_can_register')) {
      return FALSE;
    }

    if ($gid) {
      $isCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'is_cms_user');
    }

    // $cms is true when there is email(primary location) is set in the profile field.
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
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
          $extra = array(
            'onclick' => "return showHideByValue('cms_create_account','','details','block','radio',false );",
          );
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
          if (($isDrupal && !variable_get('user_email_verification', TRUE)) OR ($isJoomla) OR ($isWordPress)) {
            $form->add('password', 'cms_pass', ts('Password'));
            $form->add('password', 'cms_confirm_pass', ts('Confirm Password'));
          }

          $form->addFormRule(array('CRM_Core_BAO_CMSUser', 'formRule'), $form);
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

    $isDrupal = $config->userSystem->is_drupal;
    $isJoomla = ucfirst($config->userFramework) == 'Joomla' ? TRUE : FALSE;
    $isWordPress = $config->userFramework == 'WordPress' ? TRUE : FALSE;

    $errors = array();
    if ($isDrupal || $isJoomla || $isWordPress) {
      $emailName = NULL;
      if (!empty($form->_bltID) && array_key_exists("email-{$form->_bltID}", $fields)) {
        // this is a transaction related page
        $emailName = 'email-' . $form->_bltID;
      }
      else {
        // find the email field in a profile page
        foreach ($fields as $name => $dontCare) {
          if (substr($name, 0, 5) == 'email') {
            $emailName = $name;
            break;
          }
        }
      }

      if ($emailName == NULL) {
        $errors['_qf_default'] == ts('Could not find an email address.');
        return $errors;
      }

      if (empty($fields['cms_name'])) {
        $errors['cms_name'] = ts('Please specify a username.');
      }

      if (empty($fields[$emailName])) {
        $errors[$emailName] = ts('Please specify a valid email address.');
      }

      if (($isDrupal && !variable_get('user_email_verification', TRUE)) OR ($isJoomla) OR ($isWordPress)) {
        if (empty($fields['cms_pass']) ||
          empty($fields['cms_confirm_pass'])
        ) {
          $errors['cms_pass'] = ts('Please enter a password.');
        }
        if ($fields['cms_pass'] != $fields['cms_confirm_pass']) {
          $errors['cms_pass'] = ts('Password and Confirm Password values are not the same.');
        }
      }

      if (!empty($errors)) {
        return $errors;
      }

      // now check that the cms db does not have the user name and/or email
      if ($isDrupal OR $isJoomla OR $isWordPress) {
        $params = array(
          'name' => $fields['cms_name'],
          'mail' => $fields[$emailName],
        );
      }

      $config->userSystem->checkUserNameEmailExists($params, $errors, $emailName);
    }
    return (!empty($errors)) ? $errors : TRUE;
  }

}
