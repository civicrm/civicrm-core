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
class CRM_Mailing_Event_BAO_Forward extends CRM_Mailing_Event_DAO_Forward {

  /**
   * Create a new forward event, create a new contact if necessary
   *
   * @param $job_id
   * @param $queue_id
   * @param $hash
   * @param $forward_email
   * @param string|null $fromEmail
   * @param array|null $comment
   *
   * @return bool
   */
  public static function &forward($job_id, $queue_id, $hash, $forward_email, $fromEmail = NULL, $comment = NULL) {
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);

    $successfulForward = FALSE;
    $contact_id = NULL;
    if (!$q) {
      return $successfulForward;
    }

    // Find the email address/contact, if it exists.

    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();
    $queueTable = CRM_Mailing_Event_BAO_Queue::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $dao = new CRM_Core_DAO();
    $dao->query("
                SELECT      $contact.id as contact_id,
                            $email.id as email_id,
                            $contact.do_not_email as do_not_email,
                            $queueTable.id as queue_id
                FROM        ($email, $job as temp_job)
                INNER JOIN  $contact
                        ON  $email.contact_id = $contact.id
                LEFT JOIN   $queueTable
                        ON  $email.id = $queueTable.email_id
                LEFT JOIN   $job
                        ON  $queueTable.job_id = $job.id
                        AND temp_job.mailing_id = $job.mailing_id
                WHERE       $queueTable.job_id = $job_id
                    AND     $email.email = '" .
      CRM_Utils_Type::escape($forward_email, 'String') . "'"
    );

    $dao->fetch();

    $transaction = new CRM_Core_Transaction();

    if (isset($dao->queue_id) ||
      (isset($dao->do_not_email) && $dao->do_not_email == 1)
    ) {
      // We already sent this mailing to $forward_email, or we should
      // never email this contact.  Give up.

      return $successfulForward;
    }

    require_once 'api/api.php';
    $contactParams = [
      'email' => $forward_email,
      'version' => 3,
    ];
    $contactValues = civicrm_api('contact', 'get', $contactParams);
    $count = $contactValues['count'];

    if ($count == 0) {
      // If the contact does not exist, create one.

      $formatted = [
        'contact_type' => 'Individual',
        'version' => 3,
      ];
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $value = [
        'email' => $forward_email,
        'location_type_id' => $locationType->id,
      ];
      self::_civicrm_api3_deprecated_add_formatted_param($value, $formatted);
      $formatted['onDuplicate'] = CRM_Import_Parser::DUPLICATE_SKIP;
      $formatted['fixAddress'] = TRUE;
      $contact = civicrm_api('contact', 'create', $formatted);
      if (civicrm_error($contact)) {
        return $successfulForward;
      }
      $contact_id = $contact['id'];
    }
    $email = new CRM_Core_DAO_Email();
    $email->email = $forward_email;
    $email->find(TRUE);
    $email_id = $email->id;
    if (!$contact_id) {
      $contact_id = $email->contact_id;
    }

    // Create a new queue event.

    $queue_params = [
      'email_id' => $email_id,
      'contact_id' => $contact_id,
      'job_id' => $job_id,
    ];

    $queue = CRM_Mailing_Event_BAO_Queue::create($queue_params);

    $forward = new CRM_Mailing_Event_BAO_Forward();
    $forward->time_stamp = date('YmdHis');
    $forward->event_queue_id = $queue_id;
    $forward->dest_queue_id = $queue->id;
    $forward->save();

    $dao->reset();
    $dao->query("   SELECT  $job.mailing_id as mailing_id
                        FROM    $job
                        WHERE   $job.id = " .
      CRM_Utils_Type::escape($job_id, 'Integer')
    );
    $dao->fetch();
    $mailing_obj = new CRM_Mailing_BAO_Mailing();
    $mailing_obj->id = $dao->mailing_id;
    $mailing_obj->find(TRUE);

    $config = CRM_Core_Config::singleton();
    $mailer = \Civi::service('pear_mail');

