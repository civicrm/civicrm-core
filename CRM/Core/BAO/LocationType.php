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
class CRM_Core_BAO_LocationType extends CRM_Core_DAO_LocationType {

  /**
   * static holder for the default LT
   */
  static $_defaultLocationType = NULL;
  static $_billingLocationType = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_LocaationType object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->copyValues($params);
    if ($locationType->find(TRUE)) {
      CRM_Core_DAO::storeValues($locationType, $defaults);
      return $locationType;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_LocationType', $id, 'is_active', $is_active);
  }

  /**
   * retrieve the default location_type
   *
   * @param NULL
   *
   * @return object           The default location type object on success,
   *                          null otherwise
   * @static
   * @access public
   */
  static function &getDefault() {
    if (self::$_defaultLocationType == NULL) {
      $params = array('is_default' => 1);
      $defaults = array();
      self::$_defaultLocationType = self::retrieve($params, $defaults);
    }
    return self::$_defaultLocationType;
  }

  /*
   * Get ID of billing location type
   * @return integer
   */
  static function getBilling() {
    if (self::$_billingLocationType == NULL) {
      $locationTypes = CRM_Core_PseudoConstant::locationType();
      self::$_billingLocationType = array_search('Billing', $locationTypes);
    }
    return self::$_billingLocationType;
  }

  /**
   * Function to add a Location Type
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
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->copyValues($params);

    if ($params['is_default']) {
      $query = "UPDATE civicrm_location_type SET is_default = 0";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    $locationType->save();
    return $locationType;
  }

  /**
   * Function to delete location Types
   *
   * @param  int  $locationTypeId     ID of the location type to be deleted.
   *
   * @access public
   * @static
   */
  static function del($locationTypeId) {
    $entity = array('address', 'phone', 'email', 'im');
    //check dependencies
    foreach ($entity as $key) {
      if ($key == 'im') {
        $name = strtoupper($key);
      }
      else {
        $name = ucfirst($key);
      }
      require_once (str_replace('_', DIRECTORY_SEPARATOR, 'CRM_Core_DAO_' . $name) . ".php");
      eval('$object = new CRM_Core_DAO_' . $name . '( );');
      $object->location_type_id = $locationTypeId;
      $object->delete();
    }

    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->id = $locationTypeId;
    $locationType->delete();
  }
}

