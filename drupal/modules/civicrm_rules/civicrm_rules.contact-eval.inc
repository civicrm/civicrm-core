<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * @file
 * Contains rules integration for the user module needed during evaluation.
 *
 * @addtogroup rules
 * @{
 */
require_once 'civicrm_rules_utils.inc';
function civicrm_rules_rules_action_contact_send_email($to, $subject, $message, $from = NULL, $settings, RulesState$state, RulesPlugin$element) {

  $to = str_replace(array("\r", "\n"), ',', $to);
  $toEmails = explode(',', $to);
  if (empty($toEmails)) {
    return;
  }
  foreach ($toEmails as $toEmail) {
    $params            = array();
    $params['from']    = !empty($from) ? str_replace(array(
      "\r", "\n"), '', $from) : 'Admin';
    $params['toEmail'] = $toEmail;
    $params['subject'] = $subject;
    $params['text']    = is_array($message) ? implode("\n", $message) : $message;

    CRM_Utils_Mail::send($params);
  }
  // FIX ME: add watchdog
}

/**
 * Action contact: add a contact to a group
 */
function civicrm_rules_rules_action_add_to_group($contact, $settings) {
  $contactId = !empty($contact->id) ? $contact->id : $contact->getVar('_contactId');

  if ($contactId) {
    foreach ($settings as $gid) {
      _civicrm_rules_group_contact($contactId, $gid);
    }
    return array('contact' => $contact);
  }
}

/**
 * Action contact: remove a contact from a group
 */
function civicrm_rules_rules_action_remove_from_group($contact, $settings) {
  $contactId = !empty($contact->id) ? $contact->id : $contact->getVar('_contactId');
  if ($contactId) {
    foreach ($settings as $gid) {
      _civicrm_rules_group_contact($contactId, $gid, 'delete');
    }
    return array('contact' => $contact);
  }
}

/**
 * Action contact: add a contact to a group
 */
function civicrm_rules_rules_action_add_tag($contact, $settings) {
  $contactId = !empty($contact->id) ? $contact->id : $contact->getVar('_contactId');

  if ($contactId) {
    foreach ($settings as $tagid) {
      _civicrm_rules_tag_contact($contactId, $tagid);
    }
    return array('contact' => $contact);
  }
}

/**
 * Action contact: remove a contact from a group
 */
function civicrm_rules_rules_action_remove_tag($contact, $settings) {
  $contactId = !empty($contact->id) ? $contact->id : $contact->getVar('_contactId');
  if ($contactId) {
    foreach ($settings as $tagid) {
      _civicrm_rules_tag_contact($contactId, $tagid, 'delete');
    }
    return array('contact' => $contact);
  }
}

/**
 * Action: Creates a contact.
 * FIX ME: This function is not called, hence can be deleted
 */
function civicrm_rules_rules_contact_create($first_name, $last_name, $email) {
  $params = array(
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'version' => 3,
  );

  $contact = civicrm_api('contact', 'create', $params);

  if (!$contact['is_error']) {
    return array('contact' => _civicrm_rules_load_contact($contact['id']));
  }
  else {
    return array('contact' => NULL);
  }
}

/**
 * Options list callback for possible lising of CiviCRM Groups
 */
function civicrm_rules_options_list() {
  $settings['groups'] = _civicrm_get_groups();
  return $settings;
}

