<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 */
class CRM_Contribute_BAO_Contribution_Utils {

  /**
   * Process payment after confirmation.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   * @param array $paymentParams
   *   Array with payment related key.
   *   value pairs
   * @param array $premiumParams
   *   Array with premium related key.
   *   value pairs
   * @param int $contactID
   *   Contact id.
   * @param int $contributionTypeId
   *   Financial type id.
   * @param int|string $component component id
   * @param array $fieldTypes
   *   Presumably relates to custom field types - used when building data for sendMail.
   * @param $isTest
   * @param $isPayLater
   *
   * @throws CRM_Core_Exception
   * @throws Exception
   * @return array
   *   associated array
   *
   */
  public static function processConfirm(
    &$form,
    &$paymentParams,
    &$premiumParams,
    $contactID,
    $contributionTypeId,
    $component = 'contribution',
    $fieldTypes = NULL,
    $isTest,
    $isPayLater
  ) {
    CRM_Core_Payment_Form::mapParams($form->_bltID, $form->_params, $paymentParams, TRUE);
    $lineItems = $form->_lineItem;
    $isPaymentTransaction = self::isPaymentTransaction($form);

    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $contributionTypeId;
    $financialType->find(TRUE);
    if ($financialType->is_deductible) {
      $form->assign('is_deductible', TRUE);
      $form->set('is_deductible', TRUE);
    }

    // add some financial type details to the params list
    // if folks need to use it
    //CRM-15297 - contributionType is obsolete - pass financial type as well so people can deprecate it
    $paymentParams['financialType_name'] = $paymentParams['contributionType_name'] = $form->_params['contributionType_name'] = $financialType->name;
    //CRM-11456
    $paymentParams['financialType_accounting_code'] = $paymentParams['contributionType_accounting_code'] = $form->_params['contributionType_accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($contributionTypeId);
    $paymentParams['contributionPageID'] = $form->_params['contributionPageID'] = $form->_values['id'];

    $payment = NULL;
    $paymentObjError = ts('The system did not record payment details for this payment and so could not process the transaction. Please report this error to the site administrator.');

    if ($isPaymentTransaction && !empty($form->_paymentProcessor)) {
      // @todo - remove this line once we are sure we can just use $form->_paymentProcessor['object'] consistently.
      $payment = Civi\Payment\System::singleton()->getByProcessor($form->_paymentProcessor);
    }

    //fix for CRM-16317
    $form->_params['receive_date'] = date('YmdHis');
    $form->assign('receive_date',
      CRM_Utils_Date::mysqlToIso($form->_params['receive_date'])
    );
    $result = NULL;
    if ($form->_contributeMode == 'notify' ||
      $isPayLater
    ) {
      // this is not going to come back, i.e. we fill in the other details
      // when we get a callback from the payment processor
      // also add the contact ID and contribution ID to the params list
      $paymentParams['contactID'] = $form->_params['contactID'] = $contactID;
      $contribution = CRM_Contribute_Form_Contribution_Confirm::processFormContribution(
        $form,
        $paymentParams,
        NULL,
        $contactID,
        $financialType,
        TRUE, TRUE,
        $isTest,
        $lineItems,
        $form->_bltID
      );

      if ($contribution) {
        $form->_params['contributionID'] = $contribution->id;
      }

      $form->_params['contributionTypeID'] = $contributionTypeId;
      $form->_params['item_name'] = $form->_params['description'];

      if ($contribution && $form->_values['is_recur'] &&
        $contribution->contribution_recur_id
      ) {
        $form->_params['contributionRecurID'] = $contribution->contribution_recur_id;
      }

      $form->set('params', $form->_params);
      $form->postProcessPremium($premiumParams, $contribution);

      if ($isPaymentTransaction) {
        // add qfKey so we can send to paypal
        $form->_params['qfKey'] = $form->controller->_key;
        if ($component == 'membership') {
          return array('contribution' => $contribution);
        }
        else {
          if (!$isPayLater) {
            if (is_object($payment)) {
              // call postProcess hook before leaving
              $form->postProcessHook();
              // this does not return
              $result = $payment->doTransferCheckout($form->_params, 'contribute');
            }
            else {
              CRM_Core_Error::fatal($paymentObjError);
            }
          }
          else {
            // follow similar flow as IPN
            // send the receipt mail
            $form->set('params', $form->_params);
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
        if (!empty($paymentParams['is_recur']) && $paymentParams['is_recur'] == 1) {
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
    elseif ($isPaymentTransaction) {
      if ($form->_contributeMode == 'direct') {
        $paymentParams['contactID'] = $contactID;

        // Fix for CRM-14354. If the membership is recurring, don't create a
        // civicrm_contribution_recur record for the additional contribution
        // (i.e., the amount NOT associated with the membership). Temporarily
        // cache the is_recur values so we can process the additional gift as a
        // one-off payment.
        if (!empty($form->_values['is_recur'])) {
          if ($form->_membershipBlock['is_separate_payment'] && !empty($form->_params['auto_renew'])) {
            $cachedFormValue = CRM_Utils_Array::value('is_recur', $form->_values);
            $cachedParamValue = CRM_Utils_Array::value('is_recur', $paymentParams);
            unset($form->_values['is_recur']);
            unset($paymentParams['is_recur']);
          }
        }

        $contribution = CRM_Contribute_Form_Contribution_Confirm::processFormContribution(
          $form,
          $paymentParams,
          NULL,
          $contactID,
          $financialType,
          TRUE,
          TRUE,
          $isTest,
          $lineItems,
          $form->_bltID
        );

        // restore cached values (part of fix for CRM-14354)
        if (!empty($cachedFormValue)) {
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
      try {
        $result = $payment->doPayment($paymentParams);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        // Clean up DB as appropriate.
        if (!empty($paymentParams['contributionID'])) {
          CRM_Contribute_BAO_Contribution::failPayment($paymentParams['contributionID'],
            $paymentParams['contactID'], $e->getMessage());
        }
        if (!empty($paymentParams['contributionRecurID'])) {
          CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
        }

        $result['is_payment_failure'] = TRUE;
        $result['error'] = $e;
      }
    }

    if ($result || ($form->_amount == 0.0 && !$form->_params['is_pay_later'])) {
      if ($result) {
        $form->_params = array_merge($form->_params, $result);
      }
      $form->set('params', $form->_params);
      $form->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $result));

      // result has all the stuff we need
      // lets archive it to a financial transaction

      if (isset($paymentParams['contribution_source'])) {
        $form->_params['source'] = $paymentParams['contribution_source'];
      }

      $form->postProcessPremium($premiumParams, $contribution);
      if (is_array($result) && !empty($result['trxn_id'])) {
        $contribution->trxn_id = $result['trxn_id'];
      }
      $result['contribution'] = $contribution;
    }
    //Do not send an email if Recurring contribution is done via Direct Mode
    //We will send email once the IPN is received.
    if ($form->_contributeMode == 'direct') {
      return $result;
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
   * Is a payment being made.
   * Note that setting is_monetary on the form is somewhat legacy and the behaviour around this setting is confusing. It would be preferable
   * to look for the amount only (assuming this cannot refer to payment in goats or other non-monetary currency
   * @param CRM_Core_Form $form
   *
   * @return bool
   */
  static protected function isPaymentTransaction($form) {
    if (!empty($form->_values['is_monetary']) && $form->_amount >= 0.0) {
      return TRUE;
    }
    return FALSE;

  }

  /**
   * Get the contribution details by month of the year.
   *
   * @param int $param
   *   Year.
   *
   * @return array
   *   associated array
   */
  public static function contributionChartMonthly($param) {
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
   * Get the contribution details by year.
   *
   * @return array
   *   associated array
   */
  public static function contributionChartYearly() {
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
   * @param array $params
   * @param int $contactID
   * @param $mail
   */
  public static function createCMSUser(&$params, $contactID, $mail) {
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
   * @param array $params
   * @param string $type
   *
   * @return bool
   */
  public static function _fillCommonParams(&$params, $type = 'paypal') {
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
        1 => array(
          'email' => $params['email'],
          'location_type_id' => $billingLocTypeId,
        ),
      );
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

    if (!isset($transaction['financial_type_id'])) {
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
   * @param int $contactID
   *
   * @return mixed
   */
  public static function getFirstLastDetails($contactID) {
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

  /**
   * Calculate the tax amount based on given tax rate.
   *
   * @param float $amount
   *   Amount of field.
   * @param float $taxRate
   *   Tax rate of selected financial account for field.
   *
   * @return array
   *   array of tax amount
   *
   */
  public static function calculateTaxAmount($amount, $taxRate) {
    $taxAmount = array();
    $taxAmount['tax_amount'] = ($taxRate / 100) * CRM_Utils_Rule::cleanMoney($amount);

    return $taxAmount;
  }

}
