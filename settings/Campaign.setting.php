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
  'tag_unconfirmed' => array(
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
  ),
  'petition_contacts' => array(
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
  ),

);
