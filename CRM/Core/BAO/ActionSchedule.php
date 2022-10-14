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

use Civi\ActionSchedule\Event\MappingRegisterEvent;

/**
 * This class contains functions for managing Scheduled Reminders
 */
class CRM_Core_BAO_ActionSchedule extends CRM_Core_DAO_ActionSchedule {

  /**
   * @param array $filters
   *   Filter by property (e.g. 'id').
   *
   * @return array
   *   Array(scalar $id => Mapping $mapping).
   *
   * @throws \CRM_Core_Exception
   */
  public static function getMappings($filters = NULL) {
    static $_action_mapping;

    if ($_action_mapping === NULL) {
      $event = \Civi::dispatcher()
        ->dispatch('civi.actionSchedule.getMappings',
          new MappingRegisterEvent());
      $_action_mapping = $event->getMappings();
    }

    if (empty($filters)) {
      return $_action_mapping;
    }
    if (isset($filters['id'])) {
      return [$filters['id'] => $_action_mapping[$filters['id']]];
    }
    throw new CRM_Core_Exception("getMappings() called with unsupported filter: " . implode(', ', array_keys($filters)));
  }

  /**
   * @param string|int $id
   *
   * @return \Civi\ActionSchedule\Mapping|NULL
   * @throws \CRM_Core_Exception
   */
  public static function getMapping($id) {
    $mappings = self::getMappings();
    return $mappings[$id] ?? NULL;
  }

