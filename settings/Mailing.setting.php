<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
    'title' => null,
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
    'title' => null,
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
    'title' => null,
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
  );