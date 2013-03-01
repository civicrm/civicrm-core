<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * BAO object for crm_email table
 */
class CRM_Contact_BAO_SubscriptionHistory extends CRM_Contact_DAO_SubscriptionHistory {
  function __construct() {
    parent::__construct();
  }

  /**
   * Create a new subscription history record
   *
   * @param array $params     Values for the new history record
   *
   * @return object $history  The new history object
   * @access public
   * @static
   */
  public static function &create(&$params) {
    $history = new CRM_Contact_BAO_SubscriptionHistory();
    $history->date = date('Ymd');
    $history->copyValues($params);
    $history->save();
    return $history;
  }

  /**
   * Erase a contact's subscription history records
   *
   * @param int $id       The contact id
   *
   * @return none
   * @access public
   * @static
   */
  public static function deleteContact($id) {
    $history = new CRM_Contact_BAO_SubscriptionHistory();
    $history->contact_id = $id;
    $history->delete();
  }
}

