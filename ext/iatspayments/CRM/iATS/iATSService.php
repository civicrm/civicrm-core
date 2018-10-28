<?php

/**
 * @file IATS Service Request Object used for accessing iATS Service Interface.
 *
 * A lightweight object that encapsulates the details of the iATS Payments interface.
 *
 * Provides SOAP interface details for the various methods,
 * error messages, and cc details
 *
 * Require the method id string on construction and any options like trace, logging.
 * Require the specific payment details, and the client credentials, on request
 *
 * TODO: provide logging options for the request, exception and response
 *
 * Expected usage:
 * $iats = new iATS_Service_Request($options)
 * where options usually include
 *   type: 'report', 'customer', 'process'
 *   method: 'cc', etc. as appropriate for that type
 *   iats_domain: the domain for the api (us or uk currently)
 * $response = $iats->request($credentials, $request_params)
 * the request method encapsulates the soap inteface and requires iATS client details + payment info (cc + amount + billing info)
 * $result = $iats->response($response)
 * the 'response' method converts the soap response into a nicer format
 **/

/**
 *
 */
class iATS_Service_Request {

  // iATS transaction mode definitions:
  const iATS_TXN_NS = 'xmlns';
  const iATS_TXN_TRACE = TRUE;
  const iATS_TXN_SUCCESS = 'Success';
  const iATS_TXN_OK = 'OK';
  const iATS_URL_PROCESSLINK = '/NetGate/ProcessLinkv2.asmx?WSDL';
  const iATS_URL_REPORTLINK = '/NetGate/ReportLinkv2.asmx?WSDL';
  const iATS_URL_CUSTOMERLINK = '/NetGate/CustomerLinkv2.asmx?WSDL';
  // TODO: confirm with Stephen if this needs a v2 as well:
  const iATS_URL_DPMPROCESS = '/NetGate/IATSDPMProcess.aspx';
  const iATS_USE_DPMPROCESS = FALSE;

  /**
   *
   */
  public function __construct($options) {
    $this->type = isset($options['type']) ? $options['type'] : 'process';
    $method = $options['method'];
    $iats_domain = $options['iats_domain'];
    switch ($this->type) {
      case 'report':
        $this->_wsdl_url = 'https://' . $iats_domain . self::iATS_URL_REPORTLINK;
        break;

      case 'customer':
        $this->_wsdl_url = 'https://' . $iats_domain . self::iATS_URL_CUSTOMERLINK;
        break;

      case 'process':
      default:
        $this->_wsdl_url = 'https://' . $iats_domain . self::iATS_URL_PROCESSLINK;
        if ($method == 'cc') {/* as suggested by iATS, though not necessary I believe */
          $this->_tag_order = array('agentCode', 'password', 'customerIPAddress', 'invoiceNum', 'creditCardNum', 'ccNum', 'creditCardExpiry', 'ccExp', 'firstName', 'lastName', 'address', 'city', 'state', 'zipCode', 'cvv2', 'total', 'comment');
        }
        break;
    }
    // TODO: check that the method is allowed!
    $this->method = $this->methodInfo($this->type, $method);
    // Initialize the request array.
    $this->request = array();
    // Name space url.
    $this->_wsdl_url_ns = 'https://www.iatspayments.com/NetGate/';
    $this->options = $options;
    $this->options['debug'] = _iats_civicrm_domain_info('debug_enabled');
    // Check for valid currencies with domain/method combinations.
    if (isset($options['currencyID'])) {
      $valid = FALSE;
      switch ($iats_domain) {
        case 'www2.iatspayments.com':
        case 'www.iatspayments.com':
          if (in_array($options['currencyID'], array('USD', 'CAD'))) {
            $valid = TRUE;
          }
          break;

        case 'www.uk.iatspayments.com':
          if ('cc' == substr($method, 0, 2) || 'create_credit_card_customer' == $method) {
            if (in_array($options['currencyID'], array('AUD', 'USD', 'EUR', 'GBP', 'IEE', 'CHF', 'HKD', 'JPY', 'SGD', 'MXN'))) {
              $valid = TRUE;
            }
          }
          elseif ('direct_debit' == substr($method, 0, 12)) {
            if (in_array($options['currencyID'], array('GBP'))) {
              $valid = TRUE;
            }
          }
          break;
      }
      if (!$valid) {
        CRM_Core_Error::fatal('Invalid currency selection: ' . $options['currencyID'] . ' for domain ' . $iats_domain);
      }
    }
  }

