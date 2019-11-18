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

/**
 * This class contains payment related functions.
 */
class CRM_Financial_BAO_Payment {

  /**
   * Function to process additional payment for partial and refund contributions.
   *
   * This function is called via API payment.create function. All forms that add payments
   * should use this.
   *
   * @param array $params
   *   - contribution_id
   *   - total_amount
   *   - line_item
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function create($params) {
    $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $params['contribution_id']]);
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus($contribution['contribution_status_id'], 'name');

    $isPaymentCompletesContribution = self::isPaymentCompletesContribution($params['contribution_id'], $params['total_amount']);

    $whiteList = ['check_number', 'payment_processor_id', 'fee_amount', 'total_amount', 'contribution_id', 'net_amount', 'card_type_id', 'pan_truncation', 'trxn_result_code', 'payment_instrument_id', 'trxn_id'];
    $paymentTrxnParams = array_intersect_key($params, array_fill_keys($whiteList, 1));
    $paymentTrxnParams['is_payment'] = 1;
    if (!empty($params['payment_processor'])) {
      // I can't find evidence this is passed in - I was gonna just remove it but decided to deprecate  as I see getToFinancialAccount
      // also anticipates it.
      CRM_Core_Error::deprecatedFunctionWarning('passing payment_processor is deprecated - use payment_processor_id');
      $paymentTrxnParams['payment_processor_id'] = $params['payment_processor'];
    }
    if (isset($paymentTrxnParams['payment_processor_id']) && empty($paymentTrxnParams['payment_processor_id'])) {
      // Don't pass 0 - ie the Pay Later processor as it is  a pseudo-processor.
      unset($paymentTrxnParams['payment_processor_id']);
    }
    if (empty($paymentTrxnParams['payment_instrument_id'])) {
      if (!empty($params['payment_processor_id'])) {
        $paymentTrxnParams['payment_instrument_id'] = civicrm_api3('PaymentProcessor', 'getvalue', ['return' => 'payment_instrument_id', 'id' => $paymentTrxnParams['payment_processor_id']]);
      }
      else {
        // Fall back  on the payment instrument  already  used - should  we  deprecate  this?
        $paymentTrxnParams['payment_instrument_id'] = $contribution['payment_instrument_id'];
      }
    }
    if (empty($paymentTrxnParams['trxn_id']) && !empty($paymentTrxnParams['contribution_trxn_id'])) {
      CRM_Core_Error::deprecatedFunctionWarning('contribution_trxn_id is deprecated - use trxn_id');
      $paymentTrxnParams['trxn_id'] = $paymentTrxnParams['contribution_trxn_id'];
    }

    if ($params['total_amount'] > 0) {
      $paymentTrxnParams['to_financial_account_id'] = CRM_Contribute_BAO_Contribution::getToFinancialAccount($contribution, $params);
      $paymentTrxnParams['from_financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($contribution['financial_type_id'], 'Accounts Receivable Account is');
      $paymentTrxnParams['trxn_date'] = CRM_Utils_Array::value('trxn_date', $params, CRM_Utils_Array::value('contribution_receive_date', $params, date('YmdHis')));
      $paymentTrxnParams['currency'] = $contribution['currency'];
      $paymentTrxnParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'status_id', 'Completed');

      $trxn = CRM_Core_BAO_FinancialTrxn::create($paymentTrxnParams);

      // @todo - this is just weird & historical & inconsistent - why 2 tracks?
      if (!empty($params['line_item']) && !empty($trxn)) {
        foreach ($params['line_item'] as $values) {
          foreach ($values as $id => $amount) {
            $p = ['id' => $id];
            $check = CRM_Price_BAO_LineItem::retrieve($p, $defaults);
            if (empty($check)) {
              throw new API_Exception('Please specify a valid Line Item.');
            }
            // get financial item
            $sql = "SELECT fi.id
            FROM civicrm_financial_item fi
            INNER JOIN civicrm_line_item li ON li.id = fi.entity_id and fi.entity_table = 'civicrm_line_item'
            WHERE li.contribution_id = %1 AND li.id = %2";
            $sqlParams = [
              1 => [$params['contribution_id'], 'Integer'],
              2 => [$id, 'Integer'],
            ];
            $fid = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
            // Record Entity Financial Trxn
            $eftParams = [
              'entity_table' => 'civicrm_financial_item',
              'financial_trxn_id' => $trxn->id,
              'amount' => $amount,
              'entity_id' => $fid,
            ];
            civicrm_api3('EntityFinancialTrxn', 'create', $eftParams);
          }
        }
      }
      elseif (!empty($trxn)) {
        $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($params['contribution_id']);
        if (!empty($lineItems)) {
          // get financial item
          list($ftIds, $taxItems) = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($params['contribution_id']);
          $entityParams = [
            'contribution_total_amount' => $contribution['total_amount'],
            'trxn_total_amount' => $params['total_amount'],
            'trxn_id' => $trxn->id,
          ];
          $eftParams = [
            'entity_table' => 'civicrm_financial_item',
            'financial_trxn_id' => $entityParams['trxn_id'],
          ];
          foreach ($lineItems as $key => $value) {
            if ($value['qty'] == 0) {
              continue;
            }
            $eftParams['entity_id'] = $ftIds[$value['price_field_value_id']];
            $entityParams['line_item_amount'] = $value['line_total'];
            CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
            if (array_key_exists($value['price_field_value_id'], $taxItems)) {
              $entityParams['line_item_amount'] = $taxItems[$value['price_field_value_id']]['amount'];
              $eftParams['entity_id'] = $taxItems[$value['price_field_value_id']]['financial_item_id'];
              CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
            }
          }
        }
      }
    }
    elseif ($params['total_amount'] < 0) {
      $trxn = self::recordRefundPayment($params['contribution_id'], $params, FALSE);
    }

    if ($isPaymentCompletesContribution) {
      if ($contributionStatus == 'Pending refund') {
        // Ideally we could still call completetransaction as non-payment related actions should
        // be outside this class. However, for now we just update the contribution here.
        // Unit test cover in CRM_Event_BAO_AdditionalPaymentTest::testTransactionInfo.
        civicrm_api3('Contribution', 'create',
          [
            'id' => $contribution['id'],
            'contribution_status_id' => 'Completed',
          ]
        );
      }
      else {
        civicrm_api3('Contribution', 'completetransaction', [
          'id' => $contribution['id'],
          'is_post_payment_create' => TRUE,
          'is_email_receipt' => $params['is_send_contribution_notification'],
        ]);
        // Get the trxn
        $trxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
        $ftParams = ['id' => $trxnId['financialTrxnId']];
        $trxn = CRM_Core_BAO_FinancialTrxn::retrieve($ftParams);
      }
    }
    elseif ($contributionStatus === 'Pending') {
      self::updateContributionStatus($contribution['id'], 'Partially Paid');
    }
    CRM_Contribute_BAO_Contribution::recordPaymentActivity($params['contribution_id'], CRM_Utils_Array::value('participant_id', $params), $params['total_amount'], $trxn->currency, $trxn->trxn_date);
    return $trxn;
  }

  /**
   * Send an email confirming a payment that has been received.
   *
   * @param array $params
   *
   * @return array
   */
  public static function sendConfirmation($params) {

    $entities = self::loadRelatedEntities($params['id']);
    $sendTemplateParams = [
      'groupName' => 'msg_tpl_workflow_contribution',
      'valueName' => 'payment_or_refund_notification',
      'PDFFilename' => ts('notification') . '.pdf',
      'contactId' => $entities['contact']['id'],
      'toName' => $entities['contact']['display_name'],
      'toEmail' => $entities['contact']['email'],
      'tplParams' => self::getConfirmationTemplateParameters($entities),
    ];
    if (!empty($params['from']) && !empty($params['check_permissions'])) {
      // Filter from against permitted emails.
      $validEmails = self::getValidFromEmailsForPayment($entities['event']['id'] ?? NULL);
      if (!isset($validEmails[$params['from']])) {
        // Ignore unpermitted parameter.
        unset($params['from']);
      }
    }
    $sendTemplateParams['from'] = $params['from'] ?? key(CRM_Core_BAO_Email::domainEmails());
    return CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
  }

