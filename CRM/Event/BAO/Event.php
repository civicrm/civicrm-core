<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Event_BAO_Event extends CRM_Event_DAO_Event {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Event_DAO_Event
   */
  public static function retrieve(&$params, &$defaults) {
    $event = new CRM_Event_DAO_Event();
    $event->copyValues($params);
    if ($event->find(TRUE)) {
      CRM_Core_DAO::storeValues($event, $defaults);
      return $event;
    }
    return NULL;
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
    return CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Event', $id, 'is_active', $is_active);
  }

  /**
   * Add the event.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   * @return CRM_Event_DAO_Event
   */
  public static function add(&$params) {
    CRM_Utils_System::flushCache();
    $financialTypeId = NULL;
    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Event', $params['id'], $params);
      if (empty($params['skipFinancialType'])) {
        $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['id'], 'financial_type_id');
      }
    }
    else {
      CRM_Utils_Hook::pre('create', 'Event', NULL, $params);
    }

    $event = new CRM_Event_DAO_Event();

    $event->copyValues($params);
    $result = $event->save();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Event', $event->id, $event);
    }
    else {
      CRM_Utils_Hook::post('create', 'Event', $event->id, $event);
    }
    if ($financialTypeId && !empty($params['financial_type_id']) && $financialTypeId != $params['financial_type_id']) {
      CRM_Price_BAO_PriceFieldValue::updateFinancialType($params['id'], 'civicrm_event', $params['financial_type_id']);
    }
    return $result;
  }

  /**
   * Create the event.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   * @return object
   */
  public static function create(&$params) {
    $transaction = new CRM_Core_Transaction();
    if (empty($params['is_template'])) {
      $params['is_template'] = 0;
    }
    // check if new event, if so set the created_id (if not set)
    // and always set created_date to now
    if (empty($params['id'])) {
      if (empty($params['created_id'])) {
        $session = CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }
      $params['created_date'] = date('YmdHis');
    }

    $event = self::add($params);
    CRM_Price_BAO_PriceSet::setPriceSets($params, $event, 'event');
    if (is_a($event, 'CRM_Core_Error')) {
      CRM_Core_DAO::transaction('ROLLBACK');
      return $event;
    }

    $contactId = CRM_Core_Session::getLoggedInContactID();
    if (!$contactId) {
      $contactId = CRM_Utils_Array::value('contact_id', $params);
    }

    // Log the information on successful add/edit of Event
    $logParams = array(
      'entity_table' => 'civicrm_event',
      'entity_id' => $event->id,
      'modified_id' => $contactId,
      'modified_date' => date('Ymd'),
    );

    CRM_Core_BAO_Log::add($logParams);

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_event', $event->id);
    }

    $transaction->commit();

    return $event;
  }

  /**
   * Delete the event.
   *
   * @param int $id
   *   Event id.
   *
   * @return mixed|null
   */
  public static function del($id) {
    if (!$id) {
      return NULL;
    }

    CRM_Utils_Hook::pre('delete', 'Event', $id, CRM_Core_DAO::$_nullArray);

    $extends = array('event');
    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
    foreach ($groupTree as $values) {
      $query = "DELETE FROM %1 WHERE entity_id = %2";
      CRM_Core_DAO::executeQuery($query, array(
        1 => array($values['table_name'], 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES),
        2 => array($id, 'Integer'),
      ));
    }

    // Clean up references to profiles used by the event (CRM-20935)
    $ufJoinParams = array(
      'module' => 'CiviEvent',
      'entity_table' => 'civicrm_event',
      'entity_id' => $id,
    );
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);
    $ufJoinParams = array(
      'module' => 'CiviEvent_Additional',
      'entity_table' => 'civicrm_event',
      'entity_id' => $id,
    );
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    // price set cleanup, CRM-5527
    CRM_Price_BAO_PriceSet::removeFrom('civicrm_event', $id);

    $event = new CRM_Event_DAO_Event();
    $event->id = $id;

    if ($event->find(TRUE)) {
      $locBlockId = $event->loc_block_id;
      $result = $event->delete();

      if (!is_null($locBlockId)) {
        self::deleteEventLocBlock($locBlockId, $id);
      }

      CRM_Utils_Hook::post('delete', 'Event', $id, $event);
      return $result;
    }

    return NULL;
  }

  /**
   * Delete the location block associated with an event.
   *
   * Function checks that it is not being used by any other event.
   *
   * @param int $locBlockId
   *   Location block id to be deleted.
   * @param int $eventId
   *   Event with which loc block is associated.
   */
  public static function deleteEventLocBlock($locBlockId, $eventId = NULL) {
    $query = "SELECT count(ce.id) FROM civicrm_event ce WHERE ce.loc_block_id = $locBlockId";

    if ($eventId) {
      $query .= " AND ce.id != $eventId;";
    }

    $locCount = CRM_Core_DAO::singleValueQuery($query);

    if ($locCount == 0) {
      CRM_Core_BAO_Location::deleteLocBlock($locBlockId);
    }
  }

  /**
   * Get current/future Events.
   *
   * @param int $all
   *   0 returns current and future events.
   *                                  1 if events all are required
   *                                  2 returns events since 3 months ago
   * @param int|array $id single int event id or array of multiple event ids to return
   * @param bool $isActive
   *   true if you need only active events.
   * @param bool $checkPermission
   *   true if you need to check permission else false.
   * @param bool $titleOnly
   *   true if you need only title not appended with start date
   *
   * @return array
   */
  public static function getEvents(
    $all = 0,
    $id = NULL,
    $isActive = TRUE,
    $checkPermission = TRUE,
    $titleOnly = FALSE
  ) {
    $query = "
SELECT `id`, `title`, `start_date`
FROM   `civicrm_event`
WHERE  ( civicrm_event.is_template IS NULL OR civicrm_event.is_template = 0 )";

    if (!empty($id)) {
      $op = is_array($id) ? 'IN' : '=';
      $where = CRM_Contact_BAO_Query::buildClause('id', $op, $id);
      $query .= " AND {$where}";
    }
    elseif ($all == 0) {
      // find only events ending in the future
      $endDate = date('YmdHis');
      $query .= "
        AND ( `end_date` >= {$endDate} OR
          (
            ( end_date IS NULL OR end_date = '' ) AND start_date >= {$endDate}
          )
        )";
    }
    elseif ($all == 2) {
      // find only events starting in the last 3 months
      $startDate = date('YmdHis', strtotime('3 months ago'));
      $query .= " AND ( `start_date` >= {$startDate} OR start_date IS NULL )";
    }
    if ($isActive) {
      $query .= " AND civicrm_event.is_active = 1";
    }

    $query .= " ORDER BY title asc";
    $events = array();

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if ((!$checkPermission ||
          CRM_Event_BAO_Event::checkPermission($dao->id)
        ) &&
        $dao->title
      ) {
        $events[$dao->id] = $dao->title;
        if (!$titleOnly) {
          $events[$dao->id] .= ' - ' . CRM_Utils_Date::customFormat($dao->start_date);
        }
      }
    }

    return $events;
  }

  /**
   * Get events Summary.
   *
   * @return array
   *   Array of event summary values
   */
  public static function getEventSummary() {
    $eventSummary = $eventIds = array();
    $config = CRM_Core_Config::singleton();

    // get permission and include them here
    // does not scale, but rearranging code for now
    // FIXME in a future release
    $permissions = CRM_Event_BAO_Event::checkPermission();
    $validEventIDs = '';
    if (empty($permissions[CRM_Core_Permission::VIEW])) {
      $eventSummary['total_events'] = 0;
      return $eventSummary;
    }
    else {
      $validEventIDs = " AND civicrm_event.id IN ( " . implode(',', array_values($permissions[CRM_Core_Permission::VIEW])) . " ) ";
    }

    // We're fetching recent and upcoming events (where start date is 7 days ago OR later)
    $query = "
SELECT     count(id) as total_events
FROM       civicrm_event
WHERE      civicrm_event.is_active = 1 AND
           ( civicrm_event.is_template IS NULL OR civicrm_event.is_template = 0) AND
           civicrm_event.start_date >= DATE_SUB( NOW(), INTERVAL 7 day )
           $validEventIDs";

    $dao = CRM_Core_DAO::executeQuery($query);

    if ($dao->fetch()) {
      $eventSummary['total_events'] = $dao->total_events;
    }

    if (empty($eventSummary) ||
      $dao->total_events == 0
    ) {
      return $eventSummary;
    }

    //get the participant status type values.
    $cpstObject = new CRM_Event_DAO_ParticipantStatusType();
    $cpst = $cpstObject->getTableName();
    $query = "SELECT id, name, label, class FROM $cpst";
    $status = CRM_Core_DAO::executeQuery($query);
    $statusValues = array();
    while ($status->fetch()) {
      $statusValues[$status->id]['id'] = $status->id;
      $statusValues[$status->id]['name'] = $status->name;
      $statusValues[$status->id]['label'] = $status->label;
      $statusValues[$status->id]['class'] = $status->class;
    }

    // Get the Id of Option Group for Event Types
    $optionGroupDAO = new CRM_Core_DAO_OptionGroup();
    $optionGroupDAO->name = 'event_type';
    $optionGroupId = NULL;
    if ($optionGroupDAO->find(TRUE)) {
      $optionGroupId = $optionGroupDAO->id;
    }
    // Get the event summary display preferences
    $show_max_events = Civi::settings()->get('show_events');
    // show all events if show_events is set to a negative value
    if (isset($show_max_events) && $show_max_events >= 0) {
      $event_summary_limit = "LIMIT      0, $show_max_events";
    }
    else {
      $event_summary_limit = "";
    }

    $query = "
SELECT     civicrm_event.id as id, civicrm_event.title as event_title, civicrm_event.is_public as is_public,
           civicrm_event.max_participants as max_participants, civicrm_event.start_date as start_date,
           civicrm_event.end_date as end_date, civicrm_event.is_online_registration, civicrm_event.is_monetary, civicrm_event.is_show_location,civicrm_event.is_map as is_map, civicrm_option_value.label as event_type, civicrm_tell_friend.is_active as is_friend_active,
           civicrm_event.slot_label_id,
           civicrm_event.summary as summary,
           civicrm_pcp_block.id as is_pcp_enabled,
           civicrm_recurring_entity.parent_id as is_repeating_event
FROM       civicrm_event
LEFT JOIN  civicrm_option_value ON (
           civicrm_event.event_type_id = civicrm_option_value.value AND
           civicrm_option_value.option_group_id = %1 )
LEFT JOIN  civicrm_tell_friend ON ( civicrm_tell_friend.entity_id = civicrm_event.id  AND civicrm_tell_friend.entity_table = 'civicrm_event' )
LEFT JOIN  civicrm_pcp_block ON ( civicrm_pcp_block.entity_id = civicrm_event.id AND civicrm_pcp_block.entity_table = 'civicrm_event')
LEFT JOIN  civicrm_recurring_entity ON ( civicrm_event.id = civicrm_recurring_entity.entity_id AND civicrm_recurring_entity.entity_table = 'civicrm_event' )
WHERE      civicrm_event.is_active = 1 AND
           ( civicrm_event.is_template IS NULL OR civicrm_event.is_template = 0) AND
           civicrm_event.start_date >= DATE_SUB( NOW(), INTERVAL 7 day )
           $validEventIDs
ORDER BY   civicrm_event.start_date ASC
$event_summary_limit
";
    $eventParticipant = array();

    $properties = array(
      'id' => 'id',
      'eventTitle' => 'event_title',
      'isPublic' => 'is_public',
      'maxParticipants' => 'max_participants',
      'startDate' => 'start_date',
      'endDate' => 'end_date',
      'eventType' => 'event_type',
      'isMap' => 'is_map',
      'participants' => 'participants',
      'notCountedDueToRole' => 'notCountedDueToRole',
      'notCountedDueToStatus' => 'notCountedDueToStatus',
      'notCountedParticipants' => 'notCountedParticipants',
    );

    $params = array(1 => array($optionGroupId, 'Integer'));
    $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID,
    )));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      foreach ($properties as $property => $name) {
        $set = NULL;
        switch ($name) {
          case 'is_public':
            if ($dao->$name) {
              $set = 'Yes';
            }
            else {
              $set = 'No';
            }
            $eventSummary['events'][$dao->id][$property] = $set;
            break;

          case 'is_map':
            if ($dao->$name && $config->mapAPIKey) {
              $values = array();
              $ids = array();
              $params = array('entity_id' => $dao->id, 'entity_table' => 'civicrm_event');
              $values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);
              if (is_numeric(CRM_Utils_Array::value('geo_code_1', $values['location']['address'][1])) ||
                (
                  !empty($values['location']['address'][1]['city']) &&
                  !empty($values['location']['address'][1]['state_province_id'])
                )
              ) {
                $set = CRM_Utils_System::url('civicrm/contact/map/event', "reset=1&eid={$dao->id}");
              }
            }

            $eventSummary['events'][$dao->id][$property] = $set;
            if (is_array($permissions[CRM_Core_Permission::EDIT])
              && in_array($dao->id, $permissions[CRM_Core_Permission::EDIT])) {
              $eventSummary['events'][$dao->id]['configure'] = CRM_Utils_System::url('civicrm/admin/event', "action=update&id=$dao->id&reset=1");
            }
            break;

          case 'end_date':
          case 'start_date':
            $eventSummary['events'][$dao->id][$property] = CRM_Utils_Date::customFormat($dao->$name,
              NULL, array('d')
            );
            break;

          case 'participants':
          case 'notCountedDueToRole':
          case 'notCountedDueToStatus':
          case 'notCountedParticipants':
            $set = NULL;
            $propertyCnt = 0;
            if ($name == 'participants') {
              $propertyCnt = self::getParticipantCount($dao->id);
              if ($propertyCnt) {
                $set = CRM_Utils_System::url('civicrm/event/search',
                  "reset=1&force=1&event=$dao->id&status=true&role=true"
                );
              }
            }
            elseif ($name == 'notCountedParticipants') {
              $propertyCnt = self::getParticipantCount($dao->id, TRUE, FALSE, TRUE, FALSE);
              if ($propertyCnt) {
                // FIXME : selector fail to search w/ OR operator.
                // $set = CRM_Utils_System::url( 'civicrm/event/search',
                // "reset=1&force=1&event=$dao->id&status=false&role=false" );
              }
            }
            elseif ($name == 'notCountedDueToStatus') {
              $propertyCnt = self::getParticipantCount($dao->id, TRUE, FALSE, FALSE, FALSE);
              if ($propertyCnt) {
                $set = CRM_Utils_System::url('civicrm/event/search',
                  "reset=1&force=1&event=$dao->id&status=false"
                );
              }
            }
            else {
              $propertyCnt = self::getParticipantCount($dao->id, FALSE, FALSE, TRUE, FALSE);
              if ($propertyCnt) {
                $set = CRM_Utils_System::url('civicrm/event/search',
                  "reset=1&force=1&event=$dao->id&role=false"
                );
              }
            }

            $eventSummary['events'][$dao->id][$property] = $propertyCnt;
            $eventSummary['events'][$dao->id][$name . '_url'] = $set;
            break;

          default:
            $eventSummary['events'][$dao->id][$property] = $dao->$name;
            break;
        }
      }

      // prepare the area for per-status participant counts
      $statusClasses = array('Positive', 'Pending', 'Waiting', 'Negative');
      $eventSummary['events'][$dao->id]['statuses'] = array_fill_keys($statusClasses, array());

      $eventSummary['events'][$dao->id]['friend'] = $dao->is_friend_active;
      $eventSummary['events'][$dao->id]['is_monetary'] = $dao->is_monetary;
      $eventSummary['events'][$dao->id]['is_online_registration'] = $dao->is_online_registration;
      $eventSummary['events'][$dao->id]['is_show_location'] = $dao->is_show_location;
      $eventSummary['events'][$dao->id]['is_subevent'] = $dao->slot_label_id;
      $eventSummary['events'][$dao->id]['is_pcp_enabled'] = $dao->is_pcp_enabled;
      $eventSummary['events'][$dao->id]['reminder'] = CRM_Core_BAO_ActionSchedule::isConfigured($dao->id, $mapping->getId());
      $eventSummary['events'][$dao->id]['is_repeating_event'] = $dao->is_repeating_event;

      $statusTypes = CRM_Event_PseudoConstant::participantStatus();
      foreach ($statusValues as $statusId => $statusValue) {
        if (!array_key_exists($statusId, $statusTypes)) {
          continue;
        }
        $class = $statusValue['class'];
        $statusCount = self::eventTotalSeats($dao->id, "( participant.status_id = {$statusId} )");
        if ($statusCount) {
          $urlString = "reset=1&force=1&event={$dao->id}&status=$statusId";
          $statusInfo = array(
            'url' => CRM_Utils_System::url('civicrm/event/search', $urlString),
            'name' => $statusValue['name'],
            'label' => $statusValue['label'],
            'count' => $statusCount,
          );
          $eventSummary['events'][$dao->id]['statuses'][$class][] = $statusInfo;
        }
      }
    }

    $countedRoles = CRM_Event_PseudoConstant::participantRole(NULL, 'filter = 1');
    $nonCountedRoles = CRM_Event_PseudoConstant::participantRole(NULL, '( filter = 0 OR filter IS NULL )');
    $countedStatus = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
    $nonCountedStatus = CRM_Event_PseudoConstant::participantStatus(NULL, '( is_counted = 0 OR is_counted IS NULL )');

    $countedStatusANDRoles = array_merge($countedStatus, $countedRoles);
    $nonCountedStatusANDRoles = array_merge($nonCountedStatus, $nonCountedRoles);

    $eventSummary['nonCountedRoles'] = implode('/', array_values($nonCountedRoles));
    $eventSummary['nonCountedStatus'] = implode('/', array_values($nonCountedStatus));
    $eventSummary['countedStatusANDRoles'] = implode('/', array_values($countedStatusANDRoles));
    $eventSummary['nonCountedStatusANDRoles'] = implode('/', array_values($nonCountedStatusANDRoles));

    return $eventSummary;
  }

  /**
   * Get participant count.
   *
   * @param int $eventId
   * @param bool $considerStatus consider status for participant count.
   *   Consider status for participant count.
   * @param bool $status counted participant.
   *   Consider counted participant.
   * @param bool $considerRole consider role for participant count.
   *   Consider role for participant count.
   * @param bool $role consider counted( is filter role) participant.
   *   Consider counted( is filter role) participant.
   *
   * @return array
   *   array with count of participants for each event based on status/role
   */
  public static function getParticipantCount(
    $eventId,
    $considerStatus = TRUE,
    $status = TRUE,
    $considerRole = TRUE,
    $role = TRUE
  ) {

    // consider both role and status for counted participants, CRM-4924.
    $operator = " AND ";
    // not counted participant.
    if ($considerStatus && $considerRole && !$status && !$role) {
      $operator = " OR ";
    }
    $clause = array();
    if ($considerStatus) {
      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
      $statusClause = 'NOT IN';
      if ($status) {
        $statusClause = 'IN';
      }
      $status = implode(',', array_keys($statusTypes));
      if (empty($status)) {
        $status = 0;
      }
      $clause[] = "participant.status_id {$statusClause} ( {$status} ) ";
    }

    if ($considerRole) {
      $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, 'filter = 1');
      $roleClause = 'NOT IN';
      if ($role) {
        $roleClause = 'IN';
      }

      if (!empty($roleTypes)) {
        $escapedRoles = array();
        foreach (array_keys($roleTypes) as $roleType) {
          $escapedRoles[] = CRM_Utils_Type::escape($roleType, 'String');
        }

        $clause[] = "participant.role_id {$roleClause} ( '" . implode("', '", $escapedRoles) . "' ) ";
      }
    }

    $sqlClause = '';
    if (!empty($clause)) {
      $sqlClause = ' ( ' . implode($operator, $clause) . ' )';
    }

    return self::eventTotalSeats($eventId, $sqlClause);
  }

  /**
   * Get the information to map a event.
   *
   * @param int $id
   *   For which we want map info.
   *
   * @return null|string
   *   title of the event
   */
  public static function &getMapInfo(&$id) {

    $sql = "
SELECT
   civicrm_event.id AS event_id,
   civicrm_event.title AS display_name,
   civicrm_address.street_address AS street_address,
   civicrm_address.city AS city,
   civicrm_address.postal_code AS postal_code,
   civicrm_address.postal_code_suffix AS postal_code_suffix,
   civicrm_address.geo_code_1 AS latitude,
   civicrm_address.geo_code_2 AS longitude,
   civicrm_state_province.abbreviation AS state,
   civicrm_country.name AS country,
   civicrm_location_type.name AS location_type
FROM
   civicrm_event
   LEFT JOIN civicrm_loc_block ON ( civicrm_event.loc_block_id = civicrm_loc_block.id )
   LEFT JOIN civicrm_address ON ( civicrm_loc_block.address_id = civicrm_address.id )
   LEFT JOIN civicrm_state_province ON ( civicrm_address.state_province_id = civicrm_state_province.id )
   LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
   LEFT JOIN civicrm_location_type ON ( civicrm_location_type.id = civicrm_address.location_type_id )
WHERE civicrm_address.geo_code_1 IS NOT NULL
  AND civicrm_address.geo_code_2 IS NOT NULL
  AND civicrm_event.id = " . CRM_Utils_Type::escape($id, 'Integer');

    $dao = new CRM_Core_DAO();
    $dao->query($sql);

    $locations = array();

    $config = CRM_Core_Config::singleton();

    while ($dao->fetch()) {

      $location = array();
      $location['displayName'] = addslashes($dao->display_name);
      $location['lat'] = $dao->latitude;
      $location['marker_class'] = 'Event';
      $location['lng'] = $dao->longitude;

      $params = array('entity_id' => $id, 'entity_table' => 'civicrm_event');
      $addressValues = CRM_Core_BAO_Location::getValues($params, TRUE);
      $location['address'] = str_replace(array(
        "\r",
        "\n",
      ), '', addslashes(nl2br($addressValues['address'][1]['display_text'])));

      $location['url'] = CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $dao->event_id);
      $location['location_type'] = $dao->location_type;
      $eventImage = '<img src="' . $config->resourceBase . 'i/contact_org.gif" alt="Organization " height="20" width="15" />';
      $location['image'] = $eventImage;
      $location['displayAddress'] = str_replace('<br />', ', ', $location['address']);
      $locations[] = $location;
    }
    return $locations;
  }

  /**
   * Get the complete information for one or more events.
   *
   * @param date $start
   *   Get events with start date >= this date.
   * @param int $type Get events on the a specific event type (by event_type_id).
   *   Get events on the a specific event type (by event_type_id).
   * @param int $eventId Return a single event - by event id.
   *   Return a single event - by event id.
   * @param date $end
   *   Also get events with end date >= this date.
   * @param bool $onlyPublic Include public events only, default TRUE.
   *   Include public events only, default TRUE.
   *
   * @return array
   *   array of all the events that are searched
   */
  public static function &getCompleteInfo(
    $start = NULL,
    $type = NULL,
    $eventId = NULL,
    $end = NULL,
    $onlyPublic = TRUE
  ) {
    $publicCondition = NULL;
    if ($onlyPublic) {
      $publicCondition = "  AND civicrm_event.is_public = 1";
    }

    $dateCondition = '';
    // if start and end date are NOT passed, return all events with start_date OR end_date >= today CRM-5133
    if ($start) {
      // get events with start_date >= requested start
      $startDate = CRM_Utils_Type::escape($start, 'Date');
      $dateCondition .= " AND ( civicrm_event.start_date >= {$startDate} )";
    }

    if ($end) {
      // also get events with end_date <= requested end
      $endDate = CRM_Utils_Type::escape($end, 'Date');
      $dateCondition .= " AND ( civicrm_event.end_date <= '{$endDate}' ) ";
    }

    // CRM-9421 and CRM-8620 Default mode for ical/rss feeds. No start or end filter passed.
    // Need to exclude old events with only start date
    // and not exclude events in progress (start <= today and end >= today). DGG
    if (empty($start) && empty($end)) {
      // get events with end date >= today, not sure of this logic
      // but keeping this for backward compatibility as per issue CRM-5133
      $today = date("Y-m-d G:i:s");
      $dateCondition .= " AND ( civicrm_event.end_date >= '{$today}' OR civicrm_event.start_date >= '{$today}' ) ";
    }

    if ($type) {
      $typeCondition = " AND civicrm_event.event_type_id = " . CRM_Utils_Type::escape($type, 'Integer');
    }

    // Get the Id of Option Group for Event Types
    $optionGroupDAO = new CRM_Core_DAO_OptionGroup();
    $optionGroupDAO->name = 'event_type';
    $optionGroupId = NULL;
    if ($optionGroupDAO->find(TRUE)) {
      $optionGroupId = $optionGroupDAO->id;
    }

    $query = "
SELECT
  civicrm_event.id as event_id,
  civicrm_email.email as email,
  civicrm_event.title as title,
  civicrm_event.summary as summary,
  civicrm_event.start_date as start,
  civicrm_event.end_date as end,
  civicrm_event.description as description,
  civicrm_event.is_show_location as is_show_location,
  civicrm_event.is_online_registration as is_online_registration,
  civicrm_event.registration_link_text as registration_link_text,
  civicrm_event.registration_start_date as registration_start_date,
  civicrm_event.registration_end_date as registration_end_date,
  civicrm_option_value.label as event_type,
  civicrm_address.name as address_name,
  civicrm_address.street_address as street_address,
  civicrm_address.supplemental_address_1 as supplemental_address_1,
  civicrm_address.supplemental_address_2 as supplemental_address_2,
  civicrm_address.supplemental_address_3 as supplemental_address_3,
  civicrm_address.city as city,
  civicrm_address.postal_code as postal_code,
  civicrm_address.postal_code_suffix as postal_code_suffix,
  civicrm_state_province.abbreviation as state,
  civicrm_country.name AS country
FROM civicrm_event
LEFT JOIN civicrm_loc_block ON civicrm_event.loc_block_id = civicrm_loc_block.id
LEFT JOIN civicrm_address ON civicrm_loc_block.address_id = civicrm_address.id
LEFT JOIN civicrm_state_province ON civicrm_address.state_province_id = civicrm_state_province.id
LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
LEFT JOIN civicrm_email ON civicrm_loc_block.email_id = civicrm_email.id
LEFT JOIN civicrm_option_value ON (
                                    civicrm_event.event_type_id = civicrm_option_value.value AND
                                    civicrm_option_value.option_group_id = %1 )
WHERE civicrm_event.is_active = 1
      AND (is_template = 0 OR is_template IS NULL)
      {$publicCondition}
      {$dateCondition}";

    if (isset($typeCondition)) {
      $query .= $typeCondition;
    }

    if (isset($eventId)) {
      $query .= " AND civicrm_event.id =$eventId ";
    }
    $query .= " ORDER BY   civicrm_event.start_date ASC";

    $params = array(1 => array($optionGroupId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $all = array();
    $config = CRM_Core_Config::singleton();

    $baseURL = parse_url($config->userFrameworkBaseURL);
    $url = "@" . $baseURL['host'];
    if (!empty($baseURL['path'])) {
      $url .= substr($baseURL['path'], 0, -1);
    }

    // check 'view event info' permission
    //@todo - per CRM-14626 we have resolved that 'view event info' means 'view ALL event info'
    // and passing in the specific permission here will short-circuit the evaluation of permission to
    // see specific events (doesn't seem relevant to this call
    // however, since this function is accessed only by a convoluted call from a joomla block function
    // it seems safer not to touch here. Suggestion is that CRM_Core_Permission::check(array or relevant permissions) would
    // be clearer & safer here
    $permissions = CRM_Core_Permission::event(CRM_Core_Permission::VIEW);

    // check if we're in shopping cart mode for events
    $enable_cart = Civi::settings()->get('enable_cart');
    if ($enable_cart) {
    }
    while ($dao->fetch()) {
      if (!empty($permissions) && in_array($dao->event_id, $permissions)) {
        $info = array();
        $info['uid'] = "CiviCRM_EventID_{$dao->event_id}_" . md5($config->userFrameworkBaseURL) . $url;

        $info['title'] = $dao->title;
        $info['event_id'] = $dao->event_id;
        $info['summary'] = $dao->summary;
        $info['description'] = $dao->description;
        $info['start_date'] = $dao->start;
        $info['end_date'] = $dao->end;
        $info['contact_email'] = $dao->email;
        $info['event_type'] = $dao->event_type;
        $info['is_show_location'] = $dao->is_show_location;
        $info['is_online_registration'] = $dao->is_online_registration;
        $info['registration_link_text'] = $dao->registration_link_text;
        $info['registration_start_date'] = $dao->registration_start_date;
        $info['registration_end_date'] = $dao->registration_end_date;

        $address = '';

        $addrFields = array(
          'address_name' => $dao->address_name,
          'street_address' => $dao->street_address,
          'supplemental_address_1' => $dao->supplemental_address_1,
          'supplemental_address_2' => $dao->supplemental_address_2,
          'supplemental_address_3' => $dao->supplemental_address_3,
          'city' => $dao->city,
          'state_province' => $dao->state,
          'postal_code' => $dao->postal_code,
          'postal_code_suffix' => $dao->postal_code_suffix,
          'country' => $dao->country,
          'county' => NULL,
        );

        CRM_Utils_String::append($address, ', ',
          CRM_Utils_Address::format($addrFields)
        );
        $info['location'] = $address;
        $info['url'] = CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $dao->event_id, TRUE, NULL, FALSE);

        if ($enable_cart) {
          $reg = CRM_Event_Cart_BAO_EventInCart::get_registration_link($dao->event_id);
          $info['registration_link'] = CRM_Utils_System::url($reg['path'], $reg['query'], TRUE);
          $info['registration_link_text'] = $reg['label'];
        }

        $all[] = $info;
      }
    }

    return $all;
  }

  /**
   * Make a copy of a Event.
   *
   * Include all the fields in the event Wizard.
   *
   * @param int $id
   *   The event id to copy.
   *        boolean $afterCreate call to copy after the create function
   * @param null $newEvent
   * @param bool $afterCreate
   *
   * @return CRM_Event_DAO_Event
   */
  public static function copy($id, $newEvent = NULL, $afterCreate = FALSE) {

    $eventValues = array();

    //get the require event values.
    $eventParams = array('id' => $id);
    $returnProperties = array(
      'loc_block_id',
      'is_show_location',
      'default_fee_id',
      'default_discount_fee_id',
      'is_template',
    );

    CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $eventParams, $eventValues, $returnProperties);

    // since the location is sharable, lets use the same loc_block_id.
    $locBlockId = CRM_Utils_Array::value('loc_block_id', $eventValues);

    $fieldsFix = ($afterCreate) ? array() : array('prefix' => array('title' => ts('Copy of') . ' '));
    if (empty($eventValues['is_show_location'])) {
      $fieldsFix['prefix']['is_show_location'] = 0;
    }

    if ($newEvent && is_a($newEvent, 'CRM_Event_DAO_Event')) {
      $copyEvent = $newEvent;
    }

    if (!isset($copyEvent)) {
      $copyEvent = &CRM_Core_DAO::copyGeneric('CRM_Event_DAO_Event',
        array('id' => $id),
        array(
          'loc_block_id' =>
          ($locBlockId) ? $locBlockId : NULL,
        ),
        $fieldsFix
      );
    }
    CRM_Price_BAO_PriceSet::copyPriceSet('civicrm_event', $id, $copyEvent->id);
    $copyUF = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFJoin',
      array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ),
      array('entity_id' => $copyEvent->id)
    );

    $copyTellFriend = &CRM_Core_DAO::copyGeneric('CRM_Friend_DAO_Friend',
      array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ),
      array('entity_id' => $copyEvent->id)
    );

    $copyPCP = &CRM_Core_DAO::copyGeneric('CRM_PCP_DAO_PCPBlock',
      array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ),
      array('entity_id' => $copyEvent->id),
      array('replace' => array('target_entity_id' => $copyEvent->id))
    );

    $oldMapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => ($eventValues['is_template'] ? CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID : CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID),
    )));
    $copyMapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => ($copyEvent->is_template == 1 ? CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID : CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID),
    )));
    $copyReminder = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_ActionSchedule',
      array('entity_value' => $id, 'mapping_id' => $oldMapping->getId()),
      array('entity_value' => $copyEvent->id, 'mapping_id' => $copyMapping->getId())
    );

    if (!$afterCreate) {
      // CRM-19302
      self::copyCustomFields($id, $copyEvent->id);
    }

    $copyEvent->save();

    CRM_Utils_System::flushCache();
    if (!$afterCreate) {
      CRM_Utils_Hook::copy('Event', $copyEvent);
    }
    return $copyEvent;
  }

  /**
   * Method that copies custom fields values from an old event to a new one. Fixes bug CRM-19302,
   * where if a custom field of File type was present, left both events using the same file,
   * breaking download URL's for the old event.
   *
   * @param int $oldEventID
   * @param int $newCopyID
   */
  public static function copyCustomFields($oldEventID, $newCopyID) {
    // Obtain custom values for old event
    $customParams = $htmlType = array();
    $customValues = CRM_Core_BAO_CustomValueTable::getEntityValues($oldEventID, 'Event');

    // If custom values present, we copy them
    if (!empty($customValues)) {
      // Get Field ID's and identify File type attributes, to handle file copying.
      $fieldIds = implode(', ', array_keys($customValues));
      $sql = "SELECT id FROM civicrm_custom_field WHERE html_type = 'File' AND id IN ( {$fieldIds} )";
      $result = CRM_Core_DAO::executeQuery($sql);

      // Build array of File type fields
      while ($result->fetch()) {
        $htmlType[] = $result->id;
      }

      // Build params array of custom values
      foreach ($customValues as $field => $value) {
        if ($value !== NULL) {
          // Handle File type attributes
          if (in_array($field, $htmlType)) {
            $fileValues = CRM_Core_BAO_File::path($value, $oldEventID);
            $customParams["custom_{$field}_-1"] = array(
              'name' => CRM_Utils_File::duplicate($fileValues[0]),
              'type' => $fileValues[1],
            );
          }
          // Handle other types
          else {
            $customParams["custom_{$field}_-1"] = $value;
          }
        }
      }

      // Save Custom Fields for new Event
      CRM_Core_BAO_CustomValueTable::postProcess($customParams, 'civicrm_event', $newCopyID, 'Event');
    }

    // copy activity attachments ( if any )
    CRM_Core_BAO_File::copyEntityFile('civicrm_event', $oldEventID, 'civicrm_event', $newCopyID);
  }

  /**
   * This is sometimes called in a loop (during event search).
   *
   * We cache the values to prevent repeated calls to the db.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function isMonetary($id) {
    static $isMonetary = array();
    if (!array_key_exists($id, $isMonetary)) {
      $isMonetary[$id] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
        $id,
        'is_monetary'
      );
    }
    return $isMonetary[$id];
  }

  /**
   * This is sometimes called in a loop (during event search).
   *
   * We cache the values to prevent repeated calls to the db.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function usesPriceSet($id) {
    static $usesPriceSet = array();
    if (!array_key_exists($id, $usesPriceSet)) {
      $usesPriceSet[$id] = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $id);
    }
    return $usesPriceSet[$id];
  }

  /**
   * Send e-mails.
   *
   * @param int $contactID
   * @param array $values
   * @param int $participantId
   * @param bool $isTest
   * @param bool $returnMessageText
   * @return array|null
   */
  public static function sendMail($contactID, &$values, $participantId, $isTest = FALSE, $returnMessageText = FALSE) {

    $template = CRM_Core_Smarty::singleton();
    $gIds = array(
      'custom_pre_id' => $values['custom_pre_id'],
      'custom_post_id' => $values['custom_post_id'],
    );

    //get the params submitted by participant.
    $participantParams = CRM_Utils_Array::value($participantId, $values['params'], array());

    if (!$returnMessageText) {
      //send notification email if field values are set (CRM-1941)
      foreach ($gIds as $key => $gIdValues) {
        if ($gIdValues) {
          if (!is_array($gIdValues)) {
            $gIdValues = array($gIdValues);
          }

          foreach ($gIdValues as $gId) {
            $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify');
            if ($email) {
              //get values of corresponding profile fields for notification
              list($profileValues) = self::buildCustomDisplay($gId,
                NULL,
                $contactID,
                $template,
                $participantId,
                $isTest,
                TRUE,
                $participantParams
              );
              list($profileValues) = $profileValues;
              $val = array(
                'id' => $gId,
                'values' => $profileValues,
                'email' => $email,
              );
              CRM_Core_BAO_UFGroup::commonSendMail($contactID, $val);
            }
          }
        }
      }
    }

    if ($values['event']['is_email_confirm'] || $returnMessageText) {
      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);

      //send email only when email is present
      if (isset($email) || $returnMessageText) {
        $preProfileID = CRM_Utils_Array::value('custom_pre_id', $values);
        $postProfileID = CRM_Utils_Array::value('custom_post_id', $values);

        if (!empty($values['params']['additionalParticipant'])) {
          $preProfileID = CRM_Utils_Array::value('additional_custom_pre_id', $values, $preProfileID);
          $postProfileID = CRM_Utils_Array::value('additional_custom_post_id', $values, $postProfileID);
        }

        self::buildCustomDisplay($preProfileID,
          'customPre',
          $contactID,
          $template,
          $participantId,
          $isTest,
          NULL,
          $participantParams
        );

        self::buildCustomDisplay($postProfileID,
          'customPost',
          $contactID,
          $template,
          $participantId,
          $isTest,
          NULL,
          $participantParams
        );

        $sessions = CRM_Event_Cart_BAO_Conference::get_participant_sessions($participantId);

        $tplParams = array_merge($values, $participantParams, array(
          'email' => $email,
          'confirm_email_text' => CRM_Utils_Array::value('confirm_email_text', $values['event']),
          'isShowLocation' => CRM_Utils_Array::value('is_show_location', $values['event']),
          // The concept of contributeMode is deprecated.
          'contributeMode' => CRM_Utils_Array::value('contributeMode', $template->_tpl_vars),
          'participantID' => $participantId,
          'conference_sessions' => $sessions,
          'credit_card_number' =>
            CRM_Utils_System::mungeCreditCard(
              CRM_Utils_Array::value('credit_card_number', $participantParams)),
          'credit_card_exp_date' =>
            CRM_Utils_Date::mysqlToIso(
              CRM_Utils_Date::format(
                CRM_Utils_Array::value('credit_card_exp_date', $participantParams))),
        ));

        // CRM-13890 : NOTE wait list condition need to be given so that
        // wait list message is shown properly in email i.e. WRT online event registration template
        if (empty($tplParams['participant_status']) && empty($values['params']['isOnWaitlist'])) {
          $statusId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantId, 'status_id', 'id', TRUE);
          $tplParams['participant_status'] = CRM_Event_PseudoConstant::participantStatus($statusId, NULL, 'label');
        }
        //CRM-15754 - if participant_status contains status ID
        elseif (!empty($tplParams['participant_status']) && CRM_Utils_Rule::integer($tplParams['participant_status'])) {
          $tplParams['participant_status'] = CRM_Event_PseudoConstant::participantStatus($tplParams['participant_status'], NULL, 'label');
        }

        $sendTemplateParams = array(
          'groupName' => 'msg_tpl_workflow_event',
          'valueName' => 'event_online_receipt',
          'contactId' => $contactID,
          'isTest' => $isTest,
          'tplParams' => $tplParams,
          'PDFFilename' => ts('confirmation') . '.pdf',
        );

        // address required during receipt processing (pdf and email receipt)
        if ($displayAddress = CRM_Utils_Array::value('address', $values)) {
          $sendTemplateParams['tplParams']['address'] = $displayAddress;
          // The concept of contributeMode is deprecated.
          $sendTemplateParams['tplParams']['contributeMode'] = NULL;
        }

        // set lineItem details
        if ($lineItem = CRM_Utils_Array::value('lineItem', $values)) {
          // check if additional participant, if so filter only to relevant ones
          // CRM-9902
          if (!empty($values['params']['additionalParticipant'])) {
            $ownLineItems = array();
            foreach ($lineItem as $liKey => $liValue) {
              $firstElement = array_pop($liValue);
              if ($firstElement['entity_id'] == $participantId) {
                $ownLineItems[0] = $lineItem[$liKey];
                break;
              }
            }
            if (!empty($ownLineItems)) {
              $sendTemplateParams['tplParams']['lineItem'] = $ownLineItems;
            }
          }
          else {
            $sendTemplateParams['tplParams']['lineItem'] = $lineItem;
          }
        }

        if ($returnMessageText) {
          list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
          return array(
            'subject' => $subject,
            'body' => $message,
            'to' => $displayName,
            'html' => $html,
          );
        }
        else {
          $sendTemplateParams['from'] = CRM_Utils_Array::value('confirm_from_name', $values['event']) . " <" . CRM_Utils_Array::value('confirm_from_email', $values['event']) . ">";
          $sendTemplateParams['toName'] = $displayName;
          $sendTemplateParams['toEmail'] = $email;
          $sendTemplateParams['autoSubmitted'] = TRUE;
          $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_confirm',
            $values['event']
          );
          $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_confirm',
            $values['event']
          );
          // append invoice pdf to email
          $template = CRM_Core_Smarty::singleton();
          $taxAmt = $template->get_template_vars('totalTaxAmount');
          $prefixValue = Civi::settings()->get('contribution_invoice_settings');
          $invoicing = CRM_Utils_Array::value('invoicing', $prefixValue);
          if (isset($invoicing) && isset($prefixValue['is_email_pdf']) && !empty($values['contributionId'])) {
            $sendTemplateParams['isEmailPdf'] = TRUE;
            $sendTemplateParams['contributionId'] = $values['contributionId'];
          }
          CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        }
      }
    }
  }

  /**
   * Add the custom fields OR array of participant's profile info.
   *
   * @param int $id
   * @param string $name
   * @param int $cid
   * @param string $template
   * @param int $participantId
   * @param bool $isTest
   * @param bool $isCustomProfile
   * @param array $participantParams
   *
   * @return array|null
   */
  public static function buildCustomDisplay(
    $id,
    $name,
    $cid,
    &$template,
    $participantId,
    $isTest,
    $isCustomProfile = FALSE,
    $participantParams = array()
  ) {
    if (!$id) {
      return array(NULL, NULL);
    }

    if (!is_array($id)) {
      $id = CRM_Utils_Type::escape($id, 'Positive');
      $profileIds = array($id);
    }
    else {
      $profileIds = $id;
    }

    $val = $groupTitles = NULL;
    foreach ($profileIds as $gid) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $values = array();
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW,
          NULL, NULL, FALSE, NULL,
          FALSE, NULL, CRM_Core_Permission::CREATE,
          'field_name', TRUE
        );

        //this condition is added, since same contact can have multiple event registrations..
        $params = array(array('participant_id', '=', $participantId, 0, 0));

        //add participant id
        $fields['participant_id'] = array(
          'name' => 'participant_id',
          'title' => ts('Participant ID'),
        );
        //check whether its a text drive
        if ($isTest) {
          $params[] = array('participant_test', '=', 1, 0, 0);
        }

        //display campaign on thankyou page.
        if (array_key_exists('participant_campaign_id', $fields)) {
          if ($participantId) {
            $campaignId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
              $participantId,
              'campaign_id'
            );
            $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
            $values[$fields['participant_campaign_id']['title']] = CRM_Utils_Array::value($campaignId,
              $campaigns
            );
          }
          unset($fields['participant_campaign_id']);
        }

        $groupTitle = NULL;
        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v['groupTitle'];
          }
          // suppress all file fields from display
          if (
            CRM_Utils_Array::value('data_type', $v, '') == 'File' ||
            CRM_Utils_Array::value('name', $v, '') == 'image_URL' ||
            CRM_Utils_Array::value('field_type', $v) == 'Formatting'
          ) {
            unset($fields[$k]);
          }
        }

        if ($groupTitle) {
          $groupTitles[] = $groupTitle;
        }
        //display profile groups those are subscribed by participant.
        if (($groups = CRM_Utils_Array::value('group', $participantParams)) &&
          is_array($groups)
        ) {
          $grpIds = array();
          foreach ($groups as $grpId => $isSelected) {
            if ($isSelected) {
              $grpIds[] = $grpId;
            }
          }
          if (!empty($grpIds)) {
            //get the group titles.
            $grpTitles = array();
            $query = 'SELECT title FROM civicrm_group where id IN ( ' . implode(',', $grpIds) . ' )';
            $grp = CRM_Core_DAO::executeQuery($query);
            while ($grp->fetch()) {
              $grpTitles[] = $grp->title;
            }
            if (!empty($grpTitles) &&
              CRM_Utils_Array::value('title', CRM_Utils_Array::value('group', $fields))
            ) {
              $values[$fields['group']['title']] = implode(', ', $grpTitles);
            }
            unset($fields['group']);
          }
        }

        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params);

        if (isset($fields['participant_status_id']['title']) &&
          isset($values[$fields['participant_status_id']['title']]) &&
          is_numeric($values[$fields['participant_status_id']['title']])
        ) {
          $status = array();
          $status = CRM_Event_PseudoConstant::participantStatus();
          $values[$fields['participant_status_id']['title']] = $status[$values[$fields['participant_status_id']['title']]];
        }

        if (isset($fields['participant_role_id']['title']) &&
          isset($values[$fields['participant_role_id']['title']]) &&
          is_numeric($values[$fields['participant_role_id']['title']])
        ) {
          $roles = array();
          $roles = CRM_Event_PseudoConstant::participantRole();
          $values[$fields['participant_role_id']['title']] = $roles[$values[$fields['participant_role_id']['title']]];
        }

        if (isset($fields['participant_register_date']['title']) &&
          isset($values[$fields['participant_register_date']['title']])
        ) {
          $values[$fields['participant_register_date']['title']] = CRM_Utils_Date::customFormat($values[$fields['participant_register_date']['title']]);
        }

        //handle fee_level for price set
        if (isset($fields['participant_fee_level']['title']) &&
          isset($values[$fields['participant_fee_level']['title']])
        ) {
          $feeLevel = explode(CRM_Core_DAO::VALUE_SEPARATOR,
            $values[$fields['participant_fee_level']['title']]
          );
          foreach ($feeLevel as $key => $value) {
            if (!$value) {
              unset($feeLevel[$key]);
            }
          }
          $values[$fields['participant_fee_level']['title']] = implode(',', $feeLevel);
        }

        unset($values[$fields['participant_id']['title']]);

        $val[] = $values;
      }
    }

    if (count($val)) {
      $template->assign($name, $val);
    }

    if (count($groupTitles)) {
      $template->assign($name . '_grouptitle', $groupTitles);
    }

    //return if we only require array of participant's info.
    if ($isCustomProfile) {
      if (count($val)) {
        return array($val, $groupTitles);
      }
      else {
        return NULL;
      }
    }
  }

  /**
   * Build the array for display the profile fields.
   *
   * @param array $params
   *   Key value.
   * @param int $gid
   *   Profile Id.
   * @param array $groupTitle
   *   Profile Group Title.
   * @param array $values
   *   Formatted array of key value.
   *
   * @param array $profileFields
   */
  public static function displayProfile(&$params, $gid, &$groupTitle, &$values, &$profileFields = array()) {
    if ($gid) {
      $config = CRM_Core_Config::singleton();
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
      if ($contactID) {
        if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $contactID)) {
          $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW);
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::ADD);
      }

      foreach ($fields as $v) {
        if (!empty($v['groupTitle'])) {
          $groupTitle['groupTitle'] = $v['groupTitle'];
          break;
        }
      }

      $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
      //start of code to set the default values
      foreach ($fields as $name => $field) {
        $customVal = '';
        $skip = FALSE;
        // skip fields that should not be displayed separately
        if ($field['skipDisplay']) {
          continue;
        }

        $index = $field['title'];
        if ($name === 'organization_name') {
          $values[$index] = $params[$name];
        }

        if ('state_province' == substr($name, 0, 14)) {
          if ($params[$name]) {
            $values[$index] = CRM_Core_PseudoConstant::stateProvince($params[$name]);
          }
          else {
            $values[$index] = '';
          }
        }
        elseif ('date' == substr($name, -4)) {
          $values[$index] = CRM_Utils_Date::customFormat(CRM_Utils_Date::processDate($params[$name]),
            $config->dateformatFull);
        }
        elseif ('country' == substr($name, 0, 7)) {
          if ($params[$name]) {
            $values[$index] = CRM_Core_PseudoConstant::country($params[$name]);
          }
          else {
            $values[$index] = '';
          }
        }
        elseif ('county' == substr($name, 0, 6)) {
          if ($params[$name]) {
            $values[$index] = CRM_Core_PseudoConstant::county($params[$name]);
          }
          else {
            $values[$index] = '';
          }
        }
        elseif (in_array(substr($name, 0, -3), array('gender', 'prefix', 'suffix', 'communication_style'))) {
          $values[$index] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', $name, $params[$name]);
        }
        elseif (in_array($name, array(
          'addressee',
          'email_greeting',
          'postal_greeting',
        ))) {
          $filterCondition = array('greeting_type' => $name);
          $greeting = CRM_Core_PseudoConstant::greeting($filterCondition);
          $values[$index] = $greeting[$params[$name]];
        }
        elseif ($name === 'preferred_communication_method') {
          $communicationFields = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method');
          $compref = array();
          $pref = $params[$name];
          if (is_array($pref)) {
            foreach ($pref as $k => $v) {
              if ($v) {
                $compref[] = $communicationFields[$k];
              }
            }
          }
          $values[$index] = implode(',', $compref);
        }
        elseif ($name == 'contact_sub_type') {
          $values[$index] = implode(', ', $params[$name]);
        }
        elseif ($name == 'group') {
          $groups = CRM_Contact_BAO_GroupContact::getGroupList();
          $title = array();
          foreach ($params[$name] as $gId => $dontCare) {
            if ($dontCare) {
              $title[] = $groups[$gId];
            }
          }
          $values[$index] = implode(', ', $title);
        }
        elseif ($name == 'tag') {
          $entityTags = $params[$name];
          $allTags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
          $title = array();
          if (is_array($entityTags)) {
            foreach ($entityTags as $tagId => $dontCare) {
              $title[] = $allTags[$tagId];
            }
          }
          $values[$index] = implode(', ', $title);
        }
        elseif ('participant_role_id' == $name OR
          'participant_role' == $name
        ) {
          $roles = CRM_Event_PseudoConstant::participantRole();
          $values[$index] = $roles[$params[$name]];
        }
        elseif ('participant_status_id' == $name OR
          'participant_status' == $name
        ) {
          $status = CRM_Event_PseudoConstant::participantStatus();
          $values[$index] = $status[$params[$name]];
        }
        elseif (substr($name, -11) == 'campaign_id') {
          $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($params[$name]);
          $values[$index] = CRM_Utils_Array::value($params[$name], $campaigns);
        }
        elseif (strpos($name, '-') !== FALSE) {
          list($fieldName, $id) = CRM_Utils_System::explode('-', $name, 2);
          $detailName = str_replace(' ', '_', $name);
          if (in_array($fieldName, array(
            'state_province',
            'country',
            'county',
          ))) {
            $values[$index] = $params[$detailName];
            $idx = $detailName . '_id';
            $values[$index] = $params[$idx];
          }
          elseif ($fieldName == 'im') {
            $providerName = NULL;
            if ($providerId = $detailName . '-provider_id') {
              $providerName = CRM_Utils_Array::value($params[$providerId], $imProviders);
            }
            if ($providerName) {
              $values[$index] = $params[$detailName] . " (" . $providerName . ")";
            }
            else {
              $values[$index] = $params[$detailName];
            }
          }
          elseif ($fieldName == 'phone') {
            $phoneExtField = str_replace('phone', 'phone_ext', $detailName);
            if (isset($params[$phoneExtField])) {
              $values[$index] = $params[$detailName] . " (" . $params[$phoneExtField] . ")";
            }
            else {
              $values[$index] = $params[$detailName];
            }
          }
          else {
            $values[$index] = $params[$detailName];
          }
        }
        else {
          if (substr($name, 0, 7) === 'do_not_' or substr($name, 0, 3) === 'is_') {
            if ($params[$name]) {
              $values[$index] = '[ x ]';
            }
          }
          else {
            if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name)) {
              $query = "
SELECT html_type, data_type
FROM   civicrm_custom_field
WHERE  id = $cfID
";
              $dao = CRM_Core_DAO::executeQuery($query);
              $dao->fetch();
              $htmlType = $dao->html_type;

              if ($htmlType == 'File') {
                $path = CRM_Utils_Array::value('name', $params[$name]);
                $fileType = CRM_Utils_Array::value('type', $params[$name]);
                $values[$index] = CRM_Utils_File::getFileURL($path, $fileType);
              }
              else {
                if ($dao->data_type == 'Int' ||
                  $dao->data_type == 'Boolean'
                ) {
                  $v = $params[$name];
                  if (!CRM_Utils_System::isNull($v)) {
                    $customVal = (int) $v;
                  }
                }
                elseif ($dao->data_type == 'Float') {
                  $customVal = (float ) ($params[$name]);
                }
                elseif ($dao->data_type == 'Date') {
                  //@todo note the currently we are using default date time formatting. Since you can select/set
                  // different date and time format specific to custom field we should consider fixing this
                  // sometime in the future
                  $customVal = $displayValue = CRM_Utils_Date::customFormat(
                    CRM_Utils_Date::processDate($params[$name]), $config->dateformatFull);

                  if (!empty($params[$name . '_time'])) {
                    $customVal = $displayValue = CRM_Utils_Date::customFormat(
                      CRM_Utils_Date::processDate($params[$name], $params[$name . '_time']),
                      $config->dateformatDatetime);
                  }
                  $skip = TRUE;
                }
                else {
                  $customVal = $params[$name];
                }
                //take the custom field options
                $returnProperties = array($name => 1);
                $query = new CRM_Contact_BAO_Query($params, $returnProperties, $fields);
                if (!$skip) {
                  $displayValue = CRM_Core_BAO_CustomField::displayValue($customVal, $cfID);
                }
                //Hack since we dont have function to check empty.
                //FIXME in 2.3 using crmIsEmptyArray()
                $customValue = TRUE;
                if (is_array($customVal) && is_array($displayValue)) {
                  $customValue = array_diff($customVal, $displayValue);
                }
                //use difference of arrays
                if (empty($customValue) || !$customValue) {
                  $values[$index] = '';
                }
                else {
                  $values[$index] = $displayValue;
                }
              }
            }
            elseif ($name == 'home_URL' &&
              !empty($params[$name])
            ) {
              $url = CRM_Utils_System::fixURL($params[$name]);
              $values[$index] = "<a href=\"$url\">{$params[$name]}</a>";
            }
            elseif (in_array($name, array(
              'birth_date',
              'deceased_date',
              'participant_register_date',
            ))) {
              $values[$index] = CRM_Utils_Date::customFormat(CRM_Utils_Date::format($params[$name]));
            }
            else {
              $values[$index] = CRM_Utils_Array::value($name, $params);
            }
          }
        }
        $profileFields[$name] = $field;
      }
    }
  }

  /**
   * Build the array for Additional participant's information  array of primary and additional Ids.
   *
   * @param int $participantId
   *   Id of Primary participant.
   * @param array $values
   *   Key/value event info.
   * @param int $contactId
   *   Contact id of Primary participant.
   * @param bool $isTest
   *   Whether test or live transaction.
   * @param bool $isIdsArray
   *   To return an array of Ids.
   *
   * @param bool $skipCancel
   *
   * @return array
   *   array of Additional participant's info OR array of Ids.
   */
  public static function buildCustomProfile(
    $participantId,
    $values,
    $contactId = NULL,
    $isTest = FALSE,
    $isIdsArray = FALSE,
    $skipCancel = TRUE
  ) {

    $customProfile = $additionalIDs = array();
    if (!$participantId) {
      CRM_Core_Error::fatal(ts('Cannot find participant ID'));
    }

    //set Ids of Primary Participant also.
    if ($isIdsArray && $contactId) {
      $additionalIDs[$participantId] = $contactId;
    }

    //hack to skip cancelled participants, CRM-4320
    $where = "participant.registered_by_id={$participantId}";
    if ($skipCancel) {
      $cancelStatusId = 0;
      $negativeStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
      $cancelStatusId = array_search('Cancelled', $negativeStatuses);
      $where .= " AND participant.status_id != {$cancelStatusId}";
    }
    $query = "
  SELECT  participant.id, participant.contact_id
    FROM  civicrm_participant participant
   WHERE  {$where}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $additionalIDs[$dao->id] = $dao->contact_id;
    }

    //return if only array is required.
    if ($isIdsArray && $contactId) {
      return $additionalIDs;
    }

    $preProfileID = CRM_Utils_Array::value('additional_custom_pre_id', $values);
    $postProfileID = CRM_Utils_Array::value('additional_custom_post_id', $values);
    //else build array of Additional participant's information.
    if (count($additionalIDs)) {
      if ($preProfileID || $postProfileID) {
        $template = CRM_Core_Smarty::singleton();

        $isCustomProfile = TRUE;
        $i = 1;
        $title = $groupTitles = array();
        foreach ($additionalIDs as $pId => $cId) {
          //get the params submitted by participant.
          $participantParams = NULL;
          if (isset($values['params'])) {
            $participantParams = CRM_Utils_Array::value($pId, $values['params'], array());
          }

          list($profilePre, $groupTitles) = self::buildCustomDisplay($preProfileID,
            'additionalCustomPre',
            $cId,
            $template,
            $pId,
            $isTest,
            $isCustomProfile,
            $participantParams
          );

          if ($profilePre) {
            $profile = $profilePre;
            // $customProfile[$i] = array_merge( $groupTitles, $customProfile[$i] );
            if ($i === 1) {
              $title = $groupTitles;
            }
          }

          list($profilePost, $groupTitles) = self::buildCustomDisplay($postProfileID,
            'additionalCustomPost',
            $cId,
            $template,
            $pId,
            $isTest,
            $isCustomProfile,
            $participantParams
          );

          if ($profilePost) {
            if (isset($profilePre)) {
              $profile = array_merge($profilePre, $profilePost);
              if ($i === 1) {
                $title = array_merge($title, $groupTitles);
              }
            }
            else {
              $profile = $profilePost;
              if ($i === 1) {
                $title = $groupTitles;
              }
            }
          }
          $profiles[] = $profile;
          $i++;
        }
        $customProfile['title'] = $title;
        $customProfile['profile'] = $profiles;
      }
    }

    return $customProfile;
  }

  /**
   * Retrieve all event addresses.
   *
   * @return array
   */
  public static function getLocationEvents() {
    $events = array();
    $ret = array(
      'loc_block_id',
      'loc_block_id.address_id.name',
      'loc_block_id.address_id.street_address',
      'loc_block_id.address_id.supplemental_address_1',
      'loc_block_id.address_id.supplemental_address_2',
      'loc_block_id.address_id.supplemental_address_3',
      'loc_block_id.address_id.city',
      'loc_block_id.address_id.state_province_id.name',
    );

    $result = civicrm_api3('Event', 'get', array(
      'check_permissions' => TRUE,
      'return' => $ret,
      'loc_block_id.address_id' => array('IS NOT NULL' => 1),
      'options' => array(
        'limit' => 0,
      ),
    ));

    foreach ($result['values'] as $event) {
      $address = '';
      foreach ($ret as $field) {
        if ($field != 'loc_block_id' && !empty($event[$field])) {
          $address .= ($address ? ' :: ' : '') . $event[$field];
        }
      }
      if ($address) {
        $events[$event['loc_block_id']] = $address;
      }
    }

    return CRM_Utils_Array::asort($events);
  }

  /**
   * @param int $locBlockId
   *
   * @return int|null|string
   */
  public static function countEventsUsingLocBlockId($locBlockId) {
    if (!$locBlockId) {
      return 0;
    }

    $locBlockId = CRM_Utils_Type::escape($locBlockId, 'Integer');

    $query = "
SELECT count(*) FROM civicrm_event ce
WHERE  ce.loc_block_id = $locBlockId";

    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Check if event registration is valid according to permissions AND Dates.
   *
   * @param array $values
   * @param int $eventID
   * @return bool
   */
  public static function validRegistrationRequest($values, $eventID) {
    // check that the user has permission to register for this event
    $hasPermission = CRM_Core_Permission::event(CRM_Core_Permission::EDIT,
      $eventID, 'register for events'
    );

    return $hasPermission && self::validRegistrationDate($values);
  }

  /**
   * @param $values
   *
   * @return bool
   */
  public static function validRegistrationDate(&$values) {
    // make sure that we are between registration start date and end dates
    // and that if the event has ended, registration is still specifically open
    $startDate = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('registration_start_date', $values));
    $endDate = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('registration_end_date', $values));
    $eventEnd = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('end_date', $values));
    $now = time();
    $validDate = TRUE;
    if ($startDate && $startDate >= $now) {
      $validDate = FALSE;
    }
    elseif ($endDate && $endDate < $now) {
      $validDate = FALSE;
    }
    elseif ($eventEnd && $eventEnd < $now && !$endDate) {
      $validDate = FALSE;
    }

    return $validDate;
  }

  /* Function to Show - Hide the Registration Link.
   *
   * @param array $values
   *   Key/value event info.
   * @return boolean
   *   true if allow registration otherwise false
   */
  /**
   * @param $values
   *
   * @return bool
   */
  public static function showHideRegistrationLink($values) {

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    $alreadyRegistered = FALSE;

    if ($contactID) {
      $params = array('contact_id' => $contactID);

      if ($eventId = CRM_Utils_Array::value('id', $values['event'])) {
        $params['event_id'] = $eventId;
      }
      if ($roleId = CRM_Utils_Array::value('default_role_id', $values['event'])) {
        $params['role_id'] = $roleId;
      }
      $alreadyRegistered = self::checkRegistration($params);
    }

    if (!empty($values['event']['allow_same_participant_emails']) ||
      !$alreadyRegistered
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /* Function to check if given contact is already registered.
   *
   * @param array $params
   *   Key/value participant info.
   * @return boolean
   */
  /**
   * @param array $params
   *
   * @return bool
   */
  public static function checkRegistration($params) {
    $alreadyRegistered = FALSE;
    if (empty($params['contact_id'])) {
      return $alreadyRegistered;
    }

    $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');

    $participant = new CRM_Event_DAO_Participant();
    $participant->copyValues($params);

    $participant->is_test = CRM_Utils_Array::value('is_test', $params, 0);
    $participant->selectAdd();
    $participant->selectAdd('status_id');
    if ($participant->find(TRUE) && array_key_exists($participant->status_id, $statusTypes)) {
      $alreadyRegistered = TRUE;
    }

    return $alreadyRegistered;
  }

  /**
   * Make sure that the user has permission to access this event.
   * FIXME: We have separate caches for checkPermission('permission') and getAllPermissions['permissions'] so they don't interfere.
   *    But it would be nice to clean this up some more.
   *
   * @param int $eventId
   * @param int $permissionType
   *
   * @return bool|array
   *   Whether the user has permission for this event (or if eventId=NULL an array of permissions)
   * @throws \CiviCRM_API3_Exception
   */
  public static function checkPermission($eventId = NULL, $permissionType = CRM_Core_Permission::VIEW) {
    if (empty($eventId)) {
      CRM_Core_Error::deprecatedFunctionWarning('CRM_Event_BAO_Event::getAllPermissions');
      return self::getAllPermissions();
    }

    switch ($permissionType) {
      case CRM_Core_Permission::EDIT:
        // We also set the cached "view" permission to TRUE if "edit" is TRUE
        if (isset(Civi::$statics[__CLASS__]['permission']['edit'][$eventId])) {
          return Civi::$statics[__CLASS__]['permission']['edit'][$eventId];
        }
        Civi::$statics[__CLASS__]['permission']['edit'][$eventId] = FALSE;

        list($allEvents, $createdEvents) = self::checkPermissionGetInfo($eventId);
        // Note: for a multisite setup, a user with edit all events, can edit all events
        // including those from other sites
        if (($permissionType == CRM_Core_Permission::EDIT) && CRM_Core_Permission::check('edit all events')) {
          Civi::$statics[__CLASS__]['permission']['edit'][$eventId] = TRUE;
          Civi::$statics[__CLASS__]['permission']['view'][$eventId] = TRUE;
        }
        elseif (in_array($eventId, CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_event', $allEvents, $createdEvents))) {
          Civi::$statics[__CLASS__]['permission']['edit'][$eventId] = TRUE;
          Civi::$statics[__CLASS__]['permission']['view'][$eventId] = TRUE;
        }
        return Civi::$statics[__CLASS__]['permission']['edit'][$eventId];

      case CRM_Core_Permission::VIEW:
        if (isset(Civi::$statics[__CLASS__]['permission']['view'][$eventId])) {
          return Civi::$statics[__CLASS__]['permission']['view'][$eventId];
        }
        Civi::$statics[__CLASS__]['permission']['view'][$eventId] = FALSE;

        list($allEvents, $createdEvents) = self::checkPermissionGetInfo($eventId);
        if (CRM_Core_Permission::check('access CiviEvent')) {
          if (in_array($eventId, CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_event', $allEvents, array_keys($createdEvents)))) {
            // User created this event so has permission to view it
            return Civi::$statics[__CLASS__]['permission']['view'][$eventId] = TRUE;
          }
          if (CRM_Core_Permission::check('view event participants')) {
            // User has permission to view all events
            // use case: allow "view all events" but NOT "edit all events"
            // so for a normal site allow users with these two permissions to view all events AND
            // at the same time also allow any hook to override if needed.
            if (in_array($eventId, CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_event', $allEvents, array_keys($allEvents)))) {
              Civi::$statics[__CLASS__]['permission']['view'][$eventId] = TRUE;
            }
          }
        }
        return Civi::$statics[__CLASS__]['permission']['view'][$eventId];

      case CRM_Core_Permission::DELETE:
        if (isset(Civi::$statics[__CLASS__]['permission']['delete'][$eventId])) {
          return Civi::$statics[__CLASS__]['permission']['delete'][$eventId];
        }
        Civi::$statics[__CLASS__]['permission']['delete'][$eventId] = FALSE;
        if (CRM_Core_Permission::check('delete in CiviEvent')) {
          Civi::$statics[__CLASS__]['permission']['delete'][$eventId] = TRUE;
        }
        return Civi::$statics[__CLASS__]['permission']['delete'][$eventId];

      default:
        return FALSE;
    }
  }

  /**
   * This is a helper for refactoring checkPermission
   * FIXME: We should be able to get rid of these arrays, but that would require understanding how CRM_ACL_API::group actually works!
   *
   * @param int $eventId
   *
   * @return array $allEvents, $createdEvents
   * @throws \CiviCRM_API3_Exception
   */
  private static function checkPermissionGetInfo($eventId = NULL) {
    $params = [
      'check_permissions' => 1,
      'return' => 'id, created_id',
      'options' => ['limit' => 0],
    ];
    if ($eventId) {
      $params['id'] = $eventId;
    }

    $allEvents = [];
    $createdEvents = [];
    $eventResult = civicrm_api3('Event', 'get', $params);
    if ($eventResult['count'] > 0) {
      $contactId = CRM_Core_Session::getLoggedInContactID();
      foreach ($eventResult['values'] as $eventId => $eventDetail) {
        $allEvents[$eventId] = $eventId;
        if (isset($eventDetail['created_id']) && $contactId == $eventDetail['created_id']) {
          $createdEvents[$eventId] = $eventId;
        }
      }
    }
    return [$allEvents, $createdEvents];
  }

  /**
   * Make sure that the user has permission to access this event.
   * TODO: This function needs refactoring / cleaning up after being split from checkPermissions()
   *
   * @return array
   *   Array of events with permissions (array_keys=permissions)
   * @throws \CiviCRM_API3_Exception
   */
  public static function getAllPermissions() {
    if (!isset(Civi::$statics[__CLASS__]['permissions'])) {
      list($allEvents, $createdEvents) = self::checkPermissionGetInfo();

      // Note: for a multisite setup, a user with edit all events, can edit all events
      // including those from other sites
      if (CRM_Core_Permission::check('edit all events')) {
        Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::EDIT] = array_keys($allEvents);
      }
      else {
        Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::EDIT] = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_event', $allEvents, $createdEvents);
      }

      if (CRM_Core_Permission::check('edit all events')) {
        Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::VIEW] = array_keys($allEvents);
      }
      else {
        if (CRM_Core_Permission::check('access CiviEvent') &&
          CRM_Core_Permission::check('view event participants')
        ) {
          // use case: allow "view all events" but NOT "edit all events"
          // so for a normal site allow users with these two permissions to view all events AND
          // at the same time also allow any hook to override if needed.
          $createdEvents = array_keys($allEvents);
        }
        Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::VIEW] = CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_event', $allEvents, $createdEvents);
      }

      Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::DELETE] = array();
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        // Note: we want to restrict the scope of delete permission to
        // events that are editable/viewable (usecase multisite).
        // We can remove array_intersect once we have ACL support for delete functionality.
        Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::DELETE] = array_intersect(Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::EDIT],
          Civi::$statics[__CLASS__]['permissions'][CRM_Core_Permission::VIEW]
        );
      }
    }

    return Civi::$statics[__CLASS__]['permissions'];
  }

  /**
   * Build From Email as the combination of all the email ids of the logged in user,
   * the domain email id and the email id configured for the event
   *
   * @param int $eventId
   *   The id of the event.
   *
   * @return array
   *   an array of email ids
   */
  public static function getFromEmailIds($eventId = NULL) {
    $fromEmailValues['from_email_id'] = CRM_Core_BAO_Email::getFromEmail();

    if ($eventId) {
      // add the email id configured for the event
      $params = array('id' => $eventId);
      $returnProperties = array('confirm_from_name', 'confirm_from_email', 'cc_confirm', 'bcc_confirm');
      $eventEmail = array();

      CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $params, $eventEmail, $returnProperties);
      if (!empty($eventEmail['confirm_from_name']) && !empty($eventEmail['confirm_from_email'])) {
        $eventEmailId = "{$eventEmail['confirm_from_name']} <{$eventEmail['confirm_from_email']}>";

        $fromEmailValues['from_email_id'][$eventEmailId] = htmlspecialchars($eventEmailId);
        $fromEmailId = array(
          'cc' => CRM_Utils_Array::value('cc_confirm', $eventEmail),
          'bcc' => CRM_Utils_Array::value('bcc_confirm', $eventEmail),
        );
        $fromEmailValues = array_merge($fromEmailValues, $fromEmailId);
      }
    }

    return $fromEmailValues;
  }

  /**
   * Calculate event total seats occupied.
   *
   * @param int $eventId
   *   Event id.
   * @param sting $extraWhereClause
   *   Extra filter on participants.
   *
   * @return int
   *   event total seats w/ given criteria.
   */
  public static function eventTotalSeats($eventId, $extraWhereClause = NULL) {
    if (empty($eventId)) {
      return 0;
    }

    $extraWhereClause = trim($extraWhereClause);
    if (!empty($extraWhereClause)) {
      $extraWhereClause = " AND ( {$extraWhereClause} )";
    }

    //event seats calculation :
    //1. consider event seat as a single when participant does not have line item.
    //2. consider event seat as a single when participant has line items but does not
    //   have count for corresponding price field value ( ie price field value does not carry any seat )
    //3. consider event seat as a sum of all seats from line items in case price field value carries count.

    $query = "
    SELECT  IF ( SUM( value.count*lineItem.qty ),
                 SUM( value.count*lineItem.qty ) +
                 COUNT( DISTINCT participant.id ) -
                 COUNT( DISTINCT IF ( value.count, participant.id, NULL ) ),
                 COUNT( DISTINCT participant.id ) )
      FROM  civicrm_participant participant
INNER JOIN  civicrm_contact contact ON ( contact.id = participant.contact_id AND contact.is_deleted = 0 )
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id )
LEFT  JOIN  civicrm_line_item lineItem ON ( lineItem.entity_id    = participant.id
                                       AND  lineItem.entity_table = 'civicrm_participant' )
