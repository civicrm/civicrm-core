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
  'enable_cart' => array(
    'name' => 'enable_cart',
    'group_name' => 'Event Preferences',
    'group' => 'event',
    'type' => 'Boolean',
    'quick_form_type' => 'Element',
    'default' => '0',
    'add' => '4.1',
    'title' => 'Enable Event Cart',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "WRITE ME",
    'help_text' => 'WRITE ME',
  ),
  'show_events' => array(
    'name' => 'show_events',
    'group_name' => 'Event Preferences',
    'group' => 'event',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'default' => 10,
    'add' => '4.5',
    'title' => 'Dashboard entries',
    'html_type' => 'select',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Configure how many events should be shown on the dashboard. This overrides the default value of 10 entries.",
    'help_text' => NULL,
  ),
);
