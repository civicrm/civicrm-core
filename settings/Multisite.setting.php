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
  'multisite_is_enabled' => [
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'multisite_is_enabled',
    'title' => ts('Enable Multi Site Configuration'),
    'html_type' => 'checkbox',
    'type' => 'Boolean',
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Make CiviCRM aware of multiple domains. You should configure a domain group if enabled'),
    'documentation_link' => ['page' => 'sysadmin/setup/multisite', 'resource' => ''],
    'help_text' => NULL,
    'settings_pages' => ['multisite' => ['weight' => 10]],
  ],
  'domain_group_id' => [
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'domain_group_id',
    'title' => ts('Multisite Domain Group'),
    'type' => 'Integer',
    'html_type' => 'entity_reference',
    'entity_reference_options' => ['entity' => 'Group', 'select' => ['minimumInputLength' => 0]],
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Contacts created on this site are added to this group'),
    'help_text' => NULL,
    'settings_pages' => ['multisite' => ['weight' => 20]],
  ],
  'event_price_set_domain_id' => [
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'event_price_set_domain_id',
    'title' => ts('Domain Event Price Set'),
    'type' => 'Integer',
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => NULL,
  ],
  'uniq_email_per_site' => [
    'group_name' => 'Multi Site Preferences',
    'group' => 'multisite',
    'name' => 'uniq_email_per_site',
    'type' => 'Integer',
    'title' => ts('Unique Email per Domain?'),
    'default' => '0',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => NULL,
  ],
];