  /**
   * Get valid from emails for payment.
   *
   * @param int $eventID
   *
   * @return array
   */
  public static function getValidFromEmailsForPayment($eventID = NULL) {
    if ($eventID) {
      $emails = CRM_Event_BAO_Event::getFromEmailIds($eventID);
    }
    else {
      $emails['from_email_id'] = CRM_Core_BAO_Email::getFromEmail();
    }
    return $emails['from_email_id'];
  }

  /**
   * Load entities related to the current payment id.
   *
   * This gives us all the data we need to send an email confirmation but avoiding
   * getting anything not tested for the confirmations. We retrieve the 'full' event as
   * it has been traditionally assigned in full.
   *
   * @param int $id
   *
   * @return array
   *   - contact = ['id' => x, 'display_name' => y, 'email' => z]
   *   - event = [.... full event details......]
   *   - contribution = ['id' => x],
   *   - payment = [payment info + payment summary info]
   * @throws \CiviCRM_API3_Exception
   */
  protected static function loadRelatedEntities($id) {
    $entities = [];
    $contributionID = (int) civicrm_api3('EntityFinancialTrxn', 'getvalue', [
      'financial_trxn_id' => $id,
      'entity_table' => 'civicrm_contribution',
      'return' => 'entity_id',
    ]);
    $entities['contribution'] = ['id' => $contributionID];
    $entities['payment'] = array_merge(civicrm_api3('FinancialTrxn', 'getsingle', ['id' => $id]),
      CRM_Contribute_BAO_Contribution::getPaymentInfo($contributionID)
    );

    $contactID = self::getPaymentContactID($contributionID);
    list($displayName, $email)  = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
    $entities['contact'] = ['id' => $contactID, 'display_name' => $displayName, 'email' => $email];
    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactID, 'return' => 'email_greeting']);
    $entities['contact']['email_greeting'] = $contact['email_greeting_display'];

    $participantRecords = civicrm_api3('ParticipantPayment', 'get', [
      'contribution_id' => $contributionID,
      'api.Participant.get' => ['return' => 'event_id'],
      'sequential' => 1,
    ])['values'];
    if (!empty($participantRecords)) {
      $entities['event'] = civicrm_api3('Event', 'getsingle', ['id' => $participantRecords[0]['api.Participant.get']['values'][0]['event_id']]);
      if (!empty($entities['event']['is_show_location'])) {
        $locationParams = [
          'entity_id' => $entities['event']['id'],
          'entity_table' => 'civicrm_event',
        ];
        $entities['location'] = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      }
    }

    return $entities;
  }

  /**
   * @param int $contributionID
   *
   * @return int
   */
  public static function getPaymentContactID($contributionID) {
    $contribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $contributionID ,
      'return' => ['contact_id'],
    ]);
    return (int) $contribution['contact_id'];
  }

  /**
   * @param array $entities
   *   Related entities as an array keyed by the various entities.
   *
   * @return array
   *   Values required for the notification
   *   - contact_id
   *   - template_variables
   *     - event (DAO of event if relevant)
   */
  public static function getConfirmationTemplateParameters($entities) {
    $templateVariables = [
      'contactDisplayName' => $entities['contact']['display_name'],
      'emailGreeting' => $entities['contact']['email_greeting'],
      'totalAmount' => $entities['payment']['total'],
      'amountOwed' => $entities['payment']['balance'],
      'totalPaid' => $entities['payment']['paid'],
      'paymentAmount' => $entities['payment']['total_amount'],
      'checkNumber' => CRM_Utils_Array::value('check_number', $entities['payment']),
      'receive_date' => $entities['payment']['trxn_date'],
      'paidBy' => CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $entities['payment']['payment_instrument_id']),
      'isShowLocation' => (!empty($entities['event']) ? $entities['event']['is_show_location'] : FALSE),
      'location' => CRM_Utils_Array::value('location', $entities),
      'event' => CRM_Utils_Array::value('event', $entities),
      'component' => (!empty($entities['event']) ? 'event' : 'contribution'),
      'isRefund' => $entities['payment']['total_amount'] < 0,
      'isAmountzero' => $entities['payment']['total_amount'] === 0,
      'refundAmount' => ($entities['payment']['total_amount'] < 0 ? $entities['payment']['total_amount'] : NULL),
      'paymentsComplete' => ($entities['payment']['balance'] == 0),
    ];

    return self::filterUntestedTemplateVariables($templateVariables);
  }

  /**
   * Filter out any untested variables.
   *
   * This just serves to highlight if any variables are added without a unit test also being added.
   *
   * (if hit then add a unit test for the param & add to this array).
   *
   * @param array $params
   *
   * @return array
   */
  public static function filterUntestedTemplateVariables($params) {
    $testedTemplateVariables = [
      'contactDisplayName',
      'totalAmount',
      'amountOwed',
      'paymentAmount',
      'event',
      'component',
      'checkNumber',
      'receive_date',
      'paidBy',
      'isShowLocation',
      'location',
      'isRefund',
      'isAmountzero',
      'refundAmount',
      'totalPaid',
      'paymentsComplete',
      'emailGreeting',
    ];
    // These are assigned by the payment form - they still 'get through' from the
    // form for now without being in here but we should ideally load
    // and assign. Note we should update the tpl to use {if $billingName}
    // and ditch contributeMode - although it might need to be deprecated rather than removed.
    $todoParams = [
      'contributeMode',
      'billingName',
      'address',
      'credit_card_type',
      'credit_card_number',
      'credit_card_exp_date',
    ];
    $filteredParams = [];
    foreach ($testedTemplateVariables as $templateVariable) {
      // This will cause an a-notice if any are NOT set - by design. Ensuring
      // they are set prevents leakage.
      $filteredParams[$templateVariable] = $params[$templateVariable];
    }
    return $filteredParams;
  }

  /**
   * @param $contributionId
   * @param $trxnData
   * @param $updateStatus
   *   - deprecate this param
   *
   * @return CRM_Financial_DAO_FinancialTrxn
   */
  protected static function recordRefundPayment($contributionId, $trxnData, $updateStatus) {
    list($contributionDAO, $params) = self::getContributionAndParamsInFormatForRecordFinancialTransaction($contributionId);

    $params['payment_instrument_id'] = CRM_Utils_Array::value('payment_instrument_id', $trxnData, CRM_Utils_Array::value('payment_instrument_id', $params));

    $paidStatus = CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialItem', 'status_id', 'Paid');
    $arAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($contributionDAO->financial_type_id, 'Accounts Receivable Account is');
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $trxnData['total_amount'] = $trxnData['net_amount'] = $trxnData['total_amount'];
    $trxnData['from_financial_account_id'] = $arAccountId;
    $trxnData['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded');
    // record the entry
    $financialTrxn = CRM_Contribute_BAO_Contribution::recordFinancialAccounts($params, $trxnData);

    // note : not using the self::add method,
    // the reason because it performs 'status change' related code execution for financial records
    // which in 'Pending Refund' => 'Completed' is not useful, instead specific financial record updates
    // are coded below i.e. just updating financial_item status to 'Paid'
    if ($updateStatus) {
      CRM_Core_DAO::setFieldValue('CRM_Contribute_BAO_Contribution', $contributionId, 'contribution_status_id', $completedStatusId);
    }
    return $financialTrxn;
  }

  /**
   * @param int $contributionId
   * @param array $trxnData
   * @param int $participantId
   *
   * @return \CRM_Core_BAO_FinancialTrxn
   */
  public static function recordPayment($contributionId, $trxnData, $participantId) {
    list($contributionDAO, $params) = self::getContributionAndParamsInFormatForRecordFinancialTransaction($contributionId);

    $trxnData['trxn_date'] = !empty($trxnData['trxn_date']) ? $trxnData['trxn_date'] : date('YmdHis');
    $params['payment_instrument_id'] = CRM_Utils_Array::value('payment_instrument_id', $trxnData, CRM_Utils_Array::value('payment_instrument_id', $params));

    $paidStatus = CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialItem', 'status_id', 'Paid');
    $arAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($contributionDAO->financial_type_id, 'Accounts Receivable Account is');
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $params['partial_payment_total'] = $contributionDAO->total_amount;
    $params['partial_amount_to_pay'] = $trxnData['total_amount'];
    $trxnData['net_amount'] = !empty($trxnData['net_amount']) ? $trxnData['net_amount'] : $trxnData['total_amount'];
    $params['pan_truncation'] = CRM_Utils_Array::value('pan_truncation', $trxnData);
    $params['card_type_id'] = CRM_Utils_Array::value('card_type_id', $trxnData);
    $params['check_number'] = CRM_Utils_Array::value('check_number', $trxnData);

    // record the entry
    $financialTrxn = CRM_Contribute_BAO_Contribution::recordFinancialAccounts($params, $trxnData);
    $toFinancialAccount = $arAccountId;
    $trxnId = CRM_Core_BAO_FinancialTrxn::getBalanceTrxnAmt($contributionId, $contributionDAO->financial_type_id);
    if (!empty($trxnId)) {
      $trxnId = $trxnId['trxn_id'];
    }
    elseif (!empty($contributionDAO->payment_instrument_id)) {
      $trxnId = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($contributionDAO->payment_instrument_id);
    }
    else {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
      $queryParams = [1 => [$relationTypeId, 'Integer']];
      $trxnId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = %1", $queryParams);
    }

    // update statuses
    // criteria for updates contribution total_amount == financial_trxns of partial_payments
    $sql = "SELECT SUM(ft.total_amount) as sum_of_payments, SUM(ft.net_amount) as net_amount_total
FROM civicrm_financial_trxn ft
LEFT JOIN civicrm_entity_financial_trxn eft
  ON (ft.id = eft.financial_trxn_id)
WHERE eft.entity_table = 'civicrm_contribution'
  AND eft.entity_id = {$contributionId}
  AND ft.to_financial_account_id != {$toFinancialAccount}
  AND ft.status_id = {$completedStatusId}
";
    $query = CRM_Core_DAO::executeQuery($sql);
    $query->fetch();
    $sumOfPayments = $query->sum_of_payments;

    // update statuses
    if ($contributionDAO->total_amount == $sumOfPayments) {
      // update contribution status and
      // clean cancel info (if any) if prev. contribution was updated in case of 'Refunded' => 'Completed'
      $contributionDAO->contribution_status_id = $completedStatusId;
      $contributionDAO->cancel_date = 'null';
      $contributionDAO->cancel_reason = NULL;
      $netAmount = !empty($trxnData['net_amount']) ? NULL : $trxnData['total_amount'];
      $contributionDAO->net_amount = $query->net_amount_total + $netAmount;
      $contributionDAO->fee_amount = $contributionDAO->total_amount - $contributionDAO->net_amount;
      $contributionDAO->save();

      //Change status of financial record too
      $financialTrxn->status_id = $completedStatusId;
      $financialTrxn->save();

      // note : not using the self::add method,
      // the reason because it performs 'status change' related code execution for financial records
      // which in 'Partial Paid' => 'Completed' is not useful, instead specific financial record updates
      // are coded below i.e. just updating financial_item status to 'Paid'

      if (!$participantId) {
        $participantId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $contributionId, 'participant_id', 'contribution_id');
      }
      if ($participantId) {
        // update participant status
        $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
        $ids = CRM_Event_BAO_Participant::getParticipantIds($contributionId);
        foreach ($ids as $val) {
          $participantUpdate['id'] = $val;
          $participantUpdate['status_id'] = array_search('Registered', $participantStatuses);
          CRM_Event_BAO_Participant::add($participantUpdate);
        }
      }

      // Remove this - completeOrder does it.
      CRM_Contribute_BAO_Contribution::updateMembershipBasedOnCompletionOfContribution(
        $contributionDAO,
        $contributionId,
        $trxnData['trxn_date']
      );

      // update financial item statuses
      $baseTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contributionId);
      $sqlFinancialItemUpdate = "
