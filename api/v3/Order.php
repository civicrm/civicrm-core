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
 * This api exposes CiviCRM Order records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a set of Order.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of Order, if error an array with an error id and error message
 */
function civicrm_api3_order_get($params) {
  $contributions = array();
  $params['api.line_item.get'] = array('qty' => array('<>' => 0));
  $isSequential = FALSE;
  if (CRM_Utils_Array::value('sequential', $params)) {
    $params['sequential'] = 0;
    $isSequential = TRUE;
  }
  $result = civicrm_api3('Contribution', 'get', $params);
  if (!empty($result['values'])) {
    foreach ($result['values'] as $key => $contribution) {
      $contributions[$key] = $contribution;
      $contributions[$key]['line_items'] = $contribution['api.line_item.get']['values'];
      unset($contributions[$key]['api.line_item.get']);
    }
  }
  $params['sequential'] = $isSequential;
  return civicrm_api3_create_success($contributions, $params, 'Order', 'get');
}