  /* check iATS website for additional supported currencies */

  /**
   * Submits an API request through the iATS SOAP API Toolkit.
   *
   * @param $credentials
   *   The request object or array containing the merchant credentials
   *
   * @param $request_params
   *   The request array containing the parameters of the requested services.
   *
   * @return
   *   The response object from the API with properties pertinent to the requested
   *     services.
   */
  public function request($credentials, $request_params) {
    // Attempt the SOAP request and log the exception on failure.
    $method = $this->method['method'];
    if (empty($method)) {
      CRM_Core_Error::fatal('No method for request.');
      return FALSE;
    }
    // Do some massaging of parameters for badly behaving iATS methods ($method is now the iATS method, not our internal key)
    switch ($method) {
      case 'CreateACHEFTCustomerCode':
      case 'CreateCreditCardCustomerCode':
      case 'UpdateCreditCardCustomerCode':
        if (empty($request_params['beginDate'])) {
          $request_params['beginDate'] = date('c', time());
        }
        if (empty($request_params['endDate'])) {
          $request_params['endDate'] = date('c', strtotime('+5 years'));
        }
        if (empty($request_params['recurring'])) {
          $request_params['recurring'] = '0';
        }
        break;
     case 'GetACHEFTApprovedDateRangeCSV':
     case 'GetACHEFTRejectDateRangeCSV':
        if (!isset($request_params['startIndex'])) {
          $request_params['startIndex'] = '0';
        }
        if (!isset($request_params['endIndex'])) {
          $request_params['endIndex'] = '199';
        }
    }
    $message = $this->method['message'];
    $response = $this->method['response'];
    // Always log requests to my own table, start by making a copy of the original request
    // note: this is different from the debug logging that only happens if debug is enabled.
    if (!empty($request_params['invoiceNum'])) {
      $logged_request = $request_params;
      // Mask the cc numbers.
      $this->mask($logged_request);
      // log: ip, invoiceNum, , cc, total, date
      // dpm($logged_request);
      $cc = isset($logged_request['creditCardNum']) ? $logged_request['creditCardNum'] : (isset($logged_request['ccNum']) ? $logged_request['ccNum'] : '');
      $ip = $logged_request['customerIPAddress'];
      $query_params = array(
        1 => array($logged_request['invoiceNum'], 'String'),
        2 => array($ip, 'String'),
        3 => array(substr($cc, -4), 'String'),
        4 => array('', 'String'),
        5 => array($logged_request['total'], 'String'),
      );
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_iats_request_log
        (invoice_num, ip, cc, customer_code, total, request_datetime) VALUES (%1, %2, %3, %4, %5, NOW())", $query_params);
      if (!$this->is_ipv4($ip)) {
        $request_params['customerIPAddress'] = substr($ip, 0, 30);
      }
      // Save the invoiceNum so I can log it for the response.
      $this->invoiceNum = $logged_request['invoiceNum'];
    }
    // The agent user and password only get put in here so they don't end up in a log above.
    try {
      $credentials = (array) $credentials;
      $testAgentCode = ('TEST88' == $credentials['agentCode']) ? TRUE : FALSE;
      /* until iATS fixes it's box verify, we need to have trace on to make the hack below work */
      $soapClient = new SoapClient($this->_wsdl_url, array('trace' => 1, 'soap_version' => SOAP_1_2));
      /* build the request manually as per the iATS docs */
      $xml = '<' . $message . ' xmlns="' . $this->_wsdl_url_ns . '">';
      $request = array_merge($this->request, $credentials, $request_params);
      // Pass CiviCRM tag + version to iATS.
      $request['comment'] = 'CiviCRM: ' . CRM_Utils_System::version() . ' + ' . 'iATS Extension: ' . $this->iats_extension_version();
      $tags = (!empty($this->_tag_order)) ? $this->_tag_order : array_keys($request);
      foreach ($tags as $k) {
        if (isset($request[$k])) {
          $xml .= '<' . $k . '>' . $this->xmlsafe($request[$k]) . '</' . $k . '>';
        }
      }
      $xml .= '</' . $message . '>';
      if ($testAgentCode && !empty($this->options['debug'])) {
        CRM_Core_Error::debug_var('Method info', $method);
        CRM_Core_Error::debug_var('XML', $xml);
      }
      $soapRequest = new SoapVar($xml, XSD_ANYXML);
      if ($testAgentCode && !empty($this->options['debug'])) {
        CRM_Core_Error::debug_var('SoapRequest', $soapRequest);
      }
      $soapResponse = $soapClient->$method($soapRequest);
      if (!empty($this->options['debug'])  && $testAgentCode) {
        $request_log = "\n HEADER:\n";
        $request_log .= $soapClient->__getLastRequestHeaders();
        $request_log .= "\n BODY:\n";
        $request_log .= $soapClient->__getLastRequest();
        $request_log .= "\n BODYEND:\n";
        CRM_Core_Error::debug_var('Request Log', $request_log);
        $response_log = "\n HEADER:\n";
        $response_log .= $soapClient->__getLastResponseHeaders();
        $response_log .= "\n BODY:\n";
        $response_log .= $soapClient->__getLastResponse();
        $response_log .= "\n BODYEND:\n";
        CRM_Core_Error::debug_var('Response Log', $response_log);
      }
    }
    catch (SoapFault $exception) {
      if (!empty($this->options['debug'])) {
        CRM_Core_Error::debug_var('SoapFault Exception', $exception);
        $response_log = "\n HEADER:\n";
        $response_log .= $soapClient->__getLastResponseHeaders();
        $response_log .= "\n BODY:\n";
        $response_log .= $soapClient->__getLastResponse();
        $response_log .= "\n BODYEND:\n";
        CRM_Core_Error::debug_var('Raw Response', $response_log);
      }
      return FALSE;
    }

    // Log the response if specified.
    if (!empty($this->options['debug'])) {
      CRM_Core_Error::debug_var('iATS SOAP Response', $soapResponse);
    }
    if (isset($soapResponse->$response->any)) {
      $xml_response = $soapResponse->$response->any;
      return new SimpleXMLElement($xml_response);
    }
    // Deal with bad iats soap, this will only work if trace (debug) is on for now.
    else {
      $hack = new stdClass();
      $hack->FILE = strip_tags($soapClient->__getLastResponse());
      return $hack;
    }
  }

