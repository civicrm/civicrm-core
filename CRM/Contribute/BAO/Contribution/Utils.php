<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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
   * @param int $contactID
   *   Contact id.
   * @param int $contributionTypeId
   *   Financial type id.
   * @param int|string $component component id
   * @param bool $isTest
   * @param bool $isRecur
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
    $contactID,
    $contributionTypeId,
    $component = 'contribution',
    $isTest,
    $isRecur
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
    $paymentParams['contactID'] = $form->_params['contactID'] = $contactID;

    //fix for CRM-16317
    $form->_params['receive_date'] = date('YmdHis');
    $form->assign('receive_date',
      CRM_Utils_Date::mysqlToIso($form->_params['receive_date'])
    );

    if (empty($form->_values['amount'])) {
      // If the amount is not in _values[], set it
      $form->_values['amount'] = $form->_params['amount'];
    }

    if ($isPaymentTransaction) {
      $contributionParams = array(
        'id' => CRM_Utils_Array::value('contribution_id', $paymentParams),
        'contact_id' => $contactID,
        'line_item' => $lineItems,
        'is_test' => $isTest,
        'campaign_id' => CRM_Utils_Array::value('campaign_id', $paymentParams, CRM_Utils_Array::value('campaign_id', $form->_values)),
        'contribution_page_id' => $form->_id,
        'source' => CRM_Utils_Array::value('source', $paymentParams, CRM_Utils_Array::value('description', $paymentParams)),
      );
      $isMonetary = !empty($form->_values['is_monetary']);
      if ($isMonetary) {
        if (empty($paymentParams['is_pay_later'])) {
          // @todo look up payment_instrument_id on payment processor table.
          $contributionParams['payment_instrument_id'] = 1;
        }
      }
      $contribution = CRM_Contribute_Form_Contribution_Confirm::processFormContribution(
        $form,
        $paymentParams,
        NULL,
        $contributionParams,
        $financialType,
        TRUE,
        $form->_bltID,
        $isRecur
      );

      $paymentParams['contributionTypeID'] = $contributionTypeId;
      $paymentParams['item_name'] = $form->_params['description'];

      $paymentParams['qfKey'] = $form->controller->_key;
      if ($component == 'membership') {
        return array('contribution' => $contribution);
      }

      $paymentParams['contributionID'] = $contribution->id;
      //CRM-15297 deprecate contributionTypeID
      $paymentParams['financialTypeID'] = $paymentParams['contributionTypeID'] = $contribution->financial_type_id;
      $paymentParams['contributionPageID'] = $contribution->contribution_page_id;
      if (isset($paymentParams['contribution_source'])) {
        $paymentParams['source'] = $paymentParams['contribution_source'];
      }

      if ($form->_values['is_recur'] && $contribution->contribution_recur_id) {
        $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
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

      if (!empty($form->_paymentProcessor)) {
        try {
          $payment = Civi\Payment\System::singleton()->getByProcessor($form->_paymentProcessor);
          if ($form->_contributeMode == 'notify') {
            // We want to get rid of this & make it generic - eg. by making payment processing the last thing
            // and always calling it first.
            $form->postProcessHook();
          }
          $result = $payment->doPayment($paymentParams);
          $form->_params = array_merge($form->_params, $result);
          $form->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $result));
          if (!empty($result['trxn_id'])) {
            $contribution->trxn_id = $result['trxn_id'];
          }
          if (!empty($result['payment_status_id'])) {
            $contribution->payment_status_id = $result['payment_status_id'];
          }
          $result['contribution'] = $contribution;
          if ($result['payment_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution',
            'status_id', 'Pending') && $payment->isSendReceiptForPending()) {
            CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
              $form->_values,
              $contribution->is_test
            );
          }
          return $result;
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
          return $result;
        }
      }
    }

    // Only pay later or unpaid should reach this point, although pay later likely does not & is handled via the
    // manual processor, so it's unclear what this set is for and whether the following send ever fires.
    $form->set('params', $form->_params);

    if ($form->_params['amount'] == 0) {
      // This is kind of a back-up for pay-later $0 transactions.
      // In other flows they pick up the manual processor & get dealt with above (I
      // think that might be better...).
      return array(
        'payment_status_id' => 1,
        'contribution' => $contribution,
        'payment_processor_id' => 0,
      );
    }

    CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
      $form->_values,
      $contribution->is_test
    );
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
    return ($form->_amount >= 0.0) ? TRUE : FALSE;
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
      $params['contactID'] = !empty($params['onbehalf_contact_id']) ? $params['onbehalf_contact_id'] : $contactID;
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
    $taxAmount['tax_amount'] = round(($taxRate / 100) * CRM_Utils_Rule::cleanMoney($amount), 2);

    return $taxAmount;
  }

}
