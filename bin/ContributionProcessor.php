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
class CiviContributeProcessor {
  public static $_paypalParamsMapper = array(
    //category    => array(paypal_param    => civicrm_field);
    'contact' => array(
      'salutation' => 'prefix_id',
      'firstname' => 'first_name',
      'lastname' => 'last_name',
      'middlename' => 'middle_name',
      'suffix' => 'suffix_id',
      'email' => 'email',
    ),
    'location' => array(
      'shiptoname' => 'address_name',
      'shiptostreet' => 'street_address',
      'shiptostreet2' => 'supplemental_address_1',
      'shiptocity' => 'city',
      'shiptostate' => 'state_province',
      'shiptozip' => 'postal_code',
      'countrycode' => 'country',
    ),
    'transaction' => array(
      'amt' => 'total_amount',
      'feeamt' => 'fee_amount',
      'transactionid' => 'trxn_id',
      'currencycode' => 'currency',
      'l_name0' => 'source',
      'ordertime' => 'receive_date',
      'note' => 'note',
      'custom' => 'note',
      'l_number0' => 'note',
      'is_test' => 'is_test',
      'transactiontype' => 'trxn_type',
      'recurrences' => 'installments',
      'l_amt2' => 'amount',
      'l_period2' => 'lol',
      'invnum' => 'invoice_id',
      'subscriptiondate' => 'start_date',
      'subscriptionid' => 'processor_id',
      'timestamp' => 'modified_date',
    ),
  );

  /**
   * Note: if csv header is not present in the mapper, header itself
   * is considered as a civicrm field.
   * category    => array(csv_header      => civicrm_field);
   * @var array
   */
  public static $_csvParamsMapper = array(
    'contact' => array(
      'first_name' => 'first_name',
      'last_name' => 'last_name',
      'middle_name' => 'middle_name',
      'email' => 'email',
    ),
    'location' => array(
      'street_address' => 'street_address',
      'supplemental_address_1' => 'supplemental_address_1',
      'city' => 'city',
      'postal_code' => 'postal_code',
      'country' => 'country',
    ),
    'transaction' => array(
      'total_amount' => 'total_amount',
      'trxn_id' => 'trxn_id',
      'currency' => 'currency',
      'source' => 'source',
      'receive_date' => 'receive_date',
      'note' => 'note',
      'is_test' => 'is_test',
    ),
  );

  /**
   * @param $paymentProcessor
   * @param $paymentMode
   * @param $start
   * @param $end
   */
  public static function paypal($paymentProcessor, $paymentMode, $start, $end) {
    $url = "{$paymentProcessor['url_api']}nvp";

    $keyArgs = array(
      'user' => $paymentProcessor['user_name'],
      'pwd' => $paymentProcessor['password'],
      'signature' => $paymentProcessor['signature'],
      'version' => 3.0,
    );

    $args = $keyArgs;
    $args += array(
      'method' => 'TransactionSearch',
      'startdate' => $start,
      'enddate' => $end,
    );

    require_once 'CRM/Core/Payment/PayPalImpl.php';

    // as invokeAPI fetch only last 100 transactions.
    // we should require recursive calls to process more than 100.
    // first fetch transactions w/ give date intervals.
    // if we get error code w/ result, which means we do have more than 100
    // manipulate date interval accordingly and fetch again.

    do {
      $result = CRM_Core_Payment_PayPalImpl::invokeAPI($args, $url);
      require_once "CRM/Contribute/BAO/Contribution/Utils.php";

      $keyArgs['method'] = 'GetTransactionDetails';
      foreach ($result as $name => $value) {
        if (substr($name, 0, 15) == 'l_transactionid') {

          // We don't/can't process subscription notifications, which appear
          // to be identified by transaction ids beginning with S-
          if (substr($value, 0, 2) == 'S-') {
            continue;
          }

          // Before we bother making a remote API call to PayPal to lookup
          // details about a transaction, let's make sure that it doesn't
          // already exist in the database.
          require_once 'CRM/Contribute/DAO/Contribution.php';
          $dao = new CRM_Contribute_DAO_Contribution();
          $dao->trxn_id = $value;
          if ($dao->find(TRUE)) {
            preg_match('/(\d+)$/', $name, $matches);
            $seq = $matches[1];
            $email = $result["l_email{$seq}"];
            $amt = $result["l_amt{$seq}"];
            CRM_Core_Error::debug_log_message("Skipped (already recorded) - $email, $amt, $value ..<p>", TRUE);
            continue;
          }

          $keyArgs['transactionid'] = $value;
          $trxnDetails = CRM_Core_Payment_PayPalImpl::invokeAPI($keyArgs, $url);
          if (is_a($trxnDetails, 'CRM_Core_Error')) {
            echo "PAYPAL ERROR: Skipping transaction id: $value<p>";
            continue;
          }

          // only process completed payments
          if (strtolower($trxnDetails['paymentstatus']) != 'completed') {
            continue;
          }

          // only process receipts, not payments
          if (strtolower($trxnDetails['transactiontype']) == 'sendmoney') {
            continue;
          }

          $params = self::formatAPIParams($trxnDetails,
            self::$_paypalParamsMapper,
            'paypal'
          );
          if ($paymentMode == 'test') {
            $params['transaction']['is_test'] = 1;
          }
          else {
            $params['transaction']['is_test'] = 0;
          }

          try {
            if (self::processAPIContribution($params)) {
              CRM_Core_Error::debug_log_message("Processed - {$trxnDetails['email']}, {$trxnDetails['amt']}, {$value} ..<p>", TRUE);
            }
            else {
              CRM_Core_Error::debug_log_message("Skipped - {$trxnDetails['email']}, {$trxnDetails['amt']}, {$value} ..<p>", TRUE);
            }
          }
          catch (CRM_Core_Exception $e) {
            CRM_Core_Error::debug_log_message("Skipped - {$trxnDetails['email']}, {$trxnDetails['amt']}, {$value} ..<p>", TRUE);
          }
        }
      }
      if ($result['l_errorcode0'] == '11002') {
        $end = $result['l_timestamp99'];
        $end_time = strtotime("{$end}", time());
        $end_date = date('Y-m-d\T00:00:00.00\Z', $end_time);
        $args['enddate'] = $end_date;
      }
    } while ($result['l_errorcode0'] == '11002');
  }

