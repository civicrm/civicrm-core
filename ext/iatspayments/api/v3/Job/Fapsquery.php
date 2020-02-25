<?php

/**
 * Job.FapsQuery API specification (optional)
 *
 * Pull in the iATS/FAPS transaction journal and save it in the corresponding table
 * for local access for easier verification, auditing and reporting.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_fapsquery_spec(&$spec) {
  // no arguments
  // TODO: configure for a date range, report, etc.
}

/**
 * Job.FapsQuery API
 *
 * Fetch all recent transactions from iATS/FAPS for the purposes of auditing (in separate jobs).
 * This addresses multiple needs:
 * 1. Verify incomplete ACH/EFT contributions.
 * 2. Verify recent contributions that went through but weren't reported to CiviCRM due to unexpected connection/code breakage.
 * 3. Input recurring contributions managed by iATS/FAPS
 * 4. Input one-time contributions that did not go through CiviCRM
 * 5. Audit for remote changes in iATS/FAPS.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_fapsquery($params) {

  /* get a list of all active/non-test iATS/FAPS payment processors of any type, quit if there are none */
  /* We'll make sure they are unique from iATS/FAPS point of view (i.e. distinct processorId = username) */
  try {
    $result = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 1,
      'class_name' => array('LIKE' => 'Payment_FAPS%'),
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
  $iats_settings = Civi::settings()->get('iats_settings');
  // I also use the settings to keep track of the last time I imported journal data from iATS/FAPS.
  $iats_faps_journal = Civi::settings()->get('iats_faps_journal');
  /* initialize some values so I can report at the end */
  // count the number of records from each iats account analysed, and the number of each kind found ('action')
  $processed = array();
  // save all my api result error messages as well
  $error_log = array();
  foreach ($payment_processors as $user_name => $payment_processors_per_user) {
    $processed[$user_name] = array();
    foreach ($payment_processors_per_user as $type => $payment_processors_per_user_type) {
      // we might have multiple civi payment processors by type e.g. separate codes for
      // one-time and recurring contributions, I only want to process once per user_name + type
      $payment_processor = reset($payment_processors_per_user_type);
      $options = array(
       'action' => 'Query'
      );
      $credentials = array(
        'merchantKey' => $payment_processor['signature'],
        'processorId' => $payment_processor['user_name']
      );
      // unlike iATS legacy, we only have one method and set each contribution's status based on result instead.
      // initialize my counts
      $processed[$user_name][$type] = 0;
      // watchdog('civicrm_iatspayments_com', 'pp: <pre>!pp</pre>', array('!pp' => print_r($payment_processor,TRUE)), WATCHDOG_NOTICE);
      $query_request = new CRM_Iats_FapsRequest($options);
      $request = array(
        // 'toDate' => date('Y-m-d', strtotime('-1 day')) . 'T23:59:59+00:00',
        // 'customerIPAddress' => (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']),
      );
      // Calculate how far back I want to go, default 2 days ago.
      $fromDate = strtotime('-2 days');
      // Check when I last downloaded this box journal
      if (!empty($iats_faps_journal)) {
        // Make sure I fill in any gaps if this cron hasn't run for a while, but no more than a month
        $fromDate = min(strtotime($iats_faps_journal), strtotime('-2 days'));
        $fromDate = max($fromDate, strtotime('-30 days'));
      }
      else {
        // If I've cleared the settings, then go back a month of data.
        $fromDate = strtotime('-30 days');
      }
      // reset the request fromDate, from the beginning of fromDate's day.
      $request['queryStartDay'] = date('d', $fromDate);
      $request['queryStartMonth'] = date('m', $fromDate);
      $request['queryStartYear'] = date('Y', $fromDate);
      // TODO: should I set the timezone?
      $result = $query_request->request($credentials, $request);
      // CRM_Core_Error::debug_var('result', $result);
      // convert the result into transactions and then write to the journal table
      // via the api
      $transactions = $result['isSuccess'] ? $result['data']['orders'] : array();
      foreach ($transactions as $transaction) {
        try {
          $transaction['currency'] = ''; // unknown, but should be retrievable from processor information
          $transaction['processorId'] = $user_name;
          civicrm_api3('FapsTransaction', 'journal', $transaction);
          $processed[$user_name][$type]++;
        }
        catch (CiviCRM_API3_Exception $e) {
          $error_log[] = $e->getMessage();
        }
      }
    }
  }
  // record the current date into the settings for next time.
  $iats_faps_journal = date('c'); // ISO 8601
  Civi::settings()->set('iats_faps_journal', $iats_faps_journal);
  $message = '';
  foreach ($processed as $user_name => $p) {
    foreach ($p as $type_id => $count) {
      $type = ($type_id == 1) ? 'cc' : 'acheft';
      $results = array(
        1 => $user_name,
        2 => $type,
        3 => $count
      );
      $message .= '<br />' . ts('For account %1, type %2, retreived %3 transactions.', $results);
    }
  }
  if (count($error_log) > 0) {
    return civicrm_api3_create_error($message . '</br />' . implode('<br />', $error_log));
  }
  return civicrm_api3_create_success($message);
}
