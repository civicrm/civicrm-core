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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Event_BAO_Participant extends CRM_Event_DAO_Participant implements \Civi\Core\HookInterface {

  /**
   * Static field for all the participant information that we can potentially import.
   *
   * @var array
   */
  public static $_importableFields = NULL;

  /**
   * Static field for all the participant information that we can potentially export.
   *
   * @var array
   */
  public static $_exportableFields = NULL;

  /**
   * Static array for valid status transitions rules.
   *
   * @var array
   */
  public static $_statusTransitionsRules = [
    'Pending from pay later' => ['Registered', 'Cancelled'],
    'Pending from incomplete transaction' => ['Registered', 'Cancelled'],
    'On waitlist' => ['Cancelled', 'Pending from waitlist'],
    'Pending from waitlist' => ['Registered', 'Cancelled'],
    'Awaiting approval' => ['Cancelled', 'Pending from approval'],
    'Pending from approval' => ['Registered', 'Cancelled'],
  ];

  /**
   * Takes an associative array and creates a participant object.
   *
   * the function extract all the params it needs to initialize the create a
   * participant object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Event_BAO_Participant
   */
  public static function &add(&$params) {

    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Participant', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Participant', NULL, $params);
    }

    // converting dates to mysql format
    if (!empty($params['register_date'])) {
      $params['register_date'] = CRM_Utils_Date::isoToMysql($params['register_date']);
    }

    if (!empty($params['participant_fee_amount'])) {
      $params['participant_fee_amount'] = CRM_Utils_Rule::cleanMoney($params['participant_fee_amount']);
    }

    // ensure that role ids are encoded as a string
    if (isset($params['role_id']) && is_array($params['role_id'])) {
      if (in_array(key($params['role_id']), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($params['role_id']);
        $params['role_id'] = $params['role_id'][$op];
      }
      else {
        $params['role_id'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $params['role_id']);
      }
    }

    $participantBAO = new CRM_Event_BAO_Participant();
    if (!empty($params['id'])) {
      $participantBAO->id = $params['id'] ?? NULL;
      $participantBAO->find(TRUE);
      $participantBAO->register_date = CRM_Utils_Date::isoToMysql($participantBAO->register_date);
    }

    $participantBAO->copyValues($params);

    //CRM-6910
    //1. If currency present, it should be valid one.
    //2. We should have currency when amount is not null.
    $currency = $participantBAO->fee_currency;
    if ($currency ||
      !CRM_Utils_System::isNull($participantBAO->fee_amount)
    ) {
      if (!CRM_Utils_Rule::currencyCode($currency)) {
        $config = CRM_Core_Config::singleton();
        $currency = $config->defaultCurrency;
      }
    }
    $participantBAO->fee_currency = $currency;

    $participantBAO->save();

    // add custom field values
    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_participant', $participantBAO->id);
    }

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Participant', $participantBAO->id, $participantBAO);
    }
    else {
      CRM_Utils_Hook::post('create', 'Participant', $participantBAO->id, $participantBAO);
    }

    return $participantBAO;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @param $ids
   *
   * @return CRM_Event_BAO_Participant|null the found object or null
   */
  public static function getValues(&$params, &$values = [], &$ids = []) {
    if (empty($params)) {
      return NULL;
    }
    $participant = new CRM_Event_BAO_Participant();
    $participant->copyValues($params);
    $participant->find();
    $participants = [];
    while ($participant->fetch()) {
      $ids['participant'] = $participant->id;
      CRM_Core_DAO::storeValues($participant, $values[$participant->id]);
      $participants[$participant->id] = $participant;
    }
    return $participants;
  }

  /**
   * Takes an associative array and creates a participant object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Event_BAO_Participant
   */
  public static function create(&$params) {

    $transaction = new CRM_Core_Transaction();
    $status = NULL;

    if (!empty($params['id'])) {
      $status = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $params['id'], 'status_id');
    }

    $participant = self::add($params);

    // Log activity when creating new participant or changing status
    if (empty($params['id']) ||
      (isset($params['status_id']) && $params['status_id'] != $status)
    ) {
      // Default status if not specified
      $participant->status_id = $participant->status_id ?: self::fields()['participant_status_id']['default'];
      CRM_Activity_BAO_Activity::addActivity($participant, 'Event Registration', $participant->contact_id);
    }

    //CRM-5403
    //for update mode
    if (self::isPrimaryParticipant($participant->id) && $status) {
      self::updateParticipantStatus($participant->id, $status, $participant->status_id);
    }

    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (!$id) {
      $id = $params['contact_id'] ?? NULL;
    }

    //process note, CRM-7634
    $noteId = NULL;
    if (!empty($params['id'])) {
      $note = CRM_Core_BAO_Note::getNote($params['id'], 'civicrm_participant');
      $noteId = key($note);
    }
    $noteValue = NULL;
    $hasNoteField = FALSE;
    foreach (['note', 'participant_note'] as $noteFld) {
      if (array_key_exists($noteFld, $params)) {
        $noteValue = $params[$noteFld];
        $hasNoteField = TRUE;
        break;
      }
    }
    if ($noteId || $noteValue) {
      if ($noteValue) {
        $noteParams = [
          'entity_table' => 'civicrm_participant',
          'note' => $noteValue,
          'entity_id' => $participant->id,
          'id' => $noteId,
        ];
        CRM_Core_BAO_Note::add($noteParams);
      }
      elseif ($noteId && $hasNoteField) {
        CRM_Core_BAO_Note::deleteRecord(['id' => $noteId]);
      }
    }

    // Log the information on successful add/edit of Participant data.
    $logParams = [
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participant->id,
      'data' => CRM_Event_PseudoConstant::participantStatus($participant->status_id),
      'modified_id' => $id,
      'modified_date' => date('Ymd'),
    ];

    CRM_Core_BAO_Log::add($logParams);

    $params['participant_id'] = $participant->id;

    $transaction->commit();

    // do not add to recent items for import, CRM-4399
    if (empty($params['skipRecentView'])) {

      $url = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=view&reset=1&id={$participant->id}&cid={$participant->contact_id}&context=home"
      );

      $recentOther = [];
      if (CRM_Core_Permission::check('edit event participants')) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/participant',
          "action=update&reset=1&id={$participant->id}&cid={$participant->contact_id}&context=home"
        );
      }
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/participant/delete',
          "reset=1&id={$participant->id}"
        );
      }

      $participantRoles = CRM_Event_PseudoConstant::participantRole();

      if ($participant->role_id) {
        $role = explode(CRM_Core_DAO::VALUE_SEPARATOR, $participant->role_id);

        foreach ($role as & $roleValue) {
          if (isset($roleValue)) {
            $roleValue = $participantRoles[$roleValue];
          }
        }
        $roles = implode(', ', $role);
      }

      $roleString = empty($roles) ? '' : $roles;
      $eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $participant->event_id, 'title');
      $title = CRM_Contact_BAO_Contact::displayName($participant->contact_id) . ' (' . $roleString . ' - ' . $eventTitle . ')';

      // add the recently created Participant
      CRM_Utils_Recent::add($title,
        $url,
        $participant->id,
        'Participant',
        $participant->contact_id,
        NULL,
        $recentOther
      );
    }

    return $participant;
  }

  /**
   * Check whether the event is full for participation and return as.
   * per requirements.
   *
   * @param int $eventId
   *   Event id.
   * @param bool $returnEmptySeats
   *   Are we require number if empty seats.
   * @param bool $includeWaitingList
   *   Consider waiting list in event full.
   *                 calculation or not. (it is for cron job  purpose)
   *
   * @param bool $returnWaitingCount
   * @param bool $considerTestParticipant deprecated, unused
   * @param bool $onlyPositiveStatuses
   *   When FALSE, count all participant statuses where is_counted = 1.  This includes
   *   both "Positive" participants (Registered, Attended, etc.) and waitlisted
   *   (and some pending) participants.
   *   When TRUE, count only participants with statuses of "Positive".
   *
   * @return bool|int|null|string
   *   1. false                 => If event having some empty spaces.
   * @throws \CRM_Core_Exception
   */
  public static function eventFull(
    $eventId,
    $returnEmptySeats = FALSE,
    $includeWaitingList = TRUE,
    $returnWaitingCount = FALSE,
    $considerTestParticipant = FALSE,
    $onlyPositiveStatuses = FALSE
  ) {
    $result = NULL;
    if (!$eventId) {
      return $result;
    }

    // consider event is full when.
    // 1. (count(is_counted) >= event_size) or
    // 2. (count(participants-with-status-on-waitlist) > 0)
    // It might be case there are some empty spaces and still event
    // is full, as waitlist might represent group require spaces > empty.

    $countedStatuses = \CRM_Event_BAO_Participant::buildOptions('status_id', NULL, ['is_counted' => 1]);;
    $positiveStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Positive'");
    $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
    $onWaitlistStatusId = array_search('On waitlist', $waitingStatuses);

    $where = [' event.id = %1 ', ' participant.is_test = 0 '];
    $participantRoleClause = self::getParticipantRoleClause();
    if ($participantRoleClause) {
      $where[] = " participant.role_id " . $participantRoleClause;
    }
    $eventParams = [1 => [$eventId, 'Positive']];

    //in case any waiting, straight forward event is full.
    if ($includeWaitingList && $onWaitlistStatusId) {

      //build the where clause.
      $whereClause = ' WHERE ' . implode(' AND ', $where);
      $whereClause .= " AND participant.status_id = $onWaitlistStatusId ";
      $eventSeatsWhere = implode(' AND ', $where) . " AND ( participant.status_id = $onWaitlistStatusId )";

      $query = "
    SELECT  participant.id id
      FROM  civicrm_participant participant
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id )
            {$whereClause}";

      $hasWaitlistedParticipants = CRM_Core_DAO::singleValueQuery($query, $eventParams);
      if ($hasWaitlistedParticipants) {
        //oops here event is full and we don't want waiting count.
        if ($returnWaitingCount) {
          return CRM_Event_BAO_Event::eventTotalSeats($eventId, $eventSeatsWhere);
        }
        return CRM_Core_DAO::singleValueQuery('SELECT event_full_text FROM civicrm_event WHERE id = ' . (int) $eventId) ?: ts('This event is full.');
      }
    }

    //Consider only counted participants, or alternatively only registered (not on waitlist) participants.
    if ($onlyPositiveStatuses) {
      $where[] = ' participant.status_id IN ( ' . implode(', ', array_keys($positiveStatuses)) . ' ) ';
    }
    else {
      $where[] = ' participant.status_id IN ( ' . implode(', ', array_keys($countedStatuses)) . ' ) ';
    }
    $whereClause = ' WHERE ' . implode(' AND ', $where);
    $eventSeatsWhere = implode(' AND ', $where);

    $query = "
    SELECT  participant.id id,
            event.event_full_text as event_full_text,
            event.max_participants as max_participants
      FROM  civicrm_participant participant
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id )
            {$whereClause}";

    $eventMaxSeats = NULL;
    $eventFullText = ts('This event is full.');
    $participants = CRM_Core_DAO::executeQuery($query, $eventParams);
    while ($participants->fetch()) {
      if ($participants->event_full_text) {
        $eventFullText = $participants->event_full_text;
      }
      $eventMaxSeats = $participants->max_participants;
      //don't have limit for event seats.
      if ($participants->max_participants == NULL) {
        return $result;
      }
    }

    //get the total event seats occupied by these participants.
    $eventRegisteredSeats = CRM_Event_BAO_Event::eventTotalSeats($eventId, $eventSeatsWhere);

    if ($eventRegisteredSeats) {
      if ($eventRegisteredSeats >= $eventMaxSeats) {
        $result = $eventFullText;
      }
      elseif ($returnEmptySeats) {
        $result = $eventMaxSeats - $eventRegisteredSeats;
      }
      return $result;
    }
    else {
      $query = '
SELECT  event.event_full_text,
        event.max_participants
  FROM  civicrm_event event
 WHERE  event.id = %1';
      $event = CRM_Core_DAO::executeQuery($query, $eventParams);
      while ($event->fetch()) {
        $eventFullText = $event->event_full_text;
        $eventMaxSeats = $event->max_participants;
      }
    }

    // no limit for registration.
    if ($eventMaxSeats == NULL) {
      return $result;
    }
    if ($eventMaxSeats) {
      return ($returnEmptySeats) ? (int) $eventMaxSeats : FALSE;
    }

    return $eventFullText;
  }

  /**
   * Return the array of all price set field options,
   * with total participant count that field going to carry.
   *
   * @param int $eventId
   *   Event id.
   * @param array $skipParticipantIds
   *   An array of participant ids those we should skip.
   * @param bool $considerCounted
   * @param bool $considerWaiting
   * @param bool $considerTestParticipants
   *
   * @return array
   *   an array of each option id and total count
   */
  public static function priceSetOptionsCount(
    $eventId,
    $skipParticipantIds = [],
    $considerCounted = TRUE,
    $considerWaiting = TRUE,
    $considerTestParticipants = FALSE
  ) {
    $optionsCount = [];
    if (!$eventId) {
      return $optionsCount;
    }

    $allStatusIds = [];
    if ($considerCounted) {
      $countedStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
      $allStatusIds = array_merge($allStatusIds, array_keys($countedStatuses));
    }
    if ($considerWaiting) {
      $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
      $allStatusIds = array_merge($allStatusIds, array_keys($waitingStatuses));
    }
    $statusIdClause = NULL;
    if (!empty($allStatusIds)) {
      $statusIdClause = ' AND participant.status_id IN ( ' . implode(', ', array_values($allStatusIds)) . ')';
    }

    $isTestClause = NULL;
    if (!$considerTestParticipants) {
      $isTestClause = ' AND participant.is_test = 0 ';
    }

    $skipParticipantClause = NULL;
    if (is_array($skipParticipantIds) && !empty($skipParticipantIds)) {
      $skipParticipantClause = ' AND participant.id NOT IN ( ' . implode(', ', $skipParticipantIds) . ')';
    }

    $sql = "
    SELECT  line.id as lineId,
            line.entity_id as entity_id,
            line.qty,
            value.id as price_field_value_id,
            value.count,
            field.html_type
      FROM  civicrm_line_item line
INNER JOIN  civicrm_participant participant ON ( line.entity_table  = 'civicrm_participant'
                                                 AND participant.id = line.entity_id )
INNER JOIN  civicrm_price_field_value value ON ( value.id = line.price_field_value_id )
INNER JOIN  civicrm_price_field field       ON ( value.price_field_id = field.id )
     WHERE  participant.event_id = %1
       AND  line.qty > 0
            {$statusIdClause}
            {$isTestClause}
            {$skipParticipantClause}";

    $lineItem = CRM_Core_DAO::executeQuery($sql, [1 => [$eventId, 'Positive']]);
    while ($lineItem->fetch()) {
      $id = (int) $lineItem->price_field_value_id;
      $optionsCount[$id] ??= 0;
      $count = $lineItem->count ?: 1;
      if ($lineItem->html_type === 'Text') {
        $count *= $lineItem->qty;
      }
      $optionsCount[$id] += $count;
    }

    return $optionsCount;
  }

  /**
   * Combine all the importable fields from the lower levels object.
   *
   * @param string $contactType
   * @param bool $status
   * @param bool $onlyParticipant
   * @param bool $checkPermission
   *   Is this a permissioned retrieval?
   *
   * @deprecated since 5.74 will be removed around 5.86
   *
   * @return array
   *   array of importable Fields
   */
  public static function &importableFields($contactType = 'Individual', $status = TRUE, $onlyParticipant = FALSE, $checkPermission = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('use apiv4');
    if (!self::$_importableFields) {
      if (!$onlyParticipant) {
        if (!$status) {
          $fields = ['' => ['title' => ts('- do not import -')]];
        }
        else {
          $fields = ['' => ['title' => ts('- Participant Fields -')]];
        }
      }
      else {
        $fields = [];
      }

      $tmpFields = CRM_Event_DAO_Participant::import();

      $note = [
        'participant_note' => [
          'title' => ts('Participant Note'),
          'name' => 'participant_note',
          'headerPattern' => '/(participant.)?note$/i',
          'data_type' => CRM_Utils_Type::T_TEXT,
        ],
      ];

      // Split status and status id into 2 fields
      // Fixme: it would be better to leave as 1 field and intelligently handle both during import
      // note import undoes this - it is still here in case the search usage uses it.
      $participantStatus = [
        'participant_status' => [
          'title' => ts('Participant Status'),
          'name' => 'participant_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];
      $tmpFields['participant_status_id']['title'] = ts('Participant Status Id');

      // Split role and role id into 2 fields
      // Fixme: it would be better to leave as 1 field and intelligently handle both during import
      // note import undoes this - it is still here in case the search usage uses it.
      $participantRole = [
        'participant_role' => [
          'title' => ts('Participant Role'),
          'name' => 'participant_role',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];
      $tmpFields['participant_role_id']['title'] = ts('Participant Role Id');

      $eventType = [
        'event_type' => [
          'title' => ts('Event Type'),
          'name' => 'event_type',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];

      $tmpContactField = $contactFields = [];
      $contactFields = [];
      if (!$onlyParticipant) {
        $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

        // Using new Dedupe rule.
        $ruleParams = [
          'contact_type' => $contactType,
          'used' => 'Unsupervised',
        ];
        $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

        if (is_array($fieldsArray)) {
          foreach ($fieldsArray as $value) {
            $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
              $value,
              'id',
              'column_name'
            );
            $value = $customFieldId ? 'custom_' . $customFieldId : $value;
            $tmpContactField[trim($value)] = $contactFields[trim($value)] ?? NULL;
            if (!$status) {
              $title = $tmpContactField[trim($value)]['title'] . ' (match to contact)';
            }
            else {
              $title = $tmpContactField[trim($value)]['title'];
            }

            $tmpContactField[trim($value)]['title'] = $title;
          }
        }
      }
      $extIdentifier = $contactFields['external_identifier'] ?? NULL;
      if ($extIdentifier) {
        $tmpContactField['external_identifier'] = $extIdentifier;
        $tmpContactField['external_identifier']['title'] = ($extIdentifier['title'] ?? '') . ' (match to contact)';
      }
      $tmpFields['participant_contact_id']['title'] = $tmpFields['participant_contact_id']['title'] . ' (match to contact)';

      $fields = array_merge($fields, $tmpContactField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, $note, $participantStatus, $participantRole, $eventType);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Participant', FALSE, FALSE, FALSE, $checkPermission));

      self::$_importableFields = $fields;
    }

    return self::$_importableFields;
  }

  /**
   * Combine all the exportable fields from the lower level objects.
   *
   * @param bool $checkPermission
   *
   * @return array
   *   array of exportable Fields
   */
  public static function &exportableFields($checkPermission = TRUE) {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }

      $participantFields = CRM_Event_DAO_Participant::export();
      $eventFields = CRM_Event_DAO_Event::export();
      $noteField = [
        'participant_note' => [
          'title' => ts('Participant Note'),
          'name' => 'participant_note',
          'type' => CRM_Utils_Type::T_STRING,
        ],
      ];

      $participantStatus = [
        'participant_status' => [
          'title' => ts('Participant Status (label)'),
          'name' => 'participant_status',
          'type' => CRM_Utils_Type::T_STRING,
        ],
      ];

      $participantRole = [
        'participant_role' => [
          'title' => ts('Participant Role (label)'),
          'name' => 'participant_role',
          'type' => CRM_Utils_Type::T_STRING,
        ],
      ];

      $participantFields['participant_role_id']['title'] .= ' (ID)';

      $discountFields = CRM_Core_DAO_Discount::export();

      $fields = array_merge($participantFields, $participantStatus, $participantRole, $eventFields, $noteField, $discountFields);

      // add custom data
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Participant', FALSE, FALSE, FALSE, $checkPermission));
      self::$_exportableFields = $fields;
    }

    return self::$_exportableFields;
  }

  /**
   * Get the event name/sort name for a particular participation / participant
   *
   * @param int $participantId
   *   Id of the participant.
   *
   * @return array
   *   associated array with sort_name and event title
   */
  public static function participantDetails($participantId) {
    $query = "
SELECT civicrm_contact.sort_name as name, civicrm_event.title as title, civicrm_contact.id as cid
FROM   civicrm_participant
   LEFT JOIN civicrm_event   ON (civicrm_participant.event_id = civicrm_event.id)
   LEFT JOIN civicrm_contact ON (civicrm_participant.contact_id = civicrm_contact.id)
WHERE  civicrm_participant.id = {$participantId}
";
    $dao = CRM_Core_DAO::executeQuery($query);

    $details = [];
    while ($dao->fetch()) {
      $details['name'] = $dao->name;
      $details['title'] = $dao->title;
      $details['cid'] = $dao->cid;
    }

    return $details;
  }

  /**
   * Get the values for pseudoconstants for name->value and reverse.
   *
   * @param array $defaults
   *   (reference) the default values, some of which need to be resolved.
   * @param bool $reverse
   *   True if we want to resolve the values in the reverse direction (value -> name).
   */
  public static function resolveDefaults(&$defaults, $reverse = FALSE) {
    self::lookupValue($defaults, 'event', CRM_Event_PseudoConstant::event(), $reverse);
    self::lookupValue($defaults, 'status', CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'), $reverse);
    self::lookupValue($defaults, 'role', CRM_Event_PseudoConstant::participantRole(), $reverse);
  }

  /**
   * Convert associative array names to values and vice-versa.
   *
   * This function is used by both the web form layer and the api. Note that
   * the api needs the name => value conversion, also the view layer typically
   * requires value => name conversion
   *
   * @param array $defaults
   * @param string $property
   * @param string[] $lookup
   * @param bool $reverse
   *
   * @return bool
   */
  public static function lookupValue(&$defaults, $property, $lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!array_key_exists($src, $defaults)) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    if (is_array($look)) {
      if (!array_key_exists($defaults[$src], $look)) {
        return FALSE;
      }
    }
    $defaults[$dst] = $look[$defaults[$src]];
    return TRUE;
  }

  /**
   * Delete the records that are associated with this participation.
   *
   * @param int $id
   *   Id of the participation to delete.
   *
   * @return \CRM_Event_DAO_Participant
   */
  public static function deleteParticipant($id) {
    $participant = new CRM_Event_DAO_Participant();
    $participant->id = $id;
    if (!$participant->find()) {
      return FALSE;
    }
    CRM_Utils_Hook::pre('delete', 'Participant', $id);

    $transaction = new CRM_Core_Transaction();

    //delete activity record
    $params = [
      'source_record_id' => $id,
      // activity type id for event registration
      'activity_type_id' => 5,
    ];

    CRM_Activity_BAO_Activity::deleteActivity($params);

    // delete the participant payment record
    // we need to do this since the cascaded constraints
    // dont work with join tables
    $p = ['participant_id' => $id];
    CRM_Event_BAO_ParticipantPayment::deleteParticipantPayment($p);

    // cleanup line items.
    $participantsId = [];
    $participantsId = self::getAdditionalParticipantIds($id);
    $participantsId[] = $id;
    CRM_Price_BAO_LineItem::deleteLineItems($participantsId, 'civicrm_participant');

    //delete note when participant deleted.
    $note = CRM_Core_BAO_Note::getNote($id, 'civicrm_participant');
    $noteId = key($note);
    if ($noteId) {
      CRM_Core_BAO_Note::deleteRecord(['id' => $noteId]);
    }

    $participant->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Participant', $participant->id, $participant);

    return $participant;
  }

  /**
   * Checks duplicate participants.
   *
   * @param array $input
   *   An assosiative array of name /value pairs.
   *   from other function
   * @param array $duplicates
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Contribute_BAO_Contribution
   */
  public static function checkDuplicate($input, &$duplicates) {
    $eventId = $input['event_id'] ?? NULL;
    $contactId = $input['contact_id'] ?? NULL;

    $clause = [];
    $input = [];

    if ($eventId) {
      $clause[] = "event_id = %1";
      $input[1] = [$eventId, 'Integer'];
    }

    if ($contactId) {
      $clause[] = "contact_id = %2";
      $input[2] = [$contactId, 'Integer'];
    }

    if (empty($clause)) {
      return FALSE;
    }

    $clause = implode(' AND ', $clause);

    $query = "SELECT id FROM civicrm_participant WHERE $clause";
    $dao = CRM_Core_DAO::executeQuery($query, $input);
    $result = FALSE;
    while ($dao->fetch()) {
      $duplicates[] = $dao->id;
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Fix the event level.
   *
   * When price sets are used as event fee, fee_level is set as ^A
   * separated string. We need to change that string to comma
   * separated string before using fee_level in view mode.
   *
   * @param string $eventLevel
   *   Event_level string from db.
   */
  public static function fixEventLevel(&$eventLevel) {
    if ((substr($eventLevel, 0, 1) == CRM_Core_DAO::VALUE_SEPARATOR) &&
      (substr($eventLevel, -1, 1) == CRM_Core_DAO::VALUE_SEPARATOR)
    ) {
      $eventLevel = implode(', ', explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($eventLevel, 1, -1)));
      $pos = strrpos($eventLevel, '(multiple participants)', 0);
      if ($pos) {
        $eventLevel = substr_replace($eventLevel, "", $pos - 3, 1);
      }
    }
    elseif ((substr($eventLevel, 0, 1) == CRM_Core_DAO::VALUE_SEPARATOR)) {
      $eventLevel = implode(', ', explode(CRM_Core_DAO::VALUE_SEPARATOR,
        substr($eventLevel, 0, 1)
      ));
    }
    elseif ((substr($eventLevel, -1, 1) == CRM_Core_DAO::VALUE_SEPARATOR)) {
      $eventLevel = implode(', ', explode(CRM_Core_DAO::VALUE_SEPARATOR,
        substr($eventLevel, 0, -1)
      ));
    }
  }

  /**
   * Get the ID of the default (first) participant role
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public static function getDefaultRoleID() {
    return (int) civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'participant_role',
      'is_active' => 1,
      'options' => ['limit' => 1, 'sort' => 'is_default DESC'],
    ]);
  }

  /**
   * Get the additional participant ids.
   *
   * @param int|null $primaryParticipantID
   *   Primary participant ID. Null should not be passed in & handling for it
   *   will be removed.
   * @param bool $excludeCancel
   *   Do not include cancelled participants.
   * @param int|null $statusID
   *   Restrict to the specified status ID.
   *
   * @return array
   *
   * @throws \Civi\Core\Exception\DBQueryException
   * @internal not supported to be called from outside of core.
   */
  public static function getAdditionalParticipantIds(?int $primaryParticipantID, bool $excludeCancel = TRUE, ?int $statusID = NULL): array {
    if (!$primaryParticipantID) {
      CRM_Core_Error::deprecatedWarning('should not be called with no IDs');
      return [];
    }
    $where = "participant.registered_by_id={$primaryParticipantID}";
    if ($excludeCancel) {
      $negativeStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
      $where .= ' AND participant.status_id != ' . (int) array_search('Cancelled', $negativeStatuses, TRUE);
    }

    if ($statusID) {
      $where .= " AND participant.status_id = {$statusID}";
    }

    $query = "
  SELECT  participant.id
    FROM  civicrm_participant participant
   WHERE  {$where}";

    $additionalParticipantIDs = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $additionalParticipantIDs[$dao->id] = $dao->id;
    }
    return $additionalParticipantIDs;
  }

  /**
   * Get the amount for the undiscounted version of the field.
   *
   * Note this function is part of the refactoring process rather than the best approach.
   *
   * @param int $eventID
   * @param int $discountedPriceFieldOptionID
   * @param string $feeLevel (deprecated)
   *
   * @return null|string
   */
  public static function getUnDiscountedAmountForEventPriceSetFieldValue($eventID, $discountedPriceFieldOptionID, $feeLevel) {
    $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $eventID, NULL);
    $params = [
      1 => [$priceSetId, 'Integer'],
    ];
    if ($discountedPriceFieldOptionID) {
      $query = "SELECT cpfv.amount FROM `civicrm_price_field_value` cpfv
LEFT JOIN civicrm_price_field cpf ON cpfv.price_field_id = cpf.id
WHERE cpf.price_set_id = %1 AND cpfv.label = (SELECT label from civicrm_price_field_value WHERE id = %2)";
      $params[2] = [$discountedPriceFieldOptionID, 'Integer'];
    }
    else {
      $query = "SELECT cpfv.amount FROM `civicrm_price_field_value` cpfv
LEFT JOIN civicrm_price_field cpf ON cpfv.price_field_id = cpf.id
WHERE cpf.price_set_id = %1 AND cpfv.label LIKE %2";
      $params[2] = [$feeLevel, 'String'];
    }
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Retrieve additional participants display-names and URL to view their participant records.
   * (excludes cancelled participants automatically)
   *
   * @param int $primaryParticipantID
   *   Id of primary participant record.
   *
   * @return array
   *   $displayName => $viewUrl
   */
  public static function getAdditionalParticipants($primaryParticipantID) {
    $additionalParticipants = [];
    $additionalParticipantIDs = self::getAdditionalParticipantIds($primaryParticipantID);
    if (!empty($additionalParticipantIDs)) {
      foreach ($additionalParticipantIDs as $additionalParticipantID) {
        $additionalContactID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
          $additionalParticipantID,
          'contact_id', 'id'
        );
        $additionalContactName = CRM_Contact_BAO_Contact::displayName($additionalContactID);
        $pViewURL = CRM_Utils_System::url('civicrm/contact/view/participant',
          "action=view&reset=1&id={$additionalParticipantID}&cid={$additionalContactID}"
        );

        $additionalParticipants[$additionalContactName] = $pViewURL;
      }
    }
    return $additionalParticipants;
  }

  /**
   * Function for update primary and additional participant status.
   *
   * @param int $participantID
   *   Primary participant's id.
   * @param int $oldStatusID
   * @param int $newStatusID
   * @param bool $updatePrimaryStatus
   *
   * @return bool|NULL
   */
  public static function updateParticipantStatus($participantID, $oldStatusID, $newStatusID = NULL, $updatePrimaryStatus = FALSE) {
    if (!$participantID || !$oldStatusID) {
      return NULL;
    }

    if (!$newStatusID) {
      $newStatusID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantID, 'status_id');
    }
    elseif ($updatePrimaryStatus) {
      CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $participantID, 'status_id', $newStatusID);
    }

    $cascadeAdditionalIds = self::getValidAdditionalIds($participantID, $oldStatusID, $newStatusID);

    if (!empty($cascadeAdditionalIds)) {
      try {
        foreach ($cascadeAdditionalIds as $id) {
          $participantParams = [
            'id' => $id,
            'status_id' => $newStatusID,
          ];
          civicrm_api3('Participant', 'create', $participantParams);
        }
        return TRUE;
      }
      catch (CRM_Core_Exception $e) {
        throw new CRM_Core_Exception('Failed to update additional participant status in database');
      }
    }
    return FALSE;
  }

  /**
   * Function for update status for given participant ids.
   *
   * @param int $participantIds
   *   Array of participant ids.
   * @param int $statusId
   *   Status id for participant.
   * @param bool $updateRegisterDate
   */
  public static function updateStatus($participantIds, $statusId, $updateRegisterDate = FALSE) {
    if (!is_array($participantIds) || empty($participantIds) || !$statusId) {
      return;
    }

    //lets update register date as we update status to keep track
    //when we did update status, useful for moving participant
    //from pending to expired.
    $setClause = "status_id = {$statusId}";
    if ($updateRegisterDate) {
      $setClause .= ", register_date = NOW()";
    }

    $participantIdClause = '( ' . implode(',', $participantIds) . ' )';

    $query = "
UPDATE  civicrm_participant
   SET  {$setClause}
 WHERE  id IN {$participantIdClause}";

    $dao = CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Function takes participant ids and statuses
   * update status from $fromStatusId to $toStatusId
   * and send mail + create activities.
   *
   * @param array $participantIds
   *   Participant ids.
   * @param int $toStatusId
   *   Update status id.
   * @param int $fromStatusId
   *   From status id.
   * @param bool $returnResult
   * @param bool $skipCascadeRule
   *
   * @return array|NULL
   */
  public static function transitionParticipants(
    $participantIds, $toStatusId,
    $fromStatusId = NULL, $returnResult = FALSE, $skipCascadeRule = FALSE
  ) {
    if (!is_array($participantIds) || empty($participantIds) || !$toStatusId) {
      return NULL;
    }

    //thumb rule is if we triggering  primary participant need to triggered additional
    $allParticipantIds = $primaryANDAdditionalIds = [];
    foreach ($participantIds as $id) {
      $allParticipantIds[] = $id;
      if (self::isPrimaryParticipant($id)) {
        //filter additional as per status transition rules, CRM-5403
        if ($skipCascadeRule) {
          $additionalIds = self::getAdditionalParticipantIds($id);
        }
        else {
          $additionalIds = self::getValidAdditionalIds($id, $fromStatusId, $toStatusId);
        }
        if (!empty($additionalIds)) {
          $allParticipantIds = array_merge($allParticipantIds, $additionalIds);
          $primaryANDAdditionalIds[$id] = $additionalIds;
        }
      }
    }

    //get the unique participant ids,
    $allParticipantIds = array_unique($allParticipantIds);

    //pull required participants, contacts, events  data, if not in hand
    static $eventDetails = [];
    static $contactDetails = [];

    $contactIds = $eventIds = $participantDetails = [];

    $statusTypes = CRM_Event_PseudoConstant::participantStatus();
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL,
      "class = 'Pending'"
    );

    //first thing is pull all necessory data from db.
    $participantIdClause = '(' . implode(',', $allParticipantIds) . ')';

    //get all participants data.
    $query = "SELECT * FROM civicrm_participant WHERE id IN {$participantIdClause}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $participantDetails[$dao->id] = [
        'id' => $dao->id,
        'role' => $participantRoles[$dao->role_id],
        'is_test' => $dao->is_test,
        'event_id' => $dao->event_id,
        'status_id' => $dao->status_id,
        'fee_amount' => $dao->fee_amount,
        'contact_id' => $dao->contact_id,
        'register_date' => $dao->register_date,
        'registered_by_id' => $dao->registered_by_id,
      ];
      if (!array_key_exists($dao->contact_id, $contactDetails)) {
        $contactIds[$dao->contact_id] = $dao->contact_id;
      }

      if (!array_key_exists($dao->event_id, $eventDetails)) {
        $eventIds[$dao->event_id] = $dao->event_id;
      }
    }

    //get all required contacts detail.
    if (!empty($contactIds)) {
      $contactDetails += civicrm_api3('Contact', 'get', ['id' => ['IN' => $contactIds, 'return' => 'display_name']])['values'];
    }

    //get all required events detail.
    if (!empty($eventIds)) {
      foreach ($eventIds as $eventId) {
        //retrieve event information
        $eventParams = ['id' => $eventId];
        CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails[$eventId]);

        //get default participant role.
        $eventDetails[$eventId]['participant_role'] = $participantRoles[$eventDetails[$eventId]['default_role_id']] ?? NULL;

        //get the location info
        $locParams = ['entity_id' => $eventId, 'entity_table' => 'civicrm_event'];
        $eventDetails[$eventId]['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
      }
    }

    //now we are ready w/ all required data.
    //take a decision as per statuses.

    $emailType = NULL;
    $toStatus = $statusTypes[$toStatusId];
    $fromStatus = $statusTypes[$fromStatusId] ?? NULL;

    switch ($toStatus) {
      case 'Pending from waitlist':
      case 'Pending from approval':
        switch ($fromStatus) {
          case 'On waitlist':
          case 'Awaiting approval':
            $emailType = 'Confirm';
            break;
        }
        break;

      case 'Expired':
        //no matter from where u come send expired mail.
        $emailType = $toStatus;
        break;

      case 'Cancelled':
        //no matter from where u come send cancel mail.
        $emailType = $toStatus;
        break;
    }

    //as we process additional w/ primary, there might be case if user
    //select primary as well as additionals, so avoid double processing.
    $processedParticipantIds = [];
    $mailedParticipants = [];

    //send mails and update status.
    foreach ($participantDetails as $participantId => $participantValues) {
      $updateParticipantIds = [];
      if (in_array($participantId, $processedParticipantIds)) {
        continue;
      }

      //check is it primary and has additional.
      if (array_key_exists($participantId, $primaryANDAdditionalIds)) {
        foreach ($primaryANDAdditionalIds[$participantId] as $additionalId) {

          if ($emailType) {
            $mail = self::sendTransitionParticipantMail($additionalId,
              $participantDetails[$additionalId],
              $eventDetails[$participantDetails[$additionalId]['event_id']],
              NULL,
              $emailType
            );

            //get the mail participant ids
            if ($mail) {
              $mailedParticipants[$additionalId] = $contactDetails[$participantDetails[$additionalId]['contact_id']]['display_name'];
            }
          }
          $updateParticipantIds[] = $additionalId;
          $processedParticipantIds[] = $additionalId;
        }
      }

      //now send email appropriate mail to primary.
      if ($emailType) {
        $mail = self::sendTransitionParticipantMail($participantId,
          $participantValues,
          $eventDetails[$participantValues['event_id']],
          NULL,
          $emailType
        );

        //get the mail participant ids
        if ($mail) {
          $mailedParticipants[$participantId] = $contactDetails[$participantValues['contact_id']]['display_name'];
        }
      }

      //now update status of group/one at once.
      $updateParticipantIds[] = $participantId;

      //update the register date only when we,
      //move participant to pending class, CRM-6496
      $updateRegisterDate = FALSE;
      if (array_key_exists($toStatusId, $pendingStatuses)) {
        $updateRegisterDate = TRUE;
      }
      self::updateStatus($updateParticipantIds, $toStatusId, $updateRegisterDate);
      $processedParticipantIds[] = $participantId;
    }

    //return result for cron.
    if ($returnResult) {
      $results = [
        'mailedParticipants' => $mailedParticipants,
        'updatedParticipantIds' => $processedParticipantIds,
      ];

      return $results;
    }
  }

  /**
   * Send mail and create activity
   * when participant status changed.
   *
   * @param int $participantId
   *   Participant id.
   * @param array $participantValues
   *   Participant detail values. status id for participants.
   * @param array $eventDetails
   *   Required event details.
   * @param array $contactDetails
   *   Required contact details.
   * @param string $mailType
   *   (eg 'approval', 'confirm', 'expired' ).
   *
   * @return bool
   */
  public static function sendTransitionParticipantMail(
    $participantId,
    $participantValues,
    $eventDetails,
    $contactDetails,
    $mailType
  ) {
    //send emails.
    $mailSent = FALSE;

    if (!$contactDetails) {
      $contactDetails = civicrm_api3('Contact', 'getsingle', [
        'id' => $participantValues['contact_id'],
        'return' => ['email', 'display_name'],
      ]);
    }
    //don't send confirmation mail to additional
    //since only primary able to confirm registration.
    if (!empty($participantValues['registered_by_id']) &&
      $mailType == 'Confirm'
    ) {
      return $mailSent;
    }

    $toEmail = $contactDetails['email'] ?? NULL;
    if ($toEmail) {

      $contactId = $participantValues['contact_id'];
      $participantName = $contactDetails['display_name'];

      //calculate the checksum value.
      $checksumValue = NULL;
      if ($mailType == 'Confirm' && !$participantValues['registered_by_id']) {
        $checksumLife = 'inf';
        $endDate = $eventDetails['end_date'] ?? NULL;
        if ($endDate) {
          $checksumLife = (CRM_Utils_Date::unixTime($endDate) - time()) / (60 * 60);
        }
        $checksumValue = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId, NULL, $checksumLife);
      }

      //take a receipt from as event else domain.
      $receiptFrom = CRM_Core_BAO_Domain::getFromEmail();

      if (!empty($eventDetails['confirm_from_name']) && !empty($eventDetails['confirm_from_email'])) {
        $receiptFrom = $eventDetails['confirm_from_name'] . ' <' . $eventDetails['confirm_from_email'] . '>';
      }

      [$mailSent, $subject] = CRM_Core_BAO_MessageTemplate::sendTemplate(
        [
          'workflow' => 'participant_' . strtolower($mailType),
          'contactId' => $contactId,
          'tplParams' => [
            'participant' => $participantValues,
            'event' => $eventDetails,
            'paidEvent' => $eventDetails['is_monetary'] ?? NULL,
            'isShowLocation' => $eventDetails['is_show_location'] ?? NULL,
            'isAdditional' => $participantValues['registered_by_id'],
            'isExpired' => $mailType === 'Expired',
            'isConfirm' => $mailType === 'Confirm',
            'checksumValue' => $checksumValue,
          ],
          'modelProps' => [
            'participantID' => (int) $participantId,
            'eventID' => (int) $eventDetails['id'],
            'contactID' => (int) $contactId,
          ],
          'from' => $receiptFrom,
          'toName' => $participantName,
          'toEmail' => $toEmail,
          'cc' => $eventDetails['cc_confirm'] ?? NULL,
          'bcc' => $eventDetails['bcc_confirm'] ?? NULL,
        ]
      );

      // 3. create activity record.
      if ($mailSent) {
        $now = date('YmdHis');
        $activityType = 'Event Registration';
        $activityParams = [
          'subject' => $subject,
          'source_contact_id' => $contactId,
          'source_record_id' => $participantId,
          'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $activityType),
          'activity_date_time' => CRM_Utils_Date::isoToMysql($now),
          'due_date_time' => CRM_Utils_Date::isoToMysql($participantValues['register_date']),
          'is_test' => $participantValues['is_test'],
          'status_id' => 2,
        ];
        CRM_Activity_BAO_Activity::create($activityParams);
      }
    }

    return $mailSent;
  }

  /**
   * Get participant status change message.
   *
   * @param int $participantId
   * @param $statusChangeTo
   * @param int $fromStatusId
   *
   * @return string
   */
  public static function updateStatusMessage($participantId, $statusChangeTo, $fromStatusId) {
    $statusMsg = NULL;
    $results = self::transitionParticipants([$participantId],
      $statusChangeTo, $fromStatusId, TRUE
    );

    $allStatuses = CRM_Event_PseudoConstant::participantStatus();
    //give user message only when mail has sent.
    if (is_array($results) && !empty($results)) {
      if (is_array($results['updatedParticipantIds']) && !empty($results['updatedParticipantIds'])) {
        foreach ($results['updatedParticipantIds'] as $processedId) {
          if (is_array($results['mailedParticipants']) &&
            array_key_exists($processedId, $results['mailedParticipants'])
          ) {
            $statusMsg .= '<br /> ' . ts("Participant status has been updated to '%1'. An email has been sent to %2.",
                [
                  1 => $allStatuses[$statusChangeTo],
                  2 => $results['mailedParticipants'][$processedId],
                ]
              );
          }
        }
      }
    }

    return $statusMsg;
  }

  /**
   * Get event full and waiting list message.
   *
   * @param int $eventId
   * @param int $participantId
   *
   * @return string
   */
  public static function eventFullMessage($eventId, $participantId = NULL) {
    $eventfullMsg = $dbStatusId = NULL;
    $checkEventFull = TRUE;
    if ($participantId) {
      $dbStatusId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantId, 'status_id');
      if (array_key_exists($dbStatusId, CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1'))) {
        //participant already in counted status no need to check for event full messages.
        $checkEventFull = FALSE;
      }
    }

    //early return.
    if (!$eventId || !$checkEventFull) {
      return $eventfullMsg;
    }

    //event is truly full.
    $emptySeats = self::eventFull($eventId, FALSE, FALSE);
    if (is_string($emptySeats) && $emptySeats !== NULL) {
      $maxParticipants = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventId, 'max_participants');
      $eventfullMsg = ts("This event currently has the maximum number of participants registered (%1). However, you can still override this limit and register additional participants using this form.", [
        1 => $maxParticipants,
      ]) . '<br />';
    }

    $hasWaiting = FALSE;
    $waitListedCount = self::eventFull($eventId, FALSE, TRUE, TRUE);
    if (is_numeric($waitListedCount)) {
      $hasWaiting = TRUE;
      //only current processing participant is on waitlist.
      if ($waitListedCount == 1 && CRM_Event_PseudoConstant::participantStatus($dbStatusId) == 'On waitlist') {
        $hasWaiting = FALSE;
      }
    }

    if ($hasWaiting) {
      $waitingStatusId = array_search('On waitlist',
        CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'")
      );
      $viewWaitListUrl = CRM_Utils_System::url('civicrm/event/search',
        "reset=1&force=1&event={$eventId}&status={$waitingStatusId}"
      );

      $eventfullMsg .= ts("There are %2 people currently on the waiting list for this event. You can <a href='%1'>view waitlisted registrations here</a>, or you can continue and register additional participants using this form.",
        [
          1 => $viewWaitListUrl,
          2 => $waitListedCount,
        ]
      );
    }

    return $eventfullMsg;
  }

  /**
   * Check for whether participant is primary or not.
   *
   * @param int $participantId
   *
   * @return bool
   *   true if participant is primary
   */
  public static function isPrimaryParticipant($participantId) {

    $participant = new CRM_Event_DAO_Participant();
    $participant->registered_by_id = $participantId;

    if ($participant->find(TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get additional participant Ids for cascading with primary participant status.
   *
   * @param int $participantId
   *   Participant id.
   * @param int $oldStatusId
   *   Previous status.
   * @param int $newStatusId
   *   New status.
   *
   * @return array
   */
  public static function getValidAdditionalIds($participantId, $oldStatusId, $newStatusId) {

    $additionalParticipantIds = [];

    static $participantStatuses = [];

    if (empty($participantStatuses)) {
      $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
    }

    if (!empty(self::$_statusTransitionsRules[$participantStatuses[$oldStatusId]]) &&
      in_array($participantStatuses[$newStatusId], self::$_statusTransitionsRules[$participantStatuses[$oldStatusId]])
    ) {
      $additionalParticipantIds = self::getAdditionalParticipantIds($participantId, TRUE, $oldStatusId);
    }

    return $additionalParticipantIds;
  }

  /**
   * Get participant record count for a Contact.
   *
   * @param int $contactID
   *
   * @return int
   *   count of participant records
   */
  public static function getContactParticipantCount($contactID) {
    $query = "SELECT count(*)
FROM     civicrm_participant
WHERE    civicrm_participant.contact_id = {$contactID} AND
         civicrm_participant.is_test = 0";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Get participant ids by contribution id.
   *
   * @param int $contributionId
   *   Contribution Id.
   * @param bool $excludeCancelled
   *   Exclude cancelled additional participant.
   *
   * @return array
   */
  public static function getParticipantIds($contributionId, $excludeCancelled = FALSE) {

    $ids = [];
    if (!$contributionId) {
      return $ids;
    }

    // get primary participant id
    $query = "SELECT participant_id
      FROM civicrm_participant cp
      LEFT JOIN civicrm_participant_payment cpp ON cp.id = cpp.participant_id
      WHERE cpp.contribution_id = {$contributionId}
      AND cp.registered_by_id IS NULL";
    $participantPayment = CRM_Core_DAO::executeQuery($query);

    // get additional participant ids (including cancelled)
    while ($participantPayment->fetch()) {
      $ids = array_merge($ids, array_merge([
        $participantPayment->participant_id,
      ], self::getAdditionalParticipantIds($participantPayment->participant_id,
        $excludeCancelled
      )));
    }

    return $ids;
  }

  /**
   * Get additional Participant edit & view url .
   *
   * @param array $participantIds
   *   An array of additional participant ids.
   *
   * @return array
   *   Array of Urls.
   */
  public static function getAdditionalParticipantUrl($participantIds) {
    foreach ($participantIds as $value) {
      $links = [];
      $details = self::participantDetails($value);
      $viewUrl = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=view&reset=1&id={$value}&cid={$details['cid']}"
      );
      $editUrl = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=update&reset=1&id={$value}&cid={$details['cid']}"
      );
      $links[] = "<td><a href='{$viewUrl}'>" . $details['name'] . "</a></td><td></td><td><a href='{$editUrl}'>" . ts('Edit') . "</a></td>";
      $links = "<table><tr>" . implode("</tr><tr>", $links) . "</tr></table>";
      return $links;
    }
  }

  /**
   * create trxn entry if an event has discount.
   *
   * @param int $eventID
   *   Event id.
   * @param array $contributionParams
   *   Contribution params.
   *
   * @param string $feeLevel (deprecated)
   * @param int $discountedPriceFieldOptionID
   *   ID of the civicrm_price_field_value field for the discount id.
   */
  public static function createDiscountTrxn($eventID, $contributionParams, $feeLevel, $discountedPriceFieldOptionID = NULL) {
    $financialTypeID = $contributionParams['contribution']->financial_type_id;
    $total_amount = $contributionParams['total_amount'];
    if (is_array($feeLevel)) {
      CRM_Core_Error::deprecatedFunctionWarning('array passed for string value');
      $feeLevel = (string) current($feeLevel);
    }

    $checkDiscount = CRM_Core_BAO_Discount::findSet($eventID, 'civicrm_event');
    if (!empty($checkDiscount)) {
      $mainAmount = self::getUnDiscountedAmountForEventPriceSetFieldValue($eventID, $discountedPriceFieldOptionID, $feeLevel);
      $transactionParams['from_financial_account_id'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount(
        $financialTypeID, 'Discounts Account is');
      if (!empty($transactionParams['trxnParams']['from_financial_account_id'])) {
        $transactionParams['trxnParams']['total_amount'] = $mainAmount - $total_amount;
        $transactionParams['trxnParams']['payment_processor_id'] = NULL;
        $transactionParams['trxnParams']['payment_instrument_id'] = NULL;
        $transactionParams['trxnParams']['check_number'] = NULL;
        $transactionParams['trxnParams']['trxn_id'] = NULL;
        $transactionParams['trxnParams']['net_amount'] = NULL;
        $transactionParams['trxnParams']['fee_amount'] = NULL;
        CRM_Core_BAO_FinancialTrxn::create($transactionParams);
      }
    }
  }

  /**
   * Delete participants of contact.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-12155
   *
   * @param int $contactId
   *   Contact id.
   *
   */
  public static function deleteContactParticipant($contactId) {
    $participant = new CRM_Event_DAO_Participant();
    $participant->contact_id = $contactId;
    $participant->find();
    while ($participant->fetch()) {
      self::deleteParticipant($participant->id);
    }
  }

  /**
   * @param int $participantId
   * @param $activityType
   *
   * @throws CRM_Core_Exception
   */
  public static function addActivityForSelection($participantId, $activityType) {
    $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantId, 'event_id');
    $contactId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantId, 'contact_id');

    $date = CRM_Utils_Date::currentDBDate();
    $title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventId, 'title');
    $subject = ts('Registration selections changed for %1', [1 => $title]);

    // activity params
    $activityParams = [
      'source_contact_id' => $contactId,
      'source_record_id' => $participantId,
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $activityType),
      'subject' => $subject,
      'activity_date_time' => $date,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
      'skipRecentView' => TRUE,
    ];

    // create activity with target contacts
    $id = CRM_Core_Session::getLoggedInContactID();
    if ($id) {
      $activityParams['source_contact_id'] = $id;
      $activityParams['target_contact_id'][] = $contactId;
    }
    // @todo use api & also look at duplication of similar methods.
    CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * Pseudoconstant condition_provider for role_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterRole(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    if (isset($params['values']['filter'])) {
      $conditions->where('filter = #filter', ['filter' => (int) $params['values']['filter']]);
    }
  }

  /**
   * Pseudoconstant condition_provider for status_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterStatus(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    if (isset($params['values']['is_counted'])) {
      $conditions->where('is_counted = #counted', ['counted' => (int) $params['values']['is_counted']]);
    }
  }

  /**
   * CRM-17797 -- Format fields and setDefaults for primary and additional participants profile
   * @param int $contactId
   * @param CRM_Core_Form $form
   */
  public static function formatFieldsAndSetProfileDefaults($contactId, &$form) {
    if (!$contactId) {
      return;
    }
    $fields = [];
    if (!empty($form->_fields)) {

      foreach ($form->_fields as $name => $fieldInfo) {
        if ((substr($name, 0, 7) == 'custom_' && !$form->_allowConfirmation
          && !CRM_Core_BAO_CustomGroup::checkCustomField(substr($name, 7), ['Participant']))
          || substr($name, 0, 12) == 'participant_') {
          continue;
        }
        $fields[$name] = $fieldInfo;
      }

      if (!empty($fields)) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $fields, $form->_defaults);
      }
    }
  }

  /**
   * Evaluate whether a participant record is eligible for self-service transfer/cancellation.  If so,
   * return additional participant/event details.
   *
   * @param int $participantId
   * @param string $url
   * @param bool $isBackOffice
   */
  public static function getSelfServiceEligibility(int $participantId, string $url, bool $isBackOffice) : array {
    $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'participant_role', 'id', 'name');
    $query = "
      SELECT cpst.name as status, cpst.label as statuslabel, cov.name as role, cov.label as rolelabel, cp.fee_level, cp.fee_amount, cp.register_date, cp.status_id, ce.start_date, ce.title, cp.event_id, ce.allow_selfcancelxfer
      FROM civicrm_participant cp
      LEFT JOIN civicrm_participant_status_type cpst ON cpst.id = cp.status_id
      LEFT JOIN civicrm_option_value cov ON cov.value = cp.role_id and cov.option_group_id = {$optionGroupId}
      LEFT JOIN civicrm_event ce ON ce.id = cp.event_id
      WHERE cp.id = {$participantId}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $details['eligible'] = TRUE;
      $details['status']  = $dao->status;
      $details['role'] = $dao->role;
      $details['fee_level'] = $dao->fee_level ? implode('<br>', CRM_Core_DAO::unSerializeField($dao->fee_level, CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND)) : NULL;
      $details['fee_amount'] = $dao->fee_amount;
      $details['rolelabel'] = $dao->rolelabel;
      $details['statuslabel'] = $dao->statuslabel;
      $details['register_date'] = $dao->register_date;
      $details['event_start_date'] = $dao->start_date;
      $details['allow_selfcancelxfer'] = $dao->allow_selfcancelxfer;
      $eventTitle = $dao->title;
      $eventId = $dao->event_id;
    }
    if (!$isBackOffice) {
      if (!$details['allow_selfcancelxfer']) {
        $details['eligible'] = FALSE;
        $details['ineligible_message'] = ts('This event registration can not be transferred or cancelled. Contact the event organizer if you have questions.');
        return $details;
      }
      // Verify participant status is one that can be self-cancelled
      if (!in_array($details['status'], ['Registered', 'Pending from pay later', 'On waitlist', 'Pending from incomplete transaction'])) {
        $details['eligible'] = FALSE;
        $details['ineligible_message'] = ts('You cannot transfer or cancel your registration for %1 as you are not currently registered for this event.', [1 => $eventTitle]);
        return $details;
      }
      // Determine if it's too late to self-service cancel/transfer.
      $query = "select start_date as start, selfcancelxfer_time as time from civicrm_event where id = " . $eventId;
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $time_limit  = $dao->time;
        $start_date = $dao->start;
      }
      $timenow = new Datetime();
      if (isset($time_limit)) {
        $cancelHours = abs($time_limit);
        $cancelInterval = new DateInterval("PT{$cancelHours}H");
        $cancelInterval->invert = $time_limit < 0 ? 1 : 0;
        $cancelDeadline = (new Datetime($start_date))->sub($cancelInterval);
        if ($timenow > $cancelDeadline) {
          $details['eligible'] = FALSE;
          // Change the language of the status message based on whether the waitlist time limit is positive or negative.
          $afterOrPrior = $time_limit <= 0 ? 'after' : 'prior to';
          $moreOrLess = $time_limit <= 0 ? 'more' : 'fewer';
          $details['ineligible_message'] = ts("Registration for this event cannot be cancelled or transferred %1 than %2 hours %3 the event's start time. Contact the event organizer if you have questions.",
          [1 => $moreOrLess, 2 => $cancelHours, 3 => $afterOrPrior]);

        }
      }
    }
    return $details;
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    // Set the default role ID on create.
    if ($event->entity === 'Participant' && $event->action === 'create' && empty($event->params['role_id'])) {
      if (!empty($event->params['event_id'])) {
        $event->params['role_id'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $event->params['event_id'], 'default_role_id');
      }
      else {
        $params['role_id'] = CRM_Core_DAO::singleValueQuery('SELECT default_role_id FROM civicrm_event WHERE id = %1', [
          1 => [$event->params['event_id'], 'Integer'],
        ]);
      }
    }
    if ($event->entity === 'Participant' && $event->action === 'create' && empty($event->params['created_id'])) {
      // Set the "created_id" field if not already set.
      // The created_id should always be the person that actually did the registration.
      // That might be the first participant, but it might be someone registering someone without registering themselves.
      // 1. Prefer logged in contact id
      // 2. Fall back to 'registered_by_id' param.
      // 3. Fall back to participant contact_id (for anonymous person registering themselves)
      $event->params['created_id'] = CRM_Core_Session::getLoggedInContactID();
      if (empty($event->params['created_id'])) {
        if (!empty($event->params['registered_by_id'])) {
          // No logged in contact but participant was registered by someone else.
          // Look up the contact ID of that participant and record
          $participant = \Civi\Api4\Participant::get(FALSE)
            ->addSelect('contact_id')
            ->addWhere('id', '=', $event->params['registered_by_id'])
            ->execute()
            ->first();
          $event->params['created_id'] = $participant['contact_id'];
        }
        else {
          $event->params['created_id'] = $event->params['contact_id'];
        }
      }
    }
  }

  /**
   * Get the clause to exclude uncounted participant roles.
   *
   * @internal do not call from outside core code.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function getParticipantRoleClause(): string {
    // Only count Participant Roles with the "Counted?" flag.
    $participantRoles = self::buildOptions('role_id', NULL, ['filter' => TRUE]);
    $allRoles = self::buildOptions('role_id');
    if ($participantRoles === $allRoles) {
      // Don't complicate the query if no roles are excluded.
      return '';
    }
    if (!empty($participantRoles)) {
      $escapedRoles = [];
      foreach (array_keys($participantRoles) as $participantRole) {
        $escapedRoles[] = CRM_Utils_Type::escape($participantRole, 'String');
      }

      $regexp = "([[:cntrl:]]|^)" . implode('([[:cntrl:]]|$)|([[:cntrl:]]|^)', $escapedRoles) . "([[:cntrl:]]|$)";
      $participantRoleClause = "REGEXP '{$regexp}'";
    }
    return $participantRoleClause ?? '';
  }

}