LEFT  JOIN  civicrm_price_field_value value ON ( value.id = lineItem.price_field_value_id AND value.count )
     WHERE  ( participant.event_id = %1 )
            AND participant.is_test = 0
            {$extraWhereClause}
  GROUP BY  participant.event_id";

    return (int) CRM_Core_DAO::singleValueQuery($query, array(1 => array($eventId, 'Positive')));
  }

  /**
   * Retrieve event template default values to be set.
   *  as default values for current new event.
   *
   * @param int $templateId
   *   Event template id.
   *
   * @return array
   *   Array of custom data defaults.
   */
  public static function getTemplateDefaultValues($templateId) {
    $defaults = array();
    if (!$templateId) {
      return $defaults;
    }

    $templateParams = array('id' => $templateId);
    CRM_Event_BAO_Event::retrieve($templateParams, $defaults);
    $fieldsToExclude = array(
      'id',
      'default_fee_id',
      'default_discount_fee_id',
      'created_date',
      'created_id',
      'is_template',
      'template_title',
    );
    $defaults = array_diff_key($defaults, array_flip($fieldsToExclude));
    return $defaults;
  }

  /**
   * @param int $event_id
   *
   * @return object
   */
  public static function get_sub_events($event_id) {
    $params = array('parent_event_id' => $event_id);
    $defaults = array();
    return CRM_Event_BAO_Event::retrieve($params, $defaults);
  }

  /**
   * Update the Campaign Id of all the participants of the given event.
   *
   * @param int $eventID
   *   Event id.
   * @param int $eventCampaignID
   *   Campaign id of that event.
   */
  public static function updateParticipantCampaignID($eventID, $eventCampaignID) {
    $params = array();
    $params[1] = array($eventID, 'Integer');

    if (empty($eventCampaignID)) {
      $query = "UPDATE civicrm_participant SET campaign_id = NULL WHERE event_id = %1";
    }
    else {
      $query = "UPDATE civicrm_participant SET campaign_id = %2 WHERE event_id = %1";
      $params[2] = array($eventCampaignID, 'Integer');
    }
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Get options for a given field.
   * @see CRM_Core_DAO::buildOptions
   *
   * @param string $fieldName
   * @param string $context : @see CRM_Core_DAO::buildOptionsContext
   * @param array $props : whatever is known about this dao object
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $params = array();
    // Special logic for fields whose options depend on context or properties
    switch ($fieldName) {
      case 'financial_type_id':
        // Fixme - this is going to ignore context, better to get conditions, add params, and call PseudoConstant::get
        return CRM_Financial_BAO_FinancialType::getIncomeFinancialType();

      break;
    }
    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
  }

}
