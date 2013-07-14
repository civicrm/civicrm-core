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

/**
 * Delegate query functions based on hook system
 */
class CRM_Contact_BAO_Query_Hook {
 
  /**
   * @var array of CRM_Contact_BAO_Query_Interface objects
   */
  protected $_queryObjects = NULL;

  /**
   * singleton function used to manage this object
   *
   * @return object
   * @static
   *
   */
  public static function singleton() {
    static $singleton = NULL;
    if (!$singleton) {
      $singleton = new CRM_Contact_BAO_Query_Hook();
    }
    return $singleton;
  }

 /**
  * Get or build the list of search objects (via hook)
  *
  * @return array of CRM_Contact_BAO_Query_Interface objects
  */
  public function getSearchQueryObjects() {
    if ($this->_queryObjects === NULL) {
      $this->_queryObjects = array();
      CRM_Utils_Hook::queryObjects($this->_queryObjects);
    }
    return $this->_queryObjects;
  }
  
  public function &getFields() {
    $extFields = array();
    foreach (self::getSearchQueryObjects() as $obj) {
      $flds = $obj->getFields();
      $extFields = array_merge($extFields, $flds);
    }
    return $extFields;
  }
  
  public function alterSearchQuery(&$query, $fnName) {
    foreach (self::getSearchQueryObjects() as $obj) {
      $obj->$fnName($query);
    }
  }
  
  public function buildSearchfrom($fieldName, $mode, $side) {
    $from = '';
    foreach (self::getSearchQueryObjects() as $obj) {
      $from .= $obj->from($fieldName, $mode, $side);
    }
    return $from;
  }
}