  /**
   *
   */
  public function file($response) {
    return base64_decode($response->FILE);
  }

  /**
   * Process the response to the request into a more friendly format in an array $result;
   * Log the result to an internal table while I'm at it, unless explicitly not requested.
   */
  public function result($response, $log = TRUE) {
    $result = array('auth_result' => '', 'remote_id' => '', 'status' => '');
    switch ($this->type) {
      case 'report':
      case 'process':
        if (!empty($response->PROCESSRESULT)) {
          $processresult = $response->PROCESSRESULT;
          $result['auth_result'] = trim(current($processresult->AUTHORIZATIONRESULT));
          $result['remote_id'] = current($processresult->TRANSACTIONID);
          // If we didn't get an approval response code...
          // Note: do not use SUCCESS property, which just means iATS said "hello".
          $result['status'] = (substr($result['auth_result'], 0, 2) == self::iATS_TXN_OK) ? 1 : 0;
        }
        // If the payment failed, display an error and rebuild the form.
        if (empty($result['status'])) {
          $result['reasonMessage'] = $result['auth_result'] ? $this->reasonMessage($result['auth_result']) : 'Unexpected Server Error, please see your logs';
        }
        break;

      case 'customer':
        if ($response->STATUS == 'Success') {
          if (!empty($response->AUTHRESULT)) {
            $result = get_object_vars($response->AUTHRESULT);
            $result['status'] = (substr($result['AUTHSTATUS'], 0, 2) == self::iATS_TXN_OK) ? 1 : 0;
          }
          elseif (!empty($response->PROCESSRESULT)) {
            $result = get_object_vars($response->PROCESSRESULT);
            $result['status'] = (substr($result['AUTHORIZATIONRESULT'], 0, 2) == self::iATS_TXN_OK) ? 1 : 0;
          }
          elseif (!empty($response->CUSTOMERS->CST)) {
            $customer = get_object_vars($response->CUSTOMERS->CST);
            foreach ($customer as $key => $value) {
              if (is_string($value)) {
                $result[$key] = $value;
              }
            }
            $result['ac1'] = $customer['AC1'];
            $result['status'] = 1;
          }
        }
        // If the payment failed, display an error and rebuild the form.
        if (empty($result['status'])) {
          $result['reasonMessage'] = isset($result['BANKERROR']) ? $result['BANKERROR'] :
             (isset($result['AUTHORIZATIONRESULT']) ? $result['AUTHORIZATIONRESULT'] :
               (isset($result['ERRORS']) ? $result['ERRORS'] :
                 'Unexpected error'
               )
             );
        }
        break;
    }
    if ($log && !empty($this->invoiceNum) && ($this->type == 'process')) {
      $query_params = array(
        1 => array($this->invoiceNum, 'String'),
        2 => array($result['auth_result'], 'String'),
        3 => array($result['remote_id'], 'String'),
      );
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_iats_response_log
        (invoice_num, auth_result, remote_id, response_datetime) VALUES (%1, %2, %3, NOW())", $query_params);
      // #hack - this is necessary for 4.4 and possibly earlier versions of 4.6.x
      // this ensures that trxn_id gets written to the contribution record - even if core did not do so.
      if ($this->options['method'] == 'cc_with_customer_code') {
        $api_params = array(
          'version' => 3,
          'sequential' => 1,
          'invoice_id' => $this->invoiceNum,
        );
        $contribution = civicrm_api('contribution', 'getsingle', $api_params);
        if (!empty($contribution['id']) && empty($contribution['trxn_id'])) {
          $api_params = array(
            'version' => 3,
            'sequential' => 1,
            'id' => $contribution['id'],
            'trxn_id' => trim($result['remote_id']) . ':' . time(),
          );
          civicrm_api('contribution', 'create', $api_params);
          // watchdog('civicrm_iatspayments_com', 'rewrite: !request', array('!request' => '<pre>' . print_r($tmp, TRUE) . '</pre>', WATCHDOG_DEBUG));.
        }
      }
    }
    return $result;
  }

