<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
/*
 * Settings metadata file
 */
return array(
  'search_autocomplete_count' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'search_autocomplete_count',
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
    'help_text' => NULL,
  ),
  'enable_innodb_fts' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'enable_innodb_fts',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 0,
    'add' => '4.4',
    'title' => 'InnoDB Full Text Search',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "Enable InnoDB full-text search optimizations. (Requires MySQL 5.6+)",
    'help_text' => NULL,
    'on_change' => array(
      array('CRM_Core_InnoDBIndexer', 'onToggleFts'),
    ),
  ),
  'fts_query_mode' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'fts_query_mode',
    'type' => 'String',
    'quick_form_type' => 'Element',
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
    'description' => NULL,
    'help_text' => NULL,
  ),
  'includeOrderByClause' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'includeOrderByClause',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 1,
    'add' => '4.6',
    'title' => 'Include Order By Clause',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If disabled, the search results will not be ordered. This may improve response time on search results on large datasets',
    'help_text' => NULL,
  ),
  'includeWildCardInName' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'includeWildCardInName',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 1,
    'add' => '4.6',
    'title' => 'Automatic Wildcard',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => "If enabled, wildcards are automatically added to the beginning AND end of the search term when users search for contacts by Name. EXAMPLE: Searching for 'ada' will return any contact whose name includes those letters - e.g. 'Adams, Janet', 'Nadal, Jorge', etc. If disabled, a wildcard is added to the end of the search term only. EXAMPLE: Searching for 'ada' will return any contact whose last name begins with those letters - e.g. 'Adams, Janet' but NOT 'Nadal, Jorge'. Disabling this feature will speed up search significantly for larger databases, but users must manually enter wildcards ('%' or '_') to the beginning of the search term if they want to find all records which contain those letters. EXAMPLE: '%ada' will return 'Nadal, Jorge'.",
    'help_text' => NULL,
  ),
  'includeEmailInName' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'includeEmailInName',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 1,
    'add' => '4.6',
    'title' => 'Include Email',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If enabled, email addresses are automatically included when users search by Name. Disabling this feature will speed up search significantly for larger databases, but users will need to use the Email search fields (from Advanced Search, Search Builder, or Profiles) to find contacts by email address.',
    'help_text' => NULL,
  ),
  'includeNickNameInName' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'includeNickNameInName',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 0,
    'add' => '4.6',
    'title' => 'Include Nickname',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If enabled, nicknames are automatically included when users search by Name.',
    'help_text' => NULL,
  ),
  'includeAlphabeticalPager' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'includeAlphabeticalPager',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 1,
    'add' => '4.6',
    'title' => 'Include Alphabetical Pager',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If disabled, the alphabetical pager will not be displayed on the search screens. This will improve response time on search results on large datasets.',
    'help_text' => NULL,
  ),
  'smartGroupCacheTimeout' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'smartGroupCacheTimeout',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => 5,
    'add' => '4.6',
    'title' => 'Smart group cache timeout',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'The number of minutes to cache smart group contacts. We strongly recommend that this value be greater than zero, since a value of zero means no caching at all. If your contact data changes frequently, you should set this value to at least 5 minutes.',
    'help_text' => NULL,
  ),
  'defaultSearchProfileID' => array(
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'defaultSearchProfileID',
    'type' => 'Integer',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      'class' => 'crm-select2',
    ),
    'pseudoconstant' => array(
      'callback' => 'CRM_Admin_Form_Setting_Search::getAvailableProfiles',
    ),
    'default' => NULL,
    'add' => '4.6',
    'title' => 'Default Contact Search Profile',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If set, this will be the default profile used for contact search.',
    'help_text' => NULL,
  ),
);
