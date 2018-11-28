<?php

/**
 * Job.IatsReport API specification (optional)
 *
 * Pull in the iATS transaction journal and save it in the corresponding table
 * for local access for easier verification, auditing and reporting.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_iatsreport_spec(&$spec) {
  // no arguments
  // TODO: configure for a date range, report, etc.
}

/**
 * Job.IatsReport API
 *
 * Fetch all recent transactions from iATS for the purposes of auditing (in separate jobs).
 * This addresses multiple needs:
 * 1. Verify incomplete ACH/EFT contributions.
 * 2. Verify recent contributions that went through but weren't reported to CiviCRM due to unexpected connection/code breakage.
 * 3. Input recurring contributions managed by iATS
 * 4. Input one-time contributions that did not go through CiviCRM
 * 5. Audit for remote changes in iATS.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_iatsreport($params) {

  /* get a list of all active/non-test iATS payment processors of any type, quit if there are none */
  /* We'll make sure they are unique from iATS point of view (i.e. distinct agent codes = username) */
  try {
    $result = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 1,
      'class_name' => array('LIKE' => 'Payment_iATSService%'),
      'is_active' => 1,
      'is_test' => 0,
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    throw new API_Exception('Unexpected error getting payment processors: ' . $e->getMessage()); //  . "\n" . $e->getTraceAsString());
  }
  if (empty($result['values'])) {
    return;
  }
  $payment_processors = array();
  foreach ($result['values'] as $payment_processor) {
    $user_name = $payment_processor['user_name'];
    $type = $payment_processor['payment_type']; // 1 for cc, 2 for ach/eft
    $id = $payment_processor['id'];
    if (empty($payment_processors[$user_name])) {
      $payment_processors[$user_name] = array();
    }
    if (empty($payment_processors[$user_name][$type])) {
      $payment_processors[$user_name][$type] = array();
    }
    $payment_processors[$user_name][$type][$id] = $payment_processor;
  }
  // CRM_Core_Error::debug_var('Payment Processors', $payment_processors);
  // get the settings: TODO allow more detailed configuration of which transactions to import?
  $iats_settings = CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_settings');
  // I also use the setttings to keep track of the last time I imported journal data from iATS.
  $iats_journal = CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_journal');
  foreach (array('quick', 'recur', 'series') as $setting) {
    $import[$setting] = empty($iats_settings['import_' . $setting]) ? 0 : 1;
  }
  require_once "CRM/iATS/iATSService.php";
  // an array of types => methods => payment status of the records retrieved
  $process_methods = array(
    1 => array('cc_journal_csv' => 1, 'cc_payment_box_journal_csv' => 1, 'cc_payment_box_reject_csv' => 4),
    2 => array('acheft_journal_csv' => 1, 'acheft_payment_box_journal_csv' => 1, 'acheft_payment_box_reject_csv' => 4),
  );
  /* initialize some values so I can report at the end */
  // count the number of records from each iats account analysed, and the number of each kind found ('action')
  $processed = array();
  // save all my api result error messages as well
  $error_log = array();
  foreach ($payment_processors as $user_name => $payment_processors_per_user) {
    $processed[$user_name] = array();
    foreach ($payment_processors_per_user as $type => $payment_processors_per_user_type) {
      $processed[$user_name][$type] = array();
      // we might have multiple payment processors by type e.g. SWIPE or separate codes for
      // one-time and recurring contributions, I only want to process once per user_name + type
      $payment_processor = reset($payment_processors_per_user_type);
      $process_methods_per_type = $process_methods[$type];
      $iats_service_params = array('type' => 'report', 'iats_domain' => parse_url($payment_processor['url_site'], PHP_URL_HOST)); // + $iats_service_params;
      /* the is_test below should always be 0, but I'm leaving it in, in case eventually we want to be verifying tests */
      $credentials = iATS_Service_Request::credentials($payment_processor['id'], $payment_processor['is_test']);

      foreach ($process_methods_per_type as $method => $payment_status_id) {
        // initialize my counts
        $processed[$user_name][$type][$method] = 0;
        // watchdog('civicrm_iatspayments_com', 'pp: <pre>!pp</pre>', array('!pp' => print_r($payment_processor,TRUE)), WATCHDOG_NOTICE);
        /* get approvals from yesterday, approvals from previous days, and then rejections for this payment processor */
        /* we're going to assume that all the payment_processors_per_type are using the same server */
        $iats_service_params['method'] = $method;
        $iats = new iATS_Service_Request($iats_service_params);
        // For some methods, I only want to check once per day.
        $skip_method = FALSE;
        $journal_setting_key = 'last_update_' . $method;
        switch ($method) {
          case 'acheft_journal_csv': // special case to get today's transactions, so we're as real-time as we can be
          case 'cc_journal_csv':
            $request = array(
              'date' => date('Y-m-d') . 'T23:59:59+00:00',
              'customerIPAddress' => (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']),
            );
            break;

          default:
            // box journals (approvals and rejections) only go up to the end of yesterday
            $request = array(
              'startIndex' => 0,
              'endIndex' => 1000,
              'toDate' => date('Y-m-d', strtotime('-1 day')) . 'T23:59:59+00:00',
              'customerIPAddress' => (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']),
            );
            // Calculate how far back I want to go, default 2 days ago.
            $fromDate = strtotime('-2 days');
            // Check when I last downloaded this box journal
            if (!empty($iats_journal[$journal_setting_key])) {
              // If I've already done this today, don't do it again.
              if (0 === strpos($iats_journal[$journal_setting_key], date('Y-m-d'))) {
                $skip_method = TRUE;
              }
              else {
                // Make sure I fill in any gaps if this cron hasn't run for a while, but no more than a month
                $fromDate = min(strtotime($iats_journal[$journal_setting_key]), strtotime('-2 days'));
                $fromDate = max($fromDate, strtotime('-30 days'));
              }
            }
            else {
              // If I've cleared the settings, then go back a month of data.
              $fromDate = strtotime('-30 days');
            }
            // reset the request fromDate, from the beginning of fromDate's day.
            $request['fromDate'] = date('Y-m-d', $fromDate) . 'T00:00:00+00:00';
            break;
        }
        if (!$skip_method) {
          $iats_journal[$journal_setting_key] = date('Y-m-d H:i:s');
          // make the soap request, should return a csv file
          $response = $iats->request($credentials, $request);
          // use my iats object to parse the result into an array of transaction ojects
          $transactions = $iats->getCSV($response, $method);
          // for the acheft journal, I also pull the previous 4 days and append, a bit of a hack.
          if ('acheft_journal_csv' == $method) {
            for ($days_before = -1; $days_before > -5; $days_before--) {
              $request['date'] = date('Y-m-d', strtotime($days_before . ' day')) . 'T23:59:59+00:00';
              $response = $iats->request($credentials, $request);
              $transactions = array_merge($transactions, $iats->getCSV($response, $method));
            }
          }
          // CRM_Core_Error::debug_var($method, $transactions);
          foreach ($transactions as $transaction) {
            try {
              $t = get_object_vars($transaction);
              $t['status_id'] = $payment_status_id;
              // A few more hacks for the one day journals
              switch ($method) {
                case 'acheft_journal_csv':
                  $t['data']['Method of Payment'] = 'ACHEFT';
                  $t['data']['Client Code'] = $credentials['agentCode'];
                  break;

                case 'cc_journal_csv':
                  $t['data']['Method of Payment'] = $t['data']['CCType'];
                  $t['data']['Client Code'] = $credentials['agentCode'];
                  break;

              }
              civicrm_api3('IatsPayments', 'journal', $t);
              $processed[$user_name][$type][$method]++;
            }
            catch (CiviCRM_API3_Exception $e) {
              $error_log[] = $e->getMessage();
            }
          }
        }
      }
    }
  }
  CRM_Core_BAO_Setting::setItem($iats_journal, 'iATS Payments Extension', 'iats_journal');
  // watchdog('civicrm_iatspayments_com', 'found: <pre>!found</pre>', array('!found' => print_r($processed,TRUE)), WATCHDOG_NOTICE);
  $message = '';
  foreach ($processed as $user_name => $p) {
    foreach ($p as $type => $ps) {
      $prefix = ($type == 1) ? 'cc' : 'acheft';
      $results
        = array(
          1 => $user_name,
          2 => $prefix,
          3 => $ps[$prefix . '_journal_csv'],
          4 => $ps[$prefix . '_payment_box_journal_csv'],
          5 => $ps[$prefix . '_payment_box_reject_csv'],
        );
      $message .= '<br />' . ts('For account %1, type %2, processed %3 approvals from the one-day journals, and %4 approval and %5 rejection records from previous days using the box journals.', $results);
    }
  }
  if (count($error_log) > 0) {
    return civicrm_api3_create_error($message . '</br />' . implode('<br />', $error_log));
  }
  return civicrm_api3_create_success($message);
}
