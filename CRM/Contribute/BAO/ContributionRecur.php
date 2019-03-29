<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Contribute_BAO_ContributionRecur extends CRM_Contribute_DAO_ContributionRecur {

  /**
   * Array with statuses that mark a recurring contribution as inactive.
   *
   * @var array
   */
  private static $inactiveStatuses = ['Cancelled', 'Chargeback', 'Refunded', 'Completed'];

  /**
   * Create recurring contribution.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   activity contact object
   */
  public static function create(&$params) {
    return self::add($params);
  }

  /**
   * Takes an associative array and creates a contribution object.
   *
   * the function extract all the params it needs to initialize the create a
   * contribution object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return \CRM_Contribute_BAO_ContributionRecur|\CRM_Core_Error
   * @todo move hook calls / extended logic to create - requires changing calls to call create not add
   */
  public static function add(&$params) {
    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'ContributionRecur', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ContributionRecur', NULL, $params);
    }

    // make sure we're not creating a new recurring contribution with the same transaction ID
    // or invoice ID as an existing recurring contribution
    $duplicates = [];
    if (self::checkDuplicate($params, $duplicates)) {
      $error = CRM_Core_Error::singleton();
      $d = implode(', ', $duplicates);
      $error->push(CRM_Core_Error::DUPLICATE_CONTRIBUTION,
        'Fatal',
        [$d],
        "Found matching recurring contribution(s): $d"
      );
      return $error;
    }

    $recurring = new CRM_Contribute_BAO_ContributionRecur();
    $recurring->copyValues($params);
    $recurring->id = CRM_Utils_Array::value('id', $params);

    // set currency for CRM-1496
    if (empty($params['id']) && !isset($recurring->currency)) {
      $config = CRM_Core_Config::singleton();
      $recurring->currency = $config->defaultCurrency;
    }
    $recurring->save();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'ContributionRecur', $recurring->id, $recurring);
    }
    else {
      CRM_Utils_Hook::post('create', 'ContributionRecur', $recurring->id, $recurring);
    }

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution_recur', $recurring->id);
    }

    return $recurring;
  }

  /**
   * Check if there is a recurring contribution with the same trxn_id or invoice_id.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $duplicates
   *   (reference ) store ids of duplicate contributions.
   *
   * @return bool
   *   true if duplicate, false otherwise
   */
  public static function checkDuplicate($params, &$duplicates) {
    $id = CRM_Utils_Array::value('id', $params);
    $trxn_id = CRM_Utils_Array::value('trxn_id', $params);
    $invoice_id = CRM_Utils_Array::value('invoice_id', $params);

    $clause = [];
    $params = [];

    if ($trxn_id) {
      $clause[] = "trxn_id = %1";
      $params[1] = [$trxn_id, 'String'];
    }

    if ($invoice_id) {
      $clause[] = "invoice_id = %2";
      $params[2] = [$invoice_id, 'String'];
    }

    if (empty($clause)) {
      return FALSE;
    }

    $clause = implode(' OR ', $clause);
    if ($id) {
      $clause = "( $clause ) AND id != %3";
      $params[3] = [$id, 'Integer'];
    }

    $query = "SELECT id FROM civicrm_contribution_recur WHERE $clause";
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $result = FALSE;
    while ($dao->fetch()) {
      $duplicates[] = $dao->id;
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Get the payment processor (array) for a recurring processor.
   *
   * @param int $id
   *
   * @return array|null
   */
  public static function getPaymentProcessor($id) {
    $paymentProcessorID = self::getPaymentProcessorID($id);
    return CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID);
  }


  /**
   * Get the processor object for the recurring contribution record.
   *
   * @param int $id
   *
   * @return CRM_Core_Payment|NULL
   *   Returns a processor object or NULL if the processor is disabled.
   *   Note this returns the 'Manual' processor object if no processor is attached
   *   (since it still makes sense to update / cancel
   */
  public static function getPaymentProcessorObject($id) {
    $processor = self::getPaymentProcessor($id);
    return is_array($processor) ? $processor['object'] : NULL;
  }

  /**
   * Get the payment processor for the given recurring contribution.
   *
   * @param int $recurID
   *
   * @return int
   *   Payment processor id. If none found return 0 which represents the
   *   pseudo processor used for pay-later.
   */
  public static function getPaymentProcessorID($recurID) {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', [
      'id' => $recurID,
      'return' => ['payment_processor_id']
    ]);
    return (int) CRM_Utils_Array::value('payment_processor_id', $recur, 0);
  }

  /**
   * Get the number of installment done/completed for each recurring contribution.
   *
   * @param array $ids
   *   (reference ) an array of recurring contribution ids.
   *
   * @return array
   *   an array of recurring ids count
   */
  public static function getCount(&$ids) {
    $recurID = implode(',', $ids);
    $totalCount = [];

    $query = "
         SELECT contribution_recur_id, count( contribution_recur_id ) as commpleted
         FROM civicrm_contribution
         WHERE contribution_recur_id IN ( {$recurID}) AND is_test = 0
         GROUP BY contribution_recur_id";

    $res = CRM_Core_DAO::executeQuery($query);

    while ($res->fetch()) {
      $totalCount[$res->contribution_recur_id] = $res->commpleted;
    }
    return $totalCount;
  }

  /**
   * Delete Recurring contribution.
   *
   * @param int $recurId
   *
   * @return bool
   */
  public static function deleteRecurContribution($recurId) {
    $result = FALSE;
    if (!$recurId) {
      return $result;
    }

    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    $result = $recur->delete();

    return $result;
  }

  /**
   * Cancel Recurring contribution.
   *
   * @param int $recurId
   *   Recur contribution id.
   *
   * @param array $activityParams
   *
   * @return bool
   */
  public static function cancelRecurContribution($recurId, $activityParams = []) {
    if (!$recurId) {
      return FALSE;
    }

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $canceledId = array_search('Cancelled', $contributionStatus);
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    $recur->whereAdd("contribution_status_id != $canceledId");

    if ($recur->find(TRUE)) {
      $transaction = new CRM_Core_Transaction();
      $recur->contribution_status_id = $canceledId;
      $recur->start_date = CRM_Utils_Date::isoToMysql($recur->start_date);
      $recur->create_date = CRM_Utils_Date::isoToMysql($recur->create_date);
      $recur->modified_date = CRM_Utils_Date::isoToMysql($recur->modified_date);
      $recur->cancel_date = date('YmdHis');
      $recur->save();

      $dao = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recurId);
      if ($dao && $dao->recur_id) {
        $details = CRM_Utils_Array::value('details', $activityParams);
        if ($dao->auto_renew && $dao->membership_id) {
          // its auto-renewal membership mode
          $membershipTypes = CRM_Member_PseudoConstant::membershipType();
          $membershipType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $dao->membership_id, 'membership_type_id');
          $membershipType = CRM_Utils_Array::value($membershipType, $membershipTypes);
          $details .= '
<br/>' . ts('Automatic renewal of %1 membership cancelled.', [1 => $membershipType]);
        }
        else {
          $details .= '
<br/>' . ts('The recurring contribution of %1, every %2 %3 has been cancelled.', [
              1 => $dao->amount,
              2 => $dao->frequency_interval,
              3 => $dao->frequency_unit,
            ]);
        }
        $activityParams = [
          'source_contact_id' => $dao->contact_id,
          'source_record_id' => $dao->recur_id,
          'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Cancel Recurring Contribution'),
          'subject' => CRM_Utils_Array::value('subject', $activityParams, ts('Recurring contribution cancelled')),
          'details' => $details,
          'activity_date_time' => date('YmdHis'),
          'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
        ];
        $session = CRM_Core_Session::singleton();
        $cid = $session->get('userID');
        if ($cid) {
          $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
          $activityParams['source_contact_id'] = $cid;
        }
        // @todo use the api & do less wrangling above
        CRM_Activity_BAO_Activity::create($activityParams);
      }

      $transaction->commit();
      return TRUE;
    }
    else {
      // if already cancelled, return true
      $recur->whereAdd();
      $recur->whereAdd("contribution_status_id = $canceledId");
      if ($recur->find(TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @deprecated Get list of recurring contribution of contact Ids.
   *
   * @param int $contactId
   *   Contact ID.
   *
   * @return array
   *   list of recurring contribution fields
   *
   */
  public static function getRecurContributions($contactId) {
    CRM_Core_Error::deprecatedFunctionWarning('ContributionRecur.get API instead');
    $params = [];
    $recurDAO = new CRM_Contribute_DAO_ContributionRecur();
    $recurDAO->contact_id = $contactId;
    $recurDAO->find();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();

    while ($recurDAO->fetch()) {
      $params[$recurDAO->id]['id'] = $recurDAO->id;
      $params[$recurDAO->id]['contactId'] = $recurDAO->contact_id;
      $params[$recurDAO->id]['start_date'] = $recurDAO->start_date;
      $params[$recurDAO->id]['end_date'] = $recurDAO->end_date;
      $params[$recurDAO->id]['next_sched_contribution_date'] = $recurDAO->next_sched_contribution_date;
      $params[$recurDAO->id]['amount'] = $recurDAO->amount;
      $params[$recurDAO->id]['currency'] = $recurDAO->currency;
      $params[$recurDAO->id]['frequency_unit'] = $recurDAO->frequency_unit;
      $params[$recurDAO->id]['frequency_interval'] = $recurDAO->frequency_interval;
      $params[$recurDAO->id]['installments'] = $recurDAO->installments;
      $params[$recurDAO->id]['contribution_status_id'] = $recurDAO->contribution_status_id;
      $params[$recurDAO->id]['contribution_status'] = CRM_Utils_Array::value($recurDAO->contribution_status_id, $contributionStatus);
      $params[$recurDAO->id]['is_test'] = $recurDAO->is_test;
      $params[$recurDAO->id]['payment_processor_id'] = $recurDAO->payment_processor_id;
    }

    return $params;
  }

  /**
   * @param int $entityID
   * @param string $entity
   *
   * @return null|Object
   */
  public static function getSubscriptionDetails($entityID, $entity = 'recur') {
    $sql = "
SELECT rec.id                   as recur_id,
       rec.processor_id         as subscription_id,
       rec.frequency_interval,
       rec.installments,
       rec.frequency_unit,
       rec.amount,
       rec.is_test,
       rec.auto_renew,
       rec.currency,
       rec.campaign_id,
       rec.financial_type_id,
       rec.next_sched_contribution_date,
       rec.failure_retry_date,
       rec.cycle_day,
       con.id as contribution_id,
       con.contribution_page_id,
       rec.contact_id,
       mp.membership_id";

    if ($entity == 'recur') {
      $sql .= "
      FROM civicrm_contribution_recur rec
LEFT JOIN civicrm_contribution       con ON ( con.contribution_recur_id = rec.id )
LEFT  JOIN civicrm_membership_payment mp  ON ( mp.contribution_id = con.id )
     WHERE rec.id = %1";
    }
    elseif ($entity == 'contribution') {
      $sql .= "
      FROM civicrm_contribution       con
INNER JOIN civicrm_contribution_recur rec ON ( con.contribution_recur_id = rec.id )
LEFT  JOIN civicrm_membership_payment mp  ON ( mp.contribution_id = con.id )
     WHERE con.id = %1";
    }
    elseif ($entity == 'membership') {
      $sql .= "
      FROM civicrm_membership_payment mp
INNER JOIN civicrm_membership         mem ON ( mp.membership_id = mem.id )
INNER JOIN civicrm_contribution_recur rec ON ( mem.contribution_recur_id = rec.id )
INNER JOIN civicrm_contribution       con ON ( con.id = mp.contribution_id )
     WHERE mp.membership_id = %1";
    }

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$entityID, 'Integer']]);
    if ($dao->fetch()) {
      return $dao;
    }
    else {
      return NULL;
    }
  }

  /**
   * Does the recurring contribution support financial type change.
   *
   * This is conditional on there being only one line item or if there are no contributions as yet.
   *
   * (This second is a bit of an unusual condition but might occur in the context of a
   *
   * @param int $id
   *
   * @return bool
   */
  public static function supportsFinancialTypeChange($id) {
    // At this stage only sites with no Financial ACLs will have the opportunity to edit the financial type.
    // this is to limit the scope of the change and because financial ACLs are still fairly new & settling down.
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    $contribution = self::getTemplateContribution($id);
    return CRM_Contribute_BAO_Contribution::isSingleLineItem($contribution['id']);
  }

  /**
   * Get the contribution to be used as the template for later contributions.
   *
   * Later we might merge in data stored against the contribution recur record rather than just return the contribution.
   *
   * @param int $id
   * @param array $overrides
   *   Parameters that should be overriden. Add unit tests if using parameters other than total_amount & financial_type_id.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getTemplateContribution($id, $overrides = []) {
    $templateContribution = civicrm_api3('Contribution', 'get', [
      'contribution_recur_id' => $id,
      'options' => ['limit' => 1, 'sort' => ['id DESC']],
      'sequential' => 1,
      'contribution_test' => '',
    ]);
    if ($templateContribution['count']) {
      $result = array_merge($templateContribution['values'][0], $overrides);
      $result['line_item'] = CRM_Contribute_BAO_ContributionRecur::calculateRecurLineItems($id, $result['total_amount'], $result['financial_type_id']);
      return $result;
    }
    return [];
  }

  public static function setSubscriptionContext() {
    // handle context redirection for subscription url
    $session = CRM_Core_Session::singleton();
    if ($session->get('userID')) {
      $url = FALSE;
      $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
      $mid = CRM_Utils_Request::retrieve('mid', 'Integer');
      $qfkey = CRM_Utils_Request::retrieve('key', 'String');
      $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
      if ($cid) {
        switch ($context) {
          case 'contribution':
            $url = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&selectedChild=contribute&cid={$cid}"
            );
            break;

          case 'membership':
            $url = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&selectedChild=member&cid={$cid}"
            );
            break;

          case 'dashboard':
            $url = CRM_Utils_System::url('civicrm/user', "reset=1&id={$cid}");
            break;
        }
      }
      if ($mid) {
        switch ($context) {
          case 'dashboard':
            $url = CRM_Utils_System::url('civicrm/member', "force=1&context={$context}&key={$qfkey}");
            break;

          case 'search':
            $url = CRM_Utils_System::url('civicrm/member/search', "force=1&context={$context}&key={$qfkey}");
            break;
        }
      }
      if ($url) {
        $session->pushUserContext($url);
      }
    }
  }

  /**
   * CRM-16285 - Function to handle validation errors on form, for recurring contribution field.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $self
   * @param array $errors
   */
  public static function validateRecurContribution($fields, $files, $self, &$errors) {
    if (!empty($fields['is_recur'])) {
      if ($fields['frequency_interval'] <= 0) {
        $errors['frequency_interval'] = ts('Please enter a number for how often you want to make this recurring contribution (EXAMPLE: Every 3 months).');
      }
      if ($fields['frequency_unit'] == '0') {
        $errors['frequency_unit'] = ts('Please select a period (e.g. months, years ...) for how often you want to make this recurring contribution (EXAMPLE: Every 3 MONTHS).');
      }
    }
  }

  /**
   * Send start or end notification for recurring payments.
   *
   * @param array $ids
   * @param CRM_Contribute_BAO_ContributionRecur $recur
   * @param bool $isFirstOrLastRecurringPayment
   */
  public static function sendRecurringStartOrEndNotification($ids, $recur, $isFirstOrLastRecurringPayment) {
    if ($isFirstOrLastRecurringPayment) {
      $autoRenewMembership = FALSE;
      if ($recur->id &&
        isset($ids['membership']) && $ids['membership']
      ) {
        $autoRenewMembership = TRUE;
      }

      //send recurring Notification email for user
      CRM_Contribute_BAO_ContributionPage::recurringNotify($isFirstOrLastRecurringPayment,
        $ids['contact'],
        $ids['contributionPage'],
        $recur,
        $autoRenewMembership
      );
    }
  }

  /**
   * Copy custom data of the initial contribution into its recurring contributions.
   *
   * @param int $recurId
   * @param int $targetContributionId
   */
  static public function copyCustomValues($recurId, $targetContributionId) {
    if ($recurId && $targetContributionId) {
      // get the initial contribution id of recur id
      $sourceContributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');

      // if the same contribution is being processed then return
      if ($sourceContributionId == $targetContributionId) {
        return;
      }
      // check if proper recurring contribution record is being processed
      $targetConRecurId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $targetContributionId, 'contribution_recur_id');
      if ($targetConRecurId != $recurId) {
        return;
      }

      // copy custom data
      $extends = ['Contribution'];
      $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
      if ($groupTree) {
        foreach ($groupTree as $groupID => $group) {
          $table[$groupTree[$groupID]['table_name']] = ['entity_id'];
          foreach ($group['fields'] as $fieldID => $field) {
            $table[$groupTree[$groupID]['table_name']][] = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
          }
        }

        foreach ($table as $tableName => $tableColumns) {
          $insert = 'INSERT IGNORE INTO ' . $tableName . ' (' . implode(', ', $tableColumns) . ') ';
          $tableColumns[0] = $targetContributionId;
          $select = 'SELECT ' . implode(', ', $tableColumns);
          $from = ' FROM ' . $tableName;
          $where = " WHERE {$tableName}.entity_id = {$sourceContributionId}";
          $query = $insert . $select . $from . $where;
          CRM_Core_DAO::executeQuery($query);
        }
      }
    }
  }

  /**
   * Add soft credit to for recurring payment.
   *
   * copy soft credit record of first recurring contribution.
   * and add new soft credit against $targetContributionId
   *
   * @param int $recurId
   * @param int $targetContributionId
   */
  public static function addrecurSoftCredit($recurId, $targetContributionId) {
    $soft_contribution = new CRM_Contribute_DAO_ContributionSoft();
    $soft_contribution->contribution_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');

    // Check if first recurring contribution has any associated soft credit.
    if ($soft_contribution->find(TRUE)) {
      $soft_contribution->contribution_id = $targetContributionId;
      unset($soft_contribution->id);
      $soft_contribution->save();
    }
  }

  /**
   * Add line items for recurring contribution.
   *
   * @param int $recurId
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @return array
   */
  public static function addRecurLineItems($recurId, $contribution) {
    $foundLineItems = FALSE;

    $lineSets = self::calculateRecurLineItems($recurId, $contribution->total_amount, $contribution->financial_type_id);
    foreach ($lineSets as $lineItems) {
      if (!empty($lineItems)) {
        foreach ($lineItems as $key => $value) {
          if ($value['entity_table'] == 'civicrm_membership') {
            try {
              // @todo this should be done by virtue of editing the line item as this link
              // is deprecated. This may be the case but needs testing.
              civicrm_api3('membership_payment', 'create', [
                'membership_id' => $value['entity_id'],
                'contribution_id' => $contribution->id,
                'is_transactional' => FALSE,
              ]);
            }
            catch (CiviCRM_API3_Exception $e) {
              // we are catching & ignoring errors as an extra precaution since lost IPNs may be more serious that lost membership_payment data
              // this fn is unit-tested so risk of changes elsewhere breaking it are otherwise mitigated
            }
          }
        }
        $foundLineItems = TRUE;
      }
    }
    if (!$foundLineItems) {
      CRM_Price_BAO_LineItem::processPriceSet($contribution->id, $lineSets, $contribution);
    }
    return $lineSets;
  }

  /**
   * Update pledge associated with a recurring contribution.
   *
   * If the contribution has a pledge_payment record pledge, then update the pledge_payment record & pledge based on that linkage.
   *
   * If a previous contribution in the recurring contribution sequence is linked with a pledge then we assume this contribution
   * should be  linked with the same pledge also. Currently only back-office users can apply a recurring payment to a pledge &
   * it should be assumed they
   * do so with the intention that all payments will be linked
   *
   * The pledge payment record should already exist & will need to be updated with the new contribution ID.
   * If not the contribution will also need to be linked to the pledge
   *
   * @param int $contributionID
   * @param int $contributionRecurID
   * @param int $contributionStatusID
   * @param float $contributionAmount
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateRecurLinkedPledge($contributionID, $contributionRecurID, $contributionStatusID, $contributionAmount) {
    $returnProperties = ['id', 'pledge_id'];
    $paymentDetails = $paymentIDs = [];

    if (CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'contribution_id', $contributionID,
      $paymentDetails, $returnProperties
    )
    ) {
      foreach ($paymentDetails as $key => $value) {
        $paymentIDs[] = $value['id'];
        $pledgeId = $value['pledge_id'];
      }
    }
    else {
      //payment is not already linked - if it is linked with a pledge we need to create a link.
      // return if it is not recurring contribution
      if (!$contributionRecurID) {
        return;
      }

      $relatedContributions = new CRM_Contribute_DAO_Contribution();
      $relatedContributions->contribution_recur_id = $contributionRecurID;
      $relatedContributions->find();

      while ($relatedContributions->fetch()) {
        CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'contribution_id', $relatedContributions->id,
          $paymentDetails, $returnProperties
        );
      }

      if (empty($paymentDetails)) {
        // payment is not linked with a pledge and neither are any other contributions on this
        return;
      }

      foreach ($paymentDetails as $key => $value) {
        $pledgeId = $value['pledge_id'];
      }

      // we have a pledge now we need to get the oldest unpaid payment
      $paymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeId);
      if (empty($paymentDetails['id'])) {
        // we can assume this pledge is now completed
        // return now so we don't create a core error & roll back
        return;
      }
      $paymentDetails['contribution_id'] = $contributionID;
      $paymentDetails['status_id'] = $contributionStatusID;
      $paymentDetails['actual_amount'] = $contributionAmount;

      // put contribution against it
      $payment = civicrm_api3('PledgePayment', 'create', $paymentDetails);
      $paymentIDs[] = $payment['id'];
    }

    // update pledge and corresponding payment statuses
    CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId, $paymentIDs, $contributionStatusID,
      NULL, $contributionAmount
    );
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function recurringContribution(&$form) {
    // Recurring contribution fields
    foreach (self::getRecurringFields() as $key => $label) {
      if ($key == 'contribution_recur_payment_made' && !empty($form->_formValues) &&
        !CRM_Utils_System::isNull(CRM_Utils_Array::value($key, $form->_formValues))
      ) {
        $form->assign('contribution_recur_pane_open', TRUE);
        break;
      }
      CRM_Core_Form_Date::buildDateRange($form, $key, 1, '_low', '_high');
      // If data has been entered for a recurring field, tell the tpl layer to open the pane
      if (!empty($form->_formValues) && !empty($form->_formValues[$key . '_relative']) || !empty($form->_formValues[$key . '_low']) || !empty($form->_formValues[$key . '_high'])) {
        $form->assign('contribution_recur_pane_open', TRUE);
        break;
      }
    }

    // If values have been supplied for recurring contribution fields, open the recurring contributions pane.
    foreach (['contribution_status_id', 'payment_processor_id', 'processor_id', 'trxn_id'] as $fieldName) {
      if (!empty($form->_formValues['contribution_recur_' . $fieldName])) {
        $form->assign('contribution_recur_pane_open', TRUE);
        break;
      }
    }

    // Add field to check if payment is made for recurring contribution
    $recurringPaymentOptions = [
      1 => ts('All recurring contributions'),
      2 => ts('Recurring contributions with at least one payment'),
    ];
    $form->addRadio('contribution_recur_payment_made', NULL, $recurringPaymentOptions, ['allowClear' => TRUE]);
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_recur_start_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_recur_end_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_recur_modified_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_recur_next_sched_contribution_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_recur_failure_retry_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_recur_cancel_date', 1, '_low', '_high', ts('From'), FALSE, FALSE, 'birth');

    // Add field for contribution status
    $form->addSelect('contribution_recur_contribution_status_id',
      ['entity' => 'contribution', 'multiple' => 'multiple', 'context' => 'search', 'options' => CRM_Contribute_PseudoConstant::contributionStatus()]
    );

    $form->addElement('text', 'contribution_recur_processor_id', ts('Processor ID'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur', 'processor_id'));
    $form->addElement('text', 'contribution_recur_trxn_id', ts('Transaction ID'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur', 'trxn_id'));

    $paymentProcessorOpts = CRM_Contribute_BAO_ContributionRecur::buildOptions('payment_processor_id', 'get');
    $form->add('select', 'contribution_recur_payment_processor_id', ts('Payment Processor ID'), $paymentProcessorOpts, FALSE, ['class' => 'crm-select2', 'multiple' => 'multiple']);

    CRM_Core_BAO_Query::addCustomFormFields($form, ['ContributionRecur']);

  }

  /**
   * Get fields for recurring contributions.
   *
   * @return array
   */
  public static function getRecurringFields() {
    return [
      'contribution_recur_payment_made' => ts(''),
      'contribution_recur_start_date' => ts('Recurring Contribution Start Date'),
      'contribution_recur_next_sched_contribution_date' => ts('Next Scheduled Recurring Contribution'),
      'contribution_recur_cancel_date' => ts('Recurring Contribution Cancel Date'),
      'contribution_recur_end_date' => ts('Recurring Contribution End Date'),
      'contribution_recur_create_date' => ('Recurring Contribution Create Date'),
      'contribution_recur_modified_date' => ('Recurring Contribution Modified Date'),
      'contribution_recur_failure_retry_date' => ts('Failed Recurring Contribution Retry Date'),
    ];
  }

  /**
   * Update recurring contribution based on incoming payment.
   *
   * Do not rename or move this function without updating https://issues.civicrm.org/jira/browse/CRM-17655.
   *
   * @param int $recurringContributionID
   * @param string $paymentStatus
   *   Payment status - this correlates to the machine name of the contribution status ID ie
   *   - Completed
   *   - Failed
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateOnNewPayment($recurringContributionID, $paymentStatus, $effectiveDate) {

    $effectiveDate = $effectiveDate ? date('Y-m-d', strtotime($effectiveDate)) : date('Y-m-d');
    if (!in_array($paymentStatus, ['Completed', 'Failed'])) {
      return;
    }
    $params = [
      'id' => $recurringContributionID,
      'return' => [
        'contribution_status_id',
        'next_sched_contribution_date',
        'frequency_unit',
        'frequency_interval',
        'installments',
        'failure_count',
      ],
    ];

    $existing = civicrm_api3('ContributionRecur', 'getsingle', $params);

    if ($paymentStatus == 'Completed'
      && CRM_Contribute_PseudoConstant::contributionStatus($existing['contribution_status_id'], 'name') == 'Pending') {
      $params['contribution_status_id'] = 'In Progress';
    }
    if ($paymentStatus == 'Failed') {
      $params['failure_count'] = $existing['failure_count'];
    }
    $params['modified_date'] = date('Y-m-d H:i:s');

    if (!empty($existing['installments']) && self::isComplete($recurringContributionID, $existing['installments'])) {
      $params['contribution_status_id'] = 'Completed';
    }
    else {
      // Only update next sched date if it's empty or 'just now' because payment processors may be managing
      // the scheduled date themselves as core did not previously provide any help.
      if (empty($existing['next_sched_contribution_date']) || strtotime($existing['next_sched_contribution_date']) ==
        strtotime($effectiveDate)) {
        $params['next_sched_contribution_date'] = date('Y-m-d', strtotime('+' . $existing['frequency_interval'] . ' ' . $existing['frequency_unit'], strtotime($effectiveDate)));
      }
    }
    civicrm_api3('ContributionRecur', 'create', $params);
  }

  /**
   * Is this recurring contribution now complete.
   *
   * Have all the payments expected been received now.
   *
   * @param int $recurringContributionID
   * @param int $installments
   *
   * @return bool
   */
  protected static function isComplete($recurringContributionID, $installments) {
    $paidInstallments = CRM_Core_DAO::singleValueQuery(
      'SELECT count(*) FROM civicrm_contribution
        WHERE contribution_recur_id = %1
        AND contribution_status_id = ' . CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      [1 => [$recurringContributionID, 'Integer']]
    );
    if ($paidInstallments >= $installments) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Calculate line items for the relevant recurring calculation.
   *
   * @param int $recurId
   * @param string $total_amount
   * @param int $financial_type_id
   *
   * @return array
   */
  public static function calculateRecurLineItems($recurId, $total_amount, $financial_type_id) {
    $originalContribution = civicrm_api3('Contribution', 'getsingle', [
    'contribution_recur_id' => $recurId,
    'contribution_test' => '',
    'options' => ['limit' => 1],
    'return' => ['id', 'financial_type_id'],
    ]);
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($originalContribution['id']);
    $lineSets = [];
    if (count($lineItems) == 1) {
      foreach ($lineItems as $index => $lineItem) {
        if ($lineItem['financial_type_id'] != $originalContribution['financial_type_id']) {
          // CRM-20685, Repeattransaction produces incorrect Financial Type ID (in specific circumstance) - if number of lineItems = 1, So this conditional will set the financial_type_id as the original if line_item and contribution comes with different data.
          $financial_type_id = $lineItem['financial_type_id'];
        }
        if ($financial_type_id) {
          // CRM-17718 allow for possibility of changed financial type ID having been set prior to calling this.
          $lineItem['financial_type_id'] = $financial_type_id;
        }
        if ($lineItem['line_total'] != $total_amount) {
          // We are dealing with a changed amount! Per CRM-16397 we can work out what to do with these
          // if there is only one line item, and the UI should prevent this situation for those with more than one.
          $lineItem['line_total'] = $total_amount;
          $lineItem['unit_price'] = round($total_amount / $lineItem['qty'], 2);
        }
        $priceField = new CRM_Price_DAO_PriceField();
        $priceField->id = $lineItem['price_field_id'];
        $priceField->find(TRUE);
        $lineSets[$priceField->price_set_id][$lineItem['price_field_id']] = $lineItem;
      }
    }
    // CRM-19309 if more than one then just pass them through:
    elseif (count($lineItems) > 1) {
      foreach ($lineItems as $index => $lineItem) {
        $lineSets[$index][$lineItem['price_field_id']] = $lineItem;
      }
    }

    return $lineSets;
  }

  /**
   * Returns array with statuses that are considered to make a recurring
   * contribution inactive.
   *
   * @return array
   */
  public static function getInactiveStatuses() {
    return self::$inactiveStatuses;
  }

  /**
   * Get options for the called BAO object's field.
   *
   * This function can be overridden by each BAO to add more logic related to context.
   * The overriding function will generally call the lower-level CRM_Core_PseudoConstant::get
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   whatever is known about this bao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {

    switch ($fieldName) {
      case 'payment_processor_id':
        if (isset(\Civi::$statics[__CLASS__]['buildoptions_payment_processor_id'])) {
          return \Civi::$statics[__CLASS__]['buildoptions_payment_processor_id'];
        }
        $baoName = 'CRM_Contribute_BAO_ContributionRecur';
        $props['condition']['test'] = "is_test = 0";
        $liveProcessors = CRM_Core_PseudoConstant::get($baoName, $fieldName, $props, $context);
        $props['condition']['test'] = "is_test != 0";
        $testProcessors = CRM_Core_PseudoConstant::get($baoName, $fieldName, $props, $context);
        foreach ($testProcessors as $key => $value) {
          if ($context === 'validate') {
            // @fixme: Ideally the names would be different in the civicrm_payment_processor table but they are not.
            //     So we append '_test' to the test one so that we can select the correct processor by name using the ContributionRecur.create API.
            $testProcessors[$key] = $value . '_test';
          }
          else {
            $testProcessors[$key] = CRM_Core_TestEntity::appendTestText($value);
          }
        }
        $allProcessors = $liveProcessors + $testProcessors;
        ksort($allProcessors);
        \Civi::$statics[__CLASS__]['buildoptions_payment_processor_id'] = $allProcessors;
        return $allProcessors;
    }
    return parent::buildOptions($fieldName, $context, $props);
  }

}
