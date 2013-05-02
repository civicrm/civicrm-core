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
 * File for the CiviCRM APIv2 group nesting functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Group
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: GroupNesting.php 21624 2009-08-07 22:02:55Z wmorgan $
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 * Provides group nesting record(s) given parent and/or child id.
 *
 * @param  array $params  an array containing at least child_group_id or parent_group_id
 *
 * @return  array  list of group nesting records
 */
function civicrm_group_nesting_get(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  if (!array_key_exists('child_group_id', $params) &&
    !array_key_exists('parent_group_id', $params)
  ) {
    return civicrm_create_error(ts('At least one of child_group_id or parent_group_id is a required field'));
  }

  require_once 'CRM/Contact/DAO/GroupNesting.php';
  $dao = new CRM_Contact_DAO_GroupNesting();
  if (array_key_exists('child_group_id', $params)) {
    $dao->child_group_id = $params['child_group_id'];
  }
  if (array_key_exists('parent_group_id', $params)) {
    $dao->parent_group_id = $params['parent_group_id'];
  }

  $values = array();

  if ($dao->find()) {
    while ($dao->fetch()) {
      $temp = array();
      _civicrm_object_to_array($dao, $temp);
      $values[$dao->id] = $temp;
    }
    $values['is_error'] = 0;
  }
  else {
    return civicrm_create_error('No records found.');
  }

  return $values;
}

/**
 * Creates group nesting record for given parent and child id.
 * Parent and child groups need to exist.
 *
 * @param array &$params parameters array - allowed array keys include:
 * {@schema Contact/GroupNesting.xml}
 *
 * @return array TBD
 *
 * @todo Work out the return value.
 */
function civicrm_group_nesting_create(&$params) {

  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  require_once 'CRM/Contact/BAO/GroupNesting.php';

  if (!array_key_exists('child_group_id', $params) &&
    !array_key_exists('parent_group_id', $params)
  ) {
    return civicrm_create_error(ts('You need to define parent_group_id and child_group_id in params.'));
  }

  CRM_Contact_BAO_GroupNesting::add($params['parent_group_id'], $params['child_group_id']);

  // FIXME: CRM_Contact_BAO_GroupNesting requires some work
  $result = array('is_error' => 0);
  return $result;
}

/**
 * Removes specific nesting records.
 *
 * @param array &$params parameters array - allowed array keys include:
 * {@schema Contact/GroupNesting.xml}
 *
 * @return array TBD
 *
 * @todo Work out the return value.
 */
function civicrm_group_nesting_remove(&$params) {

  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  if (!array_key_exists('child_group_id', $params) ||
    !array_key_exists('parent_group_id', $params)
  ) {
    return civicrm_create_error(ts('You need to define parent_group_id and child_group_id in params.'));
  }

  require_once 'CRM/Contact/DAO/GroupNesting.php';
  $dao = new CRM_Contact_DAO_GroupNesting();
  $dao->copyValues($params);

  if ($dao->delete()) {
    $result = array('is_error' => 0);
  }
  return $result;
}

