<?php
/**
 * @file IATS Service transaction utility class
 *
 * Various functions that used to live in the iats.php file,
 * now converted into static functions of this class and generalised
 * to work with both legacy and FAP processors.
 *
 **/

/**
 * Class CRM_Iats_Transaction
 */
class CRM_Iats_Transaction {

  /**
   * For a recurring contribution, find a reasonable candidate for a template, where possible.
   */
  static function getContributionTemplate($contribution) {
    // Get the most recent contribution in this series that matches the same total_amount, if present.
    $template = array();
    $get = ['contribution_recur_id' => $contribution['contribution_recur_id'], 'options' => ['sort' => ' id DESC', 'limit' => 1]];
    if (!empty($contribution['total_amount'])) {
      $get['total_amount'] = $contribution['total_amount'];
    }
    $result = civicrm_api3('contribution', 'get', $get);
    if (!empty($result['values'])) {
      $template = reset($result['values']);
      $contribution_id = $template['id'];
      $template['original_contribution_id'] = $contribution_id;
      $template['line_items'] = array();
      $get = array('entity_table' => 'civicrm_contribution', 'entity_id' => $contribution_id);
      $result = civicrm_api3('LineItem', 'get', $get);
      if (!empty($result['values'])) {
        foreach ($result['values'] as $initial_line_item) {
          $line_item = array();
          foreach (array('price_field_id', 'qty', 'line_total', 'unit_price', 'label', 'price_field_value_id', 'financial_type_id') as $key) {
            $line_item[$key] = $initial_line_item[$key];
          }
          $template['line_items'][] = $line_item;
        }
      }
    }
    return $template;
  }
  
  /**
   * Function contributionrecur_next.
   *
   * @param $from_time: a unix time stamp, the function returns values greater than this
   * @param $days: an array of allowable days of the month
   *
   *   A utility function to calculate the next available allowable day, starting from $from_time.
   *   Strategy: increment the from_time by one day until the day of the month matches one of my available days of the month.
   */
  static function contributionrecur_next($from_time, $allow_mdays) {
    $dp = getdate($from_time);
    // So I don't get into an infinite loop somehow.
    $i = 0;
    while (($i++ < 60) && !in_array($dp['mday'], $allow_mdays)) {
      $from_time += (24 * 60 * 60);
      $dp = getdate($from_time);
    }
    return $from_time;
  }
  
