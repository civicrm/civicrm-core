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

use Civi\Token\TokenProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

require_once 'Mail/mime.php';

/**
 * Class CRM_Mailing_Event_BAO_MailingEventResubscribe
 */
class CRM_Mailing_Event_BAO_MailingEventResubscribe {

  /**
   * Resubscribe a contact to the groups, he/she was unsubscribed from.
   *
   * @param int $job_id
   *   The job ID.
   * @param int $queue_id
   *   The Queue Event ID of the recipient.
   * @param string $hash
   *   The hash.
   *
   * @return array|null
   *   $groups    Array of all groups to which the contact was added, or null if the queue event could not be found.
   */
  public static function &resub_to_mailing($job_id, $queue_id, $hash) {
    // First make sure there's a matching queue event.

    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $queue_id, $hash);
    $success = NULL;
    if (!$q) {
      return $success;
    }

    // check if this queue_id was actually unsubscribed
    $ue = new CRM_Mailing_Event_BAO_MailingEventUnsubscribe();
    $ue->event_queue_id = $queue_id;
    $ue->org_unsubscribe = 0;
    if (!$ue->find(TRUE)) {
      return $success;
    }

    $contact_id = $q->contact_id;

    $transaction = new CRM_Core_Transaction();
    // We Need the mailing Id for the hook...
    $mailing_id = CRM_Core_DAO::singleValueQuery("SELECT mailing_id as mailing_id
                     FROM civicrm_mailing_job
                     WHERE id = " . CRM_Utils_Type::escape($job_id, 'Integer'));

    $do = CRM_Core_DAO::executeQuery("
            SELECT      mailing_group.entity_table as entity_table,
                        mailing_group.entity_id as entity_id
            FROM        civicrm_mailing_group as mailing_group
            INNER JOIN  civicrm_mailing_job as job
                ON      job.mailing_id = mailing_group.mailing_id
            INNER JOIN  civicrm_group
                ON      mailing_group.entity_id = civicrm_group.id
            WHERE       job.id = " . CRM_Utils_Type::escape($job_id, 'Integer') . "
                AND     mailing_group.group_type IN ( 'Include', 'Base' )
                AND     civicrm_group.is_hidden = 0"
    );

    // Make a list of groups and a list of prior mailings that received
    // this mailing.
    $groups = [];
    $mailings = [];

    while ($do->fetch()) {
      if ($do->entity_table === 'civicrm_group') {
        $groups[$do->entity_id] = NULL;
      }
      elseif ($do->entity_table === 'civicrm_mailing') {
        $mailings[] = $do->entity_id;
      }
    }

    // As long as we have prior mailings, find their groups and add to the
    // list.
    while (!empty($mailings)) {
      $do = CRM_Core_DAO::executeQuery("
                SELECT      entity_table as entity_table,
                            entity_id as entity_id
                FROM        civicrm_mailing_group
                WHERE       mailing_id IN (" . implode(', ', $mailings) . ")
                    AND     group_type = 'Include'");

      $mailings = [];

      while ($do->fetch()) {
        if ($do->entity_table == 'civicrm_group') {
          $groups[$do->entity_id] = TRUE;
        }
        elseif ($do->entity_table == 'civicrm_mailing') {
          $mailings[] = $do->entity_id;
        }
      }
    }

    $group_ids = array_keys($groups);
    $base_groups = NULL;
    CRM_Utils_Hook::unsubscribeGroups('resubscribe', $mailing_id, $contact_id, $group_ids, $base_groups);

    // Now we have a complete list of recipient groups.  Filter out all
    // those except smart groups and those that the contact belongs to.
    $dao = CRM_Core_DAO::executeQuery("
            SELECT      civicrm_group.id as group_id,
                        civicrm_group.title as title
            FROM        civicrm_group
            LEFT JOIN   civicrm_group_contact as group_contact
                ON      group_contact.group_id = civicrm_group.id
            WHERE       civicrm_group.id IN (" . implode(', ', $group_ids) . ")
                AND     (civicrm_group.saved_search_id is not null
                            OR  (group_contact.contact_id = $contact_id
                                AND group_contact.status = 'Removed')
                        )");

    while ($dao->fetch()) {
      $groups[$dao->group_id] = $dao->title;
    }

    $contacts = [$contact_id];
    foreach ($groups as $group_id => $group_name) {
      $notadded = 0;
      if ($group_name) {
        list($total, $added, $notadded) = CRM_Contact_BAO_GroupContact::addContactsToGroup($contacts, $group_id, 'Email');
      }
      if ($notadded) {
        unset($groups[$group_id]);
      }
    }

    // remove entry from Unsubscribe table.
    $ue = new CRM_Mailing_Event_BAO_MailingEventUnsubscribe();
    $ue->event_queue_id = $queue_id;
    $ue->org_resubscribe = 0;
    if ($ue->find(TRUE)) {
      $ue->delete();
    }

    $transaction->commit();
    return $groups;
  }

  /**
   * Send a response email informing the contact of the groups to which he/she
   * has been resubscribed.
   *
   * @param string $queue_id
   *   The queue event ID.
   * @param array $groups
   *   List of group IDs.
   * @param int $job
   *   The job ID.
   */
  public static function send_resub_response($queue_id, $groups, $job) {
    // param is_domain is not supported as of now.

    $jobTable = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();
    $contacts = CRM_Contact_DAO_Contact::getTableName();
    $email = CRM_Core_DAO_Email::getTableName();
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();

    //get the default domain email address.
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    $dao = new CRM_Mailing_BAO_Mailing();
    $dao->query("   SELECT * FROM $mailingTable
                        INNER JOIN $jobTable ON
                            $jobTable.mailing_id = $mailingTable.id
                        WHERE $jobTable.id = $job");
    $dao->fetch();

    $component = new CRM_Mailing_BAO_MailingComponent();
    $component->id = $dao->resubscribe_id;
    $component->find(TRUE);

    $html = $component->body_html;
    if ($component->body_text) {
      $text = $component->body_text;
    }
    else {
      $text = CRM_Utils_String::htmlToText($component->body_html);
    }

    $eq = new CRM_Core_DAO();
    $eq->query(
      "SELECT
                    $contacts.id as contact_id,
                    $email.email as email,
                    $queue.hash as hash
        FROM        $contacts
        INNER JOIN  $queue ON $queue.contact_id = $contacts.id
        INNER JOIN  $email ON $queue.email_id = $email.id
        WHERE       $queue.id = " . CRM_Utils_Type::escape($queue_id, 'Integer')
    );
    $eq->fetch();
    foreach ($groups as $key => $value) {
      if (!$value) {
        unset($groups[$key]);
      }
    }

    list($addresses, $urls) = CRM_Mailing_BAO_Mailing::getVerpAndUrls($job, $queue_id, $eq->hash);
    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->body_text = $text;
    $bao->body_html = $html;
    $tokens = $bao->getTokens();
    $templates = $bao->getTemplates();

    $html = CRM_Utils_Token::replaceResubscribeTokens($templates['html'], NULL, $groups);
    $html = CRM_Utils_Token::replaceActionTokens($html, $addresses, $urls, TRUE, $tokens['html']);
    $html = CRM_Utils_Token::replaceMailingTokens($html, $dao, NULL, $tokens['html']);

    $text = CRM_Utils_Token::replaceResubscribeTokens($templates['text'], NULL, $groups);
    $text = CRM_Utils_Token::replaceActionTokens($text, $addresses, $urls, FALSE, $tokens['text']);
    $text = CRM_Utils_Token::replaceMailingTokens($text, $dao, NULL, $tokens['text']);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contactId'],
    ]);

    $tokenProcessor->addMessage('body_html', $html, 'text/html');
    $tokenProcessor->addMessage('body_text', $text, 'text/plain');
    $tokenProcessor->addRow(['contactId' => $eq->contact_id]);
    $tokenProcessor->evaluate();
    $html = $tokenProcessor->getRow(0)->render('body_html');
    $text = $tokenProcessor->getRow(0)->render('body_text');

    $params = [
      'subject' => $component->subject,
      'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
      'toEmail' => $eq->email,
      'replyTo' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'returnPath' => CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
      'html' => $html,
      'text' => $text,
      'contactId' => $eq->contact_id,
    ];
    CRM_Mailing_BAO_Mailing::addMessageIdHeader($params, 'e', NULL, $queue_id, $eq->hash);
    if (CRM_Core_BAO_MailSettings::includeMessageId()) {
      $params['messageId'] = $params['Message-ID'];
    }
    CRM_Utils_Mail::send($params);
  }

}
