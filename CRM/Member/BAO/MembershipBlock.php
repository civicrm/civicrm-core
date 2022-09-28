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
 */
class CRM_Member_BAO_MembershipBlock extends CRM_Member_DAO_MembershipBlock {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create or update a MembershipBlock.
   *
   * @param array $params
   * @return CRM_Member_DAO_MembershipBlock
   */
  public static function create($params) {
    return self::writeRecord($params);
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