  public static function csv() {
    $csvFile = '/home/deepak/Desktop/crm-4247.csv';
    $delimiter = ";";
    $row = 1;

    $handle = fopen($csvFile, "r");
    if (!$handle) {
      throw new CRM_Core_Exception("Can't locate csv file.");
    }

    require_once "CRM/Contribute/BAO/Contribution/Utils.php";
    while (($data = fgetcsv($handle, 1000, $delimiter, '"', '')) !== FALSE) {
      if ($row !== 1) {
        $data['header'] = $header;
        $params = self::formatAPIParams($data,
          self::$_csvParamsMapper,
          'csv'
        );
        if (self::processAPIContribution($params)) {
          CRM_Core_Error::debug_log_message("Processed - line $row of csv file .. {$params['email']}, {$params['transaction']['total_amount']}, {$params['transaction']['trxn_id']} ..<p>", TRUE);
        }
        else {
          CRM_Core_Error::debug_log_message("Skipped - line $row of csv file .. {$params['email']}, {$params['transaction']['total_amount']}, {$params['transaction']['trxn_id']} ..<p>", TRUE);
        }

        // clean up memory from dao's
        CRM_Core_DAO::freeResult();
      }
      else {
        // we assuming - first row is always the header line
        $header = $data;
        CRM_Core_Error::debug_log_message("Considering first row ( line $row ) as HEADER ..<p>", TRUE);

        if (empty($header)) {
          throw new CRM_Core_Exception("Header is empty.");
        }
      }
      $row++;
    }
    fclose($handle);
  }

  public static function process() {
    require_once 'CRM/Utils/Request.php';

    $type = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'csv', 'REQUEST');
    $type = strtolower($type);

