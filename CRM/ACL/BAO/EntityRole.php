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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 *  Access Control EntityRole
 */
class CRM_ACL_BAO_EntityRole extends CRM_ACL_DAO_EntityRole {
  static $_entityTable = NULL;

  static function entityTable() {
    if (!self::$_entityTable) {
      self::$_entityTable = array(
        'civicrm_contact' => ts('Contact'),
        'civicrm_group' => ts('Group'),
      );
    }
    return self::$_entityTable;
  }

  static function create(&$params) {
    $dao = new CRM_ACL_DAO_EntityRole();
    $dao->copyValues($params);

    $dao->save();
  }

  static function retrieve(&$params, &$defaults) {
    CRM_Core_DAO::commonRetrieve('CRM_ACL_DAO_EntityRole', $params, $defaults);
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_ACL_DAO_EntityRole', $id, 'is_active', $is_active);
  }

  /**
   * Function to delete Entity Role records
   *
   * @param  int  $entityRoleId ID of the EntityRole record to be deleted.
   *
   * @access public
   * @static
   */
  static function del($entityRoleId) {
    $entityDAO = new CRM_ACL_DAO_EntityRole();
    $entityDAO->id = $entityRoleId;
    $entityDAO->find(TRUE);
    $entityDAO->delete();
  }
}

