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

class_alias('CRM_Core_Exception', 'API_Exception');
class_alias('CRM_Core_Exception', 'CiviCRM_API3_Exception');