    switch ($type) {
      case 'paypal':
        $start = CRM_Utils_Request::retrieve('start', 'String',
          CRM_Core_DAO::$_nullObject, FALSE, 31, 'REQUEST'
        );
        $end = CRM_Utils_Request::retrieve('end', 'String',
          CRM_Core_DAO::$_nullObject, FALSE, 0, 'REQUEST'
        );
        if ($start < $end) {
          throw new CRM_Core_Exception("Start offset can't be less than End offset.");
        }

        $start = date('Y-m-d', time() - $start * 24 * 60 * 60) . 'T00:00:00.00Z';
        $end = date('Y-m-d', time() - $end * 24 * 60 * 60) . 'T23:59:00.00Z';

        $ppID = CRM_Utils_Request::retrieve('ppID', 'Integer',
          CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST'
        );
        $mode = CRM_Utils_Request::retrieve('ppMode', 'String',
          CRM_Core_DAO::$_nullObject, FALSE, 'live', 'REQUEST'
        );

        $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($ppID, $mode);

        CRM_Core_Error::debug_log_message("Start Date=$start,  End Date=$end, ppID=$ppID, mode=$mode <p>", TRUE);

        return self::$type($paymentProcessor, $mode, $start, $end);

      case 'csv':
        return self::csv();
    }
  }

  /**
   * @param array $apiParams
   * @param $mapper
   * @param string $type
   * @param bool $category
   *
   * @return array
   */
  public static function formatAPIParams($apiParams, $mapper, $type = 'paypal', $category = TRUE) {
    $type = strtolower($type);

    if (!in_array($type, array(
      'paypal',
      'csv',
    ))
    ) {
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

  }

  /**
   * @deprecated function.
   *
   * This function has probably been defunct for quite a long time.
   *
   * @param array $params
   *
   * @return bool
   */
  public static function processAPIContribution($params) {
    if (empty($params) || array_key_exists('error', $params)) {
      return FALSE;
    }

    $params['contact_id'] = CRM_Contact_BAO_Contact::getFirstDuplicateContact($params, 'Individual', 'Unsupervised', array(), FALSE);

    $contact = civicrm_api3('Contact', 'create', $params);

    // only pass transaction params to contribution::create, if available
    if (array_key_exists('transaction', $params)) {
      $params = $params['transaction'];
      $params['contact_id'] = $contact['id'];
    }

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $params['id'] ?? NULL,
      'Contribution'
    );
    // create contribution

    // if this is a recurring contribution then process it first
    if ($params['trxn_type'] == 'subscrpayment') {
      // see if a recurring record already exists
      $recurring = new CRM_Contribute_BAO_ContributionRecur();
      $recurring->processor_id = $params['processor_id'];
      if (!$recurring->find(TRUE)) {
        $recurring = new CRM_Contribute_BAO_ContributionRecur();
        $recurring->invoice_id = $params['invoice_id'];
        $recurring->find(TRUE);
      }

      // This is the same thing the CiviCRM IPN handler does to handle
      // subsequent recurring payments to avoid duplicate contribution
      // errors due to invoice ID. See:
      // ./CRM/Core/Payment/PayPalIPN.php:200
      if ($recurring->id) {
        $params['invoice_id'] = bin2hex(random_bytes(16));
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

    $contribution = CRM_Contribute_BAO_Contribution::create($params);
    return (bool) $contribution->id;
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
      $params['email'] = [
        1 => [
          'email' => $params['email'],
          'location_type_id' => $billingLocTypeId,
        ],
      ];
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
      $transaction['trxn_id'] = bin2hex(random_bytes(16));
    }

    if (!isset($transaction['financial_type_id'])) {
      $contributionTypes = array_keys(CRM_Contribute_PseudoConstant::financialType());
      $transaction['financial_type_id'] = $contributionTypes[0];
    }

    if (($type == 'paypal') && (!isset($transaction['net_amount']))) {
      $transaction['net_amount'] = $transaction['total_amount'] - ($transaction['fee_amount'] ?? 0);
    }

    if (!isset($transaction['invoice_id'])) {
      $transaction['invoice_id'] = $transaction['trxn_id'];
    }

    $source = ts('ContributionProcessor: %1 API',
      [1 => ucfirst($type)]
    );
    if (isset($transaction['source'])) {
      $transaction['source'] = $source . ':: ' . $transaction['source'];
    }
    else {
      $transaction['source'] = $source;
    }

    return TRUE;
  }

}

// bootstrap the environment and run the processor
session_start();
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
CRM_Core_Config::singleton();

CRM_Utils_System::authenticateScript(TRUE);

//log the execution of script
CRM_Core_Error::debug_log_message('ContributionProcessor.php');

$lock = Civi::lockManager()->acquire('worker.contribute.CiviContributeProcessor');

if ($lock->isAcquired()) {
  set_time_limit(0);

  CiviContributeProcessor::process();
}
else {
  throw new Exception('Could not acquire lock, another CiviContributeProcessor process is running');
}

$lock->release();

echo "Done processing<p>";
