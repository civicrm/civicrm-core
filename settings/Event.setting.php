<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Settings metadata file
 */
return array(
  'enable_cart' => array(
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
  ),
  'show_events' => array(
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
  ),
);
