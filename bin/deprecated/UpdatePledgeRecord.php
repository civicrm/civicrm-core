<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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


/*
 * This file checks and updates the status of all pledge records for a
 * given domain using the updatePledgePaymentStatus.
 *
 * UpdatePledgeRecord.php prior to running this script.
 */

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

/**
 * Class CRM_UpdatePledgeRecord
 */
class CRM_UpdatePledgeRecord {
  /**
   *
   */
  function __construct() {
    $config = CRM_Core_Config::singleton();
    // this does not return on failure
    require_once 'CRM/Utils/System.php';
    require_once 'CRM/Utils/Hook.php';

    CRM_Utils_System::authenticateScript(TRUE);
    $config->cleanURL = 1;

    //log the execution time of script
    CRM_Core_Error::debug_log_message('UpdatePledgeRecord.php');
  }

  /**
   * @param bool $sendReminders
   *
   * @throws Exception
   */
  public function updatePledgeStatus($sendReminders = FALSE) {

    // *** Uncomment the next line if you want automated reminders to be sent
    // $sendReminders = true;

    require_once 'CRM/Contribute/PseudoConstant.php';
    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    //unset statues that we never use for pledges
    foreach (array(
      'Completed', 'Cancelled', 'Failed') as $statusKey) {
      if ($key = CRM_Utils_Array::key($statusKey, $allStatus)) {
        unset($allStatus[$key]);
      }
    }

    $statusIds = implode(',', array_keys($allStatus));
    $updateCnt = 0;

    $query = "
SELECT  pledge.contact_id              as contact_id,
        pledge.id                      as pledge_id,
        pledge.amount                  as amount,
        payment.scheduled_date         as scheduled_date,
        pledge.create_date             as create_date,
        payment.id                     as payment_id,
        pledge.contribution_page_id    as contribution_page_id,
        payment.reminder_count         as reminder_count,
        pledge.max_reminders           as max_reminders,
        payment.reminder_date          as reminder_date,
        pledge.initial_reminder_day    as initial_reminder_day,
        pledge.additional_reminder_day as additional_reminder_day,
        pledge.status_id               as pledge_status,
        payment.status_id              as payment_status,
        pledge.is_test                 as is_test,
        pledge.campaign_id             as campaign_id,
        SUM(payment.scheduled_amount)  as amount_due,
        ( SELECT sum(civicrm_pledge_payment.actual_amount)
        FROM civicrm_pledge_payment
        WHERE civicrm_pledge_payment.status_id = 1
        AND  civicrm_pledge_payment.pledge_id = pledge.id
        ) as amount_paid
        FROM      civicrm_pledge pledge, civicrm_pledge_payment payment
        WHERE     pledge.id = payment.pledge_id
        AND     payment.status_id IN ( {$statusIds} ) AND pledge.status_id IN ( {$statusIds} )
        GROUP By  payment.id
        ";

    $dao = CRM_Core_DAO::executeQuery($query);

    require_once 'CRM/Contact/BAO/Contact/Utils.php';
    require_once 'CRM/Utils/Date.php';
    $now = date('Ymd');
    $pledgeDetails = $contactIds = $pledgePayments = $pledgeStatus = array();
    while ($dao->fetch()) {
      $checksumValue = CRM_Contact_BAO_Contact_Utils::generateChecksum($dao->contact_id);

      $pledgeDetails[$dao->payment_id] = array(
        'scheduled_date' => $dao->scheduled_date,
        'amount_due' => $dao->amount_due,
        'amount' => $dao->amount,
        'amount_paid' => $dao->amount_paid,
        'create_date' => $dao->create_date,
        'contact_id' => $dao->contact_id,
        'pledge_id' => $dao->pledge_id,
        'checksumValue' => $checksumValue,
        'contribution_page_id' => $dao->contribution_page_id,
        'reminder_count' => $dao->reminder_count,
        'max_reminders' => $dao->max_reminders,
        'reminder_date' => $dao->reminder_date,
        'initial_reminder_day' => $dao->initial_reminder_day,
        'additional_reminder_day' => $dao->additional_reminder_day,
        'pledge_status' => $dao->pledge_status,
        'payment_status' => $dao->payment_status,
        'is_test' => $dao->is_test,
        'campaign_id' => $dao->campaign_id,
      );

      $contactIds[$dao->contact_id] = $dao->contact_id;
      $pledgeStatus[$dao->pledge_id] = $dao->pledge_status;

      if (CRM_Utils_Date::overdue(CRM_Utils_Date::customFormat($dao->scheduled_date, '%Y%m%d'),
          $now
        ) && $dao->payment_status != array_search('Overdue', $allStatus)) {
        $pledgePayments[$dao->pledge_id][$dao->payment_id] = $dao->payment_id;
      }
    }

    require_once 'CRM/Pledge/BAO/PledgePayment.php';
    // process the updating script...

    foreach ($pledgePayments as $pledgeId => $paymentIds) {
      // 1. update the pledge /pledge payment status. returns new status when an update happens
      echo "<br />Checking if status update is needed for Pledge Id: {$pledgeId} (current status is {$allStatus[$pledgeStatus[$pledgeId]]})";

      $newStatus = CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId, $paymentIds,
        array_search('Overdue', $allStatus), NULL, 0, FALSE, TRUE
      );
      if ($newStatus != $pledgeStatus[$pledgeId]) {
        echo "<br />- status updated to: {$allStatus[$newStatus]}";
        $updateCnt += 1;
      }
    }