  /**
   * Helper function to process csv files
   * convert to an array of objects, each one corresponding to a transaction row.
   */
  public function getCSV($response, $method) {
    $transactions = array();
    $iats_domain = parse_url($this->_wsdl_url, PHP_URL_HOST);

    switch ($iats_domain) {
      case 'www.iatspayments.com':
        $date_format = 'm/d/Y H:i:s';
        $tz_string  = 'America/Vancouver';
        break;

      case 'www.uk.iatspayments.com':
        $date_format = 'd/m/Y H:i:s';
        $tz_string  = 'Europe/London';
        break;

      // Todo throw an exception instead? This should never happen!
      default:
        die('Invalid domain for date format');
    }
    $tz_object  = new DateTimeZone($tz_string);
    $gmt_datetime = new DateTime;
    $gmt_offset = $tz_object->getOffset($gmt_datetime);
    if (is_object($response)) {
      $box = preg_split("/\r\n|\n|\r/", $this->file($response));
      // watchdog('civicrm_iatspayments_com', 'csv: <pre>!data</pre>', array('!data' => print_r($box,TRUE)), WATCHDOG_NOTICE);.
      if (1 < count($box)) {
        // Data is an array of rows, the first of which is the column headers.
        $headers = array_flip(array_map('trim',str_getcsv($box[0])));
        for ($i = 1; $i < count($box); $i++) {
          if (empty($box[$i])) {
            continue;
          }
          $transaction = new stdClass();
          // save the raw data in 'data'
          $data = str_getcsv($box[$i]);
          // and then store it as an associate array based on the headers
          $record = array();
          foreach($headers as $label => $column_i) {
            $record[$label] = $data[$column_i];
          }
          // First get the data common to all methods.
          $transaction->id = $record['Transaction ID'];
          $transaction->customer_code = $record['Customer Code'];
          // Save the entire record in case I need it
          $transaction->data = $record;
          // Now the method specific headers.
          switch($method) {
            // These are the same-day journals
            case 'cc_journal_csv':
            case 'acheft_journal_csv':
              $datetime = $record['Date'];
              $transaction->invoice = $record['Invoice'];
              $transaction->amount = $record['Total'];
              break;
            // The box journals are the default.
            default:
              $transaction->amount = $record['Amount'];
              $datetime = $record['Date Time'];
              $transaction->invoice = $record['Invoice Number'];
              // And now the uk dd specific hack, only for the box journals.
              if ('www.uk.iatspayments.com' == $iats_domain) {
                $transaction->achref = $record['ACH Ref.'];
              }
              break;
          }
          // Note that $gmt_offset and date_format depend on the server (iats_domain)
          $rdp = date_parse_from_format($date_format, $datetime);
          $transaction->receive_date = gmmktime($rdp['hour'], $rdp['minute'], $rdp['second'], $rdp['month'], $rdp['day'], $rdp['year']) - $gmt_offset;
          // And now save it.
          $transactions[$transaction->id] = $transaction;
        }
      }
    }
    return $transactions;
  }

