<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Event_BAO_Participant extends CRM_Event_DAO_Participant {

  /**
   * Static field for all the participant information that we can potentially import.
   *
   * @var array
   */
  static $_importableFields = NULL;

  /**
   * Static field for all the participant information that we can potentially export.
   *
   * @var array
   */
  static $_exportableFields = NULL;

  /**
   * Static array for valid status transitions rules.
   *
   * @var array
   */
  static $_statusTransitionsRules = array(
    'Pending from pay later' => array('Registered', 'Cancelled'),
    'Pending from incomplete transaction' => array('Registered', 'Cancelled'),
    'On waitlist' => array('Cancelled', 'Pending from waitlist'),
    'Pending from waitlist' => array('Registered', 'Cancelled'),
    'Awaiting approval' => array('Cancelled', 'Pending from approval'),
    'Pending from approval' => array('Registered', 'Cancelled'),
  );

  /**
   */
  public function __construct() {
    parent::__construct();
  }

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

    if (!empty($params['fee_amount'])) {
      $params['fee_amount'] = CRM_Utils_Rule::cleanMoney($params['fee_amount']);
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
      $participantBAO->id = CRM_Utils_Array::value('id', $params);
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

    $session = CRM_Core_Session::singleton();

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
  public static function getValues(&$params, &$values, &$ids) {
    if (empty($params)) {
      return NULL;
    }
    $participant = new CRM_Event_BAO_Participant();
    $participant->copyValues($params);
    $participant->find();
    $participants = array();
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

    if (is_a($participant, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $participant;
    }

    if ((!CRM_Utils_Array::value('id', $params)) ||
      (isset($params['status_id']) && $params['status_id'] != $status)
    ) {
      CRM_Activity_BAO_Activity::addActivity($participant);
    }

    //CRM-5403
    //for update mode
    if (self::isPrimaryParticipant($participant->id) && $status) {
      self::updateParticipantStatus($participant->id, $status, $participant->status_id);
    }

    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (!$id) {
      $id = CRM_Utils_Array::value('contact_id', $params);
    }

    // add custom field values
    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_participant', $participant->id);
    }

    //process note, CRM-7634
    $noteId = NULL;
    if (!empty($params['id'])) {
      $note = CRM_Core_BAO_Note::getNote($params['id'], 'civicrm_participant');
      $noteId = key($note);
    }
    $noteValue = NULL;
    $hasNoteField = FALSE;
    foreach (array(
               'note',
               'participant_note',
             ) as $noteFld) {
      if (array_key_exists($noteFld, $params)) {
        $noteValue = $params[$noteFld];
        $hasNoteField = TRUE;
        break;
      }
    }
    if ($noteId || $noteValue) {
      if ($noteValue) {
        $noteParams = array(
          'entity_table' => 'civicrm_participant',
          'note' => $noteValue,
          'entity_id' => $participant->id,
          'contact_id' => $id,
          'modified_date' => date('Ymd'),
        );
        $noteIDs = array();
        if ($noteId) {
          $noteIDs['id'] = $noteId;
        }
        CRM_Core_BAO_Note::add($noteParams, $noteIDs);
      }
      elseif ($noteId && $hasNoteField) {
        CRM_Core_BAO_Note::del($noteId, FALSE);
      }
    }

    // Log the information on successful add/edit of Participant data.
    $logParams = array(
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participant->id,
      'data' => CRM_Event_PseudoConstant::participantStatus($participant->status_id),
      'modified_id' => $id,
      'modified_date' => date('Ymd'),
    );

    CRM_Core_BAO_Log::add($logParams);

    $params['participant_id'] = $participant->id;

    $transaction->commit();

    // do not add to recent items for import, CRM-4399
    if (empty($params['skipRecentView'])) {

      $url = CRM_Utils_System::url('civicrm/contact/view/participant',
        "action=view&reset=1&id={$participant->id}&cid={$participant->contact_id}&context=home"
      );

      $recentOther = array();
      if (CRM_Core_Permission::check('edit event participants')) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/participant',
          "action=update&reset=1&id={$participant->id}&cid={$participant->contact_id}&context=home"
        );
      }
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/participant',
          "action=delete&reset=1&id={$participant->id}&cid={$participant->contact_id}&context=home"
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
   * @param bool $considerTestParticipant
   *
   * @return bool|int|null|string
   *   1. false                 => If event having some empty spaces.
   */
  public static function eventFull(
    $eventId,
    $returnEmptySeats = FALSE,
    $includeWaitingList = TRUE,
    $returnWaitingCount = FALSE,
    $considerTestParticipant = FALSE
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

    $participantRoles = CRM_Event_PseudoConstant::participantRole(NULL, 'filter = 1');
    $countedStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
    $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
    $onWaitlistStatusId = array_search('On waitlist', $waitingStatuses);

    //when we do require only waiting count don't consider counted.
    if (!$returnWaitingCount && !empty($countedStatuses)) {
      $allStatusIds = array_keys($countedStatuses);
    }

    $where = array(' event.id = %1 ');
    if (!$considerTestParticipant) {
      $where[] = ' ( participant.is_test = 0 OR participant.is_test IS NULL ) ';
    }
    if (!empty($participantRoles)) {
      $escapedRoles = array();
      foreach (array_keys($participantRoles) as $participantRole) {
        $escapedRoles[] = CRM_Utils_Type::escape($participantRole, 'String');
      }

      $where[] = " participant.role_id IN ( '" . implode("', '", $escapedRoles) . "' ) ";
    }

    $eventParams = array(1 => array($eventId, 'Positive'));

    //in case any waiting, straight forward event is full.
    if ($includeWaitingList && $onWaitlistStatusId) {

      //build the where clause.
      $whereClause = ' WHERE ' . implode(' AND ', $where);
      $whereClause .= " AND participant.status_id = $onWaitlistStatusId ";
      $eventSeatsWhere = implode(' AND ', $where) . " AND ( participant.status_id = $onWaitlistStatusId )";

      $query = "
    SELECT  participant.id id,
            event.event_full_text as event_full_text
      FROM  civicrm_participant participant
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id )
            {$whereClause}";

      $eventFullText = ts('This event is full.');
      $participants = CRM_Core_DAO::executeQuery($query, $eventParams);
      while ($participants->fetch()) {
        //oops here event is full and we don't want waiting count.
        if ($returnWaitingCount) {
          return CRM_Event_BAO_Event::eventTotalSeats($eventId, $eventSeatsWhere);
        }
        else {
          return ($participants->event_full_text) ? $participants->event_full_text : $eventFullText;
        }
      }
    }

    //consider only counted participants.
    $where[] = ' participant.status_id IN ( ' . implode(', ', array_keys($countedStatuses)) . ' ) ';
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
    $skipParticipantIds = array(),
    $considerCounted = TRUE,
    $considerWaiting = TRUE,
    $considerTestParticipants = FALSE
  ) {
    $optionsCount = array();
    if (!$eventId) {
      return $optionsCount;
    }

    $allStatusIds = array();
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
      $isTestClause = ' AND ( participant.is_test IS NULL OR participant.is_test = 0 )';
    }

    $skipParticipantClause = NULL;
    if (is_array($skipParticipantIds) && !empty($skipParticipantIds)) {
      $skipParticipantClause = ' AND participant.id NOT IN ( ' . implode(', ', $skipParticipantIds) . ')';
    }

    $sql = "
    SELECT  line.id as lineId,
            line.entity_id as entity_id,
            line.qty,
            value.id as valueId,
            value.count,
            field.html_type
      FROM  civicrm_line_item line
INNER JOIN  civicrm_participant participant ON ( line.entity_table  = 'civicrm_participant'
                                                 AND participant.id = line.entity_id )
INNER JOIN  civicrm_price_field_value value ON ( value.id = line.price_field_value_id )
INNER JOIN  civicrm_price_field field       ON ( value.price_field_id = field.id )
     WHERE  participant.event_id = %1
            {$statusIdClause}
            {$isTestClause}
            {$skipParticipantClause}";

    $lineItem = CRM_Core_DAO::executeQuery($sql, array(1 => array($eventId, 'Positive')));
    while ($lineItem->fetch()) {
      $count = $lineItem->count;
      if (!$count) {
        $count = 1;
      }
      if ($lineItem->html_type == 'Text') {
        $count *= $lineItem->qty;
      }
      $optionsCount[$lineItem->valueId] = $count + CRM_Utils_Array::value($lineItem->valueId, $optionsCount, 0);
    }

    return $optionsCount;
  }

  /**
   * Get the empty spaces for event those we can allocate
   * to pending participant to become confirm.
   *
   * @param int $eventId
   *   Event id.
   *
   * @return int
   *   $spaces  Number of Empty Seats/null.
   */
  public static function pendingToConfirmSpaces($eventId) {
    $emptySeats = 0;
    if (!$eventId) {
      return $emptySeats;
    }

    $positiveStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Positive'");
    $statusIds = '(' . implode(',', array_keys($positiveStatuses)) . ')';

    $query = "
  SELECT  count(participant.id) as registered,
          civicrm_event.max_participants
    FROM  civicrm_participant participant, civicrm_event
   WHERE  participant.event_id = {$eventId}
     AND  civicrm_event.id = participant.event_id
     AND  participant.status_id IN {$statusIds}
GROUP BY  participant.event_id
";
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {

      //unlimited space.
      if ($dao->max_participants == NULL || $dao->max_participants <= 0) {
        return NULL;
      }

      //no space.
      if ($dao->registered >= $dao->max_participants) {
        return $emptySeats;
      }

      //difference.
      return $dao->max_participants - $dao->registered;
    }

    //space in case no registeration yet.
    return CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventId, 'max_participants');
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
   * @return array
   *   array of importable Fields
   */
  public static function &importableFields($contactType = 'Individual', $status = TRUE, $onlyParticipant = FALSE, $checkPermission = TRUE) {
    if (!self::$_importableFields) {
      if (!$onlyParticipant) {
        if (!$status) {
          $fields = array('' => array('title' => ts('- do not import -')));
        }
        else {
          $fields = array('' => array('title' => ts('- Participant Fields -')));
        }
      }
      else {
        $fields = array();
      }

      $tmpFields = CRM_Event_DAO_Participant::import();

      $note = array(
        'participant_note' => array(
          'title' => ts('Participant Note'),
          'name' => 'participant_note',
          'headerPattern' => '/(participant.)?note$/i',
        ),
      );

      // Split status and status id into 2 fields
      // Fixme: it would be better to leave as 1 field and intelligently handle both during import
      $participantStatus = array(
        'participant_status' => array(
          'title' => ts('Participant Status'),
          'name' => 'participant_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ),
      );
      $tmpFields['participant_status_id']['title'] = ts('Participant Status Id');

      // Split role and role id into 2 fields
      // Fixme: it would be better to leave as 1 field and intelligently handle both during import
      $participantRole = array(
        'participant_role' => array(
          'title' => ts('Participant Role'),
          'name' => 'participant_role',
          'data_type' => CRM_Utils_Type::T_STRING,
        ),
      );
      $tmpFields['participant_role_id']['title'] = ts('Participant Role Id');

      $eventType = array(
        'event_type' => array(
          'title' => ts('Event Type'),
          'name' => 'event_type',
          'data_type' => CRM_Utils_Type::T_STRING,
        ),
      );

      $tmpContactField = $contactFields = array();
      $contactFields = array();
      if (!$onlyParticipant) {
        $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

        // Using new Dedupe rule.
        $ruleParams = array(
          'contact_type' => $contactType,
          'used' => 'Unsupervised',
        );
        $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);

        if (is_array($fieldsArray)) {
          foreach ($fieldsArray as $value) {
            $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
              $value,
              'id',
              'column_name'
            );
            $value = $customFieldId ? 'custom_' . $customFieldId : $value;
            $tmpContactField[trim($value)] = CRM_Utils_Array::value(trim($value), $contactFields);
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
      $extIdentifier = CRM_Utils_Array::value('external_identifier', $contactFields);
      if ($extIdentifier) {
        $tmpContactField['external_identifier'] = $extIdentifier;
        $tmpContactField['external_identifier']['title'] = CRM_Utils_Array::value('title', $extIdentifier) . ' (match to contact)';
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
        self::$_exportableFields = array();
      }

      $participantFields = CRM_Event_DAO_Participant::export();
      $eventFields = CRM_Event_DAO_Event::export();
      $noteField = array(
        'participant_note' => array(
          'title' => 'Participant Note',
          'name' => 'participant_note',
          'type' => CRM_Utils_Type::T_STRING,
        ),
      );

      $participantStatus = array(
        'participant_status' => array(
          'title' => 'Participant Status',
          'name' => 'participant_status',
          'type' => CRM_Utils_Type::T_STRING,
        ),
      );

      $participantRole = array(
        'participant_role' => array(
          'title' => 'Participant Role',
          'name' => 'participant_role',
          'type' => CRM_Utils_Type::T_STRING,
        ),
      );

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

    $details = array();
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
   * @param string $lookup
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
    CRM_Utils_Hook::pre('delete', 'Participant', $id, CRM_Core_DAO::$_nullArray);

    $transaction = new CRM_Core_Transaction();

    //delete activity record
    $params = array(
      'source_record_id' => $id,
      // activity type id for event registration
      'activity_type_id' => 5,
    );

    CRM_Activity_BAO_Activity::deleteActivity($params);

    // delete the participant payment record
    // we need to do this since the cascaded constraints
    // dont work with join tables
    $p = array('participant_id' => $id);
    CRM_Event_BAO_ParticipantPayment::deleteParticipantPayment($p);

    // cleanup line items.
    $participantsId = array();
    $participantsId = self::getAdditionalParticipantIds($id);
    $participantsId[] = $id;
    CRM_Price_BAO_LineItem::deleteLineItems($participantsId, 'civicrm_participant');

    //delete note when participant deleted.
    $note = CRM_Core_BAO_Note::getNote($id, 'civicrm_participant');
    $noteId = key($note);
    if ($noteId) {
      CRM_Core_BAO_Note::del($noteId, FALSE);
    }

    $participant = new CRM_Event_DAO_Participant();
    $participant->id = $id;
    $participant->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Participant', $participant->id, $participant);

    // delete the recently created Participant
    $participantRecent = array(
      'id' => $id,
      'type' => 'Participant',
    );

    CRM_Utils_Recent::del($participantRecent);

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
    $eventId = CRM_Utils_Array::value('event_id', $input);
    $contactId = CRM_Utils_Array::value('contact_id', $input);

    $clause = array();
    $input = array();

    if ($eventId) {
      $clause[] = "event_id = %1";
      $input[1] = array($eventId, 'Integer');
    }

    if ($contactId) {
      $clause[] = "contact_id = %2";
      $input[2] = array($contactId, 'Integer');
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
   * Get the additional participant ids.
   *
   * @param int $primaryParticipantId
   *   Primary partycipant Id.
   * @param bool $excludeCancel
   *   Do not include participant those are cancelled.
   *
   * @param int $oldStatusId
   *
   * @return array
   */
  public static function getAdditionalParticipantIds($primaryParticipantId, $excludeCancel = TRUE, $oldStatusId = NULL) {
    $additionalParticipantIds = array();
    if (!$primaryParticipantId) {
      return $additionalParticipantIds;
    }

    $where = "participant.registered_by_id={$primaryParticipantId}";
    if ($excludeCancel) {
      $cancelStatusId = 0;
      $negativeStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
      $cancelStatusId = array_search('Cancelled', $negativeStatuses);
      $where .= " AND participant.status_id != {$cancelStatusId}";
    }

    if ($oldStatusId) {
      $where .= " AND participant.status_id = {$oldStatusId}";
    }

    $query = "
  SELECT  participant.id
    FROM  civicrm_participant participant
   WHERE  {$where}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $additionalParticipantIds[$dao->id] = $dao->id;
    }
    return $additionalParticipantIds;
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
    $params = array(
      1 => array($priceSetId, 'Integer'),
    );
    if ($discountedPriceFieldOptionID) {
      $query = "SELECT cpfv.amount FROM `civicrm_price_field_value` cpfv
LEFT JOIN civicrm_price_field cpf ON cpfv.price_field_id = cpf.id
WHERE cpf.price_set_id = %1 AND cpfv.label = (SELECT label from civicrm_price_field_value WHERE id = %2)";
      $params[2] = array($discountedPriceFieldOptionID, 'Integer');
    }
    else {
      $feeLevel = current($feeLevel);
      $query = "SELECT cpfv.amount FROM `civicrm_price_field_value` cpfv
LEFT JOIN civicrm_price_field cpf ON cpfv.price_field_id = cpf.id
WHERE cpf.price_set_id = %1 AND cpfv.label LIKE %2";
      $params[2] = array($feeLevel, 'String');
    }
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Get the event fee info for given participant ids
   * either from line item table / participant table.
   *
   * @param array $participantIds
   *   Participant ids.
   * @param bool $hasLineItems
   *   Do fetch from line items.
   *
   * @return array
   */
  public function getFeeDetails($participantIds, $hasLineItems = FALSE) {
    $feeDetails = array();
    if (!is_array($participantIds) || empty($participantIds)) {
      return $feeDetails;
    }

    $select = '
SELECT  participant.id         as id,
        participant.fee_level  as fee_level,
        participant.fee_amount as fee_amount';
    $from = 'FROM civicrm_participant participant';
    if ($hasLineItems) {
      $select .= ' ,
lineItem.id          as lineId,
lineItem.label       as label,
lineItem.qty         as qty,
lineItem.unit_price  as unit_price,
lineItem.line_total  as line_total,
field.label          as field_title,
field.html_type      as html_type,
field.id             as price_field_id,
value.id             as price_field_value_id,
value.description    as description,
IF( value.count, value.count, 0 ) as participant_count';
      $from .= "
INNER JOIN civicrm_line_item lineItem      ON ( lineItem.entity_table = 'civicrm_participant'
                                                AND lineItem.entity_id = participant.id )
INNER JOIN civicrm_price_field field ON ( field.id = lineItem.price_field_id )
INNER JOIN civicrm_price_field_value value ON ( value.id = lineItem.price_field_value_id )
";
    }
    $where = 'WHERE participant.id IN ( ' . implode(', ', $participantIds) . ' )';
    $query = "$select $from  $where";

    $feeInfo = CRM_Core_DAO::executeQuery($query);
    $feeProperties = array('fee_level', 'fee_amount');
    $lineProperties = array(
      'lineId',
      'label',
      'qty',
      'unit_price',
      'line_total',
      'field_title',
      'html_type',
      'price_field_id',
      'participant_count',
      'price_field_value_id',
      'description',
    );
    while ($feeInfo->fetch()) {
      if ($hasLineItems) {
        foreach ($lineProperties as $property) {
          $feeDetails[$feeInfo->id][$feeInfo->lineId][$property] = $feeInfo->$property;
        }
      }
      else {
        foreach ($feeProperties as $property) {
          $feeDetails[$feeInfo->id][$property] = $feeInfo->$property;
        }
      }
    }

    return $feeDetails;
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
    $additionalParticipantIDs = array();
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
      $cascadeAdditionalIds = implode(',', $cascadeAdditionalIds);
      $query = "UPDATE civicrm_participant cp SET cp.status_id = %1 WHERE  cp.id IN ({$cascadeAdditionalIds})";
      $params = array(1 => array($newStatusID, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      return TRUE;
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
    $allParticipantIds = $primaryANDAdditonalIds = array();
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
          $primaryANDAdditonalIds[$id] = $additionalIds;
        }
      }
    }

    //get the unique participant ids,
    $allParticipantIds = array_unique($allParticipantIds);

    //pull required participants, contacts, events  data, if not in hand
    static $eventDetails = array();
    static $domainValues = array();
    static $contactDetails = array();

    $contactIds = $eventIds = $participantDetails = array();

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
      $participantDetails[$dao->id] = array(
        'id' => $dao->id,
        'role' => $participantRoles[$dao->role_id],
        'is_test' => $dao->is_test,
        'event_id' => $dao->event_id,
        'status_id' => $dao->status_id,
        'fee_amount' => $dao->fee_amount,
        'contact_id' => $dao->contact_id,
        'register_date' => $dao->register_date,
        'registered_by_id' => $dao->registered_by_id,
      );
      if (!array_key_exists($dao->contact_id, $contactDetails)) {
        $contactIds[$dao->contact_id] = $dao->contact_id;
      }

      if (!array_key_exists($dao->event_id, $eventDetails)) {
        $eventIds[$dao->event_id] = $dao->event_id;
      }
    }

    //get the domain values.
    if (empty($domainValues)) {
      // making all tokens available to templates.
      $domain = CRM_Core_BAO_Domain::getDomain();
      $tokens = array(
        'domain' => array('name', 'phone', 'address', 'email'),
        'contact' => CRM_Core_SelectValues::contactTokens(),
      );

      foreach ($tokens['domain'] as $token) {
        $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
      }
    }

    //get all required contacts detail.
    if (!empty($contactIds)) {
      // get the contact details.
      list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
        FALSE, FALSE, NULL,
        array(),
        'CRM_Event_BAO_Participant'
      );
      foreach ($currentContactDetails as $contactId => $contactValues) {
        $contactDetails[$contactId] = $contactValues;
      }
    }

    //get all required events detail.
    if (!empty($eventIds)) {
      foreach ($eventIds as $eventId) {
        //retrieve event information
        $eventParams = array('id' => $eventId);
        CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails[$eventId]);

        //get default participant role.
        $eventDetails[$eventId]['participant_role'] = CRM_Utils_Array::value($eventDetails[$eventId]['default_role_id'], $participantRoles);

        //get the location info
        $locParams = array('entity_id' => $eventId, 'entity_table' => 'civicrm_event');
        $eventDetails[$eventId]['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
      }
    }

    //now we are ready w/ all required data.
    //take a decision as per statuses.

    $emailType = NULL;
    $toStatus = $statusTypes[$toStatusId];
    $fromStatus = CRM_Utils_Array::value($fromStatusId, $statusTypes);

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
    $processedParticipantIds = array();
    $mailedParticipants = array();

    //send mails and update status.
    foreach ($participantDetails as $participantId => $participantValues) {
      $updateParticipantIds = array();
      if (in_array($participantId, $processedParticipantIds)) {
        continue;
      }

      //check is it primary and has additional.
      if (array_key_exists($participantId, $primaryANDAdditonalIds)) {
        foreach ($primaryANDAdditonalIds[$participantId] as $additonalId) {

          if ($emailType) {
            $mail = self::sendTransitionParticipantMail($additonalId,
              $participantDetails[$additonalId],
              $eventDetails[$participantDetails[$additonalId]['event_id']],
              $contactDetails[$participantDetails[$additonalId]['contact_id']],
              $domainValues,
              $emailType
            );

            //get the mail participant ids
            if ($mail) {
              $mailedParticipants[$additonalId] = $contactDetails[$participantDetails[$additonalId]['contact_id']]['display_name'];
            }
          }
          $updateParticipantIds[] = $additonalId;
          $processedParticipantIds[] = $additonalId;
        }
      }

      //now send email appropriate mail to primary.
      if ($emailType) {
        $mail = self::sendTransitionParticipantMail($participantId,
          $participantValues,
          $eventDetails[$participantValues['event_id']],
          $contactDetails[$participantValues['contact_id']],
          $domainValues,
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
      $results = array(
        'mailedParticipants' => $mailedParticipants,
        'updatedParticipantIds' => $processedParticipantIds,
      );

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
   * @param array $domainValues
   *   Required domain values.
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
    &$domainValues,
    $mailType
  ) {
    //send emails.
    $mailSent = FALSE;

    //don't send confirmation mail to additional
    //since only primary able to confirm registration.
    if (!empty($participantValues['registered_by_id']) &&
      $mailType == 'Confirm'
    ) {
      return $mailSent;
    }
    $toEmail = CRM_Utils_Array::value('email', $contactDetails);
    if ($toEmail) {

      $contactId = $participantValues['contact_id'];
      $participantName = $contactDetails['display_name'];

      //calculate the checksum value.
      $checksumValue = NULL;
      if ($mailType == 'Confirm' && !$participantValues['registered_by_id']) {
        $checksumLife = 'inf';
        $endDate = CRM_Utils_Array::value('end_date', $eventDetails);
        if ($endDate) {
          $checksumLife = (CRM_Utils_Date::unixTime($endDate) - time()) / (60 * 60);
        }
        $checksumValue = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId, NULL, $checksumLife);
      }

      //take a receipt from as event else domain.
      $receiptFrom = $domainValues['name'] . ' <' . $domainValues['email'] . '>';
      if (!empty($eventDetails['confirm_from_name']) && !empty($eventDetails['confirm_from_email'])) {
        $receiptFrom = $eventDetails['confirm_from_name'] . ' <' . $eventDetails['confirm_from_email'] . '>';
      }

      list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
        array(
          'groupName' => 'msg_tpl_workflow_event',
          'valueName' => 'participant_' . strtolower($mailType),
          'contactId' => $contactId,
          'tplParams' => array(
            'contact' => $contactDetails,
            'domain' => $domainValues,
            'participant' => $participantValues,
            'event' => $eventDetails,
            'paidEvent' => CRM_Utils_Array::value('is_monetary', $eventDetails),
            'isShowLocation' => CRM_Utils_Array::value('is_show_location', $eventDetails),
            'isAdditional' => $participantValues['registered_by_id'],
            'isExpired' => $mailType == 'Expired',
            'isConfirm' => $mailType == 'Confirm',
            'checksumValue' => $checksumValue,
          ),
          'from' => $receiptFrom,
          'toName' => $participantName,
          'toEmail' => $toEmail,
          'cc' => CRM_Utils_Array::value('cc_confirm', $eventDetails),
          'bcc' => CRM_Utils_Array::value('bcc_confirm', $eventDetails),
        )
      );

      // 3. create activity record.
      if ($mailSent) {
        $now = date('YmdHis');
        $activityType = 'Event Registration';
        $activityParams = array(
          'subject' => $subject,
          'source_contact_id' => $contactId,
          'source_record_id' => $participantId,
          'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
            $activityType,
            'name'
          ),
          'activity_date_time' => CRM_Utils_Date::isoToMysql($now),
          'due_date_time' => CRM_Utils_Date::isoToMysql($participantValues['register_date']),
          'is_test' => $participantValues['is_test'],
          'status_id' => 2,
        );

        if (is_a(CRM_Activity_BAO_Activity::create($activityParams), 'CRM_Core_Error')) {
          CRM_Core_Error::fatal('Failed creating Activity for expiration mail');
        }
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
  public function updateStatusMessage($participantId, $statusChangeTo, $fromStatusId) {
    $statusMsg = NULL;
    $results = self::transitionParticipants(array($participantId),
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
                array(
                  1 => $allStatuses[$statusChangeTo],
                  2 => $results['mailedParticipants'][$processedId],
                )
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
      $eventfullMsg = ts("This event currently has the maximum number of participants registered (%1). However, you can still override this limit and register additional participants using this form.", array(
          1 => $maxParticipants,
        )) . '<br />';
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
        array(
          1 => $viewWaitListUrl,
          2 => $waitListedCount,
        )
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
   * @return bool
   *   true if allowed
   */
  public static function getValidAdditionalIds($participantId, $oldStatusId, $newStatusId) {

    $additionalParticipantIds = array();

    static $participantStatuses = array();

    if (empty($participantStatuses)) {
      $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
    }

    if (CRM_Utils_Array::value($participantStatuses[$oldStatusId], self::$_statusTransitionsRules) &&
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

    $ids = array();
    if (!$contributionId) {
      return $ids;
    }

    // get primary participant id
    $query = "SELECT participant_id FROM civicrm_participant_payment WHERE contribution_id = {$contributionId}";
    $participantId = CRM_Core_DAO::singleValueQuery($query);

    // get additional participant ids (including cancelled)
    if ($participantId) {
      $ids = array_merge(array(
        $participantId,
      ), self::getAdditionalParticipantIds($participantId,
        $excludeCancelled
      ));
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
      $links = array();
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
  public static function createDiscountTrxn($eventID, $contributionParams, $feeLevel, $discountedPriceFieldOptionID) {
    $financialTypeID = $contributionParams['contribution']->financial_type_id;
    $total_amount = $contributionParams['total_amount'];

    $checkDiscount = CRM_Core_BAO_Discount::findSet($eventID, 'civicrm_event');
    if (!empty($checkDiscount)) {
      $mainAmount = self::getUnDiscountedAmountForEventPriceSetFieldValue($eventID, $discountedPriceFieldOptionID, $feeLevel);
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Discounts Account is' "));
      $transactionParams['from_financial_account_id'] = CRM_Contribute_PseudoConstant::financialAccountType(
        $financialTypeID, $relationTypeId);
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
   * CRM-12155
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
   * @param array $params
   * @param int $participantId
   * @param int $contributionId
   * @param $feeBlock
   * @param array $lineItems
   * @param $paidAmount
   * @param int $priceSetId
   */
  public static function changeFeeSelections($params, $participantId, $contributionId, $feeBlock, $lineItems, $paidAmount, $priceSetId) {
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $partiallyPaidStatusId = array_search('Partially paid', $contributionStatuses);
    $pendingRefundStatusId = array_search('Pending refund', $contributionStatuses);
    $previousLineItems = CRM_Price_BAO_LineItem::getLineItems($participantId, 'participant');
    CRM_Price_BAO_PriceSet::processAmount($feeBlock,
      $params, $lineItems
    );

    // get the submitted
    foreach ($feeBlock as $id => $values) {
      CRM_Price_BAO_LineItem::format($id, $params, $values, $submittedLineItems);
      $submittedFieldId[] = CRM_Utils_Array::retrieveValueRecursive($submittedLineItems, 'price_field_id');
    }
    if (!empty($submittedLineItems)) {
      $insertLines = $submittedLineItems;

      $submittedFieldValueIds = array_keys($submittedLineItems);
      $updateLines = array();
      foreach ($previousLineItems as $id => $previousLineItem) {
        // check through the submitted items if the previousItem exists,
        // if found in submitted items, do not use it for new item creations
        if (in_array($previousLineItem['price_field_value_id'], $submittedFieldValueIds)) {
          // if submitted line items are existing don't fire INSERT query
          unset($insertLines[$previousLineItem['price_field_value_id']]);
          // for updating the line items i.e. use-case - once deselect-option selecting again
          if (($previousLineItem['line_total'] != $submittedLineItems[$previousLineItem['price_field_value_id']]['line_total']) ||
            ($submittedLineItems[$previousLineItem['price_field_value_id']]['line_total'] == 0 && $submittedLineItems[$previousLineItem['price_field_value_id']]['qty'] == 1) ||
            ($previousLineItem['qty'] != $submittedLineItems[$previousLineItem['price_field_value_id']]['qty'])
          ) {
            $updateLines[$previousLineItem['price_field_value_id']] = $submittedLineItems[$previousLineItem['price_field_value_id']];
            $updateLines[$previousLineItem['price_field_value_id']]['id'] = $id;
          }
        }
      }

      $submittedFields = implode(', ', $submittedFieldId);
      $submittedFieldValues = implode(', ', $submittedFieldValueIds);
    }
    if (!empty($submittedFields) && !empty($submittedFieldValues)) {
      $updateLineItem = "UPDATE civicrm_line_item li
INNER JOIN civicrm_financial_item fi
   ON (li.id = fi.entity_id AND fi.entity_table = 'civicrm_line_item')
SET li.qty = 0,
    li.line_total = 0.00,
    li.tax_amount = NULL
WHERE (li.entity_table = 'civicrm_participant' AND li.entity_id = {$participantId}) AND
       (price_field_value_id NOT IN ({$submittedFieldValues}))
";
      CRM_Core_DAO::executeQuery($updateLineItem);

      // gathering necessary info to record negative (deselected) financial_item
      $updateFinancialItem = "
  SELECT fi.*, SUM(fi.amount) as differenceAmt, price_field_value_id, financial_type_id, tax_amount
    FROM civicrm_financial_item fi LEFT JOIN civicrm_line_item li ON (li.id = fi.entity_id AND fi.entity_table = 'civicrm_line_item')
WHERE (li.entity_table = 'civicrm_participant' AND li.entity_id = {$participantId})
GROUP BY li.entity_table, li.entity_id, price_field_value_id, fi.id
";
      $updateFinancialItemInfoDAO = CRM_Core_DAO::executeQuery($updateFinancialItem);
      $trxn = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contributionId, 'DESC', TRUE);
      $trxnId['id'] = $trxn['financialTrxnId'];
      $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
      $taxTerm = CRM_Utils_Array::value('tax_term', $invoiceSettings);
      $updateFinancialItemInfoValues = array();
      $financialItemsArray = array();
      while ($updateFinancialItemInfoDAO->fetch()) {
        $updateFinancialItemInfoValues = (array) $updateFinancialItemInfoDAO;
        $updateFinancialItemInfoValues['transaction_date'] = date('YmdHis');
        // the below params are not needed
        unset($updateFinancialItemInfoValues['id']);
        unset($updateFinancialItemInfoValues['created_date']);
        // if not submitted and difference is not 0 make it negative
        if (!in_array($updateFinancialItemInfoValues['price_field_value_id'], $submittedFieldValueIds) && $updateFinancialItemInfoValues['differenceAmt'] != 0) {
          // INSERT negative financial_items
          $updateFinancialItemInfoValues['amount'] = -$updateFinancialItemInfoValues['amount'];
          if ($previousLineItems[$updateFinancialItemInfoValues['entity_id']]['tax_amount']) {
            $updateFinancialItemInfoValues['tax']['amount'] = -($previousLineItems[$updateFinancialItemInfoValues['entity_id']]['tax_amount']);
            $updateFinancialItemInfoValues['tax']['description'] = $taxTerm;
            if ($updateFinancialItemInfoValues['financial_type_id']) {
              $updateFinancialItemInfoValues['tax']['financial_account_id'] = CRM_Contribute_BAO_Contribution::getFinancialAccountId($updateFinancialItemInfoValues['financial_type_id']);
            }
          }
          // INSERT negative financial_items for tax amount
          $financialItemsArray[] = $updateFinancialItemInfoValues;
        }
        // if submitted and difference is 0 add a positive entry again
        elseif (in_array($updateFinancialItemInfoValues['price_field_value_id'], $submittedFieldValueIds) && $updateFinancialItemInfoValues['differenceAmt'] == 0) {
          $updateFinancialItemInfoValues['amount'] = $updateFinancialItemInfoValues['amount'];
          // INSERT financial_items for tax amount
          if ($updateFinancialItemInfoValues['entity_id'] == $updateLines[$updateFinancialItemInfoValues['price_field_value_id']]['id'] &&
            isset($updateLines[$updateFinancialItemInfoValues['price_field_value_id']]['tax_amount'])
          ) {
            $updateFinancialItemInfoValues['tax']['amount'] = $updateLines[$updateFinancialItemInfoValues['price_field_value_id']]['tax_amount'];
            $updateFinancialItemInfoValues['tax']['description'] = $taxTerm;
            if ($updateLines[$updateFinancialItemInfoValues['price_field_value_id']]['financial_type_id']) {
              $updateFinancialItemInfoValues['tax']['financial_account_id'] = CRM_Contribute_BAO_Contribution::getFinancialAccountId($updateLines[$updateFinancialItemInfoValues['price_field_value_id']]['financial_type_id']);
            }
          }
          $financialItemsArray[] = $updateFinancialItemInfoValues;
        }
      }
    }
    elseif (empty($submittedFields) && empty($submittedFieldValues)) {
      $updateLineItem = "UPDATE civicrm_line_item li
        INNER JOIN civicrm_financial_item fi
        ON (li.id = fi.entity_id AND fi.entity_table = 'civicrm_line_item')
        SET li.qty = 0,
        li.line_total = 0.00,
        li.tax_amount = NULL
        WHERE (li.entity_table = 'civicrm_participant' AND li.entity_id = {$participantId})";
      CRM_Core_DAO::executeQuery($updateLineItem);
    }
    $amountLevel = array();
    $totalParticipant = $participantCount = 0;
    if (!empty($updateLines)) {
      foreach ($updateLines as $valueId => $vals) {
        $taxAmount = "NULL";
        if (isset($vals['tax_amount'])) {
          $taxAmount = $vals['tax_amount'];
        }
        $amountLevel[] = $vals['label'] . ' - ' . (float) $vals['qty'];
        if (isset($vals['participant_count'])) {
          $participantCount = $vals['participant_count'];
          $totalParticipant += $vals['participant_count'];
        }
        $updateLineItem = "
UPDATE civicrm_line_item li
SET li.qty = {$vals['qty']},
    li.line_total = {$vals['line_total']},
    li.tax_amount = {$taxAmount},
    li.unit_price = {$vals['unit_price']},
    li.participant_count = {$participantCount},
    li.label = %1
WHERE (li.entity_table = 'civicrm_participant' AND li.entity_id = {$participantId}) AND
      (price_field_value_id = {$valueId})
";
        CRM_Core_DAO::executeQuery($updateLineItem, array(1 => array($vals['label'], 'String')));
      }
    }
    // insert new 'adjusted amount' transaction entry and update contribution entry.
    // ensure entity_financial_trxn table has a linking of it.
    // insert new line items
    if (!empty($insertLines)) {
      foreach ($insertLines as $valueId => $lineParams) {
        $lineParams['entity_table'] = 'civicrm_participant';
        $lineParams['entity_id'] = $participantId;
        $lineParams['contribution_id'] = $contributionId;
        $lineObj = CRM_Price_BAO_LineItem::create($lineParams);
      }
    }

    // the recordAdjustedAmt code would execute over here
    $ids = CRM_Event_BAO_Participant::getParticipantIds($contributionId);
    if (count($ids) > 1) {
      $total = 0;
      foreach ($ids as $val) {
        $total += CRM_Price_BAO_LineItem::getLineTotal($val, 'civicrm_participant');
      }
      $updatedAmount = $total;
    }
    else {
      $updatedAmount = $params['amount'];
    }
    if (strlen($params['tax_amount']) != 0) {
      $taxAmount = $params['tax_amount'];
    }
    else {
      $taxAmount = "NULL";
    }
    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }
    $updateAmountLevel = NULL;
    if (!empty($amountLevel)) {
      $updateAmountLevel = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    $trxn = self::recordAdjustedAmt($updatedAmount, $paidAmount, $contributionId, $taxAmount, $updateAmountLevel);
    $trxnId = array();
    if ($trxn) {
      $trxnId['id'] = $trxn->id;
      foreach ($financialItemsArray as $updateFinancialItemInfoValues) {
        CRM_Financial_BAO_FinancialItem::create($updateFinancialItemInfoValues, NULL, $trxnId);
        if (!empty($updateFinancialItemInfoValues['tax'])) {
          $updateFinancialItemInfoValues['tax']['amount'] = $updateFinancialItemInfoValues['amount'];
          $updateFinancialItemInfoValues['tax']['description'] = $updateFinancialItemInfoValues['description'];
          if (!empty($updateFinancialItemInfoValues['financial_account_id'])) {
            $updateFinancialItemInfoValues['financial_account_id'] = $updateFinancialItemInfoValues['tax']['financial_account_id'];
          }
          CRM_Financial_BAO_FinancialItem::create($updateFinancialItemInfoValues, NULL, $trxnId);
        }
      }
    }
    $fetchCon = array('id' => $contributionId);
    $updatedContribution = CRM_Contribute_BAO_Contribution::retrieve($fetchCon, CRM_Core_DAO::$_nullArray, CRM_Core_DAO::$_nullArray);
    // insert financial items
    if (!empty($insertLines)) {
      foreach ($insertLines as $valueId => $lineParams) {
        $lineParams['entity_table'] = 'civicrm_participant';
        $lineParams['entity_id'] = $participantId;
        $lineObj = CRM_Price_BAO_LineItem::retrieve($lineParams, CRM_Core_DAO::$_nullArray);
        // insert financial items
        // ensure entity_financial_trxn table has a linking of it.
        $prevItem = CRM_Financial_BAO_FinancialItem::add($lineObj, $updatedContribution, NULL, $trxnId);
        if (isset($lineObj->tax_amount)) {
          CRM_Financial_BAO_FinancialItem::add($lineObj, $updatedContribution, TRUE, $trxnId);
        }
      }
    }

    // update participant fee_amount column
    $partUpdateFeeAmt['id'] = $participantId;
    $getUpdatedLineItems = "SELECT *
FROM civicrm_line_item
WHERE (entity_table = 'civicrm_participant' AND entity_id = {$participantId} AND qty > 0)";
    $getUpdatedLineItemsDAO = CRM_Core_DAO::executeQuery($getUpdatedLineItems);
    while ($getUpdatedLineItemsDAO->fetch()) {
      $line[$getUpdatedLineItemsDAO->price_field_value_id] = $getUpdatedLineItemsDAO->label . ' - ' . (float) $getUpdatedLineItemsDAO->qty;
    }

    $partUpdateFeeAmt['fee_level'] = implode(', ', $line);
    $partUpdateFeeAmt['fee_amount'] = $params['amount'];
    self::add($partUpdateFeeAmt);

    //activity creation
    self::addActivityForSelection($participantId, 'Change Registration');
  }

  /**
   * Record adjusted amount.
   *
   * @param int $updatedAmount
   * @param int $paidAmount
   * @param int $contributionId
   *
   * @param int $taxAmount
   * @param bool $updateAmountLevel
   *
   * @return bool|\CRM_Core_BAO_FinancialTrxn
   */
  public static function recordAdjustedAmt($updatedAmount, $paidAmount, $contributionId, $taxAmount = NULL, $updateAmountLevel = NULL) {
    $pendingAmount = CRM_Core_BAO_FinancialTrxn::getBalanceTrxnAmt($contributionId);
    $pendingAmount = CRM_Utils_Array::value('total_amount', $pendingAmount, 0);
    $balanceAmt = $updatedAmount - $paidAmount;
    if ($paidAmount != $pendingAmount) {
      $balanceAmt -= $pendingAmount;
    }

    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $partiallyPaidStatusId = array_search('Partially paid', $contributionStatuses);
    $pendingRefundStatusId = array_search('Pending refund', $contributionStatuses);
    $completedStatusId = array_search('Completed', $contributionStatuses);

    $updatedContributionDAO = new CRM_Contribute_BAO_Contribution();
    $adjustedTrxn = $skip = FALSE;
    if ($balanceAmt) {
      if ($balanceAmt > 0 && $paidAmount != 0) {
        $contributionStatusVal = $partiallyPaidStatusId;
      }
      elseif ($balanceAmt < 0 && $paidAmount != 0) {
        $contributionStatusVal = $pendingRefundStatusId;
      }
      elseif ($paidAmount == 0) {
        //skip updating the contribution status if no payment is made
        $skip = TRUE;
        $updatedContributionDAO->cancel_date = 'null';
        $updatedContributionDAO->cancel_reason = NULL;
      }
      // update contribution status and total amount without trigger financial code
      // as this is handled in current BAO function used for change selection
      $updatedContributionDAO->id = $contributionId;
      if (!$skip) {
        $updatedContributionDAO->contribution_status_id = $contributionStatusVal;
      }
      $updatedContributionDAO->total_amount = $updatedContributionDAO->net_amount = $updatedAmount;
      $updatedContributionDAO->fee_amount = 0;
      $updatedContributionDAO->tax_amount = $taxAmount;
      if (!empty($updateAmountLevel)) {
        $updatedContributionDAO->amount_level = $updateAmountLevel;
      }
      $updatedContributionDAO->save();
      // adjusted amount financial_trxn creation
      $updatedContribution = CRM_Contribute_BAO_Contribution::getValues(
        array('id' => $contributionId),
        CRM_Core_DAO::$_nullArray,
        CRM_Core_DAO::$_nullArray
      );
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
      $toFinancialAccount = CRM_Contribute_PseudoConstant::financialAccountType($updatedContribution->financial_type_id, $relationTypeId);
      $adjustedTrxnValues = array(
        'from_financial_account_id' => NULL,
        'to_financial_account_id' => $toFinancialAccount,
        'total_amount' => $balanceAmt,
        'status_id' => $completedStatusId,
        'payment_instrument_id' => $updatedContribution->payment_instrument_id,
        'contribution_id' => $updatedContribution->id,
        'trxn_date' => date('YmdHis'),
        'currency' => $updatedContribution->currency,
      );
      $adjustedTrxn = CRM_Core_BAO_FinancialTrxn::create($adjustedTrxnValues);
    }
    return $adjustedTrxn;
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
    $event = CRM_Event_BAO_Event::getEvents(0, $eventId);
    $eventTitle = $event[$eventId];
    $subject = "Registration selections changed for $eventTitle";
    $targetCid = $contactId;
    $srcRecId = $participantId;

    // activity params
    $activityParams = array(
      'source_contact_id' => $targetCid,
      'source_record_id' => $srcRecId,
      'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
        $activityType,
        'name'
      ),
      'subject' => $subject,
      'activity_date_time' => $date,
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
        'Completed',
        'name'
      ),
      'skipRecentView' => TRUE,
    );

    // create activity with target contacts
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if ($id) {
      $activityParams['source_contact_id'] = $id;
      $activityParams['target_contact_id'][] = $targetCid;
    }
    CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * Get options for a given field.
   * @see CRM_Core_DAO::buildOptions
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   whatever is known about this dao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $params = array('condition' => array());

    if ($fieldName == 'status_id' && $context != 'validate') {
      // Get rid of cart-related option if disabled
      // FIXME: Why does this option even exist if cart is disabled?
      if (!Civi::settings()->get('enable_cart')) {
        $params['condition'][] = "name <> 'Pending in cart'";
      }
    }

    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
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
    $fields = array();
    if (!empty($form->_fields)) {
      $removeCustomFieldTypes = array('Participant');

      foreach ($form->_fields as $name => $dontCare) {
        if ((substr($name, 0, 7) == 'custom_' && !$form->_allowConfirmation
          && !CRM_Core_BAO_CustomGroup::checkCustomField(substr($name, 7), $removeCustomFieldTypes))
          || substr($name, 0, 12) == 'participant_') {
          continue;
        }
        $fields[$name] = 1;
      }

      if (!empty($fields)) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $fields, $form->_defaults);
      }
    }
  }

}
