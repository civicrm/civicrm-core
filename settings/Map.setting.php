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
 * Settings metadata file
 */
return [
  'geoAPIKey' => [
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
    'html_attributes' => [
      'size' => '32',
      'maxlength' => '64',
    ],
    'default' => NULL,
    'title' => ts('Geo Provider Key'),
    'description' => ts('Enter the API key or Application ID associated with your geocoding provider.'),
  ],
  'geoProvider' => [
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
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Core_SelectValues::geoProvider',
    ],
    'on_change' => [
      'CRM_Utils_GeocodeProvider::reset',
    ],
    'default' => NULL,
    'title' => ts('Geocoding Provider'),
    'description' => ts('This can be the same or different from the mapping provider selected.'),
  ],
  'mapAPIKey' => [
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
    'html_attributes' => [
      'size' => '32',
      'maxlength' => '64',
    ],
    'default' => NULL,
    'title' => ts('Map Provider Key'),
    'description' => ts('Enter your API Key or Application ID. An API Key is required for the Google Maps API. Refer to developers.google.com for the latest information.'),
  ],
  'mapProvider' => [
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
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Core_SelectValues::mapProvider',
    ],
    'default' => NULL,
    'title' => ts('Mapping Provider'),
    'description' => ts('Choose the mapping provider that has the best coverage for the majority of your contact addresses.'),
  ],
];
