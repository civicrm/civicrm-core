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
  'event_show_payment_during_registration' => [
    'name' => 'event_show_payment_during_registration',
    'settings_pages' => ['event' => ['weight' => 90]],
    'type' => 'Boolean',
    'default' => FALSE,
    'add' => '6.10',
    'title' => ts('Show Price-Fields during Registrations*?'),
    'html_type' => 'checkbox',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('This setting just affects event registrations that have approval enabled and pay-later enabled. If this option is enabled, the price fields of the event are already visible and selectable at the initial event registration form. This allows participants to select their price option during registration and selected prices can already be considered during approval.'),
    'help_text' => NULL,
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
