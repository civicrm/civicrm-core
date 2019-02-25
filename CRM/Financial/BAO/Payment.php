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
   */
  public static function create($params) {
    $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $params['contribution_id']]);
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus($contribution['contribution_status_id'], 'name');

    // Check if pending contribution
    $fullyPaidPayLater = FALSE;
    if ($contributionStatus == 'Pending') {
      $cmp = bccomp($contribution['total_amount'], $params['total_amount'], 5);
      // Total payment amount is the whole amount paid against pending contribution
      if ($cmp == 0 || $cmp == -1) {
        civicrm_api3('Contribution', 'completetransaction', ['id' => $contribution['id']]);
        // Get the trxn
        $trxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
        $ftParams = ['id' => $trxnId['financialTrxnId']];
        $trxn = CRM_Core_BAO_FinancialTrxn::retrieve($ftParams, CRM_Core_DAO::$_nullArray);
        $fullyPaidPayLater = TRUE;
      }
      else {
        civicrm_api3('Contribution', 'create',
          [
            'id' => $contribution['id'],
            'contribution_status_id' => 'Partially paid',
          ]
        );
      }
    }
    if (!$fullyPaidPayLater) {
      $trxn = CRM_Core_BAO_FinancialTrxn::getPartialPaymentTrxn($contribution, $params);
      if (CRM_Utils_Array::value('line_item', $params) && !empty($trxn)) {
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
        CRM_Contribute_BAO_Contribution::assignProportionalLineItems($params, $trxn->id, $contribution['total_amount']);
      }
    }

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
    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_contribution',
      'valueName' => 'payment_or_refund_notification',
      'PDFFilename' => ts('notification') . '.pdf',
      'contactId' => $entities['contact']['id'],
      'toName' => $entities['contact']['display_name'],
      'toEmail' => $entities['contact']['email'],
      'tplParams' => self::getConfirmationTemplateParameters($entities),
    );
    return CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
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
      'emailGreeting'
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
   * @todo  - make this protected once recordAdditionalPayment no longer calls it.
   *
   * @return CRM_Financial_DAO_FinancialTrxn
   */
  public static function recordRefundPayment($contributionId, $trxnData, $updateStatus) {
    $contributionDAO = new CRM_Contribute_BAO_Contribution();
    $contributionDAO->id = $contributionId;
    $contributionDAO->find(TRUE);

    $paidStatus = CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialItem', 'status_id', 'Paid');
    $arAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($contributionDAO->financial_type_id, 'Accounts Receivable Account is');
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $trxnData['total_amount'] = $trxnData['net_amount'] = -$trxnData['total_amount'];
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
    // add financial item entry
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contributionDAO->id);
    if (!empty($lineItems)) {
      foreach ($lineItems as $lineItemId => $lineItemValue) {
        // don't record financial item for cancelled line-item
        if ($lineItemValue['qty'] == 0) {
          continue;
        }
        $paid = $lineItemValue['line_total'] * ($financialTrxn->total_amount / $contributionDAO->total_amount);
        $addFinancialEntry = [
          'transaction_date' => $financialTrxn->trxn_date,
          'contact_id' => $contributionDAO->contact_id,
          'amount' => round($paid, 2),
          'currency' => $contributionDAO->currency,
          'status_id' => $paidStatus,
          'entity_id' => $lineItemId,
          'entity_table' => 'civicrm_line_item',
        ];
        $trxnIds = ['id' => $financialTrxn->id];
        CRM_Financial_BAO_FinancialItem::create($addFinancialEntry, NULL, $trxnIds);
      }
    }
    return $financialTrxn;
  }

}