UPDATE civicrm_financial_item fi
  LEFT JOIN civicrm_entity_financial_trxn eft
    ON (eft.entity_id = fi.id AND eft.entity_table = 'civicrm_financial_item')
SET status_id = {$paidStatus}
WHERE eft.financial_trxn_id IN ({$trxnId}, {$baseTrxnId['financialTrxnId']})
";
      CRM_Core_DAO::executeQuery($sqlFinancialItemUpdate);
    }
    return $financialTrxn;
  }

  /**
   * The recordFinancialTransactions function has capricious requirements for input parameters - load them.
   *
   * The function needs rework but for now we need to give it what it wants.
   *
   * @param int $contributionId
   *
   * @return array
   */
  protected static function getContributionAndParamsInFormatForRecordFinancialTransaction($contributionId) {
    $getInfoOf['id'] = $contributionId;
    $defaults = [];
    $contributionDAO = CRM_Contribute_BAO_Contribution::retrieve($getInfoOf, $defaults);

    // build params for recording financial trxn entry
    $params['contribution'] = $contributionDAO;
    $params = array_merge($defaults, $params);
    $params['skipLineItem'] = TRUE;
    return [$contributionDAO, $params];
  }

  /**
   * Does this payment complete the contribution
   *
   * @param int $contributionID
   * @param float $paymentAmount
   *
   * @return bool
   */
  protected static function isPaymentCompletesContribution($contributionID, $paymentAmount) {
    $outstandingBalance = CRM_Contribute_BAO_Contribution::getContributionBalance($contributionID);
    $cmp = bccomp($paymentAmount, $outstandingBalance, 5);
    return ($cmp == 0 || $cmp == 1);
  }

  /**
   * Update the status of the contribution.
   *
   * We pass the is_post_payment_create as we have already created the line items
   *
   * @param int $contributionID
   * @param string $status
   *
   * @throws \CiviCRM_API3_Exception
   */
  private static function updateContributionStatus(int $contributionID, string $status) {
    civicrm_api3('Contribution', 'create',
      [
        'id' => $contributionID,
        'is_post_payment_create' => TRUE,
        'contribution_status_id' => $status,
      ]
    );
  }

}