  /**
   * Provides the soap parameters for each of the ways to process payments at iATS Services
   * Parameters are: method, message and response, these are all soap object properties
   * Title and description provide a public information interface, not used internally.
   */
  public function methodInfo($type = '', $method = '') {
    $desc = 'Integrates the iATS SOAP webservice: ';
    switch ($type) {
      default:
      case 'process':
        $methods = array(
          'cc' => array(
            'title' => 'Credit card',
            'description' => $desc . 'ProcessCreditCard',
            'method' => 'ProcessCreditCard',
            'message' => 'ProcessCreditCard',
            'response' => 'ProcessCreditCardResult',
          ),
          'cc_create_customer_code' => array(
            'title' => 'Credit card, saved',
            'description' => $desc . 'CreateCustomerCodeAndProcessCreditCard',
            'method' => 'CreateCustomerCodeAndProcessCreditCard',
            'message' => 'CreateCustomerCodeAndProcessCreditCard',
            'response' => 'CreateCustomerCodeAndProcessCreditCardResult',
          ),
          'cc_with_customer_code' => array(
            'title' => 'Credit card using saved info',
            'description' => $desc . 'ProcessCreditCardWithCustomerCode',
            'method' => 'ProcessCreditCardWithCustomerCode',
            'message' => 'ProcessCreditCardWithCustomerCode',
            'response' => 'ProcessCreditCardWithCustomerCodeResult',
          ),
          'acheft' => array(
            'title' => 'ACH/EFT',
            'description' => $desc . 'ProcessACHEFT',
            'method' => 'ProcessACHEFT',
            'message' => 'ProcessACHEFT',
            'response' => 'ProcessACHEFTResult',
          ),
          'acheft_create_customer_code' => array(
            'title' => 'ACH/EFT, saved',
            'description' => $desc . 'CreateCustomerCodeAndProcessACHEFT',
            'method' => 'CreateCustomerCodeAndProcessACHEFT',
            'message' => 'CreateCustomerCodeAndProcessACHEFT',
            'response' => 'CreateCustomerCodeAndProcessACHEFTResult',
          ),
          'acheft_with_customer_code' => array(
            'title' => 'ACH/EFT with customer code',
            'description' => $desc . 'ProcessACHEFTWithCustomerCode',
            'method' => 'ProcessACHEFTWithCustomerCode',
            'message' => 'ProcessACHEFTWithCustomerCode',
            'response' => 'ProcessACHEFTWithCustomerCodeResult',
          ),
        );
        break;
      case 'report':
        $methods = array(
         // 'acheft_journal' => array(
         //   'title' => 'ACH-EFT Journal',
         //   'description'=> $desc. 'GetACHEFTJournal',
         //   'method' => 'GetACHEFTJournal',
         //   'message' => 'GetACHEFTJournal',
         //   'response' => 'GetACHEFTJournalResult',
         // ),.
          'cc_journal_csv' => array(
            'title' => 'Credit Card Journal CSV',
            'description' => $desc . 'GetCreditCardApprovedSpecificDateCSV',
            'method' => 'GetCreditCardApprovedSpecificDateCSV',
            'message' => 'GetCreditCardApprovedSpecificDateCSV',
            'response' => 'GetCreditCardApprovedSpecificDateCSVResult',
          ),
          'cc_payment_box_journal_csv' => array(
            'title' => 'Credit Card Payment Box Journal CSV',
            'description'=> $desc. 'GetCreditCardApprovedDateRangeCSV',
            'method' => 'GetCreditCardApprovedDateRangeCSV',
            'message' => 'GetCreditCardApprovedDateRangeCSV',
            'response' => 'GetCreditCardApprovedDateRangeCSVResult',
          ),
          'cc_payment_box_reject_csv' => array(
            'title' => 'Credit Card Payment Box Reject CSV',
            'description'=> $desc. 'GetCreditCardRejectDateRangeCSV',
            'method' => 'GetCreditCardRejectDateRangeCSV',
            'message' => 'GetCreditCardRejectDateRangeCSV',
            'response' => 'GetCreditCardRejectDateRangeCSVResult',
          ),
          'acheft_journal_csv' => array(
            'title' => 'ACH-EFT Journal CSV',
            'description' => $desc . 'GetACHEFTApprovedSpecificDateCSV',
            'method' => 'GetACHEFTApprovedSpecificDateCSV',
            'message' => 'GetACHEFTApprovedSpecificDateCSV',
            'response' => 'GetACHEFTApprovedSpecificDateCSVResult',
          ),
          'acheft_payment_box_journal_csv' => array(
            'title' => 'ACH-EFT Payment Box Journal CSV',
            'description' => $desc . 'GetACHEFTApprovedDateRangeCSV',
            'method' => 'GetACHEFTApprovedDateRangeCSV',
            'message' => 'GetACHEFTApprovedDateRangeCSV',
            'response' => 'GetACHEFTApprovedDateRangeCSVResult',
          ),
          'acheft_payment_box_reject_csv' => array(
            'title' => 'ACH-EFT Payment Box Reject CSV',
            'description' => $desc . 'GetACHEFTRejectDateRangeCSV',
            'method' => 'GetACHEFTRejectDateRangeCSV',
            'message' => 'GetACHEFTRejectDateRangeCSV',
            'response' => 'GetACHEFTRejectDateRangeCSVResult',
          ),
         // 'acheft_reject' => array(
         //   'title' => 'ACH-EFT Reject',
         //   'description'=> $desc. 'GetACHEFTReject',
         //   'method' => 'GetACHEFTReject',
         //   'message' => 'GetACHEFTReject',
         //   'response' => 'GetACHEFTRejectResult',
         // ),.
          'acheft_reject_csv' => array(
            'title' => 'ACH-EFT Reject CSV',
            'description' => $desc . 'GetACHEFTRejectSpecificDateCSV',
            'method' => 'GetACHEFTRejectSpecificDateCSV',
            'message' => 'GetACHEFTRejectSpecificDateCSV',
            'response' => 'GetACHEFTRejectSpecificDateCSVResult',
          ),
        );
        break;

      case 'customer':
        $methods = array(
          'get_customer_code_detail' => array(
            'title' => 'Get Customer Code Detail',
            'description' => $desc . 'GetCustomerCodeDetail',
            'method' => 'GetCustomerCodeDetail',
            'message' => 'GetCustomerCodeDetail',
            'response' => 'GetCustomerCodeDetailResult',
          ),
          'create_credit_card_customer' => array(
            'title' => 'Create CustomerCode Credit Card',
            'description' => $desc . 'CreateCreditCardCustomerCode',
            'method' => 'CreateCreditCardCustomerCode',
            'message' => 'CreateCreditCardCustomerCode',
            'response' => 'CreateCreditCardCustomerCodeResult',
          ),
          'update_credit_card_customer' => array(
            'title' => 'Update CustomerCode Credit Card',
            'description' => $desc . 'UpdateCreditCardCustomerCode',
            'method' => 'UpdateCreditCardCustomerCode',
            'message' => 'UpdateCreditCardCustomerCode',
            'response' => 'UpdateCreditCardCustomerCodeResult',
          ),
          'direct_debit_acheft_payer_validate' => array(
            'title' => 'Direct Debit ACHEFT Payer Validate',
            'description' => $desc . 'DirectDebitACHEFTPayerValidate',
            'method' => 'DirectDebitACHEFTPayerValidate',
            'message' => 'DirectDebitACHEFTPayerValidate',
            'response' => 'DirectDebitACHEFTPayerValidateResult',
          ),
          'create_acheft_customer_code' => array(
            'title' => 'Create ACHEFT Customer Code',
            'description' => $desc . 'CreateACHEFTCustomerCode',
            'method' => 'CreateACHEFTCustomerCode',
            'message' => 'CreateACHEFTCustomerCode',
            'response' => 'CreateACHEFTCustomerCodeResult',
          ),
        );
        break;
    }
    if ($method) {
      return $methods[$method];
    }
    return $methods;
  }

