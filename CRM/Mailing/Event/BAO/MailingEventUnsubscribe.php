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
 * Class CRM_Mailing_Event_BAO_Unsubscribe
 */
class CRM_Mailing_Event_BAO_MailingEventUnsubscribe extends CRM_Mailing_Event_DAO_MailingEventUnsubscribe {

  /**
   * Unsubscribe a contact from the domain.
   *
   * @param null $unused
   * @param int $queue_id
   *   The Queue Event ID of the recipient.
   * @param string $hash
   *   The hash.
   *
   * @return bool
   *   Was the contact successfully unsubscribed?
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function unsub_from_domain($unused, $queue_id, $hash): bool {
    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $queue_id, $hash);
    if (!$q) {
      return FALSE;
    }

    $transaction = new CRM_Core_Transaction();

    $now = date('YmdHis');
    if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
      $email = new CRM_Core_BAO_Email();
      $email->id = $q->email_id;
      if ($email->find(TRUE)) {
        $sql = '
UPDATE civicrm_email
SET    on_hold = 2,
       hold_date = %1
WHERE  email = %2
';
        $sqlParams = [
          1 => [$now, 'Timestamp'],
          2 => [$email->email, 'String'],
        ];
        CRM_Core_DAO::executeQuery($sql, $sqlParams);
      }
    }
    else {
      $contact = new CRM_Contact_BAO_Contact();
      $contact->id = $q->contact_id;
      $contact->is_opt_out = TRUE;
      $contact->save();
    }

    $ue = new CRM_Mailing_Event_BAO_MailingEventUnsubscribe();
    $ue->event_queue_id = $queue_id;
    $ue->org_unsubscribe = 1;
    $ue->time_stamp = $now;
    $ue->save();

    $shParams = [
      'contact_id' => $q->contact_id,
      'group_id' => NULL,
      'status' => 'Removed',
      'method' => 'Email',
      'tracking' => $ue->id,
    ];
    CRM_Contact_BAO_SubscriptionHistory::create($shParams);

    $transaction->commit();

    return TRUE;
  }

  /**
   * Unsubscribe a contact from all groups that received this mailing.
   *
   * @param null $unused
   * @param int $queue_id
   *   The Queue Event ID of the recipient.
   * @param string $hash
   *   The hash.
   * @param bool $return
   *   If true return the list of groups.
   *
   * @return array|null
   *   $groups    Array of all groups from which the contact was removed, or null if the queue event could not be found.
   *
   * @throws \CRM_Core_Exception
   */
  public static function unsub_from_mailing($unused, $queue_id, $hash, $return = FALSE): ?array {
    // First make sure there's a matching queue event.

    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $queue_id, $hash);
    if (!$q) {
      return NULL;
    }

    $contact_id = $q->contact_id;

    $relevant_mailing_id = self::getRelevantMailingID($queue_id);

