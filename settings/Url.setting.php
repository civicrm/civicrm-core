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
/*
 * Settings metadata file
 */
return array(
  'userFrameworkResourceURL' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'userFrameworkResourceURL',
    'title' => 'CiviCRM Resource URL',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Absolute URL of the location where the civicrm module or component has been installed.',
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
  ),
  'imageUploadURL' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'title' => 'Image Upload URL',
    'name' => 'imageUploadURL',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'URL of the location for uploaded image files.',
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
  ),
  'customCSSURL' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'customCSSURL',
    'title' => 'Custom CSS URL',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'You can modify the look and feel of CiviCRM by adding your own stylesheet. For small to medium sized modifications, use your css file to override some of the styles in civicrm.css. Or if you need to make drastic changes, you can choose to disable civicrm.css completely.',
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
  ),
  'extensionsURL' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'title' => 'Extension Resource URL',
    'name' => 'extensionsURL',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Base URL for extension resources (images, stylesheets, etc). This should match extensionsDir.',
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
  ),
);
