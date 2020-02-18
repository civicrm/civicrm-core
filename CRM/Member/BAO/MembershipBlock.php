<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Member_BAO_MembershipBlock extends CRM_Member_DAO_MembershipBlock {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Add the membership Blocks.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   */
  public static function create(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MembershipBlock', CRM_Utils_Array::value('id', $params), $params);
    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->copyValues($params);
    $dao->id = CRM_Utils_Array::value('id', $params);
    $dao->save();
    CRM_Utils_Hook::post($hook, 'MembershipBlock', $dao->id, $dao);
    return $dao;
  }

  /**
   * Delete membership Blocks.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function del($id) {
    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->id = $id;
    $result = FALSE;
    if ($dao->find(TRUE)) {
      $dao->delete();
      $result = TRUE;
    }
    return $result;
  }

}
