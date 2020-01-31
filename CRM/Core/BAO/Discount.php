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
class CRM_Core_BAO_Discount extends CRM_Core_DAO_Discount {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Delete the discount.
   *
   * @param int $entityId
   * @param string $entityTable
   *
   * @return bool
   */
  public static function del($entityId, $entityTable) {
    // delete all discount records with the selected discounted id
    $discount = new CRM_Core_DAO_Discount();
    $discount->entity_id = $entityId;
    $discount->entity_table = $entityTable;
    if ($discount->delete()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   *
   * The function extracts all the params it needs to create a
   * discount object. the params array contains additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return object
   *   CRM_Core_DAO_Discount object on success, otherwise null
   */
  public static function add(&$params) {
    $discount = new CRM_Core_DAO_Discount();
    $discount->copyValues($params);
    $discount->save();
    return $discount;
  }

  /**
   * Determine whether the given table/id
   * has discount associated with it
   *
   * @param int $entityId
   *   Entity id to be searched.
   * @param string $entityTable
   *   Entity table to be searched.
   *
   * @return array
   *   option group Ids associated with discount
   */
  public static function getOptionGroup($entityId, $entityTable) {
    $optionGroupIDs = [];
    $dao = new CRM_Core_DAO_Discount();
    $dao->entity_id = $entityId;
    $dao->entity_table = $entityTable;
    $dao->find();
    while ($dao->fetch()) {
      $optionGroupIDs[$dao->id] = $dao->price_set_id;
    }
    return $optionGroupIDs;
  }

  /**
   * Determine in which discount set the registration date falls.
   *
   * @param int $entityID
   *   Entity id to be searched.
   * @param string $entityTable
   *   Entity table to be searched.
   *
   * @return int
   *   $dao->id       discount id of the set which matches
   *                                 the date criteria
   */
  public static function findSet($entityID, $entityTable) {
    if (empty($entityID) || empty($entityTable)) {
      // adding this here, to trap errors if values are not sent
      CRM_Core_Error::fatal();
      return NULL;
    }

    $dao = new CRM_Core_DAO_Discount();
    $dao->entity_id = $entityID;
    $dao->entity_table = $entityTable;
    $dao->find();

    while ($dao->fetch()) {
      $endDate = $dao->end_date;
      // if end date is not we consider current date as end date
      if (!$endDate) {
        $endDate = date('Ymd');
      }
      $falls = CRM_Utils_Date::getRange($dao->start_date, $endDate);
      if ($falls == TRUE) {
        return $dao->id;
      }
    }
    return FALSE;
  }

}
