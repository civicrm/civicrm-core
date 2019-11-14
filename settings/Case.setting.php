<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2020
 * $Id$
 *
 */

/**
 * Settings metadata file
 */
return [
  'civicaseRedactActivityEmail' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseRedactActivityEmail',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      //'class' => 'crm-select2',
    ],
    'default' => 'default',
    'add' => '4.7',
    'title' => 'Redact Activity Email',
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Case_Info::getRedactOptions',
    ],
    'description' => 'Should activity emails be redacted? (Set "Default" to load setting from the legacy "Settings.xml" file.)',
    'help_text' => '',
  ],
  'civicaseAllowMultipleClients' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseAllowMultipleClients',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      //'class' => 'crm-select2',
    ],
    'default' => 'default',
    'add' => '4.7',
    'title' => 'Allow Multiple Case Clients',
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Case_Info::getMultiClientOptions',
    ],
    'description' => 'How many clients may be associated with a given case? (Set "Default" to load setting from the legacy "Settings.xml" file.)',
    'help_text' => '',
  ],
  'civicaseNaturalActivityTypeSort' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseNaturalActivityTypeSort',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      //'class' => 'crm-select2',
    ],
    'default' => 'default',
    'add' => '4.7',
    'title' => 'Activity Type Sorting',
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Case_Info::getSortOptions',
    ],
    'description' => 'How to sort activity-types on the "Manage Case" screen? (Set "Default" to load setting from the legacy "Settings.xml" file.)',
    'help_text' => '',
  ],
  'civicaseActivityRevisions' => [
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
  ],
];