  /**
   * Returns the message text for a credit card service reason code.
   * As per iATS error codes - sent to us by Ryan Creamore
   * TODO: multilingual options?
   */
  public function reasonMessage($code) {
    switch ($code) {

      case 'REJECT: 1':
        return 'Agent code has not been set up on the authorization system. Please call iATS at 1-888-955-5455.';

      case 'REJECT: 2':
        return 'Unable to process transaction. Verify and reenter credit card information.';

      case 'REJECT: 3':
        return 'Invalid customer code.';

      case 'REJECT: 4':
        return 'Incorrect expiry date.';

      case 'REJECT: 5':
        return 'Invalid transaction. Verify and re-enter credit card information.';

      case 'REJECT: 6':
        return 'Please have cardholder call the number on the back of the card.';

      case 'REJECT: 7':
        return 'Lost or stolen card.';

      case 'REJECT: 8':
        return 'Invalid card status.';

      case 'REJECT: 9':
        return 'Restricted card status, usually on corporate cards restricted to specific sales.';

      case 'REJECT: 10':
        return 'Error. Please verify and re-enter credit card information.';

      case 'REJECT: 11':
        return 'General decline code. Please have cardholder call the number on the back of the card.';

      case 'REJECT: 12':
        return 'Incorrect CVV2 or expiry date.';

      case 'REJECT: 14':
        return 'The card is over the limit.';

      case 'REJECT: 15':
        // return 'General decline code. Please have cardholder call the number on the back of the card.';
        return 'General decline code.';

      case 'REJECT: 16':
        return 'Invalid charge card number. Verify and re-enter credit card information.';

      case 'REJECT: 17':
        return 'Unable to authorize transaction. Authorizer needs more information for approval.';

      case 'REJECT: 18':
        return 'Card not supported by institution.';

      case 'REJECT: 19':
        return 'Incorrect CVV2 security code.';

      case 'REJECT: 22':
        return 'Bank timeout.  Bank lines may be down or busy. Retry later.';

      case 'REJECT: 23':
        return 'System error. Retry transaction later.';

      case 'REJECT: 24':
        return 'Charge card expired.';

      case 'REJECT: 25':
        // return 'Capture card. Reported lost or stolen.';
        return 'Possibly reported lost or stolen.';

      case 'REJECT: 26':
        return 'Invalid transaction, invalid expiry date. Please confirm and retry transaction.';

      case 'REJECT: 27':
        return 'Please have cardholder call the number on the back of the card.';

      case 'REJECT: 32':
        return 'Invalid charge card number.';

      case 'REJECT: 39':
        return 'Contact iATS at 1-888-955-5455.';

      case 'REJECT: 40':
        return 'Invalid card number. Card not supported by iATS.';

      case 'REJECT: 41':
        return 'Invalid expiry date.';

      case 'REJECT: 42':
        return 'CVV2 required.';

      case 'REJECT: 43':
        return 'Incorrect AVS.';

      case 'REJECT: 45':
        return 'Credit card name blocked. Call iATS at 1-888-955-5455.';

      case 'REJECT: 46':
        return 'Card tumbling. Call iATS at 1-888-955-5455.';

      case 'REJECT: 47':
        return 'Name tumbling. Call iATS at 1-888-955-5455.';

      case 'REJECT: 48':
        return 'IP blocked. Call iATS at 1-888-955-5455.';

      case 'REJECT: 49':
        return 'Velocity 1 – IP block. Call iATS at 1-888-955-5455.';

      case 'REJECT: 50':
        return 'Velocity 2 – IP block. Call iATS at 1-888-955-5455.';

      case 'REJECT: 51':
        return 'Velocity 3 – IP block. Call iATS at 1-888-955-5455.';

      case 'REJECT: 52':
        return 'Credit card BIN country blocked. Call iATS at 1-888-955-5455.';

      case 'REJECT: 100':
        return 'DO NOT REPROCESS. Call iATS at 1-888-955-5455.';

      case 'Timeout':
        return 'The system has not responded in the time allotted. Call iATS at 1-888-955-5455.';
    }

    return $code;
  }