  /**
   * Function contribution_payment
   *
   * @param $contribution an array of a contribution to be created (or in case of future start date,
            possibly an existing pending contribution to recycle, if it already has a contribution id).
   * @param $paymentProcessor an array of a payment processor record to use
   * @param $payment_token an array of a payment processor specific token data
   *        code
   *
   *   A high-level utility function for making a contribution payment from an existing recurring schedule
   *   Used in the Iatsrecurringcontributions.php job and the one-time ('card on file') form.
   *   
   */
  static function process_contribution_payment(&$contribution, $paymentProcessor, $payment_token) {
    // By default, don't use repeattransaction
    $use_repeattransaction = FALSE;
    $is_recurrence = !empty($contribution['original_contribution_id']);
    // First try and get the money, using my process_payment cover function.
    $payment_result = self::process_payment($contribution, $paymentProcessor, $payment_token);
    $success = $payment_result['success'];
    $auth_code = $payment_result['auth_code'];
    $auth_response = $payment_result['auth_response'];
    $trxn_id = $payment_result['trxn_id'];
    // Handle any case of a failure of some kind, either the card failed, or the system failed.
    if (!$success) {
      $error_message = $payment_result['message'];
      /* set the failed transaction status, or pending if I had a server issue */
      $contribution['contribution_status_id'] = empty($auth_code) ? 2 : 4;
      /* and include the reason in the source field */
      $contribution['source'] .= ' ' . $error_message;
    }
    else {
      // I have a transaction id.
      $contribution['trxn_id'] = $trxn_id;
      // Initialize the status to pending
      $contribution['contribution_status_id'] = 2;
      // We'll use the repeattransaction api for successful transactions under two conditions:
      // 1. if we want it (i.e. if it's for a recurring schedule)
      // 2. if we don't already have a contribution id
      $use_repeattransaction = $is_recurrence && empty($contribution['id']);
    }
    if ($use_repeattransaction) {
      // We processed it successflly and I can try to use repeattransaction. 
      // Requires the original contribution id.
      // Issues with this api call:
      // 1. Always triggers an email [update: not anymore?] and doesn't include trxn.
      // 2. Date is wrong.
      try {
        // $status = $result['contribution_status_id'] == 1 ? 'Completed' : 'Pending';
        $contributionResult = civicrm_api3('Contribution', 'repeattransaction', array(
          'original_contribution_id' => $contribution['original_contribution_id'],
          'contribution_status_id' => 'Pending',
          'is_email_receipt' => 0,
          // 'invoice_id' => $contribution['invoice_id'],
          ///'receive_date' => $contribution['receive_date'],
          // 'campaign_id' => $contribution['campaign_id'],
          // 'financial_type_id' => $contribution['financial_type_id'],.
          // 'payment_processor_id' => $contribution['payment_processor'],
          'contribution_recur_id' => $contribution['contribution_recur_id'],
        ));
        // watchdog('iats_civicrm','repeat transaction result <pre>@params</pre>',array('@params' => print_r($pending,TRUE)));.
        $contribution['id'] = CRM_Utils_Array::value('id', $contributionResult);
      }
      catch (Exception $e) {
        // Ignore this, though perhaps I should log it.
      }
      if (empty($contribution['id'])) {
        // Assume I failed completely and I'll fall back to doing it the manual way.
        $use_repeattransaction = FALSE;
      }
      else {
        // If repeattransaction succeded.
        // First restore/add various fields that the repeattransaction api may overwrite or ignore.
        // TODO - fix this in core to allow these to be set above.
        civicrm_api3('contribution', 'create', array('id' => $contribution['id'], 
          'invoice_id' => $contribution['invoice_id'],
          'source' => $contribution['source'],
          'receive_date' => $contribution['receive_date'],
          'payment_instrument_id' => $contribution['payment_instrument_id'],
          // '' => $contribution['receive_date'],
        ));
        // Save my status in the contribution array that was passed in.
        $contribution['contribution_status_id'] = $payment_result['payment_status_id'];
        if ($contribution['contribution_status_id'] == 1) {
          // My transaction completed, so record that fact in CiviCRM, potentially sending an invoice.
          try {
            civicrm_api3('Contribution', 'completetransaction', array(
              'id' => $contribution['id'],
              'payment_processor_id' => $contribution['payment_processor'],
              'is_email_receipt' => (empty($contribution['is_email_receipt']) ? 0 : 1),
              'trxn_id' => $contribution['trxn_id'],
              'receive_date' => $contribution['receive_date'],
            ));
          }
          catch (Exception $e) {
            // log the error and continue
            CRM_Core_Error::debug_var('Unexpected Exception', $e);
          }
        }
        else {
          // just save my trxn_id for ACH verification later
          try {
            civicrm_api3('Contribution', 'create', array(
              'id' => $contribution['id'],
              'trxn_id' => $contribution['trxn_id'],
            ));
          }
          catch (Exception $e) {
            // log the error and continue
            CRM_Core_Error::debug_var('Unexpected Exception', $e);
          }
        }
      }
    }
    if (!$use_repeattransaction) {
      /* If I'm not using repeattransaction for any reason, I'll create the contribution manually */
      // This code assumes that the contribution_status_id has been set properly above, either pending or failed.
      $contributionResult = civicrm_api3('contribution', 'create', $contribution);
      // Pass back the created id indirectly since I'm calling by reference.
      $contribution['id'] = CRM_Utils_Array::value('id', $contributionResult);
      // Connect to a membership if requested.
      if (!empty($contribution['membership_id'])) {
        try {
          civicrm_api3('MembershipPayment', 'create', array('contribution_id' => $contribution['id'], 'membership_id' => $contribution['membership_id']));
        }
        catch (Exception $e) {
          // Ignore.
        }
      }
      /* And then I'm done unless it completed */
      if ($payment_result['payment_status_id'] == 1 && $success) {
        /* success, and the transaction has completed */
        $complete = array('id' => $contribution['id'], 
          'payment_processor_id' => $contribution['payment_processor'],
          'trxn_id' => $trxn_id, 
          'receive_date' => $contribution['receive_date']
        );
        $complete['is_email_receipt'] = empty($contribution['is_email_receipt']) ? 0 : 1;
        try {
          $contributionResult = civicrm_api3('contribution', 'completetransaction', $complete);
        }
        catch (Exception $e) {
          // Don't throw an exception here, or else I won't have updated my next contribution date for example.
          $contribution['source'] .= ' [with unexpected api.completetransaction error: ' . $e->getMessage() . ']';
        }
        // Restore my source field that ipn code irritatingly overwrites, and make sure that the trxn_id is set also.
        civicrm_api3('contribution', 'setvalue', array('id' => $contribution['id'], 'value' => $contribution['source'], 'field' => 'source'));
        civicrm_api3('contribution', 'setvalue', array('id' => $contribution['id'], 'value' => $trxn_id, 'field' => 'trxn_id'));
        // $message = $is_recurrence ? ts('Successfully processed contribution in recurring series id %1: ', array(1 => $contribution['contribution_recur_id'])) : ts('Successfully processed one-time contribution: ');
      }
    }
    // Now return the appropriate message and code.
    if (!$success) { // calling function will restore next schedule contribution date
      $message = ts('Failed to process recurring contribution id %1: %2', array(1 => $contribution['contribution_recur_id'], 2 => $payment_result['message']));
    }
    elseif ($payment_result['payment_status_id'] == 1) {
      $message = ts('Successfully processed recurring contribution in series id %1: %2', array(1 => $contribution['contribution_recur_id'], 2 => $auth_response));
    }
    else {
      // I'm using ACH or a processor that doesn't complete.
      $message = ts('Successfully processed pending recurring contribution in series id %1: %2', array(1 => $contribution['contribution_recur_id'], 2 => $auth_response));
    }
    return array('message' => $message, 'result' => $payment_result);
  }