    $recipient = NULL;
    $attachments = NULL;
    $message = $mailing_obj->compose($job_id, $queue->id, $queue->hash,
      $queue->contact_id, $forward_email, $recipient, FALSE, NULL, $attachments, TRUE, $fromEmail
    );
    //append comment if added while forwarding.
    if (count($comment)) {
      $message->_txtbody = CRM_Utils_Array::value('body_text', $comment) . $message->_txtbody;
      if (!empty($comment['body_html'])) {
        $message->_htmlbody = $comment['body_html'] . '<br />---------------Original message---------------------<br />' . $message->_htmlbody;
      }
    }

    $body = $message->get();
    $headers = $message->headers();

    $result = NULL;
    if (is_object($mailer)) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      $result = $mailer->send($recipient, $headers, $body);
      unset($errorScope);
    }

    $params = [
      'event_queue_id' => $queue->id,
      'job_id' => $job_id,
      'hash' => $queue->hash,
    ];
    if (is_a($result, 'PEAR_Error')) {
      // Register the bounce event.

      $params = array_merge($params,
        CRM_Mailing_BAO_BouncePattern::match($result->getMessage())
      );
      CRM_Mailing_Event_BAO_Bounce::create($params);
    }
    else {
      $successfulForward = TRUE;
      // Register the delivery event.

      CRM_Mailing_Event_BAO_Delivered::create($params);
    }

    $transaction->commit();

    return $successfulForward;
  }

  /**
   * This function adds the contact variable in $values to the
   * parameter list $params.  For most cases, $values should have length 1.  If
   * the variable being added is a child of Location, a location_type_id must
   * also be included.  If it is a child of phone, a phone_type must be included.
   *
   * @param array $values
   *   The variable(s) to be added.
   * @param array $params
   *   The structured parameter list.
   *
   * @return bool|CRM_Utils_Error
   */
  protected static function _civicrm_api3_deprecated_add_formatted_param(&$values, &$params) {
    // @todo - most of this code is UNREACHABLE.
    // Crawl through the possible classes:
    // Contact
    //      Individual
    //      Household
    //      Organization
    //          Location
    //              Address
    //              Email
    //              Phone
    //              IM
    //      Note
    //      Custom

    // Cache the various object fields
    static $fields = NULL;

    if ($fields == NULL) {
      $fields = [];
    }

    // first add core contact values since for other Civi modules they are not added
    require_once 'CRM/Contact/BAO/Contact.php';
    $contactFields = CRM_Contact_DAO_Contact::fields();
    _civicrm_api3_store_values($contactFields, $values, $params);

    if (isset($values['contact_type'])) {
      // we're an individual/household/org property

      $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

      _civicrm_api3_store_values($fields[$values['contact_type']], $values, $params);
      return TRUE;
    }

    if (isset($values['individual_prefix'])) {
      if (!empty($params['prefix_id'])) {
        $prefixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
        $params['prefix'] = $prefixes[$params['prefix_id']];
      }
      else {
        $params['prefix'] = $values['individual_prefix'];
      }
      return TRUE;
    }

    if (isset($values['individual_suffix'])) {
      if (!empty($params['suffix_id'])) {
        $suffixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
        $params['suffix'] = $suffixes[$params['suffix_id']];
      }
      else {
        $params['suffix'] = $values['individual_suffix'];
      }
      return TRUE;
    }

    // CRM-4575
    if (isset($values['email_greeting'])) {
      if (!empty($params['email_greeting_id'])) {
        $emailGreetingFilter = [
          'contact_type' => $params['contact_type'] ?? NULL,
          'greeting_type' => 'email_greeting',
        ];
        $emailGreetings = CRM_Core_PseudoConstant::greeting($emailGreetingFilter);
        $params['email_greeting'] = $emailGreetings[$params['email_greeting_id']];
      }
      else {
        $params['email_greeting'] = $values['email_greeting'];
      }

      return TRUE;
    }

    if (isset($values['postal_greeting'])) {
      if (!empty($params['postal_greeting_id'])) {
        $postalGreetingFilter = [
          'contact_type' => $params['contact_type'] ?? NULL,
          'greeting_type' => 'postal_greeting',
        ];
        $postalGreetings = CRM_Core_PseudoConstant::greeting($postalGreetingFilter);
        $params['postal_greeting'] = $postalGreetings[$params['postal_greeting_id']];
      }
      else {
        $params['postal_greeting'] = $values['postal_greeting'];
      }
      return TRUE;
    }

    if (isset($values['addressee'])) {
      $params['addressee'] = $values['addressee'];
      return TRUE;
    }

    if (isset($values['gender'])) {
      if (!empty($params['gender_id'])) {
        $genders = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
        $params['gender'] = $genders[$params['gender_id']];
      }
      else {
        $params['gender'] = $values['gender'];
      }
      return TRUE;
    }

    if (!empty($values['preferred_communication_method'])) {
      $comm = [];
      $pcm = array_change_key_case(array_flip(CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method')), CASE_LOWER);

      $preffComm = explode(',', $values['preferred_communication_method']);
      foreach ($preffComm as $v) {
        $v = strtolower(trim($v));
        if (array_key_exists($v, $pcm)) {
          $comm[$pcm[$v]] = 1;
        }
      }

      $params['preferred_communication_method'] = $comm;
      return TRUE;
    }

    // format the website params.
    if (!empty($values['url'])) {
      static $websiteFields;
      if (!is_array($websiteFields)) {
        require_once 'CRM/Core/DAO/Website.php';
        $websiteFields = CRM_Core_DAO_Website::fields();
      }
      if (!array_key_exists('website', $params) ||
        !is_array($params['website'])
      ) {
        $params['website'] = [];
      }

      $websiteCount = count($params['website']);
      _civicrm_api3_store_values($websiteFields, $values,
        $params['website'][++$websiteCount]
      );

      return TRUE;
    }

    // get the formatted location blocks into params - w/ 3.0 format, CRM-4605
    if (!empty($values['location_type_id'])) {
      static $fields = NULL;
      if ($fields == NULL) {
        $fields = [];
      }

      foreach (['Phone', 'Email', 'IM', 'OpenID', 'Phone_Ext'] as $block) {
        $name = strtolower($block);
        if (!array_key_exists($name, $values)) {
          continue;
        }

        if ($name === 'phone_ext') {
          $block = 'Phone';
        }

        // block present in value array.
        if (!array_key_exists($name, $params) || !is_array($params[$name])) {
          $params[$name] = [];
        }

        if (!array_key_exists($block, $fields)) {
          $className = "CRM_Core_DAO_$block";
          $fields[$block] =& $className::fields();
        }

        $blockCnt = count($params[$name]);

        // copy value to dao field name.
        if ($name == 'im') {
          $values['name'] = $values[$name];
        }

        _civicrm_api3_store_values($fields[$block], $values,
          $params[$name][++$blockCnt]
        );

        if (empty($params['id']) && ($blockCnt == 1)) {
          $params[$name][$blockCnt]['is_primary'] = TRUE;
        }

        // we only process single block at a time.
        return TRUE;
      }

      // handle address fields.
      if (!array_key_exists('address', $params) || !is_array($params['address'])) {
        $params['address'] = [];
      }

      $addressCnt = 1;
      foreach ($params['address'] as $cnt => $addressBlock) {
        if (CRM_Utils_Array::value('location_type_id', $values) ==
          CRM_Utils_Array::value('location_type_id', $addressBlock)
        ) {
          $addressCnt = $cnt;
          break;
        }
        $addressCnt++;
      }

      if (!array_key_exists('Address', $fields)) {
        $fields['Address'] = CRM_Core_DAO_Address::fields();
      }

      // Note: we doing multiple value formatting here for address custom fields, plus putting into right format.
      // The actual formatting (like date, country ..etc) for address custom fields is taken care of while saving
      // the address in CRM_Core_BAO_Address::create method
      if (!empty($values['location_type_id'])) {
        static $customFields = [];
        if (empty($customFields)) {
          $customFields = CRM_Core_BAO_CustomField::getFields('Address');
        }
        // make a copy of values, as we going to make changes
        $newValues = $values;
        foreach ($values as $key => $val) {
          $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
          if ($customFieldID && array_key_exists($customFieldID, $customFields)) {
            // mark an entry in fields array since we want the value of custom field to be copied
            $fields['Address'][$key] = NULL;

            $htmlType = $customFields[$customFieldID]['html_type'] ?? NULL;
            if (CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]) && $val) {
              $mulValues = explode(',', $val);
              $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
              $newValues[$key] = [];
              foreach ($mulValues as $v1) {
                foreach ($customOption as $v2) {
                  if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                    (strtolower($v2['value']) == strtolower(trim($v1)))
                  ) {
                    if ($htmlType == 'CheckBox') {
                      $newValues[$key][$v2['value']] = 1;
                    }
                    else {
                      $newValues[$key][] = $v2['value'];
                    }
                  }
                }
              }
            }
          }
        }
        // consider new values
        $values = $newValues;
      }

      _civicrm_api3_store_values($fields['Address'], $values, $params['address'][$addressCnt]);

      $addressFields = [
        'county',
        'country',
        'state_province',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'StateProvince.name',
      ];

      foreach ($addressFields as $field) {
        if (array_key_exists($field, $values)) {
          if (!array_key_exists('address', $params)) {
            $params['address'] = [];
          }
          $params['address'][$addressCnt][$field] = $values[$field];
        }
      }

      if ($addressCnt == 1) {

        $params['address'][$addressCnt]['is_primary'] = TRUE;
      }
      return TRUE;
    }

    if (isset($values['note'])) {
      // add a note field
      if (!isset($params['note'])) {
        $params['note'] = [];
      }
      $noteBlock = count($params['note']) + 1;

      $params['note'][$noteBlock] = [];
      if (!isset($fields['Note'])) {
        $fields['Note'] = CRM_Core_DAO_Note::fields();
      }

      // get the current logged in civicrm user
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');

      if ($userID) {
        $values['contact_id'] = $userID;
      }

      _civicrm_api3_store_values($fields['Note'], $values, $params['note'][$noteBlock]);

      return TRUE;
    }

    // Check for custom field values

    if (empty($fields['custom'])) {
      $fields['custom'] = &CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $values),
        FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE
      );
    }

    foreach ($values as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        // check if it's a valid custom field id

        if (!array_key_exists($customFieldID, $fields['custom'])) {
          return civicrm_api3_create_error('Invalid custom field ID');
        }
        else {
          $params[$key] = $value;
        }
      }
    }
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
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE
  ) {
    $dao = new CRM_Core_DAO();

    $forward = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $query = "
            SELECT      COUNT($forward.id) as forward
            FROM        $forward
            INNER JOIN  $queue
                    ON  $forward.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    // query was missing
    $dao->query($query);

    if ($dao->fetch()) {
      return $dao->forward;
    }

    return NULL;
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
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $offset = NULL, $rowCount = NULL, $sort = NULL
  ) {

    $dao = new CRM_Core_DAO();

    $forward = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as from_name,
                        $contact.id as from_id,
                        $email.email as from_email,
                        dest_contact.id as dest_id,
                        dest_email.email as dest_email,
                        $forward.time_stamp as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $forward
                    ON  $forward.event_queue_id = $queue.id
            INNER JOIN  $queue as dest_queue
                    ON  $forward.dest_queue_id = dest_queue.id
            INNER JOIN  $contact as dest_contact
                    ON  dest_queue.contact_id = dest_contact.id
            INNER JOIN  $email as dest_email
                    ON  dest_queue.email_id = dest_email.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id, dest_contact.id, dest_email.email, $forward.time_stamp ";
    }

    $orderBy = "$contact.sort_name ASC, {$forward}.time_stamp DESC";
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
      $from_url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->from_id}"
      );
      $dest_url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->dest_id}"
      );
      $results[] = [
        'from_name' => "<a href=\"$from_url\">{$dao->from_name}</a>",
        'from_email' => $dao->from_email,
        'dest_email' => "<a href=\"$dest_url\">{$dao->dest_email}</a>",
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

}
