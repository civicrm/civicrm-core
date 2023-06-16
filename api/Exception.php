<?php
/**
 * @file
 * File for the CiviCRM APIv3 API wrapper
 *
 * @package CiviCRM_APIv3
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

// These two classes were basically equivalent

/**
 * @deprecated in CiviCRM 5.52, will be removed around 5.92. Use CRM_Core_Exception
 */
class_alias('CRM_Core_Exception', 'API_Exception');
/**
 * @deprecated in CiviCRM 5.52, will be removed around 5.92. Use CRM_Core_Exception
 */
class_alias('CRM_Core_Exception', 'CiviCRM_API3_Exception');
