<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 *  this file contains functions for synchronizing cms users with CiviCRM contacts
 */

require_once 'DB.php';

/**
 * Class CRM_Core_BAO_CMSUser
 */
class CRM_Core_BAO_CMSUser {

  /**
   * Synchronizing cms users with CiviCRM contacts.
   *
   * @param bool $is_interactive
   *   Whether to show statuses & perform redirects.
   *   This behavior is misplaced in the BAO layer, but we'll preserve it to avoid
   *   contract changes in the middle of the support cycle. In the next major
   *   release, we should remove & document it.
   *
   * @return void
   *
   */
  public static function synchronize($is_interactive = TRUE) {
    //start of schronization code
    $config = CRM_Core_Config::singleton();

    // Build an array of rows from UF users table.
    $rows = array();
    if ($config->userSystem->is_drupal == '1') {
      $id = 'uid';
      $mail = 'mail';
      $name = 'name';

      $result = db_query("SELECT uid, mail, name FROM {users} where mail != ''");

      if ($config->userFramework == 'Drupal') {
        while ($row = $result->fetchAssoc()) {
          $rows[] = $row;
        }
      }
      elseif ($config->userFramework == 'Drupal6') {
        while ($row = db_fetch_array($result)) {
          $rows[] = $row;
        }
      }
    }
    elseif ($config->userFramework == 'Joomla') {
      $id = 'id';
      $mail = 'email';
      $name = 'name';
      // TODO: Insert code here to populate $rows for Joomla;
    }
    elseif ($config->userFramework == 'WordPress') {
      $id = 'ID';
      $mail = 'user_email';
    }
    else {
      CRM_Core_Error::fatal('CMS user creation not supported for this framework');
    }

    set_time_limit(300);

    if ($config->userSystem->is_drupal == '1') {
      $user = new StdClass();
      $uf = $config->userFramework;
      $contactCount = 0;
      $contactCreated = 0;
      $contactMatching = 0;
      foreach ($rows as $row) {
        $user->$id = $row[$id];
        $user->$mail = $row[$mail];
        $user->$name = $row[$name];
        $contactCount++;
        if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $row[$id], $row[$mail], $uf, 1, 'Individual', TRUE)) {
          $contactCreated++;
        }
        else {
          $contactMatching++;
        }
        if (is_object($match)) {
          $match->free();
        }
      }
    }
    elseif ($config->userFramework == 'Joomla') {

      $JUserTable = &JTable::getInstance('User', 'JTable');

      $db = $JUserTable->getDbo();
      $query = $db->getQuery(TRUE);
      $query->select($id . ', ' . $mail . ', ' . $name);
      $query->from($JUserTable->getTableName());
      $query->where($mail != '');

      $db->setQuery($query, 0, $limit);
      $users = $db->loadObjectList();

      $user = new StdClass();
      $uf = $config->userFramework;
      $contactCount = 0;
      $contactCreated = 0;
      $contactMatching = 0;
      for ($i = 0; $i < count($users); $i++) {
        $user->$id = $users[$i]->$id;
        $user->$mail = $users[$i]->$mail;
        $user->$name = $users[$i]->$name;
        $contactCount++;
        if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user,
          $users[$i]->$id,
          $users[$i]->$mail,
          $uf,
          1,
          'Individual',
          TRUE
        )
        ) {
          $contactCreated++;
        }
        else {
          $contactMatching++;
        }
        if (is_object($match)) {
          $match->free();
        }
      }
    }
    elseif ($config->userFramework == 'WordPress') {
      $uf = $config->userFramework;
      $contactCount = 0;
      $contactCreated = 0;
      $contactMatching = 0;

      global $wpdb;
      $wpUserIds = $wpdb->get_col("SELECT $wpdb->users.ID FROM $wpdb->users");

      foreach ($wpUserIds as $wpUserId) {
        $wpUserData = get_userdata($wpUserId);
        $contactCount++;
        if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($wpUserData,
          $wpUserData->$id,
          $wpUserData->$mail,
          $uf,
          1,
          'Individual',
          TRUE
        )
        ) {
          $contactCreated++;
        }
        else {
          $contactMatching++;
        }
        if (is_object($match)) {
          $match->free();
        }
      }
    }
    //end of synchronization code

    if ($is_interactive) {
      $status = ts('Synchronize Users to Contacts completed.');
      $status .= ' ' . ts('Checked one user record.',
          array(
            'count' => $contactCount,
            'plural' => 'Checked %count user records.',
          )
        );
      if ($contactMatching) {
        $status .= ' ' . ts('Found one matching contact record.',
            array(
              'count' => $contactMatching,
              'plural' => 'Found %count matching contact records.',
            )
          );
      }

      $status .= ' ' . ts('Created one new contact record.',
          array(
            'count' => $contactCreated,
            'plural' => 'Created %count new contact records.',
          )
        );
      CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
    }
  }

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

  /**
   * @deprecated
   * This function is not used anywhere
   *
   * @param array $contact
   *   Array of contact-details.
   *
   * @return int|bool
   *   uid if user exists, false otherwise
   *
   */
  public static function userExists(&$contact) {
    $config = CRM_Core_Config::singleton();

    $isDrupal = $config->userSystem->is_drupal;
    $isJoomla = ucfirst($config->userFramework) == 'Joomla' ? TRUE : FALSE;
    $isWordPress = $config->userFramework == 'WordPress' ? TRUE : FALSE;

    if (!$isDrupal && !$isJoomla && !$isWordPress) {
      die('Unknown user framework');
    }

    // Use UF native framework to fetch data from UF user table
    if ($isDrupal) {
      $uid = db_query(
        "SELECT uid FROM {users} where mail = :email",
        array(':email' => $contact['email'])
      )->fetchField();

      if ($uid) {
        $contact['user_exists'] = TRUE;
        $result = $uid;
      }
    }
    elseif ($isJoomla) {
      $mail = $contact['email'];

      $JUserTable = &JTable::getInstance('User', 'JTable');

      $db = $JUserTable->getDbo();
      $query = $db->getQuery(TRUE);
      $query->select('username, email');
      $query->from($JUserTable->getTableName());
      $query->where('(LOWER(email) = LOWER(\'' . $email . '\'))');
      $db->setQuery($query, 0, $limit);
      $users = $db->loadAssocList();

      $row = array();;
      if (count($users)) {
        $row = $users[0];
      }

      if (!empty($row)) {
        $uid = CRM_Utils_Array::value('id', $row);
        $contact['user_exists'] = TRUE;
        $result = $uid;
      }
    }
    elseif ($isWordPress) {
      if (email_exists($params['mail'])) {
        $contact['user_exists'] = TRUE;
        $userObj = get_user_by('email', $params['mail']);
        return $userObj->ID;
      }
    }

    return $result;
  }

  /**
   * @param $config
   *
   * @return object
   */
  public static function &dbHandle(&$config) {
    $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    $db_uf = DB::connect($config->userFrameworkDSN);
    unset($errorScope);
    if (!$db_uf ||
      DB::isError($db_uf)
    ) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
      CRM_Core_Error::statusBounce(ts("Cannot connect to UF db via %1. Please check the CIVICRM_UF_DSN value in your civicrm.settings.php file",
        array(1 => $db_uf->getMessage())
      ));
    }
    $db_uf->query('/*!40101 SET NAMES utf8 */');
    return $db_uf;
  }

}
