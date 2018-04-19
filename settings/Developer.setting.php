<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 *
 */
/*
 * Settings metadata file
 */

return array(
  'assetCache' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'assetCache',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      //'class' => 'crm-select2',
    ),
    'default' => 'auto',
    'add' => '4.7',
    'title' => 'Asset Caching',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Store computed JS/CSS content in cache files? (Note: In "Auto" mode, the "Debug" setting will determine whether to activate the cache.)',
    'help_text' => NULL,
    'pseudoconstant' => array(
      'callback' => '\Civi\Core\AssetBuilder::getCacheModes',
    ),
  ),
  'userFrameworkLogging' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'userFrameworkLogging',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Enable Drupal Watchdog Logging',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Set this value to Yes if you want CiviCRM error/debugging messages to appear in the Drupal error logs",
    'help_text' => "Set this value to Yes if you want CiviCRM error/debugging messages the appear in your CMS' error log. In the case of Drupal, this will cause all CiviCRM error messages to appear in the watchdog (assuming you have Drupal's watchdog enabled)",
  ),
  'debug_enabled' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'debug_enabled',
    'config_key' => 'debug', // we can't call the setting debug as that has other meanings in api
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Enable Debugging',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Set this value to Yes if you want to use one of CiviCRM's debugging tools. This feature should NOT be enabled for production sites",
    'help_text' => 'Do not turn this on on production sites',
  ),
  'backtrace' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'backtrace',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Display Backtrace',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Set this value to Yes if you want to display a backtrace listing when a fatal error is encountered. This feature should NOT be enabled for production sites",
  ),
  'environment' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'environment',
    'type' => 'String',
    'html_type' => 'Select',
    'quick_form_type' => 'Select',
    'default' => 'Production',
    'pseudoconstant' => array(
      'optionGroupName' => 'environment',
    ),
    'add' => '4.7',
    'title' => 'Environment',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Setting to define the environment in which this CiviCRM instance is running.",
    'on_change' => array(
      'CRM_Core_BAO_Setting::onChangeEnvironmentSetting',
    ),
  ),
  'fatalErrorHandler' => array(
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'fatalErrorHandler',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => NULL,
    'add' => '4.3',
    'title' => 'Fatal Error Handler',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Enter the path and class for a custom PHP error-handling function if you want to override built-in CiviCRM error handling for your site.",
  ),
);
