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
  'enable_cart' => [
    'name' => 'enable_cart',
    'group_name' => 'Event Preferences',
    'settings_pages' => ['event' => ['weight' => 10]],
    'group' => 'event',
    'type' => 'Boolean',
    'quick_form_type' => 'CheckBox',
    'default' => '0',
    'add' => '4.1',
    'title' => ts('Use Shopping Cart Style Event Registration'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('This feature allows users to register for more than one event at a time. When enabled, users will add event(s) to a "cart" and then pay for them all at once. Enabling this setting will affect online registration for all active events. The code is an alpha state, and you will potentially need to have developer resources to debug and fix sections of the codebase while testing and deploying it'),
    'help_text' => '',
    'documentation_link' => ['page' => 'CiviEvent Cart Checkout', 'resource' => 'wiki'],
  ],
];
