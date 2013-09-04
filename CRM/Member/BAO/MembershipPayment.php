<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
class CRM_Member_BAO_MembershipPayment extends CRM_Member_DAO_MembershipPayment {


  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }
  /**
   * function to add the membership Payments
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function create(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MembershipPayment', CRM_Utils_Array::value('id', $params), $params);
    $dao = new CRM_Member_DAO_MembershipPayment();
    $dao->copyValues($params);
    $dao->id = CRM_Utils_Array::value('id', $params);
    $dao->save();
    CRM_Utils_Hook::post($hook, 'MembershipPayment', $dao->id, $dao);
    return $dao;
  }

  /**
   * Function to delete membership Payments
   *
   * @param int $id
   * @static
   */
  static function del($id) {
    $dao = new CRM_Member_DAO_MembershipPayment();
    $dao->id = $id;
    $result = FALSE;
    if ($dao->find(TRUE)) {
      $dao->delete();
      $result = TRUE;
    }
    return $result;
  }


}

