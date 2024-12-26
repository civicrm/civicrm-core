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

use Civi\Api4\FinancialAccount;
use Civi\Api4\FinancialItem;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Financial_BAO_FinancialItem extends CRM_Financial_DAO_FinancialItem {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Add the financial items and financial trxn.
   *
   * @param object $lineItem
   *   Line item object.
   * @param object $contribution
   *   Contribution object.
   * @param bool $taxTrxnID
   * @param array|null $trxnId
   *
   * @return CRM_Financial_DAO_FinancialItem
   */
  public static function add($lineItem, $contribution, $taxTrxnID = FALSE, $trxnId = NULL) {
    $financialItemStatus = CRM_Financial_DAO_FinancialItem::buildOptions('status_id');
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id);
    $itemStatus = NULL;
    if ($contributionStatus === 'Completed' || $contributionStatus === 'Pending refund') {
      $itemStatus = array_search('Paid', $financialItemStatus);
    }
    elseif ($contributionStatus === 'Pending'
      // In progress is no longer present on new installs unless extensions add it.
      || $contributionStatus === 'In Progress'
    ) {
      $itemStatus = array_search('Unpaid', $financialItemStatus);
    }
    elseif ($contributionStatus === 'Partially paid') {
      $itemStatus = array_search('Partially paid', $financialItemStatus);
    }
    $params = [
      'transaction_date' => $contribution->receive_date,
      'contact_id' => $contribution->contact_id,
      'amount' => $lineItem->line_total,
      'currency' => $contribution->currency,
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $lineItem->id,
      'description' => ($lineItem->qty != 1 ? $lineItem->qty . ' of ' : '') . $lineItem->label,
      'status_id' => $itemStatus,
    ];

    if ($taxTrxnID) {
      $params['amount'] = $lineItem->tax_amount;
      $params['description'] = Civi::settings()->get('tax_term');
      $accountRelName = 'Sales Tax Account is';
    }
    else {
      $accountRelName = 'Income Account is';
      if (property_exists($contribution, 'revenue_recognition_date') && !CRM_Utils_System::isNull($contribution->revenue_recognition_date)) {
        $accountRelName = 'Deferred Revenue Account is';
      }
    }
    if ($lineItem->financial_type_id) {
      $params['financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $lineItem->financial_type_id,
        $accountRelName
      );
    }
    if (empty($trxnId)) {
      if (empty($trxnId['id'])) {
        $trxn = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution->id, 'ASC', TRUE);
        $trxnId['id'] = $trxn['financialTrxnId'];
      }
    }
    return self::create($params, NULL, $trxnId);
  }

  /**
   * Create the financial Items and financial entity trxn.
   *
   * @param array $params
   *   Associated array to create financial items.
   * @param array $ids
   *   Financial item ids.
   * @param array $trxnIds
   *   Financial item ids.
   *
   * @return CRM_Financial_DAO_FinancialItem
   */
  public static function create(&$params, $ids = NULL, $trxnIds = NULL) {
    $financialItem = new CRM_Financial_DAO_FinancialItem();

    if (!empty($ids['id'])) {
      CRM_Utils_Hook::pre('edit', 'FinancialItem', $ids['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'FinancialItem', NULL, $params);
    }

    $financialItem->copyValues($params);
    if (!empty($ids['id'])) {
      $financialItem->id = $ids['id'];
    }

    $financialItem->save();
    $financialtrxnIDS = $trxnIds['id'] ?? NULL;
    if (!empty($financialtrxnIDS)) {
      if (!is_array($financialtrxnIDS)) {
        $financialtrxnIDS = [$financialtrxnIDS];
      }
      foreach ($financialtrxnIDS as $tID) {
        $entity_financial_trxn_params = [
          'entity_table' => "civicrm_financial_item",
          'entity_id' => $financialItem->id,
          'financial_trxn_id' => $tID,
          'amount' => $params['amount'],
        ];
        if (!empty($ids['entityFinancialTrxnId'])) {
          $entity_financial_trxn_params['id'] = $ids['entityFinancialTrxnId'];
        }
        self::createEntityTrxn($entity_financial_trxn_params);
      }
    }
    if (!empty($ids['id'])) {
      CRM_Utils_Hook::post('edit', 'FinancialItem', $financialItem->id, $financialItem);
    }
    else {
      CRM_Utils_Hook::post('create', 'FinancialItem', $financialItem->id, $financialItem);
    }
    return $financialItem;
  }

  /**
   * Takes an associative array and creates a entity financial transaction object.
   *
   * @param array $params
   *   an assoc array of name/value pairs.
   *
   * @return CRM_Financial_DAO_EntityFinancialTrxn
   */
  public static function createEntityTrxn($params) {
    $entity_trxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entity_trxn->copyValues($params);
    $entity_trxn->save();
    return $entity_trxn;
  }

  /**
   * Retrieve entity financial trxn details.
   *
   * @deprecated - only called by tests - to be replaced with
   * $trxn = (array) EntityFinancialTrxn::get()
   *  ->addWhere('id', '=', $contributionID)
   *  ->addWhere('entity_table', '=', 'civicrm_contribution')
   *  ->addSelect('*')->execute();
   *
   * @param array $params
   *   an assoc array of name/value pairs.
   * @param bool $maxId
   *   To retrieve max id.
   *
   * @deprecated
   *
   * @return array
   */
  public static function retrieveEntityFinancialTrxn($params, $maxId = FALSE) {
    CRM_Core_Error::deprecatedFunctionWarning('api');
    $financialItem = new CRM_Financial_DAO_EntityFinancialTrxn();
    $financialItem->copyValues($params);
    // retrieve last entry from civicrm_entity_financial_trxn
    if ($maxId) {
      $financialItem->orderBy('id DESC');
      $financialItem->limit(1);
    }
    $financialItem->find();
    while ($financialItem->fetch()) {
      $financialItems[$financialItem->id] = [
        'id' => $financialItem->id,
        'entity_table' => $financialItem->entity_table,
        'entity_id' => $financialItem->entity_id,
        'financial_trxn_id' => $financialItem->financial_trxn_id,
        'amount' => $financialItem->amount,
      ];
    }
    if (!empty($financialItems)) {
      return $financialItems;
    }
    else {
      return NULL;
    }
  }

  /**
   * Check if contact is present in financial_item table.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-12929
   *
   * @param array $contactIds
   *   An array contact id's.
   *
   * @param array $error
   *   Error to display.
   *
   * @return array|bool
   */
  public static function checkContactPresent($contactIds, &$error) {
    if (empty($contactIds)) {
      return FALSE;
    }

    $allowPermDelete = Civi::settings()->get('allowPermDeleteFinancial');

    if (!$allowPermDelete) {
      $sql = 'SELECT DISTINCT(cc.id), cc.display_name FROM civicrm_contact cc
INNER JOIN civicrm_contribution con ON con.contact_id = cc.id
WHERE cc.id IN (' . implode(',', $contactIds) . ') AND con.is_test = 0';
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

        $error['_qf_default'] = $errorStatus . ts('This contact(s) can not be permanently deleted because the contact record is linked to one or more live financial transactions. Deleting this contact would result in the loss of financial data.');
        return $error;
      }
    }
    return FALSE;
  }

  /**
   * Get most relevant previous financial item relating to the line item.
   *
   * This function specifically excludes sales tax.
   *
   * @param int $entityId
   *
   * @return array
   */
  public static function getPreviousFinancialItem($entityId) {
    $financialItemAPI = FinancialItem::get(FALSE)
      ->addWhere('entity_id', '=', $entityId)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addOrderBy('id', 'DESC');

    $salesTaxFinancialAccounts = FinancialAccount::get(FALSE)
      ->addSelect('id')
      ->addWhere('is_tax', '=', 1)
      ->execute();
    if ($salesTaxFinancialAccounts->count() > 0) {
      $financialItemAPI->addWhere('financial_account_id', 'NOT IN', $salesTaxFinancialAccounts->column('id'));
    }
    return $financialItemAPI->execute()->first();
  }

  /**
   * Whitelist of possible values for the entity_table field
   *
   * @return array
   */
  public static function entityTables(): array {
    return [
      'civicrm_line_item' => ts('Line Item'),
      'civicrm_financial_trxn' => ts('Financial Trxn'),
      'civicrm_campaign' => ts('Campaign'),
    ];
  }

}
