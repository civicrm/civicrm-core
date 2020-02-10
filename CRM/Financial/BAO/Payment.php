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
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function create($params) {
    $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $params['contribution_id']]);
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);
    $isPaymentCompletesContribution = self::isPaymentCompletesContribution($params['contribution_id'], $params['total_amount'], $contributionStatus);
    $lineItems = self::getPayableLineItems($params);

    $whiteList = ['check_number', 'payment_processor_id', 'fee_amount', 'total_amount', 'contribution_id', 'net_amount', 'card_type_id', 'pan_truncation', 'trxn_result_code', 'payment_instrument_id', 'trxn_id', 'trxn_date'];
    $paymentTrxnParams = array_intersect_key($params, array_fill_keys($whiteList, 1));
    $paymentTrxnParams['is_payment'] = 1;
    // Really we should have a DB default.
    $paymentTrxnParams['fee_amount'] = $paymentTrxnParams['fee_amount'] ?? 0;

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

    $paymentTrxnParams['currency'] = $contribution['currency'];

    $accountsReceivableAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($contribution['financial_type_id'], 'Accounts Receivable Account is');
    $paymentTrxnParams['to_financial_account_id'] = CRM_Contribute_BAO_Contribution::getToFinancialAccount($contribution, $params);
    $paymentTrxnParams['from_financial_account_id'] = $accountsReceivableAccount;

    if ($params['total_amount'] > 0) {
      $paymentTrxnParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'status_id', 'Completed');
    }
    elseif ($params['total_amount'] < 0) {
      $paymentTrxnParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded');
    }

    $trxn = CRM_Core_BAO_FinancialTrxn::create($paymentTrxnParams);

    if ($params['total_amount'] < 0 && !empty($params['cancelled_payment_id'])) {
      self::reverseAllocationsFromPreviousPayment($params, $trxn->id);
      return $trxn;
    }
    list($ftIds, $taxItems) = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($params['contribution_id']);

    foreach ($lineItems as $key => $value) {
      if ($value['allocation'] === (float) 0) {
        continue;
      }

      if (!empty($ftIds[$value['price_field_value_id']])) {
        $financialItemID = $ftIds[$value['price_field_value_id']];
      }
      else {
        $financialItemID = self::getNewFinancialItemID($value, $params['trxn_date'], $contribution['contact_id'], $paymentTrxnParams['currency']);
      }

      $eftParams = [
        'entity_table' => 'civicrm_financial_item',
        'financial_trxn_id' => $trxn->id,
        'entity_id' => $financialItemID,
        'amount' => $value['allocation'],
      ];

      civicrm_api3('EntityFinancialTrxn', 'create', $eftParams);

      if (array_key_exists($value['price_field_value_id'], $taxItems)) {
        // @todo - this is expected to be broken - it should be fixed to
        // a) have the getPayableLineItems add the amount to allocate for tax
        // b) call EntityFinancialTrxn directly - per above.
        // - see https://github.com/civicrm/civicrm-core/pull/14763
        $entityParams = [
          'contribution_total_amount' => $contribution['total_amount'],
          'trxn_total_amount' => $params['total_amount'],
          'trxn_id' => $trxn->id,
          'line_item_amount' => $taxItems[$value['price_field_value_id']]['amount'],
        ];
        $eftParams['entity_id'] = $taxItems[$value['price_field_value_id']]['financial_item_id'];
        CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
      }
    }

    if ($isPaymentCompletesContribution) {
      if ($contributionStatus === 'Pending refund') {
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
    elseif ($contributionStatus === 'Pending' && $params['total_amount'] > 0) {
      self::updateContributionStatus($contribution['id'], 'Partially Paid');
      $participantPayments = civicrm_api3('ParticipantPayment', 'get', [
        'contribution_id' => $contribution['id'],
        'participant_id.status_id' => ['IN' => ['Pending from pay later', 'Pending from incomplete transaction']],
      ])['values'];
      foreach ($participantPayments as $participantPayment) {
        civicrm_api3('Participant', 'create', ['id' => $participantPayment['participant_id'], 'status_id' => 'Partially paid']);
      }
    }
    elseif ($contributionStatus === 'Completed' && ((float) CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE) === 0.0)) {
      // If the contribution has previously been completed (fully paid) and now has total payments adding up to 0
      //  change status to refunded.
      self::updateContributionStatus($contribution['id'], 'Refunded');
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
   *
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
   * @throws \CiviCRM_API3_Exception
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
   * Does this payment complete the contribution.
   *
   * @param int $contributionID
   * @param float $paymentAmount
   * @param string $previousStatus
   *
   * @return bool
   */
  protected static function isPaymentCompletesContribution($contributionID, $paymentAmount, $previousStatus) {
    if ($previousStatus === 'Completed') {
      return FALSE;
    }
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

  /**
   * Get the line items for the contribution.
   *
   * Retrieve the line items and wrangle the following
   *
   * - get the outstanding balance on a line item basis.
   * - determine what amount is being paid on this line item - we get the total being paid
   *   for the whole contribution and determine the ratio of the balance that is being paid
   *   and then assign apply that ratio to each line item.
   * - if overrides have been passed in we use those amounts instead.
   *
   * @param $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected static function getPayableLineItems($params): array {
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($params['contribution_id']);
    $lineItemOverrides = [];
    if (!empty($params['line_item'])) {
      // The format is a bit weird here - $params['line_item'] => [[1 => 10], [2 => 40]]
      // Squash to [1 => 10, 2 => 40]
      foreach ($params['line_item'] as $lineItem) {
        $lineItemOverrides += $lineItem;
      }
    }
    $outstandingBalance = CRM_Contribute_BAO_Contribution::getContributionBalance($params['contribution_id']);
    if ($outstandingBalance !== 0.0) {
      $ratio = $params['total_amount'] / $outstandingBalance;
    }
    else {
      // Help we are making a payment but no money is owed. We won't allocate the overpayment to any line item.
      $ratio = 0;
    }
    foreach ($lineItems as $lineItemID => $lineItem) {
      // Ideally id would be set deeper but for now just add in here.
      $lineItems[$lineItemID]['id'] = $lineItemID;
      $lineItems[$lineItemID]['paid'] = self::getAmountOfLineItemPaid($lineItemID);
      $lineItems[$lineItemID]['balance'] = $lineItem['subTotal'] - $lineItems[$lineItemID]['paid'];

      if (!empty($lineItemOverrides)) {
        $lineItems[$lineItemID]['allocation'] = CRM_Utils_Array::value($lineItemID, $lineItemOverrides);
      }
      else {
        $lineItems[$lineItemID]['allocation'] = $lineItems[$lineItemID]['balance'] * $ratio;
      }
    }
    return $lineItems;
  }

  /**
   * Get the amount paid so far against this line item.
   *
   * @param int $lineItemID
   *
   * @return float
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected static function getAmountOfLineItemPaid($lineItemID) {
    $paid = 0;
    $financialItems = civicrm_api3('FinancialItem', 'get', [
      'entity_id' => $lineItemID,
      'entity_table' => 'civicrm_line_item',
      'options' => ['sort' => 'id DESC', 'limit' => 0],
    ])['values'];
    if (!empty($financialItems)) {
      $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', [
        'entity_table' => 'civicrm_financial_item',
        'entity_id' => ['IN' => array_keys($financialItems)],
        'options' => ['limit' => 0],
        'financial_trxn_id.is_payment' => 1,
      ])['values'];
      foreach ($entityFinancialTrxns as $entityFinancialTrxn) {
        $paid += $entityFinancialTrxn['amount'];
      }
    }
    return (float) $paid;
  }

  /**
   * Reverse the entity financial transactions associated with the cancelled payment.
   *
   * The reversals are linked to the new payemnt.
   *
   * @param array $params
   * @param int $trxnID
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected static function reverseAllocationsFromPreviousPayment($params, $trxnID) {
    // Do a direct reversal of any entity_financial_trxn records being cancelled.
    $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', [
      'entity_table' => 'civicrm_financial_item',
      'options' => ['limit' => 0],
      'financial_trxn_id.id' => $params['cancelled_payment_id'],
    ])['values'];
    foreach ($entityFinancialTrxns as $entityFinancialTrxn) {
      civicrm_api3('EntityFinancialTrxn', 'create', [
        'entity_table' => 'civicrm_financial_item',
        'entity_id' => $entityFinancialTrxn['entity_id'],
        'amount' => -$entityFinancialTrxn['amount'],
        'financial_trxn_id' => $trxnID,
      ]);
    }
  }

  /**
   * Create a financial items & return the ID.
   *
   * Ideally this will never be called.
   *
   * However, I hit a scenario in testing where 'something' had  created a pending payment with
   * no financial items and that would result in a fatal error without handling here. I failed
   * to replicate & am not investigating via a new test methodology
   * https://github.com/civicrm/civicrm-core/pull/15706
   *
   * After this is in I will do more digging & once I feel confident new instances are not being
   * created I will add deprecation notices into this function with a view to removing.
   *
   * However, I think we want to add it in 5.20 as there is a risk of users experiencing an error
   * if there is incorrect data & we need time to ensure that what I hit was not a 'thing.
   * (it might be the demo site data is a bit flawed & that was the issue).
   *
   * @param array $lineItem
   * @param string $trxn_date
   * @param int $contactID
   * @param string $currency
   *
   * @return int
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected static function getNewFinancialItemID($lineItem, $trxn_date, $contactID, $currency): int {
    $financialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $lineItem['financial_type_id'],
      'Income Account Is'
    );
    $itemParams = [
      'transaction_date' => $trxn_date,
      'contact_id' => $contactID,
      'currency' => $currency,
      'amount' => $lineItem['line_total'],
      'description' => $lineItem['label'],
      'status_id' => 'Unpaid',
      'financial_account_id' => $financialAccount,
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $lineItem['id'],
    ];
    return (int) civicrm_api3('FinancialItem', 'create', $itemParams)['id'];
  }

}
