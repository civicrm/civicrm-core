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
   * Create or update a MembershipBlock.
   *
   * @deprecated
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
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    return (bool) self::deleteRecord(['id' => $id]);
  }

}
