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
  'userFrameworkResourceURL' => array(
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'userFrameworkResourceURL',
    'type' => 'Url',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Resource URLs'),
    'title' => 'Script and CSS Resources URL',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 10,
    'description' => 'CiviCRM Resource URL',
    'help_text' => NULL,
  ),
  'imageUploadURL' => array(
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'imageUploadURL',
    'type' => 'Url',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Resource URLs'),
    'title' => 'Image URL Prefix',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 20,
    'description' => 'Image Upload URL',
    'help_text' => NULL,
  ),
  'customCSSURL' => array(
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'customCSSURL',
    'type' => 'Url',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'section_title' => array('General Settings', 'Resource URLs'),
    'title' => 'Custom CSS',
    'is_domain' => 1,
    'is_contact' => 0,
    'weight' => 30,
    'description' => 'Custom CiviCRM CSS URL',
    'help_text' => NULL,
  ),
);