    $do = CRM_Core_DAO::executeQuery(
      "SELECT entity_table, entity_id, group_type
        FROM civicrm_mailing_group
       WHERE mailing_id = $relevant_mailing_id
         AND group_type IN ('Include', 'Base')");

    $groups = [];
    $base_groups = [];
    $mailings = [];

    while ($do->fetch()) {
      // @todo this is should be a temporary measure until we stop storing the translated table name in the database
      if (substr($do->entity_table, 0, 13) === 'civicrm_group') {
        if ($do->group_type === 'Base') {
          $base_groups[$do->entity_id] = NULL;
        }
        else {
          $groups[$do->entity_id] = NULL;
        }
      }
      elseif (substr($do->entity_table, 0, 15) === 'civicrm_mailing') {
        // @todo this is should be a temporary measure until we stop storing the translated table name in the database
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
        // @todo this is should be a temporary measure until we stop storing the translated table name in the database
        if (substr($do->entity_table, 0, 13) === 'civicrm_group') {
          $groups[$do->entity_id] = TRUE;
        }
        elseif (substr($do->entity_table, 0, 15) === 'civicrm_mailing') {
          // @todo this is should be a temporary measure until we stop storing the translated table name in the database
          $mailings[] = $do->entity_id;
        }
      }
    }

    //Pass the groups to be unsubscribed from through a hook.
    $groupIds = array_keys($groups);
    //include child groups if any
    $groupIds = array_merge($groupIds, CRM_Contact_BAO_Group::getChildGroupIds($groupIds));

    $baseGroupIds = array_keys($base_groups);
    CRM_Utils_Hook::unsubscribeGroups('unsubscribe', $q->mailing_id, $contact_id, $groupIds, $baseGroupIds);

    // Now we have a complete list of recipient groups.  Filter out all
    // those except smart groups, those that the contact belongs to and
    // base groups from search based mailings.
    $baseGroupClause = '';
    if (!empty($baseGroupIds)) {
      $baseGroupClause = "OR  grp.id IN(" . implode(', ', $baseGroupIds) . ")";
    }
    $groupIdClause = '';
    if ($groupIds || $baseGroupIds) {
      $groupIdClause = "AND grp.id IN (" . implode(', ', array_merge($groupIds, $baseGroupIds)) . ")";
      // Check that groupcontactcache is up to date so we can get smartgroups
      CRM_Contact_BAO_GroupContactCache::check(array_merge($groupIds, $baseGroupIds));
    }

    /* https://lab.civicrm.org/dev/core/-/issues/3031
     * When 2 separate tables are referenced in an OR clause the index will be used on one & not the other. At the sql
     * level we usually deal with this by using UNION to join the 2 queries together - the patch is doing the same thing at
     * the php level & probably as a result performs better than the original not-that-bad OR clause did & likely similarly to
     * how a UNION would work.
     */
    $groupsCachedSQL = "
            SELECT      grp.id as id,
                        grp.title as title,
                        grp.frontend_title as frontend_title,
                        grp.frontend_description as frontend_description,
                        grp.description as description,
                        grp.saved_search_id as saved_search_id
            FROM        civicrm_group grp
            LEFT JOIN   civicrm_group_contact_cache gcc
                ON      gcc.group_id = grp.id
            WHERE       grp.is_hidden = 0
                        $groupIdClause
                AND     ((grp.saved_search_id is not null AND gcc.contact_id = %1)
                            $baseGroupClause
                        ) GROUP BY grp.id";

    $groupsAddedSQL = "
            SELECT      grp.id as id,
                        grp.title as title,
                        grp.frontend_title as frontend_title,
                        grp.frontend_description as frontend_description,
                        grp.description as description,
                        grp.saved_search_id as saved_search_id
            FROM        civicrm_group grp
            LEFT JOIN   civicrm_group_contact gc
                ON      gc.group_id = grp.id
            WHERE       grp.is_hidden = 0
                        $groupIdClause
                AND     ((gc.contact_id = %1
                                AND gc.status = 'Added')
                            $baseGroupClause
                        ) GROUP BY grp.id";
    $groupsParams = [
      1 => [$contact_id, 'Positive'],
    ];
    $doCached = CRM_Core_DAO::executeQuery($groupsCachedSQL, $groupsParams);
    $doAdded = CRM_Core_DAO::executeQuery($groupsAddedSQL, $groupsParams);
    $allGroups = $doAdded->fetchAll() + $doCached->fetchAll();
    if ($return) {
      $returnGroups = [];
      foreach ($allGroups as $group) {
        $returnGroups[$group['id']] = [
          'title' => $group['frontend_title'],
          'description' => $group['frontend_description'],
        ];
      }
      return $returnGroups;
    }
    foreach ($allGroups as $group) {
      $groups[$group['id']] = $group['frontend_title'];
    }
    $transaction = new CRM_Core_Transaction();
    $contacts = [$contact_id];
    foreach ($groups as $group_id => $group_name) {
      $notremoved = FALSE;
      if ($group_name) {
        if (in_array($group_id, $baseGroupIds)) {
          [$total, $removed, $notremoved] = CRM_Contact_BAO_GroupContact::addContactsToGroup($contacts, $group_id, 'Email', 'Removed');
        }
        else {
          [$total, $removed, $notremoved] = CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contacts, $group_id, 'Email');
        }
      }
      if ($notremoved) {
        unset($groups[$group_id]);
      }
    }

    $ue = new CRM_Mailing_Event_BAO_MailingEventUnsubscribe();
    $ue->event_queue_id = $queue_id;
    $ue->org_unsubscribe = 0;
    $ue->time_stamp = date('YmdHis');
    $ue->save();

