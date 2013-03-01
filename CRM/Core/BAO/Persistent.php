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
class CRM_Core_BAO_Persistent extends CRM_Core_DAO_Persistent {

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
   * @return object CRM_Core_BAO_Persistent object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Core_DAO_Persistent();
    $dao->copyValues($params);

    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      if (CRM_Utils_Array::value('is_config', $defaults) == 1) {
        $defaults['data'] = unserialize($defaults['data']);
      }
      return $dao;
    }
    return NULL;
  }

  /**
   * function to add the Persistent Record
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, &$ids) {
    if (CRM_Utils_Array::value('is_config', $params) == 1) {
      $params['data'] = serialize(explode(',', $params['data']));
    }
    $persistentDAO = new CRM_Core_DAO_Persistent();
    $persistentDAO->copyValues($params);

    $persistentDAO->id = CRM_Utils_Array::value('persistent', $ids);
    $persistentDAO->save();
    return $persistentDAO;
  }

  static function getContext($context, $name = NULL) {
    static $contextNameData = array();

    if (!array_key_exists($context, $contextNameData)) {
      $contextNameData[$context] = array();
      $persisntentDAO = new CRM_Core_DAO_Persistent();
      $persisntentDAO->context = $context;
      $persisntentDAO->find();

      while ($persisntentDAO->fetch()) {
        $contextNameData[$context][$persisntentDAO->name] = $persisntentDAO->is_config == 1 ? unserialize($persisntentDAO->data) : $persisntentDAO->data;
      }
    }
    if (empty($name)) {
      return $contextNameData[$context];
    }
    else {
      return CRM_Utils_Array::value($name, $contextNameData[$context]);
    }
  }
}

