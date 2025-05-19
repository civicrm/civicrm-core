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
  'show_events' => [
    'name' => 'show_events',
    'group_name' => 'Event Preferences',
    'group' => 'event',
    'settings_pages' => ['event' => ['weight' => 20]],
    'type' => 'Integer',
    'quick_form_type' => 'Select',
    'default' => 10,
    'add' => '4.5',
    'title' => ts('Dashboard entries'),
    'html_type' => 'select',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Configure how many events should be shown on the dashboard. This overrides the default value of 10 entries.'),
    'help_text' => NULL,
    'pseudoconstant' => ['callback' => 'CRM_Core_SelectValues::getDashboardEntriesCount'],
  ],
  'event_show_payment_on_confirm' => [
    'name' => 'event_show_payment_on_confirm',
    'settings_pages' => ['event' => ['weight' => 100]],
    'type' => 'Array',
    'default' => [],
    'add' => '5.58',
    'title' => ts('Show Event Payment on Confirm?'),
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
      'multiple' => TRUE,
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Should payment element be shown on the confirmation page instead of the first page?'),
    'help_text' => NULL,
    'pseudoconstant' => ['callback' => 'CRM_Event_BAO_Event::getEventsForSelect2'],
  ],
];
