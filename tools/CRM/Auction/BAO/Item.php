<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */


require_once 'CRM/Auction/DAO/Auction.php';
class CRM_Auction_BAO_Item extends CRM_Auction_DAO_Auction {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Auction_BAO_Item object
   * @access public
   * @static
   */
  static
  function retrieve(&$params, &$defaults) {
    $auction = new CRM_Auction_DAO_Item();
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
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static
  function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Auction_DAO_Item', $id, 'is_active', $is_active);
  }

  /**
   * function to add the auction
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static
  function add(&$params) {
    require_once 'CRM/Utils/Hook.php';

    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Auction_Item', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Auction_Item', NULL, $params);
    }

    $auction = new CRM_Auction_DAO_Item();

    $auction->copyValues($params);
    $auction->save();

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_auction_item',
      $auction->id
    );
    // add attachments as needed
    CRM_Core_BAO_File::processAttachment($params,
      'civicrm_auction_item',
      $auction->id
    );

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Auction_Item', $auction->id, $auction);
    }
    else {
      CRM_Utils_Hook::post('create', 'Auction_Item', $auction->id, $auction);
    }

    return $result;
  }

  /**
   * function to create the auction
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   */
  public static function create(&$params) {
    require_once 'CRM/Core/Transaction.php';
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
   * Function to delete the auction
   *
   * @param int $id  auction id
   *
   * @access public
   * @static
   *
   */
  static
  function del($id) {
    require_once 'CRM/Auction/DAO/Item.php';
    $auction     = new CRM_Auction_DAO_Item();
    $auction->id = $id;
    $result      = $auction->delete();
    return $result;
  }

  /**
   * Function to check if email is enabled for a given profile
   *
   * @param int $id profile id
   *
   * @return boolean
   * @access public
   * @static
   *
   */
  static
  function isEmailInProfile($profileId) {
    $query = "
SELECT field_name
FROM civicrm_uf_field
WHERE field_name like 'email%' And is_active = 1 And uf_group_id = %1";

    $params = array(1 => array($profileId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if (!$dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }
}

