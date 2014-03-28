<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
/*
 * Settings metadata file
 */

return array(
  'tag_unconfirmed' => array(
    'group_name' => 'Campaign Preferences',
    'group' => 'campaign',
    'name' => 'tag_unconfirmed',
    'type' => 'String',
    'html_type' => 'Text',
    'default' => 'Unconfirmed',
    'add' => '4.1',
    'title' => 'Tag for Unconfirmed Petition Signers',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => null,
    'help_text' => 'If set, new contacts that are created when signing a petition are assigned a tag of this name.',
  ),
  'petition_contacts' => array(
    'group_name' => 'Campaign Preferences',
    'group' => 'campaign',
    'name' => 'petition_contacts',
    'type' => 'String',
    'html_type' => 'Text',
    'default' => 'Petition Contacts',
    'add' => '4.1',
    'title' => 'Petition Signers Group',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => null,
    'help_text' => 'If set, new contacts that are created when signing a petition are assigned a tag of this name.',
  ),

);
