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
   * Function to send email receipt.
   *
   * @param array $params
   *
   * @return bool
   */
  public static function sendConfirmation($params) {
    if (empty($params['is_email_receipt'])) {
      return;
    }
    $templateVars = array();
    self::assignVariablesToTemplate($templateVars, $params);
    // send message template
    $fromEmails = CRM_Core_BAO_Email::getFromEmail();
    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_contribution',
      'valueName' => 'payment_or_refund_notification',
      'contactId' => CRM_Utils_Array::value('contactId', $templateVars),
      'PDFFilename' => ts('notification') . '.pdf',
    );
    $doNotEmail = NULL;
    if (!empty($templateVars['contactId'])) {
      $doNotEmail = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $templateVars['contactId'], 'do_not_email');
    }
    // try to send emails only if email id is present
    // and the do-not-email option is not checked for that contact
    if (!empty($templateVars['contributorEmail']) && empty($doNotEmail)) {
      list($userName, $receiptFrom) = CRM_Core_BAO_Domain::getDefaultReceiptFrom();
      if (!empty($params['from_email_address']) && array_key_exists($params['from_email_address'], $fromEmails)) {
        $receiptFrom = $params['from_email_address'];
      }
      $sendTemplateParams['from'] = $receiptFrom;
      $sendTemplateParams['toName'] = CRM_Utils_Array::value('contributorDisplayName', $templateVars);
      $sendTemplateParams['toEmail'] = $templateVars['contributorEmail'];
    }
    list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    return $mailSent;
  }

  /**
   * Assign template variables.
   *
   * @param array $templateVars
   * @param array $params
   *
   * @return array
   */
  public static function assignVariablesToTemplate(&$templateVars, $params) {
    $templateVars = self::getTemplateVars($params);
    $template = CRM_Core_Smarty::singleton();
    if (CRM_Utils_Array::value('component', $templateVars) == 'event') {
      // fetch event information from participant ID using API
      $eventId = civicrm_api3('Participant', 'getvalue', array(
        'return' => "event_id",
        'id' => $templateVars['id'],
      ));
      $event = civicrm_api3('Event', 'getsingle', array('id' => $eventId));
      $template->assign('event', $event);
      $template->assign('isShowLocation', $event['is_show_location']);
      if (CRM_Utils_Array::value('is_show_location', $event) == 1) {
        $locationParams = array(
          'entity_id' => $eventId,
          'entity_table' => 'civicrm_event',
        );
        $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
        $template->assign('location', $location);
      }
      // assign payment info here
      $paymentConfig['confirm_email_text'] = CRM_Utils_Array::value('confirm_email_text', $event);
      $template->assign('paymentConfig', $paymentConfig);
    }
    $template->assign('component', CRM_Utils_Array::value('component', $templateVars));
    $template->assign('totalAmount', CRM_Utils_Array::value('amtTotal', $templateVars));
    $isRefund = ($templateVars['paymentType'] == 'refund') ? TRUE : FALSE;
    $template->assign('isRefund', $isRefund);
    if ($isRefund) {
      $template->assign('totalPaid', CRM_Utils_Array::value('amtPaid', $templateVars));
      $template->assign('refundAmount', $params['total_amount']);
    }
    else {
      $balance = CRM_Contribute_BAO_Contribution::getContributionBalance($params['contribution_id']);
      $paymentsComplete = ($balance == 0) ? 1 : 0;
      $template->assign('amountOwed', $balance);
      $template->assign('paymentAmount', $params['total_amount']);
      $template->assign('paymentsComplete', $paymentsComplete);
    }
    $template->assign('contactDisplayName', CRM_Utils_Array::value('contributorDisplayName', $templateVars));
    // assign trxn details
    $template->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $params));
    $template->assign('receive_date', CRM_Utils_Array::value('trxn_date', $params));
    if (!empty($params['payment_instrument_id'])) {
      $template->assign('paidBy', CRM_Core_PseudoConstant::getLabel(
        'CRM_Contribute_BAO_Contribution',
        'payment_instrument_id',
        $params['payment_instrument_id']
      ));
    }
    $template->assign('checkNumber', CRM_Utils_Array::value('check_number', $params));
    if (!empty($params['mode'])) {
      $template->assign('contributeMode', 'direct');
      $template->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
        $params,
        CRM_Core_BAO_LocationType::getBilling()
      ));
    }
  }
  /**
   * Function to form template variables.
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
    ];
    // Need to do these before switching the form over...
    $todoParams = [
      'isRefund',
      'totalPaid',
      'refundAmount',
      'paymentsComplete',
      'contributeMode',
      'isAmountzero',
      'billingName',
      'address',
      'credit_card_type',
      'credit_card_number',
      'credit_card_exp_date',
      'eventEmail',
      '$event.participant_role',
    ];
    $filteredParams = [];
    foreach ($testedTemplateVariables as $templateVariable) {
      // This will cause an a-notice if any are NOT set - by design. Ensuring
      // they are set prevents leakage.
      $filteredParams[$templateVariable] = $params[$templateVariable];
    }
    return $filteredParams;
  }

}
