<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * new version of civicrm apis. See blog post at
 * http://civicrm.org/node/131
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: PaymentProcessorType.php 30415 2010-10-29 12:02:47Z shot $
 *
 */

/**
 * Include common API util functions
 */
require_once 'CRM/Financial/BAO/PaymentProcessorType.php';

/**
 * Function to create payment_processor type
 *
 * @param  array $params   Associative array of property name/value pairs to insert in new payment_processor type.
 *
 * @return Newly created PaymentProcessor_type object
 * {@getfields PaymentProcessorType_create}
 * @access public
 * {@schema Core/PaymentProcessorType.xml}
 */
function civicrm_api3_payment_processor_type_create($params) {
  require_once 'CRM/Utils/Rule.php';

  $ids = array();
  if (isset($params['id']) && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for payment_processor type ID');
  }

  $payProcType = new CRM_Financial_BAO_PaymentProcessorType();
  $payProcType = CRM_Financial_BAO_PaymentProcessorType::create($params);

  $relType = array();

  _civicrm_api3_object_to_array($payProcType, $relType[$payProcType->id]);

  return civicrm_api3_create_success($relType, $params, 'payment_processor_type', 'create', $payProcType);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_payment_processor_type_create_spec(&$params) {
  $params['billing_mode']['api.required'] = 1;
  $params['class_name']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
  $params['is_recur']['api.default'] = FALSE;
  // FIXME bool support // $params['is_recur']['api.required'] = 1;
  $params['name']['api.required'] = 1;
  $params['title']['api.required'] = 1;
}

/**
 * Function to get all payment_processor type
 * retruns  An array of PaymentProcessor_type
 * @access  public
 * {@getfields PaymentProcessorType_get}
 * @example PaymentProcessorTypeGet.php
 */
function civicrm_api3_payment_processor_type_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a payment_processor type delete
 *
 * @param  id of payment_processor type  $id
 *
 * @return array API Result Array
 * {@getfields PaymentProcessorType_delete}
 * @static void
 * @access public
 */
function civicrm_api3_payment_processor_type_delete($params) {

  require_once 'CRM/Utils/Rule.php';
  if ($params['id'] != NULL && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for payment processor type ID');
  }

  $payProcTypeBAO = new CRM_Financial_BAO_PaymentProcessorType();
  $result = $payProcTypeBAO->del($params['id']);
  if (!$result) {
    return civicrm_api3_create_error('Could not delete payment processor type');
  }
  return civicrm_api3_create_success($result, $params, 'payment_processor_type', 'delete', $payProcTypeBAO);
}