  /**
   * Returns the message text for a CVV match.
   * This function not currently in use.
   */
  public function cvnResponse($code) {
    switch ($code) {
      case 'D':
        return t('The transaction was determined to be suspicious by the issuing bank.');

      case 'I':
        return t("The CVN failed the processor's data validation check.");

      case 'M':
        return t('The CVN matched.');

      case 'N':
        return t('The CVN did not match.');

      case 'P':
        return t('The CVN was not processed by the processor for an unspecified reason.');

      case 'S':
        return t('The CVN is on the card but was not included in the request.');

      case 'U':
        return t('Card verification is not supported by the issuing bank.');

      case 'X':
        return t('Card verification is not supported by the card association.');

      case '1':
        return t('Card verification is not supported for this processor or card type.');

      case '2':
        return t('An unrecognized result code was returned by the processor for the card verification response.');

      case '3':
        return t('No result code was returned by the processor.');
    }

    return '-';
  }

  /**
   *
   */
  public function creditCardTypes() {
    return array(
      'VI' => t('Visa'),
      'MC' => t('MasterCard'),
      'AMX' => t('American Express'),
      'DSC' => t('Discover Card'),
    );
  }

  /**
   *
   */
  public function mask(&$log_request) {
    // Mask the credit card number and CVV.
    foreach (array('creditCardNum', 'cvv2', 'ccNum') as $mask) {
      if (!empty($log_request[$mask])) {
        // Show the last four digits of cc numbers.
        if (4 < strlen($log_request[$mask])) {
          $log_request[$mask] = str_repeat('X', strlen($log_request[$mask]) - 4) . substr($log_request[$mask], -4);
        }
        else {
          $log_request[$mask] = str_repeat('X', strlen($log_request[$mask]));
        }
      }
    }
  }

