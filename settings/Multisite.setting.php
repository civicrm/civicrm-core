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
  'is_enabled' => array(
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'is_enabled',
    'title' => ts('Enable Multi Site Configuration'),
    'html_type' => 'checkbox',
    'type' => 'Boolean',
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Make CiviCRM aware of multiple domains. You should configure a domain group if enabled'),
    'documentation_link' => ['page' => 'Multi Site Installation', 'resource' => 'wiki'],
    'help_text' => NULL,
  ),
  'domain_group_id' => array(
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'domain_group_id',
    'title' => ts('Multisite Domain Group'),
    'type' => 'Integer',
    'html_type' => 'entity_reference',
    'entity_reference_options' => ['entity' => 'group', 'select' => array('minimumInputLength' => 0)],
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Contacts created on this site are added to this group'),
    'help_text' => NULL,
  ),
  'event_price_set_domain_id' => array(
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'event_price_set_domain_id',
    'title' => 'Domain Event Price Set',
    'type' => 'Integer',
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'uniq_email_per_site' => array(
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'uniq_email_per_site',
    'type' => 'Integer',
    'title' => 'Unique Email per Domain?',
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
);
