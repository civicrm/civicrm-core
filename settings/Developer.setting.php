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
  'debug_enabled' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'debug_enabled',
    'config_only' => 1, // store only in config - this is expected to be transitional
    'config_key' => 'debug', // we can't call the setting debug as that has other meanings in api
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Enable Debugging',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Set this value to Yes if you want to use one of CiviCRM's debugging tools. This feature should NOT be enabled for production sites",
    'prefetch' => 1,
    'help_text' => 'Do not turn this on on production sites',
  ),
  'userFrameworkLogging' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'userFrameworkLogging',
    'config_only' => 1, // store only in config - this is expected to be transitional
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Enable Drupal Watchdog Logging',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Set this value to Yes if you want CiviCRM error/debugging messages to also appear in Drupal error logs",
    'prefetch' => 1,
    'help_text' => "Set this value to Yes if you want CiviCRM error/debugging messages the appear in your CMS' error log.
In the case of Drupal, this will cause all CiviCRM error messages to appear in the watchdog (assuming you have Drupal's watchdog enabled)",
  ),
  'backtrace' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'backtrace',
    'config_only' => 1, // store only in config - this is expected to be transitional
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Display Backtrace',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Set this value to Yes if you want to display a backtrace listing when a fatal error is encountered. This feature should NOT be enabled for production sites",
    'prefetch' => 1,
  ),
  'fatalErrorTemplate' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'fatalErrorTemplate',
    'config_only' => 1, // store only in config - this is expected to be transitional
    'type' => 'String',
    'quick_form_type' => 'text',
    'default' => 'CRM/common/fatal.tpl',
    'add' => '4.3',
    'title' => 'Fatal Error Template',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Enter the path and filename for a custom Smarty template if you want to define your own screen for displaying fatal errors.",
    'prefetch' => 1,
  ),
  'fatalErrorHandler' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'fatalErrorHandler',
    'config_only' => 1, // store only in config - this is expected to be transitional
    'type' => 'String',
    'quick_form_type' => 'text',
    'default' => 'null',
    'add' => '4.3',
    'title' => 'Fatal Error Handler',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Enter the path and class for a custom PHP error-handling function if you want to override built-in CiviCRM error handling for your site.",
    'prefetch' => 1,
  ),
);