  /**
   * When I'm using this object outside of the doDirect payment interface (e.g. a payment from the recurring job), I need to look up the credentials
   * I need to pay attention to whether I should use the test credentials, which is the 'mode' in doDirect payment
   * I also return the url_site value in case I need that.
   */
  public static function credentials($payment_processor_id, $is_test = 0) {
    static $credentials = array();
    if (empty($credentials[$payment_processor_id])) {
      $select = 'SELECT user_name, password, url_site FROM civicrm_payment_processor WHERE id = %1 AND is_test = %2';
      $args = array(
        1 => array($payment_processor_id, 'Int'),
        2 => array($is_test, 'Int'),
      );
      $dao = CRM_Core_DAO::executeQuery($select, $args);
      if ($dao->fetch()) {
        $cred = array(
          'agentCode' => $dao->user_name,
          'password' => $dao->password,
          'domain' => parse_url($dao->url_site, PHP_URL_HOST),
        );
        $credentials[$payment_processor_id] = $cred;
        return $cred;
      }
      return;
    }
    return $credentials[$payment_processor_id];
  }

  /**
   *
   */
  public static function is_ipv4($ip) {
    // The regular expression checks for any number between 0 and 255 beginning with a dot (repeated 3 times)
    // followed by another number between 0 and 255 at the end. The equivalent to an IPv4 address.
    // It does not allow leading zeros [from http://runnable.com/UmrneujI6Q4_AAIW/how-to-validate-an-ipv4-address-using-regular-expressions-for-php-and-pcre]
    return (bool) preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])' .
    '\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])$/', $ip);
  }

  /**
   *
   */
  public static function isDPM($pp) {
    return self::iATS_USE_DPMPROCESS;
  }

  /**
   *
   */
  public static function dpm_url($iats_domain) {
    return 'https://' . $iats_domain . self::iATS_URL_DPMPROCESS;
  }

  /**
   *
   */
  public static function iats_extension_version($reset = 0) {
    $version = $reset ? '' : CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_extension_version');
    if (empty($version)) {
      $xmlfile = CRM_Core_Resources::singleton()->getPath('com.iatspayments.civicrm', 'info.xml');
      $myxml = simplexml_load_file($xmlfile);
      $version = (string) $myxml->version;
      CRM_Core_BAO_Setting::setItem($version, 'iATS Payments Extension', 'iats_extension_version');
    }
    return $version;
  }
  /**
   * function xmlsafe
   *
   * Replacement for using ENT_XML1 with htmlspecialchars for php5.3 compatibility.
   */
  private function xmlsafe($string) {
    if (version_compare(PHP_VERSION, '5.4.0') < 0) {
      $replace = array(
        '"'=> "&quot;",
        "&" => "&amp;",
        "'"=> "&apos;",
        "<" => "&lt;",
        ">"=> "&gt;"
      );
      return strtr($string, $replace);
    }
    // else, better way for php5.4 and above
    return htmlspecialchars($string, ENT_XML1, 'UTF-8');
  }

}
