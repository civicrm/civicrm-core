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
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\Mailing;
use Civi\Api4\MailingJob;

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
      self::_process($dao, (bool) $is_create_activities);
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
      self::_process($dao, TRUE);
    }
    if (!$found) {
      throw new CRM_Core_Exception(ts('No mailboxes have been configured for Email to Activity Processing'));
    }
    return $found;
  }

  /**
   * @param CRM_Core_DAO_MailSettings $dao
   * @param bool $is_create_activities
   *   Create activities.
   *
   * @throws Exception
   * @throws CRM_Core_Exception
   */
  private static function _process($dao, bool $is_create_activities) {
      // create an array of all of to, from, cc, bcc that are in use for this Mail Account, so we don't create contacts for emails we aren't adding to the activity
    $emailFields = array_filter(array_unique(array_merge(explode(',', $dao->activity_targets), explode(',', $dao->activity_assignees), explode(',', $dao->activity_source))));
    $createContact = !($dao->is_contact_creation_disabled_if_no_match ?? FALSE);
    $targetEmailFields = array_filter(explode(',', $dao->activity_targets));
    $assigneeEmailFields = array_filter(explode(',', $dao->activity_assignees));
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
    $permittedNumberOfAttachments = Civi::settings()->get('max_attachments_backend') ?? CRM_Core_BAO_File::DEFAULT_MAX_ATTACHMENTS_BACKEND;

    // process fifty at a time, CRM-4002
    while ($mails = $store->fetchNext(MAIL_BATCH_SIZE)) {
      foreach ($mails as $key => $mail) {
        $incomingMail = new CRM_Utils_Mail_IncomingMail($mail, (string) $dao->domain, (string) $dao->localpart);

        // for every addressee: match address elements if it's to CiviMail
        $matches = $incomingMail->isVerp();
        $action = $incomingMail->getVerpAction();
        $queue = $incomingMail->getQueueID();
        $job = $incomingMail->getJobID();
        $hash = $incomingMail->getHash();

        if ($is_create_activities) {
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
            $params = [];
            if (!$incomingMail->isVerp()) {
              $mailingParams = CRM_Utils_Mail_Incoming::parseMailingObject($mail, $createContact, FALSE, $emailFields);
              $params['target_contact_id'] = [];
              $params['assignee_contact_id'] = [];
              foreach ($targetEmailFields as $targetKey) {
                $params['target_contact_id'] += $mailingParams[$targetKey] ?? [];
              }
              foreach ($assigneeEmailFields as $assigneeKey) {
                $params['assignee_contact_id'] += $mailingParams[$assigneeKey];
              }
              $params['source_contact_id'] = $mailingParams[$dao->activity_source][0];
            }
            else {
              $params['target_contact_id'] = (int) $incomingMail->lookup('Queue', 'contact_id');
              $fromEmail = $incomingMail->lookup('Mailing', 'from_email');
              $replyToEmail = $incomingMail->lookup('Mailing', 'replyto_email');
              $emails = Email::get(FALSE)
                ->addWhere('email', 'IN', array_filter([
                  $fromEmail,
                  $replyToEmail
                ]))
                ->addSelect('is_primary', 'email', 'contact_id')
                ->addOrderBy('is_primary', 'DESC')
                ->execute()
                ->indexBy('email');
              $params['source_contact_id'] = $emails[$replyToEmail] ?? ($emails[$fromEmail] ?? $incomingMail->lookup('Mailing', 'created_id'));
              if (empty($params['source_contact_id'])) {
                $params['source_contact_id'] = Contact::create(FALSE)
                  // Organization? Individual? No right answers here - hopefully this is
                  // not hit as mostly the fall-back in civicrm_mailing.created_id.
                  ->setValues([
                    'contact_type' => 'Organization',
                    'organization_name' => $incomingMail->lookup('Mailing', 'from_name'),
                    'email_primary.email' => $replyToEmail ?: $fromEmail,
                  ])->execute()->first()['id'];
              }
            }
            $params['subject'] = $incomingMail->getEmailSubject();
            $params['activity_date_time'] = $incomingMail->getDate();
            $params['details'] = $incomingMail->getBody();
            $params['activity_type_id'] = (int) $dao->activity_type_id ?: 'Inbound Email';
            $params['campaign_id'] = $dao->campaign_id;
            $params['status_id'] = $dao->activity_status;
            $params['activity_type_id'] = (int) $dao->activity_type_id;
            // Do not add attachments for bounce as the later effort to load the attachments will fail
            // at $text = $mail->generateBody(); because it has already moved the files.
            if ($incomingMail->getVerpAction() !== 'bounce') {
              foreach ($incomingMail->getAttachments() as $attachmentNumber => $attachment) {
                if ($permittedNumberOfAttachments < $attachmentNumber) {
                  break;
                }
                $params["attachFile_$attachmentNumber"] = $attachment;
              }
            }
            $result = civicrm_api3('activity', 'create', $params);
          }
          catch (Exception $e) {
            echo "Failed Processing: {$mail->subject}. Reason: " . $e->getMessage() ."\n";
            $store->markIgnored($key);
            continue;
          }

          $matches = TRUE;
          CRM_Utils_Hook::emailProcessor('activity', $params, $mail, $result);
          echo "Processed as Activity: {$mail->subject}\n";
        }

        // if $matches is empty, this email is not CiviMail-bound
        if (!$matches) {
          $store->markIgnored($key);
          continue;
        }

        // get $replyTo from either the Reply-To header or from From
        // FIXME: make sure it works with Reply-Tos containing non-email stuff
        $replyTo = $mail->getHeader('Reply-To') ? $mail->getHeader('Reply-To') : ($mail->from ? $mail->from->email : "");

        // handle the action by passing it to the proper API call
        if ($incomingMail->isVerp()) {
          $result = NULL;

          switch ($incomingMail->getVerpAction()) {
            case 'bounce':
              $text = '';
              if ($mail->body instanceof ezcMailText) {
                $text = $mail->body->text;
              }
              elseif ($mail->body instanceof ezcMailMultipart) {
                $text = self::getTextFromMultipart($mail->body);
              }
              elseif ($mail->body instanceof ezcMailFile) {
                $text = file_get_contents($mail->body->__get('fileName'));
              }

              if (
                empty($text) &&
                $mail->subject === 'Delivery Status Notification (Failure)'
              ) {
                // Exchange error - CRM-9361
                foreach ($mail->body->getParts() as $part) {
                  if ($part instanceof ezcMailDeliveryStatus) {
                    foreach ($part->recipients as $rec) {
                      if ($rec['Status'] === '5.1.1') {
                        if (isset($rec['Description'])) {
                          $text = $rec['Description'];
                        }
                        else {
                          $text = $rec['Status'] . ' Delivery to the following recipients failed';
                        }
                        break;
                      }
                    }
                  }
                }
              }

              if (empty($text)) {
                // If bounce processing fails, just take the raw body. Cf. CRM-11046
                $text = $mail->generateBody();

                // if text is still empty, lets fudge a blank text so the api call below will succeed
                if (empty($text)) {
                  $text = ts('We could not extract the mail body from this bounce message.');
                }
              }

              $params = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $incomingMail->getHash(),
                'body' => $text,
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
              $result = civicrm_api('Mailing', 'event_bounce', $params);
              break;

            case 'confirm':
              // CRM-7921
              $params = [
                'contact_id' => $job,
                'subscribe_id' => $queue,
                'hash' => $incomingMail->getHash(),
                'version' => 3,
              ];
              $result = civicrm_api('Mailing', 'event_confirm', $params);
              break;

            case 'opt_out':
              $params = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $incomingMail->getHash(),
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_domain_unsubscribe', $params);
              break;

            case 'reply':
              // instead of text and HTML parts (4th and 6th params) send the whole email as the last param
              $params = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $incomingMail->getHash(),
                'bodyTxt' => NULL,
                'replyTo' => $replyTo,
                'bodyHTML' => NULL,
                'fullEmail' => $mail->generate(),
                'version' => 3,
              ];
              $result = civicrm_api('Mailing', 'event_reply', $params);
              break;

            case 'resubscribe':
              $params = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $incomingMail->getHash(),
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_resubscribe', $params);
              break;

            case 'subscribe':
              $params = [
                'email' => $mail->from->email,
                'group_id' => $job,
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_subscribe', $params);
              break;

            case 'unsubscribe':
              $params = [
                'job_id' => $job,
                'event_queue_id' => $queue,
                'hash' => $hash,
                'version' => 3,
              ];
              $result = civicrm_api('MailingGroup', 'event_unsubscribe', $params);
              break;
          }

          if ($result['is_error']) {
            echo "Failed Processing: {$mail->subject}, Action: $action, Job ID: $job, Queue ID: ' . $, Hash: $hash. Reason: {$result['error_message']}\n";
          }
          else {
            CRM_Utils_Hook::emailProcessor('mailing', $params, $mail, $result, $action);
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
