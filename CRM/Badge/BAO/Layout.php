<?php
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Badge_BAO_Layout extends CRM_Core_DAO_PrintLabel {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_PrintLabel object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->copyValues($params);
    if ($printLabel->find(TRUE)) {
      CRM_Core_DAO::storeValues($printLabel, $defaults);
      return $printLabel;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int $id        id of the database record
   * @param boolean $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on success, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_PrintLabel', $id, 'is_active', $is_active);
  }

  /**
   * Function to add a name label
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function create(&$params) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);

    // action is taken depending upon the mode
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->copyValues($params);

    if ($params['is_default']) {
      $query = "UPDATE civicrm_print_label SET is_default = 0";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    $printLabel->save();
    return $printLabel;
  }

  /**
   * Function to delete name labels
   *
   * @param  int $printLabelId ID of the name label to be deleted.
   *
   * @access public
   * @static
   */
  static function del($printLabelId) {
    $printLabel = new CRM_Core_DAO_PrintLabel();
    $printLabel->id = $printLabelId;
    $printLabel->delete();
  }
}

