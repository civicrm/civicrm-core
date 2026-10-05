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
class CRM_Pledge_BAO_PledgeBlock extends CRM_Pledge_DAO_PledgeBlock {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Takes an associative array and creates a pledgeBlock object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @deprecated
   * @return CRM_Pledge_DAO_PledgeBlock
   */
  public static function &create(&$params) {
    $transaction = new CRM_Core_Transaction();
    $pledgeBlock = self::add($params);

    if (is_a($pledgeBlock, 'CRM_Core_Error')) {
      $pledgeBlock->rollback();
      return $pledgeBlock;
    }

    $params['id'] = $pledgeBlock->id;

    $transaction->commit();

    return $pledgeBlock;
  }

  /**
   * Add or update pledgeBlock.
   *
   * @param array $params
   * @deprecated
   * @return CRM_Pledge_DAO_PledgeBlock
   */
  public static function add($params) {
    // FIXME: This is assuming checkbox input like ['foo' => 1, 'bar' => 0, 'baz' => 1]. Not API friendly.
    if (!empty($params['pledge_frequency_unit']) && is_array($params['pledge_frequency_unit'])) {
      $params['pledge_frequency_unit'] = array_keys(array_filter($params['pledge_frequency_unit']));
    }
    return self::writeRecord($params);
  }

  /**
   * Delete the pledgeBlock.
   *
   * @param int $id
   *   PledgeBlock id.
   *
   * @return mixed|null
   */
  public static function deletePledgeBlock($id) {
    CRM_Utils_Hook::pre('delete', 'PledgeBlock', $id);

    $transaction = new CRM_Core_Transaction();

    $results = NULL;

    $dao = new CRM_Pledge_DAO_PledgeBlock();
    $dao->id = $id;
    $results = $dao->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'PledgeBlock', $dao->id, $dao);

    return $results;
  }

  /**
   * Return Pledge  Block info in Contribution Pages.
   *
   * @param int $pageID
   *   Contribution page id.
   *
   * @return array
   */
  public static function getPledgeBlock($pageID) {
    $pledgeBlock = [];

    $dao = new CRM_Pledge_DAO_PledgeBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $pageID;
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $pledgeBlock);
    }

    return $pledgeBlock;
  }

}
