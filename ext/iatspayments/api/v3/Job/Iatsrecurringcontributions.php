<?php

/**
 * @file
 */

/**
 * Job.iATSRecurringContributions API specification.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */

/**
 *
 */
function _civicrm_api3_job_iatsrecurringcontributions_spec(&$spec) {
  $spec['recur_id'] = array(
    'name' => 'recur_id',
    'title' => 'Recurring payment id',
    'api.required' => 0,
    'type' => 1,
  );
  $spec['cycle_day'] = array(
    'name' => 'cycle_day',
    'title' => 'Only contributions that match a specific cycle day.',
    'api.required' => 0,
    'type' => 1,
  );
  $spec['failure_count'] = array(
    'name' => 'failure_count',
    'title' => 'Filter by number of failure counts',
    'api.required' => 0,
    'type' => 1,
  );
  $spec['catchup'] = array(
    'title' => 'Process as if in the past to catch up.',
    'api.required' => 0,
  );
  $spec['ignoremembership'] = array(
    'title' => 'Ignore memberships',
    'api.required' => 0,
  );
}

/**
 * Job.iATSRecurringContributions API.
 *
 * @param array $params
 *
 * @return array API result descriptor
 *
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 *
 * @throws API_Exception
 */
function civicrm_api3_job_iatsrecurringcontributions($params) {
  // Running this job in parallell could generate bad duplicate contributions.
  $lock = new CRM_Core_Lock('civicrm.job.IatsRecurringContributions');

  if (!$lock->acquire()) {
    return civicrm_api3_create_success(ts('Failed to acquire lock. No contribution records were processed.'));
  }
  $catchup = !empty($params['catchup']);
  unset($params['catchup']);
  $domemberships = empty($params['ignoremembership']);
  unset($params['ignoremembership']);

  // TODO: what kind of extra security do we want or need here to prevent it from being triggered inappropriately? Or does it matter?
  // The next scheduled contribution date field name is civicrm version dependent.
  define('IATS_CIVICRM_NSCD_FID', _iats_civicrm_nscd_fid());
  // $config = &CRM_Core_Config::singleton();
  // $debug  = false;
  // do my calculations based on yyyymmddhhmmss representation of the time
  // not sure about time-zone issues.
  $dtCurrentDay    = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  $dtCurrentDayStart = $dtCurrentDay . "000000";
  $dtCurrentDayEnd   = $dtCurrentDay . "235959";
  $expiry_limit = date('ym');
  // Restrict this method of recurring contribution processing to only these three payment processors.
  $args = array(
    1 => array('Payment_iATSService', 'String'),
    2 => array('Payment_iATSServiceACHEFT', 'String'),
    3 => array('Payment_iATSServiceSWIPE', 'String'),
  );
  // Before triggering payments, we need to do some housekeeping of the civicrm_contribution_recur records.
  // First update the end_date and then the complete/in-progress values.
  // We do this both to fix any failed settings previously, and also
  // to deal with the possibility that the settings for the number of payments (installments) for an existing record has changed.
  // First check for recur end date values on non-open-ended recurring contribution records that are either complete or in-progress.
  $select = 'SELECT cr.id, count(c.id) AS installments_done, cr.installments, cr.end_date, NOW() as test_now
      FROM civicrm_contribution_recur cr
      INNER JOIN civicrm_contribution c ON cr.id = c.contribution_recur_id
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      WHERE
        (pp.class_name = %1 OR pp.class_name = %2 OR pp.class_name = %3)
        AND (cr.installments > 0)
        AND (cr.contribution_status_id IN (1,5))
        AND (c.contribution_status_id IN (1,2))
      GROUP BY c.contribution_recur_id';
  $dao = CRM_Core_DAO::executeQuery($select, $args);
  while ($dao->fetch()) {
    // Check for end dates that should be unset because I haven't finished
    // at least one more installment todo.
    if ($dao->installments_done < $dao->installments) {
      // Unset the end_date.
      if (($dao->end_date > 0) && ($dao->end_date <= $dao->test_now)) {
        $update = 'UPDATE civicrm_contribution_recur SET end_date = NULL, contribution_status_id = 5 WHERE id = %1';
        CRM_Core_DAO::executeQuery($update, array(1 => array($dao->id, 'Int')));
      }
    }
    // otherwise, check if my end date should be set to the past because I have finished
    // I'm done with installments.
    elseif ($dao->installments_done >= $dao->installments) {
      if (empty($dao->end_date) || ($dao->end_date >= $dao->test_now)) {
        // This interval complete, set the end_date to an hour ago.
        $update = 'UPDATE civicrm_contribution_recur SET end_date = DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE id = %1';
        CRM_Core_DAO::executeQuery($update, array(1 => array($dao->id, 'Int')));
      }
    }
  }
  // Second, make sure any open-ended recurring contributions have no end date set.
  $update = 'UPDATE civicrm_contribution_recur cr
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      SET
        cr.end_date = NULL
      WHERE
        cr.contribution_status_id IN (1,5)
        AND NOT(cr.installments > 0)
        AND (pp.class_name = %1 OR pp.class_name = %2 OR pp.class_name = %3)
        AND NOT(ISNULL(cr.end_date))';
  $dao = CRM_Core_DAO::executeQuery($update, $args);

  // Third, we update the status_id of the all in-progress or completed recurring contribution records
  // Unexpire uncompleted cycles.
  $update = 'UPDATE civicrm_contribution_recur cr
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      SET
        cr.contribution_status_id = 5
      WHERE
        cr.contribution_status_id = 1
        AND (pp.class_name = %1 OR pp.class_name = %2 OR pp.class_name = %3)
        AND (cr.end_date IS NULL OR cr.end_date > NOW())';
  $dao = CRM_Core_DAO::executeQuery($update, $args);
  // Expire or badly-defined completed cycles.
  $update = 'UPDATE civicrm_contribution_recur cr
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      SET
        cr.contribution_status_id = 1
      WHERE
        cr.contribution_status_id = 5
        AND (pp.class_name = %1 OR pp.class_name = %2 OR pp.class_name = %3)
        AND (
          (NOT(cr.end_date IS NULL) AND cr.end_date <= NOW())
          OR
          ISNULL(cr.frequency_unit)
          OR
          (frequency_interval = 0)
        )';
  $dao = CRM_Core_DAO::executeQuery($update, $args);

  // Now we're ready to trigger payments
  // Select the ongoing recurring payments for iATSServices where the next scheduled contribution date (NSCD) is before the end of of the current day.
  $select = 'SELECT cr.*, icc.customer_code, icc.expiry as icc_expiry, icc.cid as icc_contact_id, pp.class_name as pp_class_name, pp.url_site as url_site, pp.is_test
      FROM civicrm_contribution_recur cr
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      INNER JOIN civicrm_iats_customer_codes icc ON cr.id = icc.recur_id
      WHERE
        cr.contribution_status_id = 5
        AND (pp.class_name = %1 OR pp.class_name = %2 OR pp.class_name = %3)';
  // AND pp.is_test = 0
  // in case the job was called to execute a specific recurring contribution id -- not yet implemented!
  if (!empty($params['recur_id'])) {
    $select .= ' AND icc.recur_id = %4';
    $args[4] = array($params['recur_id'], 'Int');
  }
  // If (!empty($params['scheduled'])) {.
  else {
    // normally, process all recurring contributions due today or earlier.
    $select .= ' AND cr.' . IATS_CIVICRM_NSCD_FID . ' <= %4';
    $args[4] = array($dtCurrentDayEnd, 'String');
    // ' AND cr.next_sched_contribution >= %2
    // $args[2] = array($dtCurrentDayStart, 'String');
    // also filter by cycle day.
    if (!empty($params['cycle_day'])) {
      $select .= ' AND cr.cycle_day = %5';
      $args[5] = array($params['cycle_day'], 'Int');
    }
    // Also filter by cycle day.
    if (isset($params['failure_count'])) {
      $select .= ' AND cr.failure_count = %6';
      $args[6] = array($params['failure_count'], 'Int');
    }
  }
  $dao = CRM_Core_DAO::executeQuery($select, $args);
  $counter = 0;
  $error_count  = 0;
  $output  = array();
  $settings = CRM_Core_BAO_Setting::getItem('iATS Payments Extension', 'iats_settings');
  $receipt_recurring = $settings['receipt_recurring'];
  $email_failure_report = empty($settings['email_recurring_failure_report']) ? '' : $settings['email_recurring_failure_report'];
  // By default, after 3 failures move the next scheduled contribution date forward.
  $failure_threshhold = empty($settings['recurring_failure_threshhold']) ? 3 : (int) $settings['recurring_failure_threshhold'];

  /* while ($dao->fetch()) {
  foreach($dao as $key => $value) {
  echo "$value,";
  }
  echo "\n";
  }
  die();  */
  $failure_report_text = '';
  while ($dao->fetch()) {

    // Strategy: create the contribution record with status = 2 (= pending), try the payment, and update the status to 1 if successful
    // Try to get a contribution template for this contribution series - if none matches (e.g. if a donation amount has been changed), we'll just be naive about it.
    $contribution_template = _iats_civicrm_getContributionTemplate(array('contribution_recur_id' => $dao->id, 'total_amount' => $dao->amount));
    $contact_id = $dao->contact_id;
    $total_amount = $dao->amount;
    $hash = md5(uniqid(rand(), TRUE));
    $contribution_recur_id    = $dao->id;
    $original_contribution_id = $contribution_template['original_contribution_id'];
    $failure_count    = $dao->failure_count;
    $subtype = substr($dao->pp_class_name, 19);
    $source = "iATS Payments $subtype Recurring Contribution (id=$contribution_recur_id)";
    $receive_ts = $catchup ? strtotime($dao->next_sched_contribution_date) : time();
    // i.e. now or whenever it was supposed to run if in catchup mode.
    $receive_date = date("YmdHis", $receive_ts);
    // Check if we already have an error.
    $errors = array();
    if (empty($dao->customer_code)) {
      $errors[] = ts('Recur id %1 is missing a customer code.', array(1 => $contribution_recur_id));
    }
    else {
      if ($dao->contact_id != $dao->icc_contact_id) {
        $errors[] = ts('Recur id %1 is has a mismatched contact id for the customer code.', array(1 => $contribution_recur_id));
      }
      if (($dao->icc_expiry != '0000') && ($dao->icc_expiry < $expiry_limit)) {
        // $errors[] = ts('Recur id %1 is has an expired cc for the customer code.', array(1 => $contribution_recur_id));.
      }
    }
    if (count($errors)) {
      $source .= ' Errors: ' . implode(' ', $errors);
    }
    $contribution = array(
      'version'        => 3,
      'contact_id'       => $contact_id,
      'receive_date'       => $receive_date,
      'total_amount'       => $total_amount,
      'payment_instrument_id'  => $dao->payment_instrument_id,
      'contribution_recur_id'  => $contribution_recur_id,
      'invoice_id'       => $hash,
      'source'         => $source,
      'contribution_status_id' => 2, /* initialize as pending, so we can run completetransaction after taking the money */
      'currency'  => $dao->currency,
      'payment_processor'   => $dao->payment_processor_id,
      'is_test'        => $dao->is_test, /* propagate the is_test value from the parent contribution */
    );
    $get_from_template = array('contribution_campaign_id', 'amount_level');
    foreach ($get_from_template as $field) {
      if (isset($contribution_template[$field])) {
        $contribution[$field] = is_array($contribution_template[$field]) ? implode(', ', $contribution_template[$field]) : $contribution_template[$field];
      }
    }
    // 4.2.
    if (isset($dao->contribution_type_id)) {
      $contribution['contribution_type_id'] = $dao->contribution_type_id;
    }
    // 4.3+.
    else {
      $contribution['financial_type_id'] = $dao->financial_type_id;
    }
    // if we have a created a pending contribution record due to a future start time, then recycle that CiviCRM contribution record now.
    // Note that the date and amount both could have changed.
    // The key is to only match if we find a single pending contribution, with a NULL transaction id, for this recurring schedule.
    // We'll need to pay attention later that we may or may not already have a contribution id.
    try {
      $pending_contribution = civicrm_api3('Contribution', 'get', array(
        'return' => array('id'),
        'trxn_id' => array('IS NULL' => 1),
        'contribution_recur_id' => $contribution_recur_id,
        'contribution_status_id' => "Pending",
      ));
      if (!empty($pending_contribution['id'])) {
        $contribution['id'] = $pending_contribution['id'];
      }
    }
    catch (Exception $e) {
      // ignore, we'll proceed normally without a contribution id
    }
    // If I'm not recycling a contribution record and my original has line_items, then I'll add them to the contribution creation array.
    // TODO: if the amount of a matched pending contribution has changed, then we should be removing line items from the contribution and replacing them.
    if (empty($contribution['id']) && !empty($contribution_template['line_items'])) {
      $contribution['skipLineItem'] = 1;
      $contribution['api.line_item.create'] = $contribution_template['line_items'];
    }
    if (count($errors)) {
      ++$error_count;
      ++$counter;
      /* create a failed contribution record, don't bother talking to iats */
      $contribution['contribution_status_id'] = 4;
      $contributionResult = civicrm_api('contribution', 'create', $contribution);
      if ($contributionResult['is_error']) {
        $errors[] = $contributionResult['error_message'];
      }
      if ($email_failure_report) {
        $failure_report_text .= "\n Unexpected Errors: " . implode(' ', $errors);
      }
      continue;
    }
    else {
      // Assign basic options.
      $options = array(
        'is_email_receipt' => (($receipt_recurring < 2) ? $receipt_recurring : $dao->is_email_receipt),
        'customer_code' => $dao->customer_code,
        'subtype' => $subtype,
      );
      // If our template contribution is a membership payment, make this one also.
      if ($domemberships && !empty($contribution_template['contribution_id'])) {
        try {
          $membership_payment = civicrm_api('MembershipPayment', 'getsingle', array('version' => 3, 'contribution_id' => $contribution_template['contribution_id']));
          if (!empty($membership_payment['membership_id'])) {
            $options['membership_id'] = $membership_payment['membership_id'];
          }
        }
        catch (Exception $e) {
          // ignore, if will fail correctly if there is no membership payment.
        }
      }
      // So far so, good ... now create the pending contribution, and save its id
      // and then try to get the money, and do one of:
      // update the contribution to failed, leave as pending for server failure, complete the transaction,
      // or update a pending ach/eft with it's transaction id.
      $result = _iats_process_contribution_payment($contribution, $options, $original_contribution_id);
      if ($email_failure_report && !empty($contribution['iats_reject_code'])) {
        $failure_report_text .= "\n $result ";
      }
      $output[] = $result;
    }

    /* in case of critical failure set the series to pending */
    if (!empty($contribution['iats_reject_code'])) {
      switch ($contribution['iats_reject_code']) {
        // Reported lost or stolen.
        case 'REJECT: 25':
          // Do not reprocess!
        case 'REJECT: 100':
          /* convert the contribution series to pending to avoid reprocessing until dealt with */
          civicrm_api('ContributionRecur', 'create',
            array(
              'version' => 3,
              'id'      => $contribution['contribution_recur_id'],
              'contribution_status_id'   => 2,
            )
          );
          break;
      }
    }

    /* calculate the next collection date, based on the recieve date (note effect of catchup mode, above)  */
    $next_collection_date = date('Y-m-d H:i:s', strtotime("+$dao->frequency_interval $dao->frequency_unit", $receive_ts));
    /* by default, advance to the next schduled date and set the failure count back to 0 */
    $contribution_recur_set = array('version' => 3, 'id' => $contribution['contribution_recur_id'], 'failure_count' => '0', 'next_sched_contribution_date' => $next_collection_date);
    /* special handling for failures */
    if (4 == $contribution['contribution_status_id']) {
      $contribution_recur_set['failure_count'] = $failure_count + 1;
      /* if it has failed but the failure threshold will not be reached with this failure, leave the next sched contribution date as it was */
      if ($contribution_recur_set['failure_count'] < $failure_threshhold) {
        // Should the failure count be reset otherwise? It is not.
        unset($contribution_recur_set['next_sched_contribution_date']);
      }
    }
    civicrm_api('ContributionRecur', 'create', $contribution_recur_set);
    $result = civicrm_api('activity', 'create',
      array(
        'version'       => 3,
        'activity_type_id'  => 6,
        'source_contact_id'   => $contact_id,
        'source_record_id' => $contribution['id'],
        'assignee_contact_id' => $contact_id,
        'subject'       => "Attempted iATS Payments $subtype Recurring Contribution for " . $total_amount,
        'status_id'       => 2,
        'activity_date_time'  => date("YmdHis"),
      )
    );
    if ($result['is_error']) {
      $output[] = ts(
        'An error occurred while creating activity record for contact id %1: %2',
        array(
          1 => $contact_id,
          2 => $result['error_message'],
        )
      );
      ++$error_count;
    }
    else {
      $output[] = ts('Created activity record for contact id %1', array(1 => $contact_id));
    }
    ++$counter;
  }

  // Now update the end_dates and status for non-open-ended contribution series if they are complete (so that the recurring contribution status will show correctly)
  // This is a simplified version of what we did before the processing.
  $select = 'SELECT cr.id, count(c.id) AS installments_done, cr.installments
      FROM civicrm_contribution_recur cr
      INNER JOIN civicrm_contribution c ON cr.id = c.contribution_recur_id
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      WHERE
        (pp.class_name = %1 OR pp.class_name = %2 OR pp.class_name = %3)
        AND (cr.installments > 0)
        AND (cr.contribution_status_id  = 5)
        AND (c.contribution_status_id IN (1,2))
      GROUP BY c.contribution_recur_id';
  $dao = CRM_Core_DAO::executeQuery($select, $args);
  while ($dao->fetch()) {
    // Check if my end date should be set to now because I have finished
    // I'm done with installments.
    if ($dao->installments_done >= $dao->installments) {
      // Set this series complete and the end_date to now.
      $update = 'UPDATE civicrm_contribution_recur SET contribution_status_id = 1, end_date = NOW() WHERE id = %1';
      CRM_Core_DAO::executeQuery($update, array(1 => array($dao->id, 'Int')));
    }
  }

  $lock->release();
  // If errors ..
  if ((strlen($failure_report_text) > 0) && $email_failure_report) {
    list($fromName, $fromEmail) = CRM_Core_BAO_Domain::getNameAndEmail();
    $mailparams = array(
      'from' => $fromName . ' <' . $fromEmail . '> ',
      'to' => 'System Administrator <' . $email_failure_report . '>',
      'subject' => ts('iATS Recurring Payment job failure report: ' . date('c')),
      'text' => $failure_report_text,
      'returnPath' => $fromEmail,
    );
    // print_r($mailparams);
    CRM_Utils_Mail::send($mailparams);
  }
  // If errors ..
  if ($error_count > 0) {
    return civicrm_api3_create_error(
      ts("Completed, but with %1 errors. %2 records processed.",
        array(
          1 => $error_count,
          2 => $counter,
        )
      ) . "<br />" . implode("<br />", $output)
    );
  }
  // If no errors and records processed ..
  if ($counter) {
    return civicrm_api3_create_success(
      ts(
        '%1 contribution record(s) were processed.',
        array(
          1 => $counter,
        )
      ) . "<br />" . implode("<br />", $output)
    );
  }
  // No records processed.
  return civicrm_api3_create_success(ts('No contribution records were processed.'));
}
