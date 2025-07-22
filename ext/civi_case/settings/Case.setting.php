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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    'title' => ts('Redact Activity Email'),
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Case_Info::getRedactOptions',
    ],
    'description' => ts('Should activity emails be redacted? (Set "Default" to load setting from the legacy "Settings.xml" file.)'),
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
    'title' => ts('Allow Multiple Case Clients'),
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Case_Info::getMultiClientOptions',
    ],
    'description' => ts('How many clients may be associated with a given case? (Set "Default" to load setting from the legacy "Settings.xml" file.)'),
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
    'title' => ts('Activity Type Sorting'),
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Case_Info::getSortOptions',
    ],
    'description' => ts('How to sort activity-types on the "Manage Case" screen? (Set "Default" to load setting from the legacy "Settings.xml" file.)'),
    'help_text' => '',
  ],
  'civicaseShowCaseActivities' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'civicaseShowCaseActivities',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => FALSE,
    'html_type' => 'radio',
    'add' => '5.24',
    'title' => ts('Include case activities in general activity views.'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('e.g. the Contact form\'s Activity tab listing. Without this ticked, activities that belong to a case are hidden (default behavior). Warning: enabling this option means that all case activities relating to a contact will be listed which could result in users without "access all cases and activities" permission being able to see see the summarized details (date, subject, assignees, status etc.). Such users will still be prevented from managing the case and viewing/editing the activity.'),
    'help_text' => '',
  ],
];
