<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 *
 * Settings metadata file
 */
return array(
  'geoAPIKey' => array(
    'add' => '4.7',
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'group_name' => 'Map Preferences',
    'group' => 'map',
    'name' => 'geoAPIKey',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => '32',
      'maxlength' => '64',
    ),
    'default' => NULL,
    'title' => 'Geo Provider Key',
    'description' => 'Enter the API key or Application ID associated with your geocoding provider (not required for Yahoo).',
  ),
  'geoProvider' => array(
    'add' => '4.7',
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'group_name' => 'Map Preferences',
    'group' => 'map',
    'name' => 'geoProvider',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      'class' => 'crm-select2',
    ),
    'pseudoconstant' => array(
      'callback' => 'CRM_Core_SelectValues::geoProvider',
    ),
    'default' => NULL,
    'title' => 'Geocoding Provider',
    'description' => 'This can be the same or different from the mapping provider selected.',
  ),
  'mapAPIKey' => array(
    'add' => '4.7',
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'group_name' => 'Map Preferences',
    'group' => 'map',
    'name' => 'mapAPIKey',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => '32',
      'maxlength' => '64',
    ),
    'default' => NULL,
    'title' => 'Map Provider Key',
    'description' => 'Enter your API Key or Application ID. An API Key is required for the Google Maps API. Refer to developers.google.com for the latest information.',
  ),
  'mapProvider' => array(
    'add' => '4.7',
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'group_name' => 'Map Preferences',
    'group' => 'map',
    'name' => 'mapProvider',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => array(
      'class' => 'crm-select2',
    ),
    'pseudoconstant' => array(
      'callback' => 'CRM_Core_SelectValues::mapProvider',
    ),
    'default' => NULL,
    'title' => 'Mapping Provider',
    'description' => 'Choose the mapping provider that has the best coverage for the majority of your contact addresses.',
  ),
);
