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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Member_BAO_MembershipPayment extends CRM_Member_DAO_MembershipPayment {


  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Add the membership Payments.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MembershipPayment', CRM_Utils_Array::value('id', $params), $params);
    $dao = new CRM_Member_DAO_MembershipPayment();
    $dao->copyValues($params);
    $dao->id = CRM_Utils_Array::value('id', $params);
    //Fixed for avoiding duplicate entry error when user goes
    //back and forward during payment mode is notify
    if (!$dao->find(TRUE)) {
      $dao->save();
    }
    CRM_Utils_Hook::post($hook, 'MembershipPayment', $dao->id, $dao);
    // CRM-14197 we are in the process on phasing out membershipPayment in favour of storing both contribution_id & entity_id (membership_id) on the line items
    // table. However, at this stage we have both - there is still quite a bit of refactoring to do to set the line_iten entity_id right the first time
    // however, we can assume at this stage that any contribution id will have only one line item with that membership type in the line item table
    // OR the caller will have taken responsibility for updating the line items themselves so we will update using SQL here
    if (!isset($params['membership_type_id'])) {
      $membership_type_id = civicrm_api3('membership', 'getvalue', array(
        'id' => $dao->membership_id,
        'return' => 'membership_type_id',
      ));
    }
    else {
      $membership_type_id = $params['membership_type_id'];
    }
    $sql = "UPDATE civicrm_line_item li
      LEFT JOIN civicrm_price_field_value pv ON pv.id = li.price_field_value_id
      SET entity_table = 'civicrm_membership', entity_id = %1
      WHERE pv.membership_type_id = %2
      AND contribution_id = %3";
    CRM_Core_DAO::executeQuery($sql, array(
        1 => array($dao->membership_id, 'Integer'),
        2 => array($membership_type_id, 'Integer'),
        3 => array($dao->contribution_id, 'Integer'),
      ));
    return $dao;
  }

  /**
   * Delete membership Payments.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function del($id) {
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
