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

// we should consider moving this to the settings table
use Civi\Api4\Activity;

define('MAIL_BATCH_SIZE', 50);

/**
 * Class CRM_Utils_Mail_EmailProcessor.
 */
class CRM_Utils_Mail_EmailProcessor {

  const MIME_MAX_RECURSION = 10;

  /**
   * Process the default mailbox (ie. that is used by civiMail for the bounce)
   *
   * @param bool $is_create_activities
   *   Should activities be created
   */
  public static function processBounces($is_create_activities) {
    $dao = new CRM_Core_DAO_MailSettings();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->is_default = TRUE;
    $dao->find();

    while ($dao->fetch()) {
      self::_process(TRUE, $dao, $is_create_activities);
    }
  }

  /**
   * Process the mailboxes that aren't default (ie. that aren't used by civiMail for the bounce).
   *
   * @return bool
   *
   * @throws CRM_Core_Exception.
   */
  public static function processActivities() {
    $dao = new CRM_Core_DAO_MailSettings();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->is_default = FALSE;
    $dao->is_active = TRUE;
    $dao->find();
    $found = FALSE;
    while ($dao->fetch()) {
      $found = TRUE;
      self::_process(FALSE, $dao, TRUE);
    }
    if (!$found) {
      throw new CRM_Core_Exception(ts('No mailboxes have been configured for Email to Activity Processing'));
    }
    return $found;
  }

