<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Settings metadata file
 */
return [
  'address_standardization_provider' => [
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name' => 'address_standardization_provider',
    'type' => 'String',
    'html_type' => 'select',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Address Standardization Provider.'),
    'pseudoconstant' => ['callback' => 'CRM_Core_SelectValues::addressProvider'],
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => [
      ts('CiviCRM includes an optional plugin for interfacing with the United States Postal Services (USPS) Address Standardization web service.'),
      ts('Plugins for other address standardization services may be available from 3rd party developers. If installed, they will be included in the drop-down below.'),
    ],
    'help_doc_url' => [
      'page' => 'user/common-workflows/importing-data-into-civicrm/#address-standardisation',
    ],
    'settings_pages' => ['address' => ['section' => 'standardization', 'weight' => 10]],
  ],
  'address_standardization_userid' => [
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name' => 'address_standardization_userid',
    'type' => 'String',
    'html_type' => 'text',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Provider service user ID'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Web service user ID.'),
    'settings_pages' => ['address' => ['section' => 'standardization', 'weight' => 20]],
  ],
  'address_standardization_url' => [
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name' => 'address_standardization_url',
    'type' => 'Text',
    'html_type' => 'text',
    'default' => NULL,
    'add' => '4.1',
    'title' => ts('Provider Service URL'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Web Service URL'),
    'validate_callback' => 'CRM_Utils_Rule::url',
    'settings_pages' => ['address' => ['section' => 'standardization', 'weight' => 30]],
  ],
  'hideCountryMailingLabels' => [
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name'  => 'hideCountryMailingLabels',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 0,
    'add' => '4.7',
    'title' => ts('Hide Country in Mailing Labels when same as domain country'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Do not display the country field in mailing labels when the country is the same as that of the domain'),
    'settings_pages' => ['address' => ['section' => 'labels', 'weight' => 20]],
  ],
];
