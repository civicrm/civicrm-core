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

class CRM_Financial_BAO_FinancialItem extends CRM_Financial_DAO_FinancialItem {

  /**
   * class constructor
   */
  function __construct( ) {
    parent::__construct( );
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
   * @return object CRM_Contribute_BAO_FinancialItem object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $financialItem = new CRM_Financial_DAO_FinancialItem();
    $financialItem->copyValues($params);
    if ($financialItem->find(TRUE)) {
      CRM_Core_DAO::storeValues($financialItem, $defaults);
      return $financialItem;
    }
    return NULL;
  }

  /**
   * function to add the financial items and financial trxn
   *
   * @param object $lineItem     line item object
   * @param object $contribution contribution object
   * 
   * @access public
   * @static 
   * @return void
   */
  static function add($lineItem, $contribution) {
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $financialItemStatus = CRM_Core_PseudoConstant::accountOptionValues('financial_item_status');
    if ($contribution->contribution_status_id == array_search('Completed', $contributionStatuses)) {
      $itemStatus = array_search('Paid', $financialItemStatus);
    } 
    elseif ($contribution->contribution_status_id == array_search('Pending', $contributionStatuses)) {
      $itemStatus = array_search('Unpaid', $financialItemStatus);
    } 
    $params = array(
      'transaction_date'  => CRM_Utils_Date::isoToMysql($contribution->receive_date),
      'contact_id'        => $contribution->contact_id, 
      'amount'            => $lineItem->line_total,
      'currency'          => $contribution->currency,
      'entity_table'      => 'civicrm_line_item',
      'entity_id'         => $lineItem->id,
      'description'       => ( $lineItem->qty != 1 ? $lineItem->qty . ' of ' : ''). ' ' . $lineItem->label,
      'status_id'         => $itemStatus,
    );
    
    if ($lineItem->financial_type_id) {
      $searchParams = array( 
        'entity_table'         => 'civicrm_financial_type',
        'entity_id'            => $lineItem->financial_type_id,
        'account_relationship' => 1,
      );

      $result = array();
      CRM_Financial_BAO_FinancialTypeAccount::retrieve( $searchParams, $result );
      $params['financial_account_id'] = CRM_Utils_Array::value( 'financial_account_id', $result );
    }

    $trxn = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution->id, 'ASC', TRUE);
    $trxnId['id'] = $trxn['financialTrxnId'];

    self::create($params, NULL, $trxnId);
  } 

  /**
   * function to create the financial Items and financial enity trxn
   *
   * @param array $params  associated array to create financial items
   * @param array $ids financial item ids
   * @param array $trxnIds financial item ids
   * 
   * @access public
   * @static 
   * @return object
   */
  static function create(&$params, $ids = NULL, $trxnIds = NULL) {
    $financialItem = new CRM_Financial_DAO_FinancialItem();
    $financialItem->copyValues($params);
    if (CRM_Utils_Array::value('id', $ids)) {
      $financialItem->id = $ids['id']; 
    }

    $financialItem->save();
    if (CRM_Utils_Array::value('id', $trxnIds)) {
      $entity_financial_trxn_params = array(
        'entity_table'      => "civicrm_financial_item",
        'entity_id'         => $financialItem->id,
        'financial_trxn_id' => $trxnIds['id'],
        'amount'            => $params['amount'],
      );
      
      $entity_trxn = new CRM_Financial_DAO_EntityFinancialTrxn();
      $entity_trxn->copyValues($entity_financial_trxn_params);
      if (CRM_Utils_Array::value('entityFinancialTrxnId', $ids)) {
        $entity_trxn->id = $ids['entityFinancialTrxnId'];
      }
      $entity_trxn->save();
    }
    return $financialItem;
  }   

  /**
   * takes an associative array and creates a entity financial transaction object
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Core_BAO_FinancialTrxn object
   * @access public
   * @static
   */
  static function createEntityTrxn($params) {
    $entity_trxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entity_trxn->copyValues($params);
    $entity_trxn->save();
    return $entity_trxn;
  }

  /**
   * retrive entity financial trxn details
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @param boolean $maxID to retrive max id 
   *
   * @return array
   * @access public
   * @static
   */
  static function retrieveEntityFinancialTrxn($params, $maxId = FALSE) {
    $financialItem = new CRM_Financial_DAO_EntityFinancialTrxn();
    $financialItem->copyValues($params);
    //retrieve last entry from civicrm_entity_financial_trxn
    if ($maxId) {
      $financialItem->orderBy('id DESC');
      $financialItem->limit(1);
    }
    $financialItem->find();
    while ($financialItem->fetch()) {
      $financialItems[$financialItem->id] = array(
        'id'                => $financialItem->id,
        'entity_table'      => $financialItem->entity_table,
        'entity_id'         => $financialItem->entity_id,
        'financial_trxn_id' => $financialItem->financial_trxn_id,
        'amount'            => $financialItem->amount,
      );
    } 
    if (!empty($financialItems)) {
      return $financialItems;
    }
    else {
      return null;
    }
  }
  
  /**
   * check if contact is present in financial_item table
   *
   * CRM-12929
   *
   * @param array $contactIds  an array contact id's
   *
   * @param array $error error to display
   *
   * @return array
   * @access public
   * @static
   */
  static function checkContactPresent($contactIds, &$error) {
    if (empty($contactIds)) {
      return FALSE;
    }
    
    $allowPermDelete = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'allowPermDeleteFinancial');

    if (!$allowPermDelete) {
      $sql = 'SELECT DISTINCT(cc.id), cc.display_name FROM civicrm_contact cc
INNER JOIN civicrm_contribution con ON con.contact_id = cc.id
WHERE cc.id IN (' . implode (',', $contactIds) . ') AND con.is_test = 0';
      $dao = CRM_Core_DAO::executeQuery($sql);
      if ($dao->N) {
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$dao->id");
          $not_deleted[$dao->id] = "<a href='$url'>$dao->display_name</a>";
        }
        
        $errorStatus = '';
        if (is_array($error)) {
          $errorStatus = '<ul><li>' . implode('</li><li>', $not_deleted) . '</li></ul>';
        }
        
        $error['_qf_default'] = $errorStatus .  ts('This contact(s) can not be permanently deleted because the contact record is linked to one or more live financial transactions. Deleting this contact would result in the loss of financial data.');
        return $error; 
      }
    }
    return FALSE;
  }
}