  /**
   * @param $civiMail
   * @param CRM_Core_DAO_MailSettings $dao
   * @param bool $is_create_activities
   *   Create activities.
   *
   * @throws Exception
   * @throws CRM_Core_Exception
   */
  private static function _process($civiMail, $dao, $is_create_activities) {
    // 0 = activities; 1 = bounce;
    $isBounceProcessing = $dao->is_default;
    $targetFields = array_filter(explode(',', $dao->activity_targets));
    $assigneeFields = array_filter(explode(",", $dao->activity_assignees));
    $sourceFields = array_filter(explode(",", $dao->activity_source));
    // create an array of all of to, from, cc, bcc that are in use for this Mail Account, so we don't create contacts for emails we aren't adding to the activity.
    $emailFields = array_merge($targetFields, $assigneeFields, $sourceFields);
    $createContact = !($dao->is_contact_creation_disabled_if_no_match);
    $bounceActivityTypeID = $activityTypeID = (int) $dao->activity_type_id;
    $activityTypes = Activity::getFields(TRUE)
      ->setLoadOptions(['id', 'name'])
      ->addWhere('name', '=', 'activity_type_id')
      ->execute()->first()['options'];
    foreach ($activityTypes as $activityType) {
      if ($activityType['name'] === 'Bounce') {
        $bounceActivityTypeID = (int) $activityType['id'];
      }
    }

    // retrieve the emails
    try {
      $store = CRM_Mailing_MailStore::getStore($dao->name);
    }
    catch (Exception $e) {
      $message = ts('Could not connect to MailStore for ') . $dao->username . '@' . $dao->server . '<p>';
      $message .= ts('Error message: ');
      $message .= '<pre>' . $e->getMessage() . '</pre><p>';
      throw new CRM_Core_Exception($message);
    }

    // process fifty at a time, CRM-4002
    while ($mails = $store->fetchNext(MAIL_BATCH_SIZE)) {
      foreach ($mails as $key => $mail) {
        $incomingMail = new CRM_Utils_Mail_IncomingMail($mail, (string) $dao->domain, (string) $dao->localpart);
        $action = $incomingMail->getAction();
        $job = $incomingMail->getJobID();
        $queue = $incomingMail->getQueueID();
        $hash = $incomingMail->getHash();

        // preserve backward compatibility
        if (!$isBounceProcessing || $is_create_activities) {
          // Mail account may have 'Skip emails which do not have a Case ID
          // or Case hash' option, if its enabled and email is not related
          // to cases - then we need to put email to ignored folder.
          $caseMailUtils = new CRM_Utils_Mail_CaseMail();
          if (!empty($dao->is_non_case_email_skipped) && !$caseMailUtils->isCaseEmail($mail->subject)) {
            $store->markIgnored($key);
            continue;
          }

          // if its the activities that needs to be processed ..
          try {
            if ($incomingMail->isBounce()) {
              $activityTypeID = $bounceActivityTypeID;
            }
            $mailParams = CRM_Utils_Mail_Incoming::parseMailingObject($mail, $incomingMail->getAttachments(), $createContact, $emailFields, [$incomingMail->getFrom()]);
            $activityParams = [
              'activity_type_id' => $activityTypeID,
              'campaign_id' => $dao->campaign_id ? (int) $dao->campaign_id : NULL,
              'status_id' => $dao->activity_status,
              'subject' => $incomingMail->getSubject(),
              'activity_date_time' => $incomingMail->getDate(),
              'details' => $incomingMail->getBody(),
            ];
            if ($incomingMail->isVerp()) {
              $activityParams['source_contact_id'] = $incomingMail->lookup('Queue', 'contact_id');
            }
            else {
              $activityParams['source_contact_id'] = $mailParams[$dao->activity_source][0]['id'];

              $activityContacts = [
                'target_contact_id' => $targetFields,
                'assignee_contact_id' => $assigneeFields,
              ];
              foreach ($activityContacts as $activityContact => $activityKeys) {
                $activityParams[$activityContact] = [];
                foreach ($activityKeys as $activityKey) {
                  if (is_array($mailParams[$activityKey])) {
                    foreach ($mailParams[$activityKey] as $keyValue) {
                      if (!empty($keyValue['id'])) {
                        $activityParams[$activityContact][] = $keyValue['id'];
                      }
                    }
                  }
                }
              }
            }
            $numAttachments = Civi::settings()->get('max_attachments_backend') ?? CRM_Core_BAO_File::DEFAULT_MAX_ATTACHMENTS_BACKEND;
            for ($i = 1; $i <= $numAttachments; $i++) {
              if (isset($mailParams["attachFile_$i"])) {
                $activityParams["attachFile_$i"] = $mailParams["attachFile_$i"];
              }
              else {
                // No point looping 100 times if there's only one attachment
                break;
              }
            }

            $result = civicrm_api3('Activity', 'create', $activityParams);
          }
          catch (Exception $e) {
            echo "Failed Processing: {$mail->subject}. Reason: " . $e->getMessage() . "\n";
            $store->markIgnored($key);
            continue;
          }
          $matches = TRUE;
          CRM_Utils_Hook::emailProcessor('activity', $activityParams, $mail, $result);
          echo "Processed as Activity: {$mail->subject}\n";
        }

        // if $matches is empty, this email is not CiviMail-bound
        if (!$incomingMail->isVerp() && empty($matches)) {
          $store->markIgnored($key);
          continue;
        }

        // get $replyTo from either the Reply-To header or from From
        // FIXME: make sure it works with Reply-Tos containing non-email stuff
        $replyTo = $mail->getHeader('Reply-To') ? $mail->getHeader('Reply-To') : ($mail->from ? $mail->from->email : "");

        // handle the action by passing it to the proper API call
        if (!empty($action)) {
          $result = NULL;

          switch ($action) {
            case 'b':
              $text = $incomingMail->getBody();

              $activityParams = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $hash,
                'body' => $text ?: ts('We could not extract the mail body from this bounce message.'),
                'version' => 3,
                // Setting is_transactional means it will rollback if
                // it crashes part way through creating the bounce.
                // If the api were standard & had a create this would be the
                // default. Adding the standard api & deprecating this one
                // would probably be the
                // most consistent way to address this - but this is
                // a quick hack.
                'is_transactional' => 1,
              ];
              $result = civicrm_api('Mailing', 'event_bounce', $activityParams);
              break;

            case 'c':
              // CRM-7921
              $activityParams = [
                'contact_id' => $job,
                'subscribe_id' => $queue,
                'hash' => $hash,
                'version' => 3,
              ];
              $result = civicrm_api('Mailing', 'event_confirm', $activityParams);
              break;

            case 'o':
              $activityParams = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $hash,
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_domain_unsubscribe', $activityParams);
              break;

            case 'r':
              // instead of text and HTML parts (4th and 6th params) send the whole email as the last param
              $activityParams = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $hash,
                'bodyTxt' => NULL,
                'replyTo' => $replyTo,
                'bodyHTML' => NULL,
                'fullEmail' => $mail->generate(),
                'version' => 3,
              ];
              $result = civicrm_api('Mailing', 'event_reply', $activityParams);
              break;

            case 'e':
              $activityParams = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $hash,
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_resubscribe', $activityParams);
              break;

            case 's':
              $activityParams = [
                'email' => $mail->from->email,
                'group_id' => $job,
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_subscribe', $activityParams);
              break;

            case 'u':
              $activityParams = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $hash,
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_unsubscribe', $activityParams);
              break;
          }

          if ($result['is_error']) {
            echo "Failed Processing: {$mail->subject}, Action: $action, Job ID: $job, Queue ID: $queue, Hash: $hash. Reason: {$result['error_message']}\n";
          }
          else {
            CRM_Utils_Hook::emailProcessor('mailing', $activityParams, $mail, $result, $action);
          }
        }

        $store->markProcessed($key);
      }
      // CRM-7356 – used by IMAP only
      $store->expunge();
    }
  }

  /**
   * @param \ezcMailMultipart $multipart
   * @param int $recursionLevel
   *
   * @return array
   */
  protected static function getTextFromMultipart($multipart, $recursionLevel = 0) {
    if ($recursionLevel >= self::MIME_MAX_RECURSION) {
      return NULL;
    }
    $recursionLevel += 1;
    $text = NULL;
    if ($multipart instanceof ezcMailMultipartReport) {
      $text = self::getTextFromMulipartReport($multipart, $recursionLevel);
    }
    elseif ($multipart instanceof ezcMailMultipartRelated) {
      $text = self::getTextFromMultipartRelated($multipart, $recursionLevel);
    }
    else {
      foreach ($multipart->getParts() as $part) {
        if (isset($part->subType) and $part->subType === 'plain') {
          $text = $part->text;
        }
        elseif ($part instanceof ezcMailMultipart) {
          $text = self::getTextFromMultipart($part, $recursionLevel);
        }
        if ($text) {
          break;
        }
      }
    }
    return $text;
  }

  /**
   * @param \ezcMailMultipartRelated $related
   * @param int $recursionLevel
   *
   * @return array
   */
  protected static function getTextFromMultipartRelated($related, $recursionLevel) {
    $text = NULL;
    foreach ($related->getRelatedParts() as $part) {
      if (isset($part->subType) and $part->subType === 'plain') {
        $text = $part->text;
      }
      elseif ($part instanceof ezcMailMultipart) {
        $text = self::getTextFromMultipart($part, $recursionLevel);
      }
      if ($text) {
        break;
      }
    }
    return $text;
  }

  /**
   * @param \ezcMailMultipartReport $multipart
   * @param $recursionLevel
   *
   * @return array
   */
  protected static function getTextFromMulipartReport($multipart, $recursionLevel) {
    $text = NULL;
    $part = $multipart->getMachinePart();
    if ($part instanceof ezcMailDeliveryStatus) {
      foreach ($part->recipients as $rec) {
        if (isset($rec["Diagnostic-Code"])) {
          $text = $rec["Diagnostic-Code"];
          break;
        }
        elseif (isset($rec["Description"])) {
          $text = $rec["Description"];
          break;
        }
        // no diagnostic info present - try getting the human readable part
        elseif (isset($rec["Status"])) {
          $text = $rec["Status"];
          $textpart = $multipart->getReadablePart();
          if ($textpart !== NULL and isset($textpart->text)) {
            $text .= " " . $textpart->text;
          }
          else {
            $text .= " Delivery failed but no diagnostic code or description.";
          }
          break;
        }
      }
    }
    elseif ($part !== NULL and isset($part->text)) {
      $text = $part->text;
    }
    elseif (($part = $multipart->getReadablePart()) !== NULL) {
      if (isset($part->text)) {
        $text = $part->text;
      }
      elseif ($part instanceof ezcMailMultipart) {
        $text = self::getTextFromMultipart($part, $recursionLevel);
      }
    }
    return $text;
  }

}
