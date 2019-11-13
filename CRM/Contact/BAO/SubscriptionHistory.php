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

/**
 * BAO object for crm_email table.
 */
class CRM_Contact_BAO_SubscriptionHistory extends CRM_Contact_DAO_SubscriptionHistory {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create a new subscription history record.
   *
   * @param array $params
   *   Values for the new history record.
   *
   * @return object
   *   $history  The new history object
   */
  public static function &create(&$params) {
    $history = new CRM_Contact_BAO_SubscriptionHistory();
    $history->date = date('Ymd');
    $history->copyValues($params);
    $history->save();
    return $history;
  }

  /**
   * Erase a contact's subscription history records.
   *
   * @param int $id
   *   The contact id.
   */
  public static function deleteContact($id) {
    $history = new CRM_Contact_BAO_SubscriptionHistory();
    $history->contact_id = $id;
    $history->delete();
  }

}
