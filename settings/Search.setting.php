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
return array (
  'search_autocomplete_count' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'search_autocomplete_count',
    'prefetch' => 0,
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 2,
      'maxlength' => 2,
    ),
    'default' => 10,
    'add' => '4.3',
    'title' => 'Autocomplete Results',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'The maximum number of contacts to show at a time when typing in an autocomplete field.',
    'help_text' => null,
  ),
  'enable_innodb_fts' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'enable_innodb_fts',
    'prefetch' => 0,
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 0,
    'add' => '4.4',
    'title' => 'InnoDB Full Text Search',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Enable InnoDB full-text search optimizations. (Requires MySQL 5.6+)",
    'help_text' => null,
    'on_change' => array(
      array('CRM_Core_InnoDBIndexer', 'onToggleFts'),
    ),
  ),
  'fts_query_mode' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'fts_query_mode',
    'prefetch' => 0,
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 64,
      'maxlength' => 64,
    ),
    'html_type' => 'Text',
    'default' => 'simple',
    'add' => '4.5',
    'title' => 'How to handle full-tet queries',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => null,
    'help_text' => null,
  ),
);
