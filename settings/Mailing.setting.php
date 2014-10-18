<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
/*
 * Settings metadata file
 */

return array(
  'profile_double_optin' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'profile_double_optin',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Enable Double Opt-in for Profile Group(s) field',
    'is_domain' => 1,
    'is_contact' => 0,
     'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => null,
  ),
  'track_civimail_replies' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'track_civimail_replies',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Enable Double Opt-in for Profile Group(s) field',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => null,
    'validate_callback' => 'CRM_Core_BAO_Setting::validateBoolSetting',
  ),
  'civimail_workflow' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'civimail_workflow',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Use CiviMail Workflow',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => null,
  ),
  'civimail_server_wide_lock' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'civimail_server_wide_lock',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Lock Mails Server-Wide for Mail Sending',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => null,
    'help_text' => null,
  ),
  'mailing_backend' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailing_backend',
    'type' => 'Array',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Mailing Backend',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => null,
    'help_text' => null,
  ),
  'profile_double_optin' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'profile_double_optin',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Enable Double Opt-in for Profile Group(s) field',
    'is_domain' => 1,
    'is_contact' => 0,
     'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => null,
  ),
  'profile_add_to_group_double_optin' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'profile_add_to_group_double_optin',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.1',
    'title' => 'Enable Double Opt-in for Profile Group(s) field',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => null,
  ),
  'disable_mandatory_tokens_check' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'disable_mandatory_tokens_check',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.4',
    'title' => 'Disable check for mandatory tokens',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Don\'t check for presence of mandatory tokens (domain address; unsubscribe/opt-out) before sending mailings. WARNING: Mandatory tokens are a safe-guard which facilitate compliance with the US CAN-SPAM Act. They should only be disabled if your organization adopts other mechanisms for compliance or if your organization is not subject to CAN-SPAM.',
    'help_text' => null,
  ),
  'dedupe_email_default' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'dedupe_email_default',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '4.5',
    'title' => 'CiviMail dedupes e-mail addresses by default',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Set the "dedupe e-mail" option when sending a new mailing to "true" by default.',
    'help_text' => null,
  ),
  'hash_mailing_url' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'hash_mailing_url',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.5',
    'title' => 'Hashed Mailing URL\'s',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If enabled, a randomized hash key will be used to reference the mailing URL in the mailing.viewUrl token, instead of the mailing ID',
    'help_text' => null,
  ),
);