  /**
   * Function process_payment
   *
   * @param $contribution an array of properties of a contribution to be processed
   * @param $paymentProcessor an array of a payment processor record
   * @param $payment_token an array of a payment processor specific values for this
   *        transaction, e.g. client or vault code
   *
   * return an array of return values
   *   success boolean
   *   trxn_id transaction id to store in civicrm
   *   payment_status_id payment status id to store in case of success
   *   auth_code authorization code returned - if empty, then it's a server
   *   failure
   *   result  raw payment processor-dependent array/object
   *
   *   A low-level utility function for triggering a payment transaction on iATS using a card on file.
   */
  static function process_payment($contribution, $paymentProcessor, $payment_token) {
    // set default result status
    $result = [
      'payment_status_id' => 1,
      'auth_code' => '',
    ];
    $request = [
    ];
    switch ($paymentProcessor['class_name']) {
      case 'Payment_FapsACH':
        $paymentProcessorGroup = 'Faps';
        $action = 'AchDebitUsingVault';
        // Will complete later
        $result['payment_status_id'] = 2;
        // store it in request 
        $credentials = array(
          'merchantKey' => $paymentProcessor['signature'],
          'processorId' => $paymentProcessor['user_name']
        );
        $request['categoryText'] = CRM_Core_Payment_FapsACH::getCategoryText($credentials, $contribution['is_test']);
        break;
      case 'Payment_Faps':
        $paymentProcessorGroup = 'Faps';
        $action = 'SaleUsingVault';
        $credentials = array(
          'merchantKey' => $paymentProcessor['signature'],
          'processorId' => $paymentProcessor['user_name']
        );
        break;
      case 'Payment_iATSServiceACHEFT':
        $paymentProcessorGroup = 'iATS';
        $method = 'acheft_with_customer_code';
        // Will complete later.
        $result['payment_status_id'] = 2;
        break;
      case 'Payment_iATSService':
      case 'Payment_iATSServiceSWIPE':
        $paymentProcessorGroup = 'iATS';
        $method = 'cc_with_customer_code';
        break;
      default:
        CRM_Core_Error::debug_var('Unsupported processor class:', $paymentProcessor['class_name']);
        throw new Exception(ts('Unsupported processor class %1', array(1 => $paymentProcessor['class_name'])));
    }

    // Two different "group" flows, either Faps or iATS Legacy
    switch ($paymentProcessorGroup) {
      case 'Faps':
        $service_params = array('action' => $action);
        $faps = new CRM_Iats_FapsRequest($service_params);
        // Build the request array.
        // CRM_Core_Error::debug_var('options', $options);
        // TODO: Get the vault key!
        list($vaultKey,$vaultId) = explode(':', $payment_token['token'], 2);
        $request = $request + array(
          'vaultKey' => $vaultKey,
          'vaultId' => $vaultId,
          'orderId' => $contribution['invoice_id'],
          'transactionAmount' => sprintf('%01.2f', CRM_Utils_Rule::cleanMoney($contribution['total_amount'])),
        );
        // Make the request.
        // CRM_Core_Error::debug_var('process transaction request', $request);
        $result['result'] = $faps->request($credentials, $request);
        $data = empty($result['result']['data']) ? [] : $result['result']['data'];
        // CRM_Core_Error::debug_var('process transaction result', $result);
        $result['success'] = !empty($result['result']['isSuccess']);
        if ($result['success']) {
          $result['trxn_id'] = empty($data['referenceNumber']) ? '' : trim($data['referenceNumber']).':'.time();
          $result['auth_code'] = empty($data['authCode']) ? '' : trim($data['authCode']);
          $result['message'] = $result['auth_response'] = empty($data['authResponse']) ? '' : trim($data['authResponse']);
        }
        else {
          $result['message'] = $result['result']['errorMessages'];
        }
        /* in case of critical failure set the series to pending */
        switch ($result['auth_code']) {
          // Reported lost or stolen.
          case 'REJECT: 25':
            // Do not reprocess!
          case 'REJECT: 100':
            /* convert the contribution series to pending to avoid reprocessing until dealt with */
            civicrm_api('ContributionRecur', 'create',
              array(
                'version' => 3,
                'id'      => $contribution['contribution_recur_id'],
                'contribution_status_id'   => 'Pending',
              )
            );
            break;
        }
        break;
      case 'iATS':
        $credentials = array(
          'agentCode' => $paymentProcessor['user_name'],
          'password' => $paymentProcessor['password'],
          'domain' => parse_url($paymentProcessor['url_site'], PHP_URL_HOST),
        );
        $iats_service_params = array('method' => $method, 'type' => 'process', 'iats_domain' => $credentials['domain']);
        $iats = new CRM_Iats_iATSServiceRequest($iats_service_params);
        // Build the request array.
        $request = array(
          'customerCode' => $payment_token['token'],
          'invoiceNum' => $contribution['invoice_id'],
          'total' => $contribution['total_amount'],
          'customerIPAddress' => '',
        );
        // Make the soap request.
        $response = $iats->request($credentials, $request);
        // Process the soap response into a readable result.
        $result['result'] = $iats->result($response);
        $result['success'] = !empty($result['result']['status']);
        if ($result['success']) {
          $result['trxn_id'] = trim($result['result']['remote_id']) . ':' . time();
          $result['message'] = $result['auth_code'] = $result['result']['auth_result'];
        }
        else {
          $result['message'] = $result['result']['reasonMessage'];
        }
        break;
      default:
        CRM_Core_Error::debug_var('Unsupported processor group:', $paymentProcessorGroup);
        throw new Exception(ts('Unsupported processor group %1', array(1 => $paymentProcessorGroup)));
    }
    return $result;
  }
  
  /**
   * Function get_future_start_dates
   *
   * @param $start_date a timestamp, only return dates after this.
   * @param $allow_days an array of allowable days of the month.
   *
   *   A low-level utility function for triggering a transaction on iATS.
   */
  static function get_future_monthly_start_dates($start_date, $allow_days) {
    // Future date options.
    $start_dates = array();
    // special handling for today - it means immediately or now.
    $today = date('Ymd').'030000';
    // If not set, only allow for the first 28 days of the month.
    if (max($allow_days) <= 0) {
      $allow_days = range(1,28);
    }
    for ($j = 0; $j < count($allow_days); $j++) {
      // So I don't get into an infinite loop somehow ..
      $i = 0;
      $dp = getdate($start_date);
      while (($i++ < 60) && !in_array($dp['mday'], $allow_days)) {
        $start_date += (24 * 60 * 60);
        $dp = getdate($start_date);
      }
      $key = date('Ymd', $start_date).'030000';
      if ($key == $today) { // special handling
        $display = ts('Now');
        $key = ''; // date('YmdHis');
      }
      else {
        $display = strftime('%B %e, %Y', $start_date);
      }
      $start_dates[$key] = $display;
      $start_date += (24 * 60 * 60);
    }
    return $start_dates;
  }
}
