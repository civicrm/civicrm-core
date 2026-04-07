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
  'tag_unconfirmed' => [
    'group_name' => 'Campaign Preferences',
    'group' => 'campaign',
    'name' => 'tag_unconfirmed',
    'type' => 'String',
    'html_type' => 'text',
    'default' => 'Unconfirmed',
    'add' => '4.1',
    'title' => ts('Tag for Unconfirmed Petition Signers'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('If set, new contacts that are created when signing a petition are assigned a tag of this name.'),
    'help_text' => '',
    'settings_pages' => ['campaign' => ['weight' => 10]],
  ],
  'petition_contacts' => [
    'group_name' => 'Campaign Preferences',
    'group' => 'campaign',
    'name' => 'petition_contacts',
    'type' => 'String',
    'html_type' => 'text',
    'default' => 'Petition Contacts',
    'add' => '4.1',
    'title' => ts('Petition Signers Group'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('All contacts that have signed a CiviCampaign petition will be added to this group. The group will be created if it does not exist (it is required for email verification).'),
    'help_text' => '',
    'settings_pages' => ['campaign' => ['weight' => 20]],
  ],

];