    if ($sendReminders) {
      // retrieve domain tokens
      require_once 'CRM/Core/BAO/Domain.php';
      require_once 'CRM/Core/SelectValues.php';
      $domain = CRM_Core_BAO_Domain::getDomain();
      $tokens = array('domain' => array('name', 'phone', 'address', 'email'),
        'contact' => CRM_Core_SelectValues::contactTokens(),
      );

      require_once 'CRM/Utils/Token.php';
      $domainValues = array();
      foreach ($tokens['domain'] as $token) {
        $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
      }

      //get the domain email address, since we don't carry w/ object.
      require_once 'CRM/Core/BAO/Domain.php';
      $domainValue = CRM_Core_BAO_Domain::getNameAndEmail();
      $domainValues['email'] = $domainValue[1];

      // retrieve contact tokens

      // this function does NOT return Deceased contacts since we don't want to send them email
      require_once 'CRM/Utils/Token.php';
      list($contactDetails) = CRM_Utils_Token::getTokenDetails($contactIds,
        NULL,
        FALSE, FALSE, NULL,
        $tokens, 'CRM_UpdatePledgeRecord'
      );

      // assign domain values to template
      $template = CRM_Core_Smarty::singleton();
      $template->assign('domain', $domainValues);

      //set receipt from
      $receiptFrom = '"' . $domainValues['name'] . '" <' . $domainValues['email'] . '>';

      foreach ($pledgeDetails as $paymentId => $details) {
        if (array_key_exists($details['contact_id'], $contactDetails)) {
          $contactId = $details['contact_id'];
          $pledgerName = $contactDetails[$contactId]['display_name'];
        }
        else {
          continue;
        }

        if (empty($details['reminder_date'])) {
          $nextReminderDate = new DateTime($details['scheduled_date']);
          $nextReminderDate->modify("-" . $details['initial_reminder_day'] . "day");
          $nextReminderDate = $nextReminderDate->format("Ymd");
        }
        else {
          $nextReminderDate = new DateTime($details['reminder_date']);
          $nextReminderDate->modify("+" . $details['additional_reminder_day'] . "day");
          $nextReminderDate = $nextReminderDate->format("Ymd");
        }
        if (($details['reminder_count'] < $details['max_reminders'])
          && ($nextReminderDate <= $now)
        ) {

          $toEmail = $doNotEmail = $onHold = NULL;

          if (!empty($contactDetails[$contactId]['email'])) {
            $toEmail = $contactDetails[$contactId]['email'];
          }

          if (!empty($contactDetails[$contactId]['do_not_email'])) {
            $doNotEmail = $contactDetails[$contactId]['do_not_email'];
          }

          if (!empty($contactDetails[$contactId]['on_hold'])) {
            $onHold = $contactDetails[$contactId]['on_hold'];
          }

          // 2. send acknowledgement mail
          if ($toEmail && !($doNotEmail || $onHold)) {
            //assign value to template
            $template->assign('amount_paid', $details['amount_paid'] ? $details['amount_paid'] : 0);
            $template->assign('contact', $contactDetails[$contactId]);
            $template->assign('next_payment', $details['scheduled_date']);
            $template->assign('amount_due', $details['amount_due']);
            $template->assign('checksumValue', $details['checksumValue']);
            $template->assign('contribution_page_id', $details['contribution_page_id']);
            $template->assign('pledge_id', $details['pledge_id']);
            $template->assign('scheduled_payment_date', $details['scheduled_date']);
            $template->assign('amount', $details['amount']);
            $template->assign('create_date', $details['create_date']);

            require_once 'CRM/Core/BAO/MessageTemplate.php';
            list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
              array(
                'groupName' => 'msg_tpl_workflow_pledge',
                'valueName' => 'pledge_reminder',
                'contactId' => $contactId,
                'from' => $receiptFrom,
                'toName' => $pledgerName,
                'toEmail' => $toEmail,
              )
            );

            // 3. update pledge payment details
            if ($mailSent) {
              CRM_Pledge_BAO_PledgePayment::updateReminderDetails($paymentId);
              $activityType = 'Pledge Reminder';
              $activityParams = array(
                'subject' => $subject,
                'source_contact_id' => $contactId,
                'source_record_id' => $paymentId,
                'assignee_contact_id' => $contactId,
                'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
                  $activityType,
                  'name'
                ),
                'activity_date_time' => CRM_Utils_Date::isoToMysql($now),
                'due_date_time' => CRM_Utils_Date::isoToMysql($details['scheduled_date']),
                'is_test' => $details['is_test'],
                'status_id' => 2,
                'campaign_id' => $details['campaign_id'],
              );
              if (is_a(civicrm_api('activity', 'create', $activityParams), 'CRM_Core_Error')) {
                CRM_Core_Error::fatal("Failed creating Activity for acknowledgment");
              }
              echo "<br />Payment reminder sent to: {$pledgerName} - {$toEmail}";
            }
          }
        }
      }
      // end foreach on $pledgeDetails
    }
    // end if ( $sendReminders )
    echo "<br />{$updateCnt} records updated.";
  }
}

$obj = new CRM_UpdatePledgeRecord();
echo "Updating<br />";
$obj->updatePledgeStatus();
echo "<br />Pledge records update script finished.";


