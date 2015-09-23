<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * Utilties for Drupal 7 compatibility
 */
function _civicrm_get_user_table_name() {
  if (function_exists('db_select')) {
    //docs say 'user', but not the schema in alpha 3.
    $user_tab = 'users';
  }
  else {
    $user_tab = 'users';
  }
}

/**
 * Create a Drupal user and return Drupal ID
 *
 * @param       email   email address of new user
 *
 * @return      res     Drupal ID for new user or FALSE if error
 */
function civicrm_drupal_create_user($email, $rid = NULL) {

  $email = trim($email);

  if (empty($email)) {
    return FALSE;
  }

  $user_tab = _civicrm_get_user_table_name();

  // If user already exists, return Drupal id
  $uid = db_result(db_query("SELECT uid FROM {$user_tab} WHERE mail = '%s'", $email));
  if ($uid) {
    return $uid;
  }

  // escape email to prevent sql injection
  $dao = new CRM_Core_DAO();
  $email = $dao->escape($email);

  // Default values for new user
  $params = array();
  //WARNING -- this is likely *wrong* since it will crash Drupal 6.
  //calling conventions for Drupal 7 are different, as well.
  //$params['uid']     = db_next_id('{users}_uid');

  $params['name']   = $email;
  $params['pass']   = md5(uniqid(rand(), TRUE));
  $params['mail']   = $email;
  $params['mode']   = 0;
  $params['access'] = 0;
  // don't allow user to login until verified
  $params['status']  = 0;
  $params['init']    = $email;
  $params['created'] = time();

  $db_fields = '(';
  $db_values = '(';
  foreach ($params as $key => $value) {
    $db_fields .= "$key,";
    $db_values .= "'$value',";
  }
  $db_fields = rtrim($db_fields, ",");
  $db_values = rtrim($db_values, ",");

  $db_fields .= ')';
  $db_values .= ')';

  $q = "INSERT INTO {$user_tab} $db_fields VALUES $db_values";
  db_query($q);

  if ($rid) {
    // Delete any previous roles entry before adding the role id
    //NOTE: weirdly, D7 schema from alpha 3 allows the following:
    db_query('DELETE FROM {users_roles} WHERE uid = %d', $params['uid']);
    db_query('INSERT INTO {users_roles} (uid, rid) VALUES (%d, %d)', $params['uid'], $rid);
  }

  return $params['uid'];
}

/**
 * Get the role id for a given name
 *
 * @param string $name name of the role
 *
 * @return int the role id
 * @static
 */
function civicrm_drupal_role_id($name) {
  $roleIDs = user_roles();
  $roleNames = array_flip($roleIDs);
  return array_key_exists($name, $roleNames) ? $roleNames[$name] : NULL;
}

/**
 * Check status of Drupal user
 *
 * @param       id      Drupal ID of user
 *
 * @return      status  Status of user
 */
function civicrm_drupal_is_user_verified($id) {
  if (!$id) {
    return FALSE;
  }

  $params = array();
  $params['uid'] = $id;

  $user = user_load($params);

  if (!$user->uid) {
    return FALSE;
  }

  return $user->status;
}

/**
 * Verify user and update user's status
 *
 * @param       params  User fields, includes email
 */
function civicrm_drupal_user_update_and_redirect($params) {
  global $user;

  if (!($params['email'] && $params['drupalID'] && $params['password'])) {
    return FALSE;
  }

  $user_fields['uid']  = $params['drupalID'];
  $user_fields['mail'] = $params['email'];
  $user                = user_load($user_fields);

  if (!$user->uid) {
    return FALSE;
  }

  $update           = array();
  $update['status'] = 1;
  $update['pass']   = $params['password'];

  $user = user_save($user, $update);

  // Login the user
  $edit = array();
  user_module_invoke('login', $edit, $user);

  // redirect user to locker
  drupal_goto('locker');
}
//end func civicrm_drupal_user_update_and_redirect

