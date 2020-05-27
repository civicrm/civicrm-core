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
 * $Id$
 *
 */
/*
 * Settings metadata file
 */
return [
  'ext_repo_url' => [
    'group_name' => 'Extension Preferences',
    'group' => 'ext',
    'name' => 'ext_repo_url',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_attributes' => [
      'size' => 64,
      'maxlength' => 128,
    ],
    'html_type' => 'text',
    'default' => 'https://civicrm.org/extdir/ver={ver}|cms={uf}',
    'add' => '4.3',
    'title' => ts('Extension Repo URL'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => '',
  ],
];
