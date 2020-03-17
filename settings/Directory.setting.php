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
  'uploadDir' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'uploadDir',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Temporary Files Directory'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => 'File system path where temporary CiviCRM files - such as import data files - are uploaded.',
  ],
  'imageUploadDir' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'imageUploadDir',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Image Directory'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('File system path where image files are uploaded. Currently, this path is used for images associated with premiums (CiviContribute thank-you gifts).'),
    'help_text' => NULL,
  ],
  'customFileUploadDir' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customFileUploadDir',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Custom Files Directory'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Path where documents and images which are attachments to contact records are stored (e.g. contact photos, resumes, contracts, etc.). These attachments are defined using \'file\' type custom fields.'),
    'help_text' => NULL,
  ],
  'customTemplateDir' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customTemplateDir',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Custom Template Directory'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Path where site specific templates are stored if any. This directory is searched first if set. Custom JavaScript code can be added to templates by creating files named templateFile.extra.tpl. (learn more...)'),
    'help_text' => NULL,
  ],
  'customPHPPathDir' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customPHPPathDir',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Custom PHP Directory'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Path where site specific PHP code files are stored if any. This directory is searched first if set.'),
    'help_text' => NULL,
  ],
  'extensionsDir' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'extensionsDir',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Extensions Directory'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Path where CiviCRM extensions are stored.'),
    'help_text' => NULL,
  ],

];