  /**
   * For each entity, get a list of entity-value labels.
   *
   * @return array
   *   Ex: $entityValueLabels[$mappingId][$valueId] = $valueLabel.
   * @throws CRM_Core_Exception
   */
  public static function getAllEntityValueLabels() {
    $entityValueLabels = [];
    foreach (CRM_Core_BAO_ActionSchedule::getMappings() as $mapping) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */
      $entityValueLabels[$mapping->getId()] = $mapping->getValueLabels();
      $valueLabel = ['- ' . strtolower($mapping->getValueHeader()) . ' -'];
      $entityValueLabels[$mapping->getId()] = $valueLabel + $entityValueLabels[$mapping->getId()];
    }
    return $entityValueLabels;
  }

  /**
   * For each entity, get a list of entity-status labels.
   *
   * @return array
   *   Ex: $entityValueLabels[$mappingId][$valueId][$statusId] = $statusLabel.
   */
  public static function getAllEntityStatusLabels() {
    $entityValueLabels = self::getAllEntityValueLabels();
    $entityStatusLabels = [];
    foreach (CRM_Core_BAO_ActionSchedule::getMappings() as $mapping) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */
      $statusLabel = ['- ' . strtolower($mapping->getStatusHeader()) . ' -'];
      $entityStatusLabels[$mapping->getId()] = $entityValueLabels[$mapping->getId()];
      foreach ($entityStatusLabels[$mapping->getId()] as $kkey => & $vval) {
        $vval = $statusLabel + $mapping->getStatusLabels($kkey);
      }
    }
    return $entityStatusLabels;
  }

  /**
   * Retrieve list of Scheduled Reminders.
   *
   * @param \Civi\ActionSchedule\Mapping|null $filterMapping
   *   Filter by the schedule's mapping type.
   * @param int $filterValue
   *   Filter by the schedule's entity_value.
   *
   * @return array
   *   (reference)   reminder list
   * @throws \CRM_Core_Exception
   */
  public static function getList($filterMapping = NULL, $filterValue = NULL): array {
    $list = [];
    $query = "
SELECT
       title,
       cas.id as id,
       cas.mapping_id,
       cas.entity_value as entityValueIds,
       cas.entity_status as entityStatusIds,
       cas.start_action_date as entityDate,
       cas.start_action_offset,
       cas.start_action_unit,
       cas.start_action_condition,
       cas.absolute_date,
       is_repeat,
       is_active

FROM civicrm_action_schedule cas
";
    $queryParams = [];
    $where = " WHERE 1 ";
    if ($filterMapping and $filterValue) {
      $where .= " AND cas.entity_value = %1 AND cas.mapping_id = %2";
      $queryParams[1] = [$filterValue, 'Integer'];
      $queryParams[2] = [$filterMapping->getId(), 'String'];
    }
    $where .= " AND cas.used_for IS NULL";
    $query .= $where;
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      /** @var Civi\ActionSchedule\Mapping $filterMapping */
      $filterMapping = CRM_Utils_Array::first(self::getMappings([
        'id' => $dao->mapping_id,
      ]));
      $list[$dao->id]['id'] = $dao->id;
      $list[$dao->id]['title'] = $dao->title;
      $list[$dao->id]['start_action_offset'] = $dao->start_action_offset;
      $list[$dao->id]['start_action_unit'] = $dao->start_action_unit;
      $list[$dao->id]['start_action_condition'] = $dao->start_action_condition;
      $list[$dao->id]['entityDate'] = ucwords(str_replace('_', ' ', $dao->entityDate));
      $list[$dao->id]['absolute_date'] = $dao->absolute_date;
      $list[$dao->id]['entity'] = $filterMapping->getLabel();
      $list[$dao->id]['value'] = implode(', ', CRM_Utils_Array::subset(
        $filterMapping->getValueLabels(),
        explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityValueIds)
      ));
      $list[$dao->id]['status'] = implode(', ', CRM_Utils_Array::subset(
        $filterMapping->getStatusLabels($dao->entityValueIds),
        explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityStatusIds)
      ));
      $list[$dao->id]['is_repeat'] = $dao->is_repeat;
      $list[$dao->id]['is_active'] = $dao->is_active;
    }

    return $list;
  }

  /**
   * Add the scheduled reminders in the db.
   *
   * @param array $params
   *   An assoc array of name/value pairs.
   *
   * @deprecated
   * @return CRM_Core_DAO_ActionSchedule
   * @throws \CRM_Core_Exception
   */
  public static function add(array $params): CRM_Core_DAO_ActionSchedule {
    return self::writeRecord($params);
  }

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    if (empty($params)) {
      return NULL;
    }
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Delete a Reminder.
   *
   * @param int $id
   * @deprecated
   * @throws CRM_Core_Exception
   */
  public static function del($id) {
    self::deleteRecord(['id' => $id]);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_ActionSchedule', $id, 'is_active', $is_active);
  }

  /**
   * @param int $mappingID
   * @param $now
   *
   * @throws CRM_Core_Exception
   */
  public static function sendMailings($mappingID, $now) {
    $mapping = CRM_Utils_Array::first(self::getMappings([
      'id' => $mappingID,
    ]));

    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    $actionSchedule->find(FALSE);

    while ($actionSchedule->fetch()) {
      $query = CRM_Core_BAO_ActionSchedule::prepareMailingQuery($mapping, $actionSchedule);
      $dao = CRM_Core_DAO::executeQuery($query,
        [1 => [$actionSchedule->id, 'Integer']]
      );

      $multilingual = CRM_Core_I18n::isMultilingual();
      $tokenProcessor = self::createTokenProcessor($actionSchedule, $mapping);
      while ($dao->fetch()) {
        $row = $tokenProcessor->addRow()
          ->context('contactId', $dao->contactID)
          ->context('actionSearchResult', (object) $dao->toArray());

        // switch language if necessary
        if ($multilingual) {
          $preferred_language = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $dao->contactID, 'preferred_language');
          $row->context('locale', CRM_Core_BAO_ActionSchedule::pickLocale($actionSchedule->communication_language, $preferred_language));
        }

        foreach ($dao->toArray() as $key => $value) {
          if (preg_match('/^tokenContext_(.*)/', $key, $m)) {
            if (!in_array($m[1], $tokenProcessor->context['schema'])) {
              $tokenProcessor->context['schema'][] = $m[1];
            }
            $row->context($m[1], $value);
          }
        }
      }

      $tokenProcessor->evaluate();
      foreach ($tokenProcessor->getRows() as $tokenRow) {
        $dao = $tokenRow->context['actionSearchResult'];
        $errors = [];

        // It's possible, eg, that sendReminderEmail fires Hook::alterMailParams() and that some listener use ts().
        $swapLocale = empty($row->context['locale']) ? NULL : \CRM_Utils_AutoClean::swapLocale($row->context['locale']);

        if ($actionSchedule->mode === 'SMS' || $actionSchedule->mode === 'User_Preference') {
          CRM_Utils_Array::extend($errors, self::sendReminderSms($tokenRow, $actionSchedule, $dao->contactID));
        }

        if ($actionSchedule->mode === 'Email' || $actionSchedule->mode === 'User_Preference') {
          CRM_Utils_Array::extend($errors, self::sendReminderEmail($tokenRow, $actionSchedule, $dao->contactID));
        }
        // insert activity log record if needed
        if ($actionSchedule->record_activity && empty($errors)) {
          $caseID = empty($dao->case_id) ? NULL : $dao->case_id;
          CRM_Core_BAO_ActionSchedule::createMailingActivity($tokenRow, $mapping, $dao->contactID, $dao->entityID, $caseID);
        }

        unset($swapLocale);

        // update action log record
        $logParams = [
          'id' => $dao->reminderID,
          'is_error' => !empty($errors),
          'message' => empty($errors) ? "null" : implode(' ', $errors),
          'action_date_time' => $now,
        ];
        CRM_Core_BAO_ActionLog::create($logParams);
      }

    }
  }

  /**
   * Build a list of the contacts to send to.
   *
   * @param string $mappingID
   *   Value from the mapping_id field in the civicrm_action_schedule able. It might be a string like
   *  'contribpage' for an older class like CRM_Contribute_ActionMapping_ByPage of for ones following
   *   more recent patterns, an integer.
   * @param string $now
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildRecipientContacts(string $mappingID, $now, $params = []) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();

    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    if (!empty($params)) {
      _civicrm_api3_dao_set_filter($actionSchedule, $params, FALSE);
    }
    $actionSchedule->find();

    while ($actionSchedule->fetch()) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */
      $mapping = CRM_Utils_Array::first(self::getMappings([
        'id' => $mappingID,
      ]));
      $builder = new \Civi\ActionSchedule\RecipientBuilder($now, $actionSchedule, $mapping);
      $builder->build();
    }
  }

  /**
   * Main processing callback for sending out scheduled reminders.
   *
   * @param string $now
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public static function processQueue($now = NULL, $params = []): void {
    $now = $now ? CRM_Utils_Time::setTime($now) : CRM_Utils_Time::getTime();

    $mappings = CRM_Core_BAO_ActionSchedule::getMappings();
    foreach ($mappings as $mappingID => $mapping) {
      CRM_Core_BAO_ActionSchedule::buildRecipientContacts((string) $mappingID, $now, $params);
      CRM_Core_BAO_ActionSchedule::sendMailings($mappingID, $now);
    }
  }

  /**
   * @param int $id
   * @param int $mappingID
   *
   * @return null|string
   */
  public static function isConfigured($id, $mappingID) {
    $queryString = "SELECT count(id) FROM civicrm_action_schedule
                        WHERE  mapping_id = %1 AND
                               entity_value = %2";

    $params = [
      1 => [$mappingID, 'String'],
      2 => [$id, 'Integer'],
    ];
    return CRM_Core_DAO::singleValueQuery($queryString, $params);
  }

  /**
   * @param int $mappingID
   * @param $recipientType
   *
   * @return array
   */
  public static function getRecipientListing($mappingID, $recipientType) {
    if (!$mappingID) {
      return [];
    }

    /** @var \Civi\ActionSchedule\Mapping $mapping */
    $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings([
      'id' => $mappingID,
    ]));
    return $mapping->getRecipientListing($recipientType);
  }

  /**
   * @param string|null $communication_language
   * @param string|null $preferred_language
   * @return string
   */
  public static function pickLocale($communication_language, $preferred_language) {
    $currentLocale = CRM_Core_I18n::getLocale();
    $language = $currentLocale;

    // prepare the language for the email
    if ($communication_language == CRM_Core_I18n::AUTO) {
      if (!empty($preferred_language)) {
        $language = $preferred_language;
      }
    }
    else {
      $language = $communication_language;
    }

    // language not in the existing language, use default
    $languages = CRM_Core_I18n::languages(TRUE);
    if (!array_key_exists($language, $languages)) {
      $language = $currentLocale;
    }

    // change the language
    return $language;
  }

  /**
   * Save a record about the delivery of a reminder email.
   *
   * WISHLIST: Instead of saving $actionSchedule->body_html, call this immediately after
   * sending the message and pass in the fully rendered text of the message.
   *
   * @param object $tokenRow
   * @param Civi\ActionSchedule\Mapping $mapping
   * @param int $contactID
   * @param int $entityID
   * @param int|null $caseID
   * @throws CRM_Core_Exception
   */
  protected static function createMailingActivity($tokenRow, $mapping, $contactID, $entityID, $caseID) {
    $session = CRM_Core_Session::singleton();

    if ($mapping->getEntity() == 'civicrm_membership') {
      // @todo - not required with api
      $activityTypeID
        = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Membership Renewal Reminder');
    }
    else {
      // @todo - not required with api
      $activityTypeID
        = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Reminder Sent');
    }

    $activityParams = [
      'subject' => $tokenRow->render('subject'),
      'details' => $tokenRow->render('body_html'),
      'source_contact_id' => $session->get('userID') ? $session->get('userID') : $contactID,
      'target_contact_id' => $contactID,
      // @todo - not required with api
      'activity_date_time' => CRM_Utils_Time::getTime('YmdHis'),
      // @todo - not required with api
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
      'activity_type_id' => $activityTypeID,
      'source_record_id' => $entityID,
      'case_id' => $caseID,
    ];
    // @todo use api, remove all the above wrangling
    $activity = CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * @param \Civi\ActionSchedule\MappingInterface $mapping
   * @param \CRM_Core_DAO_ActionSchedule $actionSchedule
   *
   * @return string
   */
  protected static function prepareMailingQuery($mapping, $actionSchedule) {
    $select = CRM_Utils_SQL_Select::from('civicrm_action_log reminder')
      ->select("reminder.id as reminderID, reminder.contact_id as contactID, reminder.entity_table as entityTable, reminder.*, e.id AS entityID")
      ->join('e', "!casMailingJoinType !casMappingEntity e ON !casEntityJoinExpr")
      ->select("e.id as entityID, e.*")
      ->where("reminder.action_schedule_id = #casActionScheduleId")
      ->where("reminder.action_date_time IS NULL")
      ->param([
        'casActionScheduleId' => $actionSchedule->id,
        'casMailingJoinType' => ($actionSchedule->limit_to == 0) ? 'LEFT JOIN' : 'INNER JOIN',
        'casMappingId' => $mapping->getId(),
        'casMappingEntity' => $mapping->getEntity(),
        'casEntityJoinExpr' => 'e.id = IF(reminder.entity_table = "civicrm_contact", reminder.contact_id, reminder.entity_id)',
      ]);

    if ($actionSchedule->limit_to == 0) {
      $select->where("e.id = reminder.entity_id OR reminder.entity_table = 'civicrm_contact'");
    }

    \Civi::dispatcher()
      ->dispatch(
        'civi.actionSchedule.prepareMailingQuery',
        new \Civi\ActionSchedule\Event\MailingQueryEvent($actionSchedule, $mapping, $select)
      );

    return $select->toSQL();
  }

  /**
   * @param \Civi\Token\TokenRow $tokenRow
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param int $toContactID
   * @throws CRM_Core_Exception
   * @return array
   *   List of error messages.
   */
  protected static function sendReminderSms($tokenRow, $schedule, $toContactID) {
    $toPhoneNumber = self::pickSmsPhoneNumber($toContactID);
    if (!$toPhoneNumber) {
      return ["sms_phone_missing" => "Couldn't find recipient's phone number."];
    }

    // dev/core#369 If an SMS provider is deleted then the relevant row in the action_schedule_table is set to NULL
    // So we need to exclude them.
    if (CRM_Utils_System::isNull($schedule->sms_provider_id)) {
      return ["sms_provider_missing" => "SMS reminder cannot be sent because the SMS provider has been deleted."];
    }

    $messageSubject = $tokenRow->render('subject');
    $sms_body_text = $tokenRow->render('sms_body_text');

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID') ? $session->get('userID') : $tokenRow->context['contactId'];
    $smsParams = [
      'To' => $toPhoneNumber,
      'provider_id' => $schedule->sms_provider_id,
      'activity_subject' => $messageSubject,
    ];
    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS');
    $activityParams = [
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $messageSubject,
      'details' => $sms_body_text,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
    ];

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    try {
      CRM_Activity_BAO_Activity::sendSMSMessage($tokenRow->context['contactId'],
        $sms_body_text,
        $smsParams,
        $activity->id,
        $userID
      );
    }
    catch (CRM_Core_Exception $e) {
      return ["sms_send_error" => $e->getMessage()];
    }

    return [];
  }

  /**
   * @param CRM_Core_DAO_ActionSchedule $actionSchedule
   *
   * @return string
   *   Ex: "Alice <alice@example.org>".
   * @throws \CRM_Core_Exception
   */
  protected static function pickFromEmail($actionSchedule) {
    $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
    $fromEmailAddress = "$domainValues[0] <$domainValues[1]>";
    if ($actionSchedule->from_email) {
      $fromEmailAddress = "\"$actionSchedule->from_name\" <$actionSchedule->from_email>";
      return $fromEmailAddress;
    }
    return $fromEmailAddress;
  }

  /**
   * Send the reminder email.
   *
   * @param \Civi\Token\TokenRow $tokenRow
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param int $toContactID
   *
   * @return array
   *   List of error messages.
   * @throws \CRM_Core_Exception
   */
  protected static function sendReminderEmail($tokenRow, $schedule, $toContactID): array {
    $toEmail = CRM_Contact_BAO_Contact::getPrimaryEmail($toContactID, TRUE);
    if (!$toEmail) {
      return ['email_missing' => "Couldn't find recipient's email address."];
    }

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = [
      'groupName' => 'Scheduled Reminder Sender',
      'from' => self::pickFromEmail($schedule),
      'toName' => $tokenRow->render('toName'),
      'toEmail' => $toEmail,
      'subject' => $tokenRow->render('subject'),
      'entity' => 'action_schedule',
      'entity_id' => $schedule->id,
    ];
    $body_text = $tokenRow->render('body_text');
    $mailParams['html'] = $tokenRow->render('body_html');
    // todo - remove these lines for body_text as there is similar handling in
    // CRM_Utils_Mail::send()
    if (!$schedule->body_text) {
      $body_text = CRM_Utils_String::htmlToText($mailParams['html']);
    }
    // render the &amp; entities in text mode, so that the links work
    $mailParams['text'] = str_replace('&amp;', '&', $body_text);

    $result = CRM_Utils_Mail::send($mailParams);
    if (!$result) {
      return ['email_fail' => 'Failed to send message'];
    }

    return [];
  }

  /**
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param \Civi\ActionSchedule\Mapping $mapping
   * @return \Civi\Token\TokenProcessor
   */
  protected static function createTokenProcessor($schedule, $mapping) {
    $tp = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'actionSchedule' => $schedule,
      'actionMapping' => $mapping,
      'smarty' => TRUE,
      'schema' => ['contactId'],
    ]);
    $tp->addMessage('body_text', $schedule->body_text, 'text/plain');
    $tp->addMessage('body_html', $schedule->body_html, 'text/html');
    $tp->addMessage('sms_body_text', $schedule->sms_body_text, 'text/plain');
    $tp->addMessage('subject', $schedule->subject, 'text/plain');
    // These 2 are not 'real' tokens - but it tells the processor to load them.
    $tp->addMessage('toName', '{contact.display_name}', 'text/plain');
    $tp->addMessage('preferred_mail_format', '{contact.preferred_mail_format}', 'text/plain');

    return $tp;
  }

  /**
   * Pick SMS phone number.
   *
   * @param int $smsToContactId
   *
   * @return NULL|string
   */
  protected static function pickSmsPhoneNumber($smsToContactId) {
    $toPhoneNumbers = CRM_Core_BAO_Phone::allPhones($smsToContactId, FALSE, 'Mobile', [
      'is_deceased' => 0,
      'is_deleted' => 0,
      'do_not_sms' => 0,
    ]);
    //to get primary mobile ph,if not get a first mobile phONE
    if (!empty($toPhoneNumbers)) {
      $toPhoneNumberDetails = reset($toPhoneNumbers);
      $toPhoneNumber = $toPhoneNumberDetails['phone'] ?? NULL;
      return $toPhoneNumber;
    }
    return NULL;
  }

  /**
   * Get the list of generic recipient types supported by all entities/mappings.
   *
   * @return array
   *   array(mixed $value => string $label).
   */
  public static function getAdditionalRecipients(): array {
    return [
      'manual' => ts('Choose Recipient(s)'),
      'group' => ts('Select Group'),
    ];
  }

}
