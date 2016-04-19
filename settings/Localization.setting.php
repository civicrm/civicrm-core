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
/*
 * Settings metadata file
 */

return array(
  'monetaryThousandSeparator' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'monetaryThousandSeparator',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 2,
    ),
    'default' => ',',
    'add' => '4.3',
    'title' => 'Thousands Separator',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'monetaryDecimalPoint' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'monetaryDecimalPoint',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 2,
    ),
    'default' => '.',
    'add' => '4.3',
    'title' => 'Decimal Delimiter',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'moneyformat' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'moneyformat',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => '%c %a',
    'add' => '4.3',
    'title' => 'Monetary Amount Display',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'moneyvalueformat' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'moneyvalueformat',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => '%!i',
    'add' => '4.3',
    'title' => 'Monetary Amount Display',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'defaultCurrency' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'defaultCurrency',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 2,
    ),
    'default' => 'USD',
    'add' => '4.3',
    'title' => 'Default Currency',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Default currency assigned to contributions and other monetary transactions.',
    'help_text' => NULL,
  ),
  'defaultContactCountry' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'defaultContactCountry',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 4,
    ),
    'default' => '1228',
    'add' => '4.4',
    'title' => 'Default Country',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'This value is selected by default when adding a new contact address.',
    'help_text' => NULL,
  ),
  'countryLimit' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'countryLimit',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'Array',
    'quick_form_type' => 'Element',
    'html_type' => 'advmultiselect',
    'html_attributes' => array(
      'size' => 5,
      'style' => 'width:150px',
      'class' => 'advmultiselect',
    ),
    'default' => 'null',
    'add' => '4.3',
    'title' => 'Available Countries',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'provinceLimit' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'provinceLimit',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'Array',
    'quick_form_type' => 'Element',
    'html_type' => 'advmultiselect',
    'html_attributes' => array(
      'size' => 5,
      'style' => 'width:150px',
      'class' => 'advmultiselect',
    ),
    'default' => 'null',
    'add' => '4.3',
    'title' => 'Available States and Provinces',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'inheritLocale' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'inheritLocale',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '0',
    'add' => '4.3',
    'title' => 'Inherit CMS Language',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'dateformatDatetime' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'dateformatDatetime',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'default' => '%B %E%f, %Y %l:%M %P',
    'add' => '4.3',
    'title' => 'Complete Date and Time',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'dateformatFull' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'dateformatFull',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'default' => '%B %E%f, %Y',
    'add' => '4.3',
    'title' => 'Complete Date',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'dateformatPartial' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'dateformatPartial',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'default' => '%B %Y',
    'add' => '4.3',
    'title' => 'Month and Year',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'lcMessages' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'lcMessages',
    'prefetch' => 1,
    // prefetch causes it to be cached in config settings. Usually this is a transitional setting. Some things like urls are permanent. Remove this comment if you have assessed & it should be permanent
    'config_only' => 1,
    //@todo - see https://wiki.civicrm.org/confluence/display/CRMDOC/Settings+Reference#SettingsReference-Convertingaconfigobjecttoasetting on removing this deprecated value
    'type' => 'String',
    'default' => 'en_US',
    'add' => '4.3',
    'title' => 'Default Language',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'contact_default_language' => array(
    'group_name' => 'Localization Preferences',
    'group' => 'localization',
    'name' => 'contact_default_language',
    'type' => 'String',
    'default' => '*default*',
    'add' => '4.7',
    'title' => 'Default Language for contacts',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Default language (if any) for contact records',
    'help_text' => 'If a contact is created with no language this setting will determine the language data (if any) to save.'
    . 'You may or may not wish to make an assumption here about whether it matches the site language',
  ),
);
