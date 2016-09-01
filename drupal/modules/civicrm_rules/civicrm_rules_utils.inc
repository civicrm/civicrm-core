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
 * $Id$
 *
 */

/**
 * This file contains functions that calls civicrm apis for data manupulation / retrieval
 */

/**
 * Function to get the list of groups
 */
function _civicrm_get_groups() {
  civicrm_initialize();
  $groupInformation = civicrm_api('group', 'get', array('version' => 3));

  $groups = array();
  foreach ($groupInformation['values'] as $values) {
    $groups[$values['id']] = $values['title'];
  }

  return $groups;
}

/**
 * Function to add contacts to group
 */
function _civicrm_rules_group_contact($contactId, $groupId, $action = 'create') {
  civicrm_initialize();
  $params = array(
    'contact_id' => $contactId,
    'group_id' => $groupId,
    'version' => 3,
  );
  // This used to be civicrm_group_contact_common($params, $action);
  civicrm_api('group_contact', $action, $params);
}

/**
 * Function to get the list of tags
 */
function _civicrm_get_tags() {
  civicrm_initialize();
  $tagInformation = civicrm_api3('Tag', 'get', array('options' => array('limit' => 0),));

  $tags = array();
  foreach ($tagInformation['values'] as $values) {
    $tags[$values['id']] = $values['title'];
  }

  return $tags;
}

/**
 * Function to add tag to contacts
 */
function _civicrm_rules_tag_contact($contactId, $tagId, $action = 'create') {
  civicrm_initialize();
  $params = array(
    'entity_table' => "civicrm_contact",
    'entity_id' => $contactId,
    'tag_id' => $tagId,
  );
  
  civicrm_api3('EntityTag', $action, $params);
}

/**
 * Loads a contact into a version suitable for the rules actions
 */
function _civicrm_rules_load_contact($contactID) {
  $contact = new CRM_Contact_DAO_Contact();
  $contact->id = $contactID;

  if ($contact->id) {
    // load the contact data here
    $contact->find(TRUE);
  }

  return $contact;
}

function _civicrm_rules_in_group($contact, $group) {
  // check if contact is in the group gid
  return TRUE;
}

/**
 * Loads a mailing into a version suitable for the rules actions
 */
function _civicrm_rules_load_mailing($mailingID) {
  $mailing = new CRM_Mailing_DAO_Mailing();
  $mailing->id = $mailingID;

  if ($mailing->id) {
    // load the mailing data here
    $mailing->find(TRUE);
  }

  return $mailing;
}


/*
 * function to return labels for checkboxes & stuff
 */
function civicrm_rules_get_options($type) {
  if (!civicrm_initialize()) {
    return;
  }

  $result = civicrm_api('OptionValue', 'get', array('version' => 3, 'option_group_name' => $type));
  foreach ($result['values'] as $options) {
    $values[$options['value']] = $options['label'];
  }
  return $values;
}
