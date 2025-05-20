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

use Civi\Api4\Contribution;
use Civi\Api4\FinancialItem;
use Civi\Api4\LineItem;
use Civi\Api4\EntityFinancialTrxn;

/**
 * This class contains payment related functions.
 */
class CRM_Financial_BAO_Payment {

  /**
   * Function to process additional payment for partial and refund
   * contributions.
   *
   * This function is called via API payment.create function. All forms that
   * add payments should use this.
   *
   * @param array $params
   *   - contribution_id
   *   - total_amount
   *   - line_item
   * @param bool $disableActionsOnCompleteOrder Disble membership, participant processing when the payment completes the order
   *   Note this is only set by Payment.Create in APIv4 and should not be used elsewhere and is likely to change.
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(array $params, $disableActionsOnCompleteOrder = FALSE): CRM_Financial_DAO_FinancialTrxn {
    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $params['contribution_id'])
      ->addSelect('*', 'contribution_status_id:name', 'balance_amount', 'paid_amount')
      ->execute()->first();
    $contributionStatus = $contribution['contribution_status_id:name'];
    $isPaymentCompletesContribution = self::isPaymentCompletesContribution($params['contribution_id'], $params['total_amount'], $contributionStatus);
    $payableItems = self::getPayableItems($params, $contribution);

    $whiteList = ['check_number', 'payment_processor_id', 'fee_amount', 'total_amount', 'contribution_id', 'net_amount', 'card_type_id', 'pan_truncation', 'trxn_result_code', 'payment_instrument_id', 'trxn_id', 'trxn_date', 'order_reference'];
    $paymentTrxnParams = array_intersect_key($params, array_fill_keys($whiteList, 1));
    $paymentTrxnParams['is_payment'] = 1;
    // Really we should have a DB default.
    $paymentTrxnParams['fee_amount'] ??= 0;
    if (!empty($params['custom'])) {
      $paymentTrxnParams['custom'] = $params['custom'];
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

    //If Payment is recorded on Failed contribution, update it to Pending.
    if ($contributionStatus === 'Failed' && $params['total_amount'] > 0) {
      //Enter a financial trxn to record a payment in receivable account
      //as failed transaction does not insert any trxn values. Hence, if Payment is
      //recorded on a failed contribution, the transition happens from Failed -> Pending -> Completed.
      $ftParams = array_merge($paymentTrxnParams, [
        'from_financial_account_id' => NULL,
        'to_financial_account_id' => $accountsReceivableAccount,
        'is_payment' => 0,
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      ]);
      CRM_Core_BAO_FinancialTrxn::create($ftParams);
      $contributionStatus = 'Pending';
      self::updateContributionStatus($contribution['id'], $contributionStatus);
    }
    $trxn = CRM_Core_BAO_FinancialTrxn::create($paymentTrxnParams);

    if (array_key_exists('fee_amount', $params) && $params['fee_amount'] > 0) {
      $trxnParams = [
        'contribution_status_id' => $paymentTrxnParams['status_id'],
        'trxnParams' => [
          'trxn_date' => $paymentTrxnParams['trxn_date'],
          'currency' => $paymentTrxnParams['currency'],
          'trxn_id' => isset($paymentTrxnParams['trxn_id']) ? $paymentTrxnParams['trxn_id'] : NULL,
          'payment_instrument_id' => isset($paymentTrxnParams['payment_instrument_id']) ? $paymentTrxnParams['payment_instrument_id'] : NULL,
          'check_number' => isset($paymentTrxnParams['check_number']) ? $paymentTrxnParams['check_number'] : NULL,
          'pan_truncation' => isset($paymentTrxnParams['pan_truncation']) ? $paymentTrxnParams['pan_truncation'] : NULL,
          'card_type_id' => isset($paymentTrxnParams['card_type_id']) ? $paymentTrxnParams['card_type_id'] : NULL,
          'payment_processor_id' => isset($paymentTrxnParams['payment_processor_id']) ? $paymentTrxnParams['payment_processor_id'] : NULL,
        ],
      ];

      $trxnParams = array_merge($paymentTrxnParams, $trxnParams);
      CRM_Core_BAO_FinancialTrxn::recordFees($trxnParams);
    }

    if ($params['total_amount'] < 0 && !empty($params['cancelled_payment_id'])) {
      // Payment was cancelled. Reverse the financial transactions.
      self::reverseAllocationsFromPreviousPayment($params, $trxn->id);
    }
    else {
      // Link the payment with the relevant financial items, by creating EntityFinancialItems.
      // We also ensure the status of the Item is set to Paid or Partially Paid as appropriate.
      foreach ($payableItems as $payableItem) {
        if ($payableItem['allocation'] === 0.0) {
          continue;
        }

        EntityFinancialTrxn::create(FALSE)->setValues([
          'entity_table' => 'civicrm_financial_item',
          'financial_trxn_id' => $trxn->id,
          'entity_id' => $payableItem['financial_item.id'],
          'amount' => $payableItem['allocation'],
        ])->execute();

        if ('Paid' !== $payableItem['financial_item.status_id:name']) {
          // Did the item get fully paid?
          $newStatus = $payableItem['allocation'] < $payableItem['balance'] ? 'Partially paid' : 'Paid';
          FinancialItem::update(FALSE)
            ->addValue('status_id:name', $newStatus)
            ->addWhere('id', '=', $payableItem['financial_item.id'])
            ->execute();
        }
      }
    }
    self::updateRelatedContribution($params, $params['contribution_id']);
    if ($isPaymentCompletesContribution) {
      if ($contributionStatus === 'Pending refund') {
        // Unit test cover in CRM_Event_BAO_AdditionalPaymentTest::testTransactionInfo.
        civicrm_api3('Contribution', 'create',
          [
            'id' => $contribution['id'],
            'contribution_status_id' => 'Completed',
          ]
        );
      }
      else {
        $contributionBAO = new CRM_Contribute_BAO_Contribution();
        $contributionBAO->id = $contribution['id'];
        if (!$contributionBAO->find(TRUE)) {
          throw new CRM_Core_Exception('A valid contribution ID is required', 'invalid_data');
        }
        if ($contributionBAO->contribution_status_id == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')) {
          throw new CRM_Core_Exception(ts('Contribution already completed'), 'contribution_completed');
        }
        CRM_Contribute_BAO_Contribution::completeOrder([
          'is_email_receipt' => $params['is_send_contribution_notification'],
          'trxn_date' => $params['trxn_date'],
          'payment_instrument_id' => $paymentTrxnParams['payment_instrument_id'],
          'payment_processor_id' => $paymentTrxnParams['payment_processor_id'] ?? '',
        ], $contributionBAO->contribution_recur_id, $contribution['id'], TRUE, $disableActionsOnCompleteOrder);
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
    // Note that we reload the payments rather than use $contribution['paid_amount']
    // here as we are interested in the paid_amount AFTER this payment has been made.
    elseif ($contributionStatus === 'Completed' && ((float) CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE) === 0.0)) {
      // If the contribution has previously been completed (fully paid) and now has total payments adding up to 0
      //  change status to 'refunded'.
      self::updateContributionStatus($contribution['id'], 'Refunded');
    }
    CRM_Contribute_BAO_Contribution::recordPaymentActivity($params['contribution_id'], $params['participant_id'] ?? NULL, $params['total_amount'], $trxn->currency, $trxn->trxn_date);
    return $trxn;
  }

  /**
   * Function to update contribution's check_number and trxn_id by
   *  concatenating values from financial trxn's check_number and trxn_id
   * respectively
   *
   * @param array $params
   * @param int $contributionID
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateRelatedContribution(array $params, int $contributionID): void {
    $contributionDAO = new CRM_Contribute_DAO_Contribution();
    $contributionDAO->id = $contributionID;
    $contributionDAO->find(TRUE);
    if (isset($params['fee_amount'])) {
      // Update contribution.fee_amount to be be the total of all fees
      // since the payment is already saved the total here will be right.
      $payments = civicrm_api3('Payment', 'get', [
        'contribution_id' => $contributionID,
        'return' => 'fee_amount',
      ])['values'];
      $totalFees = 0;
      foreach ($payments as $payment) {
        $totalFees += $payment['fee_amount'] ?? 0;
      }
      $contributionDAO->fee_amount = $totalFees;
      $contributionDAO->net_amount = $contributionDAO->total_amount - $contributionDAO->fee_amount;
    }

    foreach (['trxn_id', 'check_number'] as $fieldName) {
      if (!empty($params[$fieldName])) {
        $values = [];
        if (!empty($contributionDAO->$fieldName)) {
          $values = explode(',', $contributionDAO->$fieldName);
        }
        // if submitted check_number or trxn_id value is
        //   already present then ignore else add to $values array
        if (!in_array($params[$fieldName], $values)) {
          $values[] = $params[$fieldName];
        }
        $contributionDAO->$fieldName = implode(',', $values);
      }
    }

    $contributionDAO->save();
  }

  /**
   * Send an email confirming a payment that has been received.
   *
   * @param array $params
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function sendConfirmation($params) {

    $entities = self::loadRelatedEntities($params['id']);

    $sendTemplateParams = [
      'workflow' => 'payment_or_refund_notification',
      'PDFFilename' => ts('notification') . '.pdf',
      'toName' => $entities['contact']['display_name'],
      'toEmail' => $entities['contact']['email'],
      'tplParams' => self::getConfirmationTemplateParameters($entities),
      'modelProps' => array_filter([
        'contributionID' => $entities['contribution']['id'],
        'contactID' => $entities['contact']['id'],
        'financialTrxnID' => $params['id'],
        'eventID' => $entities['event']['id'] ?? NULL,
        'participantID' => $entities['participant']['id'] ?? NULL,
      ]),
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
   * @throws \CRM_Core_Exception
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
    [$displayName, $email]  = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
    $entities['contact'] = ['id' => $contactID, 'display_name' => $displayName, 'email' => $email];
    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactID, 'return' => 'email_greeting']);
    $entities['contact']['email_greeting'] = $contact['email_greeting_display'];

    $participantRecords = civicrm_api3('ParticipantPayment', 'get', [
      'contribution_id' => $contributionID,
      'api.Participant.get' => ['return' => 'event_id'],
      'sequential' => 1,
    ])['values'];
    if (!empty($participantRecords)) {
      $entities['participant'] = $participantRecords[0]['api.Participant.get']['values'][0];
      $entities['event'] = civicrm_api3('Event', 'getsingle', ['id' => $entities['participant']['event_id']]);
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
   * @throws \CRM_Core_Exception
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
   * @deprecated these template variables no longer used in the core template
   * from 5.69 - stop assigning them.
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
      'currency' => $entities['payment']['currency'],
      'amountOwed' => $entities['payment']['balance'],
      'totalPaid' => $entities['payment']['paid'],
      'paymentAmount' => $entities['payment']['total_amount'],
      'checkNumber' => $entities['payment']['check_number'] ?? NULL,
      'receive_date' => $entities['payment']['trxn_date'],
      'paidBy' => CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $entities['payment']['payment_instrument_id']),
      'isShowLocation' => (!empty($entities['event']) ? $entities['event']['is_show_location'] : FALSE),
      'location' => $entities['location'] ?? NULL,
      'event' => $entities['event'] ?? NULL,
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
      'currency',
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
   * @throws \CRM_Core_Exception
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
   * Retrieve the financial items that need to be linked to the payment.
   *
   * EntityFinancialItems will be added to the sum of the Payment total
   * linking it to these items.
   *
   * - get the outstanding balance on a line item basis.
   * - determine what amount is being paid on this line item - we get the total being paid
   *   for the whole contribution and determine the ratio of the balance that is being paid
   *   and then assign apply that ratio to each line item.
   * - if overrides have been passed in we use those amounts instead.
   *
   * @param array $params
   * @param array $contribution
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function getPayableItems(array $params, array $contribution): array {
    $outstandingBalance = $contribution['balance_amount'];
    if ($outstandingBalance !== 0.0) {
      $ratio = $params['total_amount'] / $outstandingBalance;
    }
    elseif ($params['total_amount'] < 0) {
      $ratio = $params['total_amount'] / $contribution['paid_amount'];
    }
    else {
      // Help we are making a payment but no money is owed. We won't allocate the overpayment to any line item.
      $ratio = 0;
    }
    $lineItemOverrides = [];
    if (!empty($params['line_item'])) {
      // The format is a bit weird here - $params['line_item'] => [[1 => 10], [2 => 40]]
      // Squash to [1 => 10, 2 => 40]
      foreach ($params['line_item'] as $lineItem) {
        $lineItemOverrides += $lineItem;
      }
    }

    $items = LineItem::get(FALSE)
      ->addSelect('*', 'financial_item.status_id:name', 'financial_item.id', 'financial_item.financial_account_id', 'financial_item_id.currency', 'financial_item.financial_account_id.is_tax', 'financial_item.entity_id', 'financial_item.amount', 'allocated.amount')
      ->addJoin(
        'FinancialItem AS financial_item',
        'LEFT',
        ['financial_item.entity_table', '=', '"civicrm_line_item"'],
        ['financial_item.entity_id', '=', 'id']
      )
      ->addJoin('EntityFinancialTrxn AS allocated',
        'LEFT',
        ['allocated.entity_id', '=', 'financial_item.id'],
        ['allocated.entity_table', '=', '"civicrm_financial_item"'],
        ['allocated.financial_trxn_id.is_payment', '=', TRUE]
      )
      // Ideally we would group by financial_item.id & get the sum of
      // amount, but we hit full group by issues.
      ->addOrderBy('financial_item.id')
      ->addWhere('contribution_id', '=', (int) $params['contribution_id'])
      ->execute();

    $payableItems = [];

    foreach ($items as $item) {
      $lineItemID = $item['id'];
      if (!$item['financial_item.id']) {
        // If we didn't find a financial item that is NOT of type "Sales Tax" then create a new one.
        // This covers a situation that would not normally exist where the site has a data issue.
        $item = self::createFinancialItem($item, $params['trxn_date'], $contribution['contact_id'], $contribution['currency']);
      }
      // Add up the amount paid by line item, separated into tax & non-tax.
      // Up to 2 items per line item are added to payable items (tax + no tax).
      // The item added from the last row 'wins' - it will have the totals based on the total
      // of the amount paid across all of the rows.
      // @todo perhaps this should be done by financial item, not line item.
      $payableItemIndex = $item['financial_item.financial_account_id.is_tax'] ? ($item['id'] . '-tax') : $item['id'];
      $item['paid'] = ($item['allocated.amount'] ?: 0) + ($payableItems[$payableItemIndex]['paid'] ?? 0);
      $item['item_total'] = $item['financial_item.financial_account_id.is_tax'] ? $item['tax_amount'] : $item['line_total'];
      $item['balance'] = $item['item_total'] - $item['paid'];
      if (!empty($lineItemOverrides)) {
        $item['allocation'] = $lineItemOverrides[$lineItemID] ?? NULL;
      }
      else {
        if (empty($item['balance']) && !empty($ratio) && $params['total_amount'] < 0) {
          $item['allocation'] = $item['item_total'] * $ratio;
        }
        else {
          $item['allocation'] = $item['balance'] * $ratio;
        }
      }
      $payableItems[$payableItemIndex] = $item;
    }

    return $payableItems;
  }

  /**
   * Reverse the entity financial transactions associated with the cancelled payment.
   *
   * The reversals are linked to the new payment.
   *
   * @param array $params
   * @param int $trxnID
   *
   * @throws \CRM_Core_Exception
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
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected static function createFinancialItem(array $lineItem, string $trxn_date, int $contactID, string $currency): array {
    $financialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $lineItem['financial_type_id'],
      'Income Account Is'
    );

    FinancialItem::create(FALSE)
      ->setValues([
        'transaction_date' => $trxn_date,
        'contact_id' => $contactID,
        'currency' => $currency,
        'amount' => $lineItem['line_total'],
        'description' => $lineItem['label'],
        'status_id:name' => 'Unpaid',
        'financial_account_id' => $financialAccount,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItem['id'],
      ])
      ->execute();

    return LineItem::get(FALSE)
      ->addSelect('*', 'financial_item.status_id:name', 'financial_item.id', 'financial_item.financial_account_id', 'financial_item_id.currency', 'financial_item.financial_account_id.is_tax', 'financial_item.entity_id', 'financial_item.amount', 'allocated.amount')
      ->addJoin(
        'FinancialItem AS financial_item',
        'LEFT',
        ['financial_item.entity_table', '=', '"civicrm_line_item"'],
        ['financial_item.entity_id', '=', 'id']
      )
      ->addJoin('EntityFinancialTrxn AS allocated',
        'LEFT',
        ['allocated.entity_id', '=', 'financial_item.id'],
        ['allocated.entity_table', '=', '"civicrm_financial_item"'],
        ['allocated.financial_trxn_id.is_payment', '=', TRUE]
      )
      ->addOrderBy('financial_item.id', 'DESC')
      ->addWhere('id', '=', (int) $lineItem['id'])
      ->execute()->first();
  }

}
