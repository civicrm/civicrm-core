<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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


require_once 'CRM/Auction/DAO/Auction.php';

/**
 * Class CRM_Auction_BAO_Auction
 */
class CRM_Auction_BAO_Auction extends CRM_Auction_DAO_Auction {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return CRM_Auction_BAO_Auction object
   */
  static function retrieve(&$params, &$defaults) {
    $auction = new CRM_Auction_DAO_Auction();
    $auction->copyValues($params);
    if ($auction->find(TRUE)) {
      CRM_Core_DAO::storeValues($auction, $defaults);
      return $auction;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on success, null otherwise
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Auction_DAO_Auction', $id, 'is_active', $is_active);
  }

  /**
   * add the auction
   *
   * @param array $params reference array contains the values submitted by the form
   * @return object
   */
  static function add(&$params) {
    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Auction', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Auction', NULL, $params);
    }

    $auction = new CRM_Auction_DAO_Auction();

    $auction->copyValues($params);
    $result = $auction->save();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Auction', $auction->id, $auction);
    }
    else {
      CRM_Utils_Hook::post('create', 'Auction', $auction->id, $auction);
    }

    return $result;
  }

  /**
   * create the auction
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @return object
   */
  public static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $auction = self::add($params);

    if (is_a($auction, 'CRM_Core_Error')) {
      CRM_Core_DAO::transaction('ROLLBACK');
      return $auction;
    }

    $transaction->commit();

    return $auction;
  }

  /**
   * delete the auction
   *
   * @param int $id  auction id
   *
   */
  static function del($id) {
    $auction     = new CRM_Auction_DAO_Auction();
    $auction->id = $id;
    $result      = $auction->delete();
    return $result;
  }

  /**
   * Function to get current/future Auctions
   *
   * @param $all boolean true if auctions all are required else returns current and future auctions
   *
   * @param bool $id
   *
   * @return array
   */
  static function getAuctions($all = FALSE, $id = FALSE) {
    $query = "SELECT `id`, `title`, `start_date` FROM `civicrm_auction`";

    if (!$all) {
      $endDate = date('YmdHis');
      $query .= " WHERE `end_date` >= {$endDate} OR end_date IS NULL";
    }
    if ($id) {
      $query .= " WHERE `id` = {$id}";
    }

    $query .= " ORDER BY title asc";
    $auctions = array();

    $dao = &CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {
      $auctions[$dao->id] = $dao->title . ' - ' . CRM_Utils_Date::customFormat($dao->start_date);
    }

    return $auctions;
  }
}

