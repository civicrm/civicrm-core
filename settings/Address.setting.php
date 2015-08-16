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
  'address_standardization_provider' => array(
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name' => 'address_standardization_provider',
    'type' => 'String',
    'html_type' => 'Select',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Address Standardization Provider.',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => 'CiviCRM includes an optional plugin for interfacing with the United States Postal Services (USPS) Address Standardization web service. You must register to use the USPS service at https://www.usps.com/business/web-tools-apis/address-information.htm. If you are approved, they will provide you with a User ID and the URL for the service. Plugins for other address standardization services may be available from 3rd party developers. If installed, they will be included in the drop-down below. ',
  ),
  'address_standardization_userid' => array(
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name' => 'address_standardization_userid',
    'type' => 'String',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Web service user ID',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'address_standardization_url' => array(
    'group_name' => 'Address Preferences',
    'group' => 'address',
    'name' => 'address_standardization_url',
    'type' => 'Text',
    'html_type' => 'Text',
    'default' => NULL,
    'add' => '4.1',
    'title' => 'Web Service URL',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => 'Web Service URL',
    'validate_callback' => 'CRM_Utils_Rule::url',
  ),
);
