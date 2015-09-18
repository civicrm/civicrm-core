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
  'uploadDir' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'uploadDir',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Temporary Files Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => 'File system path where temporary CiviCRM files - such as import data files - are uploaded.',
  ),
  'imageUploadDir' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'imageUploadDir',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Image Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'File system path where image files are uploaded. Currently, this path is used for images associated with premiums (CiviContribute thank-you gifts).',
    'help_text' => NULL,
  ),
  'customFileUploadDir' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customFileUploadDir',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Custom Files Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Path where documents and images which are attachments to contact records are stored (e.g. contact photos, resumes, contracts, etc.). These attachments are defined using \'file\' type custom fields.',
    'help_text' => NULL,
  ),
  'customTemplateDir' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customTemplateDir',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Custom Template Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Path where site specific templates are stored if any. This directory is searched first if set. Custom JavaScript code can be added to templates by creating files named templateFile.extra.tpl. (learn more...)',
    'help_text' => NULL,
  ),
  'customPHPPathDir' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customPHPPathDir',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Custom PHP Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Path where site specific PHP code files are stored if any. This directory is searched first if set.',
    'help_text' => NULL,
  ),
  'extensionsDir' => array(
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'extensionsDir',
    'type' => 'String',
    'html_type' => 'Text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Extensions Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Path where CiviCRM extensions are stored.',
    'help_text' => NULL,
  ),

);
