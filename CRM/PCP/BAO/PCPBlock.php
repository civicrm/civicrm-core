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
class CRM_PCP_BAO_PCPBlock extends CRM_PCP_DAO_PCPBlock {

  /**
   * Create or update either a Personal Campaign Page OR a PCP Block.
   *
   * @param array $params
   *
   * @return CRM_PCP_DAO_PCPBlock
   */
  public static function create($params) {
    $dao = new CRM_PCP_DAO_PCPBlock();
    $dao->copyValues($params);
    $dao->save();
    return $dao;
  }

}
