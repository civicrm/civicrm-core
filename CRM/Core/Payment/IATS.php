<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the Affero General Public License Version 1,    |
 | March 2002.                                                        |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the Affero General Public License for more details.            |
 |                                                                    |
 | You should have received a copy of the Affero General Public       |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org.  If you have questions about the       |
 | Affero General Public License or the licensing  of CiviCRM,        |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @author Alan Dixon
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_Payment_IATS extends CRM_Core_Payment {
  # (not used, implicit in the API, might need to convert?)
  CONST CHARSET = 'UFT-8';
  /* check IATS website for additional supported currencies */
  CONST CURRENCIES = 'CAD,USD,AUD,GBP,EUR,NZD';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('IATS');

    // get merchant data from config
    $config = CRM_Core_Config::singleton();
    // live or test
    $this->_profile['mode'] = $mode;
    $this->_profile['webserver'] = parse_url($this->_paymentProcessor['url_site'], PHP_URL_HOST);
    $currencyID = $config->defaultCurrency;

    if (!in_array($currencyID, explode(',', self::CURRENCIES))) {
      // Configuration error: default currency must be in CURRENCIES const
      return self::error('Invalid configuration:' . $currencyID . ', you must use one of ' . self::CURRENCIES . ' with IATS');
    }
  }

  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_IATS($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  function doDirectPayment(&$params) {
    // $result = '';
    //       foreach($params as $key => $value) {
    //         $result .= "<strong>$key</strong>: $value<br />";
    //       }
    //       return self::error($result);
    // make sure i've been called correctly ...

    if (!$this->_profile) {
      return self::error('Unexpected error, missing profile');
    }
    if (!in_array($params['currencyID'], explode(',', self::CURRENCIES))) {
      return self::error('Invalid currency selection, must be one of ' . self::CURRENCIES);
    }
    $isRecur = $params['is_recur'];
    // AgentCode = $this->_paymentProcessor['signature'];
    // Password  = $this->_paymentProcessor['password' ];
    // beginning of modified sample code from IATS php api include IATS supplied api library

    if ($isRecur) {
      include_once ('Services/IATS/iats_reoccur.php');
      $iatslink1 = new iatslinkReoccur;
    }
    else {
      include_once ('Services/IATS/iatslink.php');
      $iatslink1 = new iatslink;
    }

    $iatslink1->setTestMode($this->_profile['mode'] != 'live');
    $iatslink1->setWebServer($this->_profile['webserver']);

    // return self::error($this->_profile['webserver']);

    // Put your invoice here
    $iatslink1->setInvoiceNumber($params['invoiceID']);

    // $iatslink1->setCardType("VISA");
    // If CardType is not set, iatslink will find the cardType
    // CardType not set because IATS uses different names!
    // $iatslink1->setCardType($params['credit_card_type']);

    $iatslink1->setCardNumber($params['credit_card_number']);
    $expiry_string = sprintf('%02d/%02d', $params['month'], ($params['year'] % 100));
    $iatslink1->setCardExpiry($expiry_string);
    $amount = sprintf('%01.2f', $params['amount']);
    // sell
    $iatslink1->setDollarAmount($amount);
    // refund
    //$iatslink1->setDollarAmount(-1.15);

    $AgentCode = $this->_paymentProcessor['signature'];
    $Password = $this->_paymentProcessor['password'];
    $iatslink1->setAgentCode($AgentCode);
    $iatslink1->setPassword($Password);
    // send IATS my invoiceID to match things up later
    $iatslink1->setInvoiceNumber($params['invoiceID']);

    // Set billing fields
    $iatslink1->setFirstName($params['billing_first_name']);
    $iatslink1->setLastName($params['billing_last_name']);
    $iatslink1->setStreetAddress($params['street_address']);
    $iatslink1->setCity($params['city']);
    $iatslink1->setState($params['state_province']);
    $iatslink1->setZipCode($params['postal_code']);
    // and now go! ... uses curl to post and retrieve values
    // after various data integrity tests
    // simple version
    if (!$isRecur) {
      // cvv2 only seems to get set for this!
      $iatslink1->setCVV2($params['cvv2']);

      // Allow further manipulation of the arguments via custom hooks,
      // before initiating processCreditCard()
      CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $iatslink1);

      $iatslink1->processCreditCard();
      // extra fields for recurring donations
    }
    else {
      // implicit - test?: 1 == $params['frequency_interval'];
      $scheduleType = NULL;
      if ($params['installments']) {
        $paymentsRecur = $params['installments'] - 1;
      }
      // handle unspecified installments by setting to 10 years, IATS doesn't allow indefinitely recurring contributions
      else {
        switch ($params['frequency_unit']) {
          case 'week':
            $paymentsRecur = 520;
          case 'month':
            $paymentsRecur = 120;
        }
      }
      // IATS requires end date, calculated here

      // to be converted to date format later
      $startTime = time();
      $date = getdate($startTime);

      switch ($params['frequency_unit']) {
        case 'week':
          $scheduleType = 'WEEKLY';
          $scheduleDate = $date['wday'] + 1;
          $endTime      = $startTime + ($paymentsRecur * 7 * 24 * 60 * 60);
          break;

        case 'month':
          $scheduleType = 'MONTHLY';
          $scheduleDate = $date['mday'];
          $date['mon'] += $paymentsRecur;
          while ($date['mon'] > 12) {
            $date['mon'] -= 12;
            $date['year'] += 1;
          }
          $endTime = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
          break;

        default:
          die('Invalid frequency unit!');
          break;
      }
      $endDate = date('Y-m-d', $endTime);
      $startDate = date('Y-m-d', $startTime);
      $iatslink1->setReoccuringStatus("ON");
      $iatslink1->setBeginDate($startDate);
      $iatslink1->setEndDate($endDate);
      $iatslink1->setScheduleType($scheduleType);
      $iatslink1->setScheduleDate($scheduleDate);

      // Allow further manipulation of the arguments via custom hooks,
      // before initiating the curl process
      CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $iatslink1);

      // this next line is the reoccc equiv of processCreditCard
      $iatslink1->createReoccCustomer();
    }

    if ($iatslink1->getStatus() == 1) {
      // this just means we got some kind of answer, not necessarily approved
      $result = $iatslink1->getAuthorizationResult();
      //return self::error($result);
      $result      = explode(':', $result, 2);
      $trxn_result = trim($result[0]);
      $trxn_id     = trim($result[1]);
      if ($trxn_result == 'OK') {
        $params['trxn_id'] = $trxn_id . ':' . time();
        $params['gross_amount'] = $amount;
        return $params;
      }
      // createReoccCustomer() may return other, valid result codes...
      elseif (preg_match('/A\d+/', $trxn_result)) {
        $params['trxn_id'] = $trxn_result;
        $params['gross_amount'] = $amount;
        return $params;
      }
      else {
        return self::error($trxn_id);
      }
    }
    else {
      return self::error($iatslink1->getError());
    }
  }

  function &error($error = NULL) {
    $e = CRM_Core_Error::singleton();
    if (is_object($error)) {
      $e->push($error->getResponseCode(),
        0, NULL,
        $error->getMessage()
      );
    }
    elseif ($error && is_numeric($error)) {
      $e->push($error,
        0, NULL,
        $this->errorString($error)
      );
    }
    elseif (is_string($error)) {
      $e->push(9002,
        0, NULL,
        $error
      );
    }
    else {
      $e->push(9001, 0, NULL, "Unknown System Error.");
    }
    return $e;
  }

  function errorString($error_id) {
    $errors = array(
      1 => 'Agent Code has not been set up on the authorization system.',
      2 => 'Unable to process transaction. Verify and re-enter credit card information.',
      3 => 'Charge card expired.',
      4 => 'Incorrect expiration date.',
      5 => 'Invalid transaction. Verify and re-enter credit card information.',
      6 => 'Transaction not supported by institution.',
      7 => 'Lost or stolen card.',
      8 => 'Invalid card status.',
      9 => 'Restricted card status. Usually on corporate cards restricted to specific sales.',
      10 => 'Error. Please verify and re-enter credit card information.',
      11 => 'General decline code, may have different reasons for each card type. Please have your client call customer service.',
      14 => 'This means that the credit card is over the limit.',
      15 => 'Decline code, may have different reasons for each card type. Please have your client call customer service.',
      16 => 'Invalid charge card number. Verify and re-enter credit card information.',
      17 => 'Unable to authorize transaction. Verify card information with customer and re-enter. Could be invalid name or expiry date.',
      18 => 'Card not supported by institution.',
      19 => 'Incorrect CVV2.',
      22 => 'Bank Timeout. Bank lines may be down or busy. Re-try transaction later.',
      23 => 'System error. Re-try transaction later.',
      24 => 'Charge card expired.',
      25 => 'Capture card. Reported lost or stolen.',
      27 => 'System error, please re-enter transaction.',
      29 => 'Rejected by Ticketmaster.',
      31 => 'Manual reject code ',
      39 => 'Contact Ticketmaster 1-888-955-5455 ',
      40 => 'Card not supported by Ticketmaster. Invalid cc number.',
      41 => 'Invalid Expiry date ',
      100 => 'Authorization system down. DO NOT REPROCESS.',
    );
    return ' <strong>' . $errors[(integer) $error_id] . '</strong>';
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @param  string $mode the mode we are operating in (live or test)
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();

    if (empty($this->_paymentProcessor['signature'])) {
      $error[] = ts('Agent Code is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }
}

