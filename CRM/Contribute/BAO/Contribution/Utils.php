<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contribute_BAO_Contribution_Utils {

  /**
   * Function to process payment after confirmation
   *
   * @param object $form form object
   * @param array $paymentParams array with payment related key
   * value pairs
   * @param array $premiumParams array with premium related key
   * value pairs
   * @param int $contactID contact id
   * @param int $contributionTypeId financial type id
   * @param int|string $component component id
   *
   * @param null $fieldTypes
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function processConfirm(&$form,
    &$paymentParams,
    &$premiumParams,
    $contactID,
    $contributionTypeId,
    $component = 'contribution',
    $fieldTypes = NULL
  ) {
    CRM_Core_Payment_Form::mapParams($form->_bltID, $form->_params, $paymentParams, TRUE);
    $isTest = ($form->_mode == 'test') ? 1 : 0;
    $lineItems = $form->_lineItem;

    //do overrides that should have been done in the calling function -
    // @todo the calling function should pass the correct
    // variable for $contributionTypeId as it's impossible to debug what is happening in each flow here
    // but we need to ensure it is doing so before we remove this cruft

    if (isset($paymentParams['financial_type'])) {
      $contributionTypeId = $paymentParams['financial_type'];
    }
    elseif (!empty($form->_values['pledge_id'])) {
      $contributionTypeId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge',
        $form->_values['pledge_id'],
        'financial_type_id'
      );
    }

    $contributionType = new CRM_Financial_DAO_FinancialType();
    $contributionType->id = $contributionTypeId;
    if (!$contributionType->find(TRUE)) {
      //@todo - surely this check was part of the 4.3 upgrade & can go now?
      CRM_Core_Error::fatal('Could not find a system table');
    }

    // add some financial type details to the params list
    // if folks need to use it
    //CRM-15297 - contributionType is obsolete - pass financial type as well so people can deprecate it
    $paymentParams['financialType_name'] = $paymentParams['contributionType_name'] = $form->_params['contributionType_name'] = $contributionType->name;
    //CRM-11456
    $paymentParams['financialType_accounting_code'] =  $paymentParams['contributionType_accounting_code'] = $form->_params['contributionType_accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($contributionTypeId);
    $paymentParams['contributionPageID'] = $form->_params['contributionPageID'] = $form->_values['id'];


    $payment = NULL;
    $paymentObjError = ts('The system did not record payment details for this payment and so could not process the transaction. Please report this error to the site administrator.');
    if ($form->_values['is_monetary'] && $form->_amount > 0.0 && is_array($form->_paymentProcessor)) {
      $payment = CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);
    }

    //fix for CRM-2062
    $now = date('YmdHis');

    $result = NULL;
    if ($form->_contributeMode == 'notify' ||
      $form->_params['is_pay_later']
    ) {
      // this is not going to come back, i.e. we fill in the other details
      // when we get a callback from the payment processor
      // also add the contact ID and contribution ID to the params list
      $paymentParams['contactID'] = $form->_params['contactID'] = $contactID;
      $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution(
        $form,
        $paymentParams,
        NULL,
        $contactID,
        $contributionType,
        TRUE, TRUE,
        $isTest,
        $lineItems
      );

      if ($contribution) {
      $form->_params['contributionID'] = $contribution->id;
      }

      $form->_params['contributionTypeID'] = $contributionTypeId;
      $form->_params['item_name'] = $form->_params['description'];
      $form->_params['receive_date'] = $now;

      if ($contribution && $form->_values['is_recur'] &&
        $contribution->contribution_recur_id
      ) {
        $form->_params['contributionRecurID'] = $contribution->contribution_recur_id;
      }

      $form->set('params', $form->_params);
      $form->postProcessPremium($premiumParams, $contribution);

      if ($form->_values['is_monetary'] && $form->_amount >= 0.0) {
        // add qfKey so we can send to paypal
        $form->_params['qfKey'] = $form->controller->_key;
        if ($component == 'membership') {
          $membershipResult = array(1 => $contribution);
          return $membershipResult;
        }
        else {
          if (!$form->_params['is_pay_later']) {
            if (is_object($payment)) {
              // call postProcess hook before leaving
              $form->postProcessHook();
              // this does not return
              $result = &$payment->doTransferCheckout($form->_params, 'contribute');
            }
            else{
              CRM_Core_Error::fatal($paymentObjError);
            }
          }
          else {
            // follow similar flow as IPN
            // send the receipt mail
            $form->set('params', $form->_params);
            if ($contributionType->is_deductible) {
              $form->assign('is_deductible', TRUE);
              $form->set('is_deductible', TRUE);
            }
            if (isset($paymentParams['contribution_source'])) {
              $form->_params['source'] = $paymentParams['contribution_source'];
            }

            // get the price set values for receipt.
            if ($form->_priceSetId && $form->_lineItem) {
              $form->_values['lineItem'] = $form->_lineItem;
              $form->_values['priceSetID'] = $form->_priceSetId;
            }

            $form->_values['contribution_id'] = $contribution->id;
            $form->_values['contribution_page_id'] = $contribution->contribution_page_id;

            CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
              $form->_values,
              $contribution->is_test
            );
            return;
          }
        }
      }
    }
    elseif ($form->_contributeMode == 'express') {
      if ($form->_values['is_monetary'] && $form->_amount > 0.0) {
        // determine if express + recurring and direct accordingly
        if ($paymentParams['is_recur'] == 1) {
          if (is_object($payment)) {
            $result = $payment->createRecurringPayments($paymentParams);
          }
          else {
            CRM_Core_Error::fatal($paymentObjError);
          }
        }
        else {
          if (is_object($payment)) {
            $result = $payment->doExpressCheckout($paymentParams);
          }
          else {
            CRM_Core_Error::fatal($paymentObjError);
          }
        }
      }
    }
    elseif ($form->_values['is_monetary'] && $form->_amount > 0.0) {
      if (!empty($paymentParams['is_recur']) &&
        $form->_contributeMode == 'direct'
      ) {

        // For recurring contribution, create Contribution Record first.
        // Contribution ID, Recurring ID and Contact ID needed
        // When we get a callback from the payment processor

        $paymentParams['contactID'] = $contactID;

        // Fix for CRM-14354. If the membership is recurring, don't create a
        // civicrm_contribution_recur record for the additional contribution
        // (i.e., the amount NOT associated with the membership). Temporarily
        // cache the is_recur values so we can process the additional gift as a
        // one-off payment.
        if ($form->_membershipBlock['is_separate_payment']) {
          $cachedFormValue = CRM_Utils_Array::value('is_recur', $form->_values);
          unset($form->_values['is_recur']);
          $cachedParamValue = CRM_Utils_Array::value('is_recur', $paymentParams);
          unset($paymentParams['is_recur']);
        }

        $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution(
          $form,
          $paymentParams,
          NULL,
          $contactID,
          $contributionType,
          TRUE, TRUE,
          $isTest,
          $lineItems
        );

        // restore cached values (part of fix for CRM-14354)
        if ($form->_membershipBlock['is_separate_payment']) {
          $form->_values['is_recur'] = $cachedFormValue;
          $paymentParams['is_recur'] = $cachedParamValue;
        }

        $paymentParams['contributionID'] = $contribution->id;
        //CRM-15297 deprecate contributionTypeID
        $paymentParams['financialTypeID'] = $paymentParams['contributionTypeID'] = $contribution->financial_type_id;
        $paymentParams['contributionPageID'] = $contribution->contribution_page_id;

        if ($form->_values['is_recur'] && $contribution->contribution_recur_id) {
          $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
        }
      }
      if (is_object($payment)) {
        $result = $payment->doDirectPayment($paymentParams);
      }
      else {
        CRM_Core_Error::fatal($paymentObjError);
      }
    }

    if ($component == 'membership') {
      $membershipResult = array();
    }

    if (is_a($result, 'CRM_Core_Error')) {
      //make sure to cleanup db for recurring case.
      //@todo this clean up has always been controversial as many orgs prefer to see failed transactions.
      // most recent discussion has been that they should be retained and this could be altered
      if (!empty($paymentParams['contributionID'])) {
        CRM_Contribute_BAO_Contribution::deleteContribution($paymentParams['contributionID']);
      }
      if (!empty($paymentParams['contributionRecurID'])) {
        CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
      }

      if ($component !== 'membership') {
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact',
            "_qf_Main_display=true&qfKey={$form->_params['qfKey']}"
          ));
      }
      $membershipResult[1] = $result;
    }
    elseif ($result || ($form->_amount == 0.0 && !$form->_params['is_pay_later'])) {
      if ($result) {
        $form->_params = array_merge($form->_params, $result);
      }
      $form->_params['receive_date'] = $now;
      $form->set('params', $form->_params);
      $form->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $result));
      $form->assign('receive_date',
        CRM_Utils_Date::mysqlToIso($form->_params['receive_date'])
      );

      // result has all the stuff we need
      // lets archive it to a financial transaction
      //@todo - this is done in 2 places - can't we just do it once straight after retrieving contribution type -
      // when would this be a bad thing?
      if ($contributionType->is_deductible) {
        $form->assign('is_deductible', TRUE);
        $form->set('is_deductible', TRUE);
      }

      if (isset($paymentParams['contribution_source'])) {
        $form->_params['source'] = $paymentParams['contribution_source'];
      }

      // check if pending was set to true by payment processor
      $pending = FALSE;
      if (!empty($form->_params['contribution_status_pending'])) {
        $pending = TRUE;
      }
      if (!(!empty($paymentParams['is_recur']) && $form->_contributeMode == 'direct')) {
        $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($form,
          $form->_params, $result,
          $contactID, $contributionType,
          $pending, TRUE,
          $isTest,
          $lineItems
        );
      }
      $form->postProcessPremium($premiumParams, $contribution);
      if (is_array($result) && !empty($result['trxn_id'])) {
        $contribution->trxn_id = $result['trxn_id'];
      }
      $membershipResult[1] = $contribution;
    }

    if ($component == 'membership') {
      return $membershipResult;
    }

    //Do not send an email if Recurring contribution is done via Direct Mode
    //We will send email once the IPN is received.
    if (!empty($paymentParams['is_recur']) && $form->_contributeMode == 'direct') {
      return TRUE;
    }

    // get the price set values for receipt.
    if ($form->_priceSetId && $form->_lineItem) {
      $form->_values['lineItem'] = $form->_lineItem;
      $form->_values['priceSetID'] = $form->_priceSetId;
    }

    // finally send an email receipt
    if ($contribution) {
    $form->_values['contribution_id'] = $contribution->id;
      CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
        $form->_values, $contribution->is_test,
      FALSE, $fieldTypes
    );
  }
  }

  /**
   * Function to get the contribution details by month
   * of the year
   *
   * @param int     $param year
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function contributionChartMonthly($param) {
    if ($param) {
      $param = array(1 => array($param, 'Integer'));
    }
    else {
      $param = date("Y");
      $param = array(1 => array($param, 'Integer'));
    }

    $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             MONTH( contrib.receive_date) AS contribMonth
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact AS contact ON ( contact.id = contrib.contact_id )
     WHERE   contrib.contact_id = contact.id
       AND   ( contrib.is_test = 0 OR contrib.is_test IS NULL )
       AND   contrib.contribution_status_id = 1
       AND   date_format(contrib.receive_date,'%Y') = %1
       AND   contact.is_deleted = 0
  GROUP BY   contribMonth
  ORDER BY   month(contrib.receive_date)";

    $dao = CRM_Core_DAO::executeQuery($query, $param);

    $params = NULL;
    while ($dao->fetch()) {
      if ($dao->contribMonth) {
        $params['By Month'][$dao->contribMonth] = $dao->ctAmt;
      }
    }
    return $params;
  }

  /**
   * Function to get the contribution details by year
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function contributionChartYearly() {
    $config = CRM_Core_Config::singleton();
    $yearClause = "year(contrib.receive_date) as contribYear";
    if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] != 1 || $config->fiscalYearStart['d'] != 1)) {
      $yearClause = "CASE
        WHEN (MONTH(contrib.receive_date)>= " . $config->fiscalYearStart['M'] . "
          && DAYOFMONTH(contrib.receive_date)>= " . $config->fiscalYearStart['d'] . " )
          THEN
            concat(YEAR(contrib.receive_date), '-',YEAR(contrib.receive_date)+1)
          ELSE
            concat(YEAR(contrib.receive_date)-1,'-', YEAR(contrib.receive_date))
        END AS contribYear";
    }

    $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             {$yearClause}
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact contact ON ( contact.id = contrib.contact_id )
     WHERE   ( contrib.is_test = 0 OR contrib.is_test IS NULL )
       AND   contrib.contribution_status_id = 1
       AND   contact.is_deleted = 0
  GROUP BY   contribYear
  ORDER BY   contribYear";
    $dao = CRM_Core_DAO::executeQuery($query);

    $params = NULL;
    while ($dao->fetch()) {
      if (!empty($dao->contribYear)) {
        $params['By Year'][$dao->contribYear] = $dao->ctAmt;
      }
    }
    return $params;
  }

  /**
   * @param $params
   * @param $contactID
   * @param $mail
   */
  static function createCMSUser(&$params, $contactID, $mail) {
    // lets ensure we only create one CMS user
    static $created = FALSE;

    if ($created) {
      return;
    }
    $created = TRUE;

    if (!empty($params['cms_create_account'])) {
      $params['contactID'] = $contactID;
      if (!CRM_Core_BAO_CMSUser::create($params, $mail)) {
        CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
  }

  /**
   * @param $params
   * @param string $type
   *
   * @return bool
   */
  static function _fillCommonParams(&$params, $type = 'paypal') {
    if (array_key_exists('transaction', $params)) {
      $transaction = &$params['transaction'];
    }
    else {
      $transaction = &$params;
    }

    $params['contact_type'] = 'Individual';

    $billingLocTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', 'Billing', 'id', 'name');
    if (!$billingLocTypeId) {
      $billingLocTypeId = 1;
    }
    if (!CRM_Utils_System::isNull($params['address'])) {
      $params['address'][1]['is_primary'] = 1;
      $params['address'][1]['location_type_id'] = $billingLocTypeId;
    }
    if (!CRM_Utils_System::isNull($params['email'])) {
      $params['email'] = array(
        1 => array('email' => $params['email'],
          'location_type_id' => $billingLocTypeId,
        ));
    }

    if (isset($transaction['trxn_id'])) {
      // set error message if transaction has already been processed.
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->trxn_id = $transaction['trxn_id'];
      if ($contribution->find(TRUE)) {
        $params['error'][] = ts('transaction already processed.');
      }
    }
    else {
      // generate a new transaction id, if not already exist
      $transaction['trxn_id'] = md5(uniqid(rand(), TRUE));
    }

    if (!isset( $transaction['financial_type_id'])) {
      $contributionTypes = array_keys(CRM_Contribute_PseudoConstant::financialType());
      $transaction['financial_type_id'] = $contributionTypes[0];
    }

    if (($type == 'paypal') && (!isset($transaction['net_amount']))) {
      $transaction['net_amount'] = $transaction['total_amount'] - CRM_Utils_Array::value('fee_amount', $transaction, 0);
    }

    if (!isset($transaction['invoice_id'])) {
      $transaction['invoice_id'] = $transaction['trxn_id'];
    }

    $source = ts('ContributionProcessor: %1 API',
      array(1 => ucfirst($type))
    );
    if (isset($transaction['source'])) {
      $transaction['source'] = $source . ':: ' . $transaction['source'];
    }
    else {
      $transaction['source'] = $source;
    }

    return TRUE;
  }

  /**
   * @param $apiParams
   * @param $mapper
   * @param string $type
   * @param bool $category
   *
   * @return array
   */
  static function formatAPIParams($apiParams, $mapper, $type = 'paypal', $category = TRUE) {
    $type = strtolower($type);

    if (!in_array($type, array(
      'paypal', 'google', 'csv'))) {
      // return the params as is
      return $apiParams;
    }
    $params = $transaction = array();

    if ($type == 'paypal') {
      foreach ($apiParams as $detail => $val) {
        if (isset($mapper['contact'][$detail])) {
          $params[$mapper['contact'][$detail]] = $val;
        }
        elseif (isset($mapper['location'][$detail])) {
          $params['address'][1][$mapper['location'][$detail]] = $val;
        }
        elseif (isset($mapper['transaction'][$detail])) {
          switch ($detail) {
            case 'l_period2':
              // Sadly, PayPal seems to send two distinct data elements in a single field,
              // so we break them out here.  This is somewhat ugly and tragic.
              $freqUnits = array(
                'D' => 'day',
                'W' => 'week',
                'M' => 'month',
                'Y' => 'year',
              );
              list($frequency_interval, $frequency_unit) = explode(' ', $val);
              $transaction['frequency_interval'] = $frequency_interval;
              $transaction['frequency_unit'] = $freqUnits[$frequency_unit];
              break;

            case 'subscriptiondate':
            case 'timestamp':
              // PayPal dates are in  ISO-8601 format.  We need a format that
              // MySQL likes
              $unix_timestamp = strtotime($val);
              $transaction[$mapper['transaction'][$detail]] = date('YmdHis', $unix_timestamp);
              break;

            case 'note':
            case 'custom':
            case 'l_number0':
              if ($val) {
                $val = "[PayPal_field:{$detail}] {$val}";
                $transaction[$mapper['transaction'][$detail]] = !empty($transaction[$mapper['transaction'][$detail]]) ? $transaction[$mapper['transaction'][$detail]] . " <br/> " . $val : $val;
              }
              break;

            default:
              $transaction[$mapper['transaction'][$detail]] = $val;
          }
        }
      }

      if (!empty($transaction) && $category) {
        $params['transaction'] = $transaction;
      }
      else {
        $params += $transaction;
      }

      self::_fillCommonParams($params, $type);

      return $params;
    }

    if ($type == 'csv') {
      $header = $apiParams['header'];
      unset($apiParams['header']);
      foreach ($apiParams as $key => $val) {
        if (isset($mapper['contact'][$header[$key]])) {
          $params[$mapper['contact'][$header[$key]]] = $val;
        }
        elseif (isset($mapper['location'][$header[$key]])) {
          $params['address'][1][$mapper['location'][$header[$key]]] = $val;
        }
        elseif (isset($mapper['transaction'][$header[$key]])) {
          $transaction[$mapper['transaction'][$header[$key]]] = $val;
        }
        else {
          $params[$header[$key]] = $val;
        }
      }

      if (!empty($transaction) && $category) {
        $params['transaction'] = $transaction;
      }
      else {
        $params += $transaction;
      }

      self::_fillCommonParams($params, $type);

      return $params;
    }

    if ($type == 'google') {
      // return if response smell invalid
      if (!array_key_exists('risk-information-notification', $apiParams[1][$apiParams[0]]['notifications'])) {
        return FALSE;
      }
      $riskInfo = &$apiParams[1][$apiParams[0]]['notifications']['risk-information-notification'];

      if (array_key_exists('new-order-notification', $apiParams[1][$apiParams[0]]['notifications'])) {
        $newOrder = &$apiParams[1][$apiParams[0]]['notifications']['new-order-notification'];
      }

      if ($riskInfo['google-order-number']['VALUE'] == $apiParams[2]['google-order-number']['VALUE']) {
        foreach ($riskInfo['risk-information']['billing-address'] as $field => $info) {
          if (!empty($mapper['location'][$field])) {
            $params['address'][1][$mapper['location'][$field]] = $info['VALUE'];
          }
          elseif (!empty($mapper['contact'][$field])) {
            if ($newOrder && !empty($newOrder['buyer-billing-address']['structured-name'])) {
              foreach ($newOrder['buyer-billing-address']['structured-name'] as $namePart => $nameValue) {
                $params[$mapper['contact'][$namePart]] = $nameValue['VALUE'];
              }
            }
            else {
              $params[$mapper['contact'][$field]] = $info['VALUE'];
            }
          }
          elseif (!empty($mapper['transaction'][$field])) {
            $transaction[$mapper['transaction'][$field]] = $info['VALUE'];
          }
        }

        // Response is an huge array. Lets pickup only those which we ineterested in
        // using a local mapper, rather than traversing the entire array.
        $localMapper = array(
          'google-order-number' => $riskInfo['google-order-number']['VALUE'],
          'total-charge-amount' => $apiParams[2]['total-charge-amount']['VALUE'],
          'currency' => $apiParams[2]['total-charge-amount']['currency'],
          'item-name' => $newOrder['shopping-cart']['items']['item']['item-name']['VALUE'],
          'timestamp' => $apiParams[2]['timestamp']['VALUE'],
        );
        if (array_key_exists('latest-charge-fee', $apiParams[2])) {
          $localMapper['latest-charge-fee'] = $apiParams[2]['latest-charge-fee']['total']['VALUE'];
          $localMapper['net-amount'] = $localMapper['total-charge-amount'] - $localMapper['latest-charge-fee'];
        }

        // This is a subscription (recurring) donation.
        if (array_key_exists('subscription', $newOrder['shopping-cart']['items']['item'])) {
          $subscription = $newOrder['shopping-cart']['items']['item']['subscription'];
          $localMapper['amount'] = $newOrder['order-total']['VALUE'];
          $localMapper['times'] = $subscription['payments']['subscription-payment']['times'];
          // Convert Google's period to one compatible with the CiviCRM db field.
          $freqUnits = array(
            'DAILY' => 'day',
            'WEEKLY' => 'week',
            'MONHTLY' => 'month',
            'YEARLY' => 'year',
          );
          $localMapper['period'] = $freqUnits[$subscription['period']];
          // Unlike PayPal, Google has no concept of freq. interval, it is always 1.
          $localMapper['frequency_interval'] = '1';
          // Google Checkout dates are in ISO-8601 format. We need a format that
          // MySQL likes
          $unix_timestamp = strtotime($localMapper['timestamp']);
          $mysql_date = date('YmdHis', $unix_timestamp);
          $localMapper['modified_date'] = $mysql_date;
          $localMapper['start_date'] = $mysql_date;
          // This is PayPal's nomenclature, but just use it for Google as well since
          // we act on the value of trxn_type in processAPIContribution().
          $localMapper['trxn_type'] = 'subscrpayment';
        }

        foreach ($localMapper as $localKey => $localVal) {
          if (!empty($mapper['transaction'][$localKey])) {
            $transaction[$mapper['transaction'][$localKey]] = $localVal;
          }
        }

        if (empty($params) && empty($transaction)) {
          continue;
        }

        if (!empty($transaction) && $category) {
          $params['transaction'] = $transaction;
        }
        else {
          $params += $transaction;
        }

        self::_fillCommonParams($params, $type);
      }
      return $params;
    }
  }

  /**
   * @param $params
   *
   * @return bool
   */
  static function processAPIContribution($params) {
    if (empty($params) || array_key_exists('error', $params)) {
      return FALSE;
    }

    // add contact using dedupe rule
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
    $dedupeParams['check_permission'] = FALSE;
    $dupeIds = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
    // if we find more than one contact, use the first one
    if (!empty($dupeIds[0])) {
      $params['contact_id'] = $dupeIds[0];
    }
    $contact = CRM_Contact_BAO_Contact::create($params);
    if (!$contact->id) {
      return FALSE;
    }

    // only pass transaction params to contribution::create, if available
    if (array_key_exists('transaction', $params)) {
      $params = $params['transaction'];
      $params['contact_id'] = $contact->id;
    }

    // handle contribution custom data
    $customFields = CRM_Core_BAO_CustomField::getFields('Contribution',
      FALSE,
      FALSE,
      CRM_Utils_Array::value('financial_type_id',
        $params
      )
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      CRM_Utils_Array::value('id', $params, NULL),
      'Contribution'
    );
    // create contribution

    // if this is a recurring contribution then process it first
    if ($params['trxn_type'] == 'subscrpayment') {
      // see if a recurring record already exists
      $recurring = new CRM_Contribute_BAO_ContributionRecur;
      $recurring->processor_id = $params['processor_id'];
      if (!$recurring->find(TRUE)) {
        $recurring = new CRM_Contribute_BAO_ContributionRecur;
        $recurring->invoice_id = $params['invoice_id'];
        $recurring->find(TRUE);
      }

      // This is the same thing the CiviCRM IPN handler does to handle
      // subsequent recurring payments to avoid duplicate contribution
      // errors due to invoice ID. See:
      // ./CRM/Core/Payment/PayPalIPN.php:200
      if ($recurring->id) {
        $params['invoice_id'] = md5(uniqid(rand(), TRUE));
      }

      $recurring->copyValues($params);
      $recurring->save();
      if (is_a($recurring, 'CRM_Core_Error')) {
        return FALSE;
      }
      else {
        $params['contribution_recur_id'] = $recurring->id;
      }
    }

    $contribution = &CRM_Contribute_BAO_Contribution::create($params,
      CRM_Core_DAO::$_nullArray
    );
    if (!$contribution->id) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $contactID
   *
   * @return mixed
   */
  static function getFirstLastDetails($contactID) {
    static $_cache;

    if (!$_cache) {
      $_cache = array();
    }

    if (!isset($_cache[$contactID])) {
      $sql = "
SELECT   total_amount, receive_date
FROM     civicrm_contribution c
WHERE    contact_id = %1
ORDER BY receive_date ASC
LIMIT 1
";
      $params = array(1 => array($contactID, 'Integer'));

      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $details = array(
        'first' => NULL,
        'last' => NULL,
      );
      if ($dao->fetch()) {
        $details['first'] = array(
          'total_amount' => $dao->total_amount,
          'receive_date' => $dao->receive_date,
        );
      }

      // flip asc and desc to get the last query
      $sql = str_replace('ASC', 'DESC', $sql);
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $details['last'] = array(
          'total_amount' => $dao->total_amount,
          'receive_date' => $dao->receive_date,
        );
      }

      $_cache[$contactID] = $details;
    }
    return $_cache[$contactID];
  }
}

