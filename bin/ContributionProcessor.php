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
 *
 */
class CiviContributeProcessor {
  static $_paypalParamsMapper = array(
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

  static $_googleParamsMapper = array(
    //category    => array(google_param    => civicrm_field);
    'contact' => array(
      'first-name' => 'first_name',
      'last-name' => 'last_name',
      'contact-name' => 'display_name',
      'email' => 'email',
    ),
    'location' => array(
      'address1' => 'street_address',
      'address2' => 'supplemental_address_1',
      'city' => 'city',
      'postal-code' => 'postal_code',
      'country-code' => 'country',
    ),
    'transaction' => array(
      'total-charge-amount' => 'total_amount',
      'google-order-number' => 'trxn_id',
      'currency' => 'currency',
      'item-name' => 'source',
      'item-description' => 'note',
      'timestamp' => 'receive_date',
      'latest-charge-fee' => 'fee_amount',
      'net-amount' => 'net_amount',
      'times' => 'installments',
      'period' => 'frequency_unit',
      'frequency_interval' => 'frequency_interval',
      'start_date' => 'start_date',
      'modified_date' => 'modified_date',
      'trxn_type' => 'trxn_type',
      'amount' => 'amount',
    ),
  );

  static $_csvParamsMapper = array(
    // Note: if csv header is not present in the mapper, header itself
    // is considered as a civicrm field.
    //category    => array(csv_header      => civicrm_field);
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

          if (self::processAPIContribution($params)) {
            CRM_Core_Error::debug_log_message("Processed - {$trxnDetails['email']}, {$trxnDetails['amt']}, {$value} ..<p>", TRUE);
          }
          else {
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

  /**
   * @param $paymentProcessor
   * @param $paymentMode
   * @param $start
   * @param $end
   */
  public static function google($paymentProcessor, $paymentMode, $start, $end) {
    require_once "CRM/Contribute/BAO/Contribution/Utils.php";
    require_once 'CRM/Core/Payment/Google.php';
    $nextPageToken = TRUE;
    $searchParams = array(
      'start' => $start,
      'end' => $end,
      'notification-types' => array('charge-amount'),
    );

    $response = CRM_Core_Payment_Google::invokeAPI($paymentProcessor, $searchParams);

    while ($nextPageToken) {
      if ($response[0] == 'error') {
        CRM_Core_Error::debug_log_message("GOOGLE ERROR: " .
          $response[1]['error']['error-message']['VALUE'], TRUE
        );
      }
      $nextPageToken = isset($response[1][$response[0]]['next-page-token']['VALUE']) ? $response[1][$response[0]]['next-page-token']['VALUE'] : FALSE;

      if (is_array($response[1][$response[0]]['notifications']['charge-amount-notification'])) {

        if (array_key_exists('google-order-number',
          $response[1][$response[0]]['notifications']['charge-amount-notification']
        )) {
          // sometimes 'charge-amount-notification' itself is an absolute
          // array and not array of arrays. This is the case when there is only one
          // charge-amount-notification. Hack for this special case -
          $chrgAmt = $response[1][$response[0]]['notifications']['charge-amount-notification'];
          unset($response[1][$response[0]]['notifications']['charge-amount-notification']);
          $response[1][$response[0]]['notifications']['charge-amount-notification'][] = $chrgAmt;
        }

        foreach ($response[1][$response[0]]['notifications']['charge-amount-notification'] as $amtData) {
          $searchParams = array(
            'order-numbers' => array($amtData['google-order-number']['VALUE']),
            'notification-types' => array('risk-information', 'new-order', 'charge-amount'),
          );
          $response = CRM_Core_Payment_Google::invokeAPI($paymentProcessor,
            $searchParams
          );
          // append amount information as well
          $response[] = $amtData;

          $params = self::formatAPIParams($response,
            self::$_googleParamsMapper,
            'google'
          );
          if ($paymentMode == 'test') {
            $params['transaction']['is_test'] = 1;
          }
          else {
            $params['transaction']['is_test'] = 0;
          }
          if (self::processAPIContribution($params)) {
            CRM_Core_Error::debug_log_message("Processed - {$params['email']}, {$amtData['total-charge-amount']['VALUE']}, {$amtData['google-order-number']['VALUE']} ..<p>", TRUE);
          }
          else {
            CRM_Core_Error::debug_log_message("Skipped - {$params['email']}, {$amtData['total-charge-amount']['VALUE']}, {$amtData['google-order-number']['VALUE']} ..<p>", TRUE);
          }
        }

        if ($nextPageToken) {
          $searchParams = array('next-page-token' => $nextPageToken);
          $response = CRM_Core_Payment_Google::invokeAPI($paymentProcessor, $searchParams);
        }
      }
    }
  }

  public static function csv() {
    $csvFile = '/home/deepak/Desktop/crm-4247.csv';
    $delimiter = ";";
    $row = 1;

    $handle = fopen($csvFile, "r");
    if (!$handle) {
      CRM_Core_Error::fatal("Can't locate csv file.");
    }

    require_once "CRM/Contribute/BAO/Contribution/Utils.php";
    while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
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
          CRM_Core_Error::fatal("Header is empty.");
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
      case 'google':
        $start = CRM_Utils_Request::retrieve('start', 'String',
          CRM_Core_DAO::$_nullObject, FALSE, 31, 'REQUEST'
        );
        $end = CRM_Utils_Request::retrieve('end', 'String',
          CRM_Core_DAO::$_nullObject, FALSE, 0, 'REQUEST'
        );
        if ($start < $end) {
          CRM_Core_Error::fatal("Start offset can't be less than End offset.");
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
      'google',
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

      CRM_Contribute_BAO_Contribution_Utils::_fillCommonParams($params, $type);

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

      CRM_Contribute_BAO_Contribution_Utils::_fillCommonParams($params, $type);

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

        CRM_Contribute_BAO_Contribution_Utils::_fillCommonParams($params, $type);
      }
      return $params;
    }
  }

  /**
   * @param array $params
   *
   * @return bool
   */
  public static function processAPIContribution($params) {
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
      CRM_Utils_Array::value('id', $params, NULL),
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

}

// bootstrap the environment and run the processor
session_start();
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

CRM_Utils_System::authenticateScript(TRUE);

//log the execution of script
CRM_Core_Error::debug_log_message('ContributionProcessor.php');

require_once 'CRM/Core/Lock.php';
$lock = new CRM_Core_Lock('CiviContributeProcessor');

if ($lock->isAcquired()) {
  // try to unset any time limits
  if (!ini_get('safe_mode')) {
    set_time_limit(0);
  }

  CiviContributeProcessor::process();
}
else {
  throw new Exception('Could not acquire lock, another CiviMailProcessor process is running');
}

$lock->release();

echo "Done processing<p>";
