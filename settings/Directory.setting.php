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
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'uploadDir',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Directory Preferences'),
    'title' => 'Upload Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 10,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'imageUploadDir' => array(
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'imageUploadDir',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Directory Preferences'),
    'title' => 'Image Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 20,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'customFileUploadDir' => array(
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customFileUploadDir',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Directory Preferences'),
    'title' => 'Custom Files Upload Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 30,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'customTemplateDir' => array(
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customTemplateDir',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Directory Preferences'),
    'title' => 'Custom Template Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 40,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'customPHPPathDir' => array(
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'customPHPPathDir',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Directory Preferences'),
    'title' => 'Custom PHP Path',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 50,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'extensionsDir' => array(
    'group_name' => 'Directory Preferences',
    'group' => 'directory',
    'name' => 'extensionsDir',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Directory Preferences'),
    'title' => 'Extensions Directory',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 60,
    'description' => NULL,
    'help_text' => NULL,
  ),
);