    $transaction->commit();
    return $groups;
  }

  /**
   * Send a response email informing the contact of the groups from which he.
   * has been unsubscribed.
   *
   * @param int $queue_id
   *   The queue event ID.
   * @param array|null $groups
   *   List of group IDs.
   * @param bool $is_domain
   *   Is this domain-level?.
   * @param int $job
   *   The job ID.
   *
   * @throws \CRM_Core_Exception
   */
  public static function send_unsub_response($queue_id, $groups, $is_domain, $job) {
    $domain = CRM_Core_BAO_Domain::getDomain();
    $mailingObject = new CRM_Mailing_DAO_Mailing();
    $mailingTable = $mailingObject->getTableName();
    $contactsObject = new CRM_Contact_DAO_Contact();
    $contacts = $contactsObject->getTableName();
    $emailObject = new CRM_Core_DAO_Email();
    $email = $emailObject->getTableName();
    $queueObject = new CRM_Mailing_Event_BAO_MailingEventQueue();
    $queue = $queueObject->getTableName();

    //get the default domain email address.
    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

    $dao = new CRM_Mailing_BAO_Mailing();
    $dao->query("   SELECT * FROM $mailingTable
                        INNER JOIN civicrm_mailing_event_queue queue ON
                            queue.mailing_id = $mailingTable.id
                        WHERE queue.id = $queue_id");
    $dao->fetch();

    $component = new CRM_Mailing_BAO_MailingComponent();

    if ($is_domain) {
      $component->id = $dao->optout_id;
    }
    else {
      $component->id = $dao->unsubscribe_id;
    }
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

    if ($groups) {
      foreach ($groups as $key => $value) {
        if (!$value) {
          unset($groups[$key]);
        }
      }
    }

    [$addresses, $urls] = CRM_Mailing_BAO_Mailing::getVerpAndUrls($job, $queue_id, $eq->hash);
    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->body_text = $text;
    $bao->body_html = $html;
    $tokens = $bao->getTokens();
    $templates = $bao->getTemplates();

    $html = CRM_Utils_Token::replaceUnsubscribeTokens($templates['html'], $domain, $groups, TRUE, $eq->contact_id, $eq->hash);
    $html = CRM_Utils_Token::replaceActionTokens($html, $addresses, $urls, TRUE, $tokens['html']);
    $html = CRM_Utils_Token::replaceMailingTokens($html, $dao, NULL, $tokens['html']);

    $text = CRM_Utils_Token::replaceUnsubscribeTokens($templates['text'], $domain, $groups, FALSE, $eq->contact_id, $eq->hash);
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
    CRM_Mailing_BAO_Mailing::addMessageIdHeader($params, 'u', NULL, $queue_id, $eq->hash);
    if (CRM_Core_BAO_MailSettings::includeMessageId()) {
      $params['messageId'] = $params['Message-ID'];
    }
    CRM_Utils_Mail::send($params);
  }

  /**
   * Get row count for the event selector.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of a job to filter on.
   * @param bool $is_distinct
   *   Group by queue ID?.
   *
   * @param string $org_unsubscribe
   *
   * @param string $toDate
   *
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $org_unsubscribe = NULL, $toDate = NULL
  ) {
    $dao = new CRM_Core_DAO();

    $unsub = self::getTableName();
    $queueObject = new CRM_Mailing_Event_BAO_MailingEventQueue();
    $queue = $queueObject->getTableName();
    $mailingObject = new CRM_Mailing_BAO_Mailing();
    $mailing = $mailingObject->getTableName();
    $jobObject = new CRM_Mailing_BAO_MailingJob();
    $job = $jobObject->getTableName();

    $query = "
            SELECT      COUNT($unsub.id) as unsubs
            FROM        $unsub
            INNER JOIN  $queue
                    ON  $unsub.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($toDate)) {
      $query .= " AND $unsub.time_stamp <= $toDate";
    }

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($org_unsubscribe !== NULL) {
      $query .= " AND $unsub.org_unsubscribe = " . ($org_unsubscribe ? 0 : 1);
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $dao->query($query);
    $dao->fetch();
    if ($is_distinct) {
      return $dao->N;
    }
    else {
      return $dao->unsubs ?: 0;
    }
  }

  /**
   * Get rows for the event browser.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of the job.
   * @param bool $is_distinct
   *   Group by queue id?.
   * @param int $offset
   *   Offset.
   * @param int $rowCount
   *   Number of rows.
   * @param array $sort
   *   Sort array.
   *
   * @param null $org_unsubscribe
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $offset = NULL, $rowCount = NULL, $sort = NULL,
    $org_unsubscribe = NULL
  ) {

    $dao = new CRM_Core_DAO();

    $unsub = self::getTableName();
    $queueObject = new CRM_Mailing_Event_BAO_MailingEventQueue();
    $queue = $queueObject->getTableName();
    $mailingObject = new CRM_Mailing_BAO_Mailing();
    $mailing = $mailingObject->getTableName();
    $jobObject = new CRM_Mailing_BAO_MailingJob();
    $job = $jobObject->getTableName();
    $contactObject = new CRM_Contact_BAO_Contact();
    $contact = $contactObject->getTableName();
    $emailObject = new CRM_Core_BAO_Email();
    $email = $emailObject->getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $unsub.time_stamp as date,
                        $unsub.org_unsubscribe as org_unsubscribe
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $unsub
                    ON  $unsub.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($org_unsubscribe !== NULL) {
      $query .= " AND $unsub.org_unsubscribe = " . ($org_unsubscribe ? 0 : 1);
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id, $unsub.time_stamp, $unsub.org_unsubscribe";
    }

    $orderBy = "sort_name ASC, {$unsub}.time_stamp DESC";
    if ($sort) {
      if (is_string($sort)) {
        $sort = CRM_Utils_Type::escape($sort, 'String');
        $orderBy = $sort;
      }
      else {
        $orderBy = trim($sort->orderBy());
      }
    }

    $query .= " ORDER BY {$orderBy} ";

    if ($offset || $rowCount) {
      //Added "||$rowCount" to avoid displaying all records on first page
      $query .= ' LIMIT ' . CRM_Utils_Type::escape($offset, 'Integer') . ', ' . CRM_Utils_Type::escape($rowCount, 'Integer');
    }

    $dao->query($query);

    $results = [];

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[] = [
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        // Next value displays in selector under either Unsubscribe OR Optout column header, so always s/b Yes.
        'unsubOrOptout' => ts('Yes'),
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

  /**
   * @param int $queueID
   *
   * @return array
   */
  public static function getContactInfo($queueID) {
    $query = "
SELECT DISTINCT(civicrm_mailing_event_queue.contact_id) as contact_id,
       civicrm_contact.display_name as display_name
       civicrm_email.email as email
  FROM civicrm_mailing_event_queue,
       civicrm_contact,
       civicrm_email
 WHERE civicrm_mailing_event_queue.contact_id = civicrm_contact.id
   AND civicrm_mailing_event_queue.email_id = civicrm_email.id
   AND civicrm_mailing_event_queue.id = " . CRM_Utils_Type::escape($queueID, 'Integer');

    $dao = CRM_Core_DAO::executeQuery($query);

    $displayName = 'Unknown';
    $email = 'Unknown';
    if ($dao->fetch()) {
      $displayName = $dao->display_name;
      $email = $dao->email;
    }

    return [$displayName, $email];
  }

  /**
   * Get the mailing ID that is relevant to the given queue record.
   *
   * If we are unsubscribing from one of an a-b test we want to unsubscribe from the
   * 'a' variant which has the relevant information.
   *
   * @param int $queue_id
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  private static function getRelevantMailingID(int $queue_id): int {
    $mailing_id = (int) civicrm_api3('MailingEventQueue', 'getvalue', [
      'id' => $queue_id,
      'return' => 'mailing_id',
    ]);
    $mailing_type = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $mailing_id, 'mailing_type', 'id');

    // We need a mailing id that points to the mailing that defined the recipients.
    // This is usually just the passed-in mailing_id, however in the case of AB
    // tests, it's the variant 'A' one.
    $relevant_mailing_id = $mailing_id;

    // Special case for AB Tests:
    if (in_array($mailing_type, ['experiment', 'winner'])) {
      // The mailing belongs to an AB test.
      // See if we can find an AB test where this is variant B.
      $mailing_id_a = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_MailingAB', $mailing_id, 'mailing_id_a', 'mailing_id_b');
      if (!empty($mailing_id_a)) {
        // OK, we were given mailing B and we looked up variant A which is the relevant one.
        $relevant_mailing_id = $mailing_id_a;
      }
      else {
        // No, it wasn't variant B, let's see if we can find an AB test where
        // the given mailing was the winner (C).
        $mailing_id_a = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_MailingAB', $mailing_id, 'mailing_id_a', 'mailing_id_c');
        if (!empty($mailing_id_a)) {
          // OK, this was the winner and we looked up variant A which is the relevant one.
          $relevant_mailing_id = $mailing_id_a;
        }
        // (otherwise we were passed in variant A so we already have the relevant_mailing_id correct already.)
      }
    }

    // Make a list of groups and a list of prior mailings that received this
    // mailing.  Nb. the 'Base' group is called the 'Unsubscribe group' in the
    // UI.
    // Just to definitely make it SQL safe.
    return (int) $relevant_mailing_id;
  }

}
