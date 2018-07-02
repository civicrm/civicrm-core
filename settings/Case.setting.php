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

/**
 * Settings metadata file
 */
return array(
  'civicaseRedactActivityEmail' => array(
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseRedactActivityEmail',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      //'class' => 'crm-select2',
    ),
    'default' => 'default',
    'add' => '4.7',
    'title' => 'Redact Activity Email',
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => array(
      'callback' => 'CRM_Case_Info::getRedactOptions',
    ),
    'description' => 'Should activity emails be redacted? (Set "Default" to load setting from the legacy "Settings.xml" file.)',
    'help_text' => '',
  ),
  'civicaseAllowMultipleClients' => array(
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseAllowMultipleClients',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      //'class' => 'crm-select2',
    ),
    'default' => 'default',
    'add' => '4.7',
    'title' => 'Allow Multiple Case Clients',
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => array(
      'callback' => 'CRM_Case_Info::getMultiClientOptions',
    ),
    'description' => 'How many clients may be associated with a given case? (Set "Default" to load setting from the legacy "Settings.xml" file.)',
    'help_text' => '',
  ),
  'civicaseNaturalActivityTypeSort' => array(
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseNaturalActivityTypeSort',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      //'class' => 'crm-select2',
    ),
    'default' => 'default',
    'add' => '4.7',
    'title' => 'Activity Type Sorting',
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => array(
      'callback' => 'CRM_Case_Info::getSortOptions',
    ),
    'description' => 'How to sort activity-types on the "Manage Case" screen? (Set "Default" to load setting from the legacy "Settings.xml" file.)',
    'help_text' => '',
  ),
  'civicaseActivityRevisions' => array(
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseActivityRevisions',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => FALSE,
    'html_type' => 'radio',
    'add' => '4.7',
    'title' => 'Enable Embedded Activity Revisions',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Enable tracking of activity revisions embedded within the "civicrm_activity" table. Alternatively, see "Administer => System Settings => Misc => Logging".',
    'help_text' => '',
  ),
);
