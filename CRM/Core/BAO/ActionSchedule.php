<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class contains functions for managing Scheduled Reminders
 */
class CRM_Core_BAO_ActionSchedule extends CRM_Core_DAO_ActionSchedule {

  /**
   * @param array $filters
   *   Filter by property (e.g. 'id', 'entity_value').
   * @return array
   *   Array(scalar $id => Mapping $mapping).
   */
  public static function getMappings($filters = NULL) {
    static $_action_mapping;

    if ($_action_mapping === NULL) {
      $_action_mapping = array(
        1 => \Civi\ActionSchedule\Mapping::create(array(
          'id' => 1,
          'entity' => 'civicrm_activity',
          'entity_label' => ts('Activity'),
          'entity_value' => 'activity_type',
          'entity_value_label' => 'Activity Type',
          'entity_status' => 'activity_status',
          'entity_status_label' => 'Activity Status',
          'entity_date_start' => 'activity_date_time',
          'entity_recipient' => 'activity_contacts',
        )),
        2 => \Civi\ActionSchedule\Mapping::create(array(
          'id' => 2,
          'entity' => 'civicrm_participant',
          'entity_label' => ts('Event Type'),
          'entity_value' => 'event_type',
          'entity_value_label' => 'Event Type',
          'entity_status' => 'civicrm_participant_status_type',
          'entity_status_label' => 'Participant Status',
          'entity_date_start' => 'event_start_date',
          'entity_date_end' => 'event_end_date',
          'entity_recipient' => 'event_contacts',
        )),
        3 => \Civi\ActionSchedule\Mapping::create(array(
          'id' => 3,
          'entity' => 'civicrm_participant',
          'entity_label' => ts('Event Name'),
          'entity_value' => 'civicrm_event',
          'entity_value_label' => 'Event Name',
          'entity_status' => 'civicrm_participant_status_type',
          'entity_status_label' => 'Participant Status',
          'entity_date_start' => 'event_start_date',
          'entity_date_end' => 'event_end_date',
          'entity_recipient' => 'event_contacts',
        )),
        4 => \Civi\ActionSchedule\Mapping::create(array(
          'id' => 4,
          'entity' => 'civicrm_membership',
          'entity_label' => ts('Membership'),
          'entity_value' => 'civicrm_membership_type',
          'entity_value_label' => 'Membership Type',
          'entity_status' => 'auto_renew_options',
          'entity_status_label' => 'Auto Renew Options',
          'entity_date_start' => 'membership_join_date',
          'entity_date_end' => 'membership_end_date',
        )),
        5 => \Civi\ActionSchedule\Mapping::create(array(
          'id' => 5,
          'entity' => 'civicrm_participant',
          'entity_label' => ts('Event Template'),
          'entity_value' => 'event_template',
          'entity_value_label' => 'Event Template',
          'entity_status' => 'civicrm_participant_status_type',
          'entity_status_label' => 'Participant Status',
          'entity_date_start' => 'event_start_date',
          'entity_date_end' => 'event_end_date',
          'entity_recipient' => 'event_contacts',
        )),
        6 => \Civi\ActionSchedule\Mapping::create(array(
          'id' => 6,
          'entity' => 'civicrm_contact',
          'entity_label' => ts('Contact'),
          'entity_value' => 'civicrm_contact',
          'entity_value_label' => 'Date Field',
          'entity_status' => 'contact_date_reminder_options',
          'entity_status_label' => 'Annual Options',
          'entity_date_start' => 'date_field',
        )),
      );
    }

    if ($filters === NULL) {
      return $_action_mapping;
    }

    $result = array();
    foreach ($_action_mapping as $mappingId => $mapping) {
      $match = TRUE;
      foreach ($filters as $filterField => $filterValue) {
        if ($mapping->{$filterField} != $filterValue) {
          $match = FALSE;
        }
      }
      if ($match) {
        $result[$mappingId] = $mapping;
      }
    }
    return $result;
  }

  /**
   * Retrieve list of selections/drop downs for Scheduled Reminder form
   *
   * @param bool $id
   *   Mapping id.
   *
   * @return array
   *   associated array of all the drop downs in the form
   */
  public static function getSelection($id = NULL) {
    $mappings = CRM_Core_BAO_ActionSchedule::getMappings();

    $entityValueLabels = $entityStatusLabels = $dateFieldLabels = array();
    $entityRecipientLabels = $entityRecipientNames = array();

    if (!$id) {
      $id = 1;
    }

    foreach ($mappings as $mapping) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */

      $entityValueLabels[$mapping->id] = $mapping->getValueLabels();
      // Not sure why: everything *except* contact-dates have a $valueLabel.
      if ($mapping->entity_value !== 'civicrm_contact') {
        $valueLabel = array('- ' . strtolower($mapping->entity_value_label) . ' -');
        $entityValueLabels[$mapping->id] = $valueLabel + $entityValueLabels[$mapping->id];
      }

      if ($mapping->id == $id) {
        $dateFieldLabels = $mapping->getDateFields();
        $entityRecipientLabels = array($mapping->entity_recipient => $mapping->getRecipientTypes());
        $entityRecipientNames = array_combine(array_keys($entityRecipientLabels[$mapping->entity_recipient]), array_keys($entityRecipientLabels[$mapping->entity_recipient]));
      }

      $statusLabel = array('- ' . strtolower($mapping->entity_status_label) . ' -');
      $entityStatusLabels[$mapping->id] = $entityValueLabels[$mapping->id];
      foreach ($entityStatusLabels[$mapping->id] as $kkey => & $vval) {
        $vval = $statusLabel + $mapping->getStatusLabels($kkey);
      }
    }

    return array(
      'sel1' => CRM_Utils_Array::collect('entity_label', $mappings),
      'sel2' => $entityValueLabels,
      'sel3' => $entityStatusLabels,
      'sel4' => $dateFieldLabels,
      'sel5' => $entityRecipientLabels,
      'entityMapping' => CRM_Utils_Array::collect('entity', $mappings),
      'recipientMapping' => $entityRecipientNames,
    );
  }

  /**
   * @param int $mappingId
   * @param int $isLimit
   *
   * @return array
   */
  public static function getSelection1($mappingId = NULL, $isLimit = NULL) {
    $mappings = CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => $mappingId,
    ));
    $dateFieldLabels = $entityRecipientLabels = array();

    foreach ($mappings as $mapping) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */
      $dateFieldLabels = $mapping->getDateFields();
      $entityRecipientLabels = $mapping->getRecipientTypes(!$isLimit);
    }

    return array(
      'sel4' => $dateFieldLabels,
      'sel5' => $entityRecipientLabels,
      'recipientMapping' => array_combine(array_keys($entityRecipientLabels), array_keys($entityRecipientLabels)),
    );
  }

  /**
   * Retrieve list of Scheduled Reminders.
   *
   * @param bool $namesOnly
   *   Return simple list of names.
   *
   * @param null $entityValue
   * @param int $id
   *
   * @return array
   *   (reference)   reminder list
   */
  public static function &getList($namesOnly = FALSE, $entityValue = NULL, $id = NULL) {
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
    $queryParams = array();
    $where = " WHERE 1 ";
    if ($entityValue and $id) {
      $mappings = self::getMappings(array(
        'entity_value' => $entityValue,
      ));
      $mappingIds = implode(',', array_keys($mappings));
      $where .= " AND cas.entity_value = %1 AND cas.mapping_id IN ($mappingIds)";
      $queryParams[1] = array($id, 'Integer');
    }
    $where .= " AND cas.used_for IS NULL";
    $query .= $where;
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      /** @var Civi\ActionSchedule\Mapping $mapping */
      $mapping = CRM_Utils_Array::first(self::getMappings(array(
        'id' => $dao->mapping_id,
      )));
      $list[$dao->id]['id'] = $dao->id;
      $list[$dao->id]['title'] = $dao->title;
      $list[$dao->id]['start_action_offset'] = $dao->start_action_offset;
      $list[$dao->id]['start_action_unit'] = $dao->start_action_unit;
      $list[$dao->id]['start_action_condition'] = $dao->start_action_condition;
      $list[$dao->id]['entityDate'] = ucwords(str_replace('_', ' ', $dao->entityDate));
      $list[$dao->id]['absolute_date'] = $dao->absolute_date;
      $list[$dao->id]['entity'] = $mapping->entity_label;
      $list[$dao->id]['value'] = implode(', ', CRM_Utils_Array::subset(
        $mapping->getValueLabels(),
        explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityValueIds)
      ));
      $list[$dao->id]['status'] = implode(', ', CRM_Utils_Array::subset(
        $mapping->getStatusLabels($dao->entityValueIds),
        explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityStatusIds)
      ));
      $list[$dao->id]['is_repeat'] = $dao->is_repeat;
      $list[$dao->id]['is_active'] = $dao->is_active;
    }

    return $list;
  }

  /**
   * Add the schedules reminders in the db.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   Unused variable.
   *
   * @return CRM_Core_DAO_ActionSchedule
   */
  public static function add(&$params, $ids = array()) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->copyValues($params);

    return $actionSchedule->save();
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $values
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_ActionSchedule|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$values) {
    if (empty($params)) {
      return NULL;
    }
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();

    $actionSchedule->copyValues($params);

    if ($actionSchedule->find(TRUE)) {
      $ids['actionSchedule'] = $actionSchedule->id;

      CRM_Core_DAO::storeValues($actionSchedule, $values);

      return $actionSchedule;
    }
    return NULL;
  }

  /**
   * Delete a Reminder.
   *
   * @param int $id
   *   ID of the Reminder to be deleted.
   *
   */
  public static function del($id) {
    if ($id) {
      $dao = new CRM_Core_DAO_ActionSchedule();
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        $dao->delete();
        return;
      }
    }
    CRM_Core_Error::fatal(ts('Invalid value passed to delete function.'));
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
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
    $mapping = CRM_Utils_Array::first(self::getMappings(array(
      'id' => $mappingID,
    )));

    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    $actionSchedule->find(FALSE);

    while ($actionSchedule->fetch()) {
      $query = CRM_Core_BAO_ActionSchedule::prepareMailingQuery($mapping, $actionSchedule);
      $dao = CRM_Core_DAO::executeQuery($query,
        array(1 => array($actionSchedule->id, 'Integer'))
      );

      $multilingual = CRM_Core_I18n::isMultilingual();
      while ($dao->fetch()) {
        // switch language if necessary
        if ($multilingual) {
          $preferred_language = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $dao->contactID, 'preferred_language');
          CRM_Core_BAO_ActionSchedule::setCommunicationLanguage($actionSchedule->communication_language, $preferred_language);
        }

        $errors = array();
        try {
          $tokenProcessor = self::createTokenProcessor($actionSchedule, $mapping);
          $tokenProcessor->addRow()
            ->context('contactId', $dao->contactID)
            ->context('actionSearchResult', (object) $dao->toArray());
          foreach ($tokenProcessor->evaluate()->getRows() as $tokenRow) {
            if ($actionSchedule->mode == 'SMS' or $actionSchedule->mode == 'User_Preference') {
              CRM_Utils_Array::extend($errors, self::sendReminderSms($tokenRow, $actionSchedule, $dao->contactID));
            }

            if ($actionSchedule->mode == 'Email' or $actionSchedule->mode == 'User_Preference') {
              CRM_Utils_Array::extend($errors, self::sendReminderEmail($tokenRow, $actionSchedule, $dao->contactID));
            }
          }
        }
        catch (\Civi\Token\TokenException $e) {
          $errors['token_exception'] = $e->getMessage();
        }

        // update action log record
        $logParams = array(
          'id' => $dao->reminderID,
          'is_error' => !empty($errors),
          'message' => empty($errors) ? "null" : implode(' ', $errors),
          'action_date_time' => $now,
        );
        CRM_Core_BAO_ActionLog::create($logParams);

        // insert activity log record if needed
        if ($actionSchedule->record_activity && empty($errors)) {
          $caseID = empty($dao->case_id) ? NULL : $dao->case_id;
          CRM_Core_BAO_ActionSchedule::createMailingActivity($actionSchedule, $mapping, $dao->contactID, $dao->entityID, $caseID);
        }
      }

      $dao->free();
    }
  }

  /**
   * @param int $mappingID
   * @param $now
   * @param array $params
   *
   * @throws API_Exception
   */
  public static function buildRecipientContacts($mappingID, $now, $params = array()) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    if (!empty($params)) {
      _civicrm_api3_dao_set_filter($actionSchedule, $params, FALSE);
    }
    $actionSchedule->find();

    while ($actionSchedule->fetch()) {
      $mapping = CRM_Utils_Array::first(self::getMappings(array(
        'id' => $mappingID,
      )));

      // note: $where - this filtering applies for both
      // 'limit to' and 'addition to' options
      // $limitWhere - this filtering applies only for
      // 'limit to' option
      $select = $join = $where = $limitWhere = array();
      $selectColumns = "contact_id, entity_id, entity_table, action_schedule_id";
      $limitTo = $actionSchedule->limit_to;
      $value = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_value, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $value = implode(',', $value);

      $status = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_status, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $status = implode(',', $status);

      $anniversary = FALSE;

      if (!CRM_Utils_System::isNull($mapping->entity_recipient)) {
        if ($mapping->entity_recipient == 'event_contacts') {
          $recipientOptions = CRM_Core_OptionGroup::values($mapping->entity_recipient, FALSE, FALSE, FALSE, NULL, 'name', TRUE, FALSE, 'name');
        }
        else {
          $recipientOptions = CRM_Core_OptionGroup::values($mapping->entity_recipient, FALSE, FALSE, FALSE, NULL, 'name');
        }
      }
      $from = "{$mapping->entity} e";

      if ($mapping->entity == 'civicrm_activity') {
        $contactField = 'r.contact_id';
        $table = 'civicrm_activity e';
        $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
        $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
        $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

        if (!is_null($limitTo)) {
          if ($limitTo == 0) {
            // including the activity target contacts if 'in addition' is defined
            $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$targetID}";
          }
          else {
            switch (CRM_Utils_Array::value($actionSchedule->recipient, $recipientOptions)) {
              case 'Activity Assignees':
                $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$assigneeID}";
                break;

              case 'Activity Source':
                $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$sourceID}";
                break;

              default:
              case 'Activity Targets':
                $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$targetID}";
                break;
            }
          }
        }
        // build where clause
        if (!empty($value)) {
          $where[] = "e.activity_type_id IN ({$value})";
        }
        else {
          $where[] = "e.activity_type_id IS NULL";
        }
        if (!empty($status)) {
          $where[] = "e.status_id IN ({$status})";
        }
        $where[] = ' e.is_current_revision = 1 ';
        $where[] = ' e.is_deleted = 0 ';

        $dateField = 'e.activity_date_time';
      }

      if ($mapping->entity == 'civicrm_participant') {
        $table = 'civicrm_event r';
        $contactField = 'e.contact_id';
        $join[] = 'INNER JOIN civicrm_event r ON e.event_id = r.id';
        if ($actionSchedule->recipient_listing && $limitTo) {
          $rList = explode(CRM_Core_DAO::VALUE_SEPARATOR,
            trim($actionSchedule->recipient_listing, CRM_Core_DAO::VALUE_SEPARATOR)
          );
          $rList = implode(',', $rList);

          switch (CRM_Utils_Array::value($actionSchedule->recipient, $recipientOptions)) {
            case 'participant_role':
              $where[] = "e.role_id IN ({$rList})";
              break;

            default:
              break;
          }
        }

        // build where clause
        if (!empty($value)) {
          $where[] = ($mapping->entity_value == 'event_type') ? "r.event_type_id IN ({$value})" : "r.id IN ({$value})";
        }
        else {
          $where[] = ($mapping->entity_value == 'event_type') ? "r.event_type_id IS NULL" : "r.id IS NULL";
        }

        // participant status criteria not to be implemented
        // for additional recipients
        if (!empty($status)) {
          $limitWhere[] = "e.status_id IN ({$status})";
        }

        $where[] = 'r.is_active = 1';
        $where[] = 'r.is_template = 0';
        $dateField = str_replace('event_', 'r.', $actionSchedule->start_action_date);
      }

      $notINClause = '';
      if ($mapping->entity == 'civicrm_membership') {
        $contactField = 'e.contact_id';
        $table = 'civicrm_membership e';
        // build where clause
        if ($status == 2) {
          //auto-renew memberships
          $where[] = "e.contribution_recur_id IS NOT NULL ";
        }
        elseif ($status == 1) {
          $where[] = "e.contribution_recur_id IS NULL ";
        }

        // build where clause
        if (!empty($value)) {
          $where[] = "e.membership_type_id IN ({$value})";
        }
        else {
          $where[] = "e.membership_type_id IS NULL";
        }

        $where[] = "( e.is_override IS NULL OR e.is_override = 0 )";
        $dateField = str_replace('membership_', 'e.', $actionSchedule->start_action_date);
        $notINClause = CRM_Core_BAO_ActionSchedule::permissionedRelationships($contactField);

        $membershipStatus = CRM_Member_PseudoConstant::membershipStatus(NULL, "(is_current_member = 1 OR name = 'Expired')", 'id');
        $mStatus = implode(',', $membershipStatus);
        $where[] = "e.status_id IN ({$mStatus})";

        // We are not tracking the reference date for 'repeated' schedule reminders,
        // for further details please check CRM-15376
        if ($actionSchedule->start_action_date && $actionSchedule->is_repeat == FALSE) {
          $select[] = $dateField;
          $selectColumns = "reference_date, " . $selectColumns;
        }
      }

      if ($mapping->entity == 'civicrm_contact') {
        $contactFields = array(
          'birth_date',
          'created_date',
          'modified_date',
        );
        if (in_array($value, $contactFields)) {
          $dateDBField = $value;
          $table = 'civicrm_contact e';
          $contactField = 'e.id';
          $where[] = 'e.is_deleted = 0';
          $where[] = 'e.is_deceased = 0';
        }
        else {
          //custom field
          $customFieldParams = array('id' => substr($value, 7));
          $customGroup = $customField = array();
          CRM_Core_BAO_CustomField::retrieve($customFieldParams, $customField);
          $dateDBField = $customField['column_name'];
          $customGroupParams = array('id' => $customField['custom_group_id'], $customGroup);
          CRM_Core_BAO_CustomGroup::retrieve($customGroupParams, $customGroup);
          $from = $table = "{$customGroup['table_name']} e";
          $contactField = 'e.entity_id';
          $where[] = '1'; // possible to have no "where" in this case
        }

        $status_ = explode(',', $status);
        if (in_array(2, $status_)) {
          // anniversary mode:
          $dateField = 'DATE_ADD(e.' . $dateDBField . ', INTERVAL ROUND(DATEDIFF(DATE(' . $now . '), e.' . $dateDBField . ') / 365) YEAR)';
          $anniversary = TRUE;
        }
        else {
          // regular mode:
          $dateField = 'e.' . $dateDBField;
        }
      }

      // CRM-13577 Introduce Smart Groups Handling
      if ($actionSchedule->group_id) {

        // Need to check if its a smart group or not
        // Then decide which table to join onto the query
        $group = CRM_Contact_DAO_Group::getTableName();

        // Get the group information
        $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
WHERE      $group.id = {$actionSchedule->group_id}
";

        $groupDAO = CRM_Core_DAO::executeQuery($sql);
        $isSmartGroup = FALSE;
        if (
          $groupDAO->fetch() &&
          !empty($groupDAO->saved_search_id)
        ) {
          // Check that the group is in place in the cache and up to date
          CRM_Contact_BAO_GroupContactCache::check($actionSchedule->group_id);
          // Set smart group flag
          $isSmartGroup = TRUE;
        }
      }
      // CRM-13577 End Introduce Smart Groups Handling

      if ($limitTo) {
        if ($actionSchedule->group_id) {
          // CRM-13577 If smart group then use Cache table
          if ($isSmartGroup) {
            $join[] = "INNER JOIN civicrm_group_contact_cache grp ON {$contactField} = grp.contact_id";
            $where[] = "grp.group_id IN ({$actionSchedule->group_id})";
          }
          else {
            $join[] = "INNER JOIN civicrm_group_contact grp ON {$contactField} = grp.contact_id AND grp.status = 'Added'";
            $where[] = "grp.group_id IN ({$actionSchedule->group_id})";
          }
        }
        elseif (!empty($actionSchedule->recipient_manual)) {
          $rList = CRM_Utils_Type::escape($actionSchedule->recipient_manual, 'String');
          $where[] = "{$contactField} IN ({$rList})";
        }
      }
      elseif (!is_null($limitTo)) {
        $addGroup = $addWhere = '';
        if ($actionSchedule->group_id) {
          // CRM-13577 If smart group then use Cache table
          if ($isSmartGroup) {
            $addGroup = " INNER JOIN civicrm_group_contact_cache grp ON c.id = grp.contact_id";
            $addWhere = " grp.group_id IN ({$actionSchedule->group_id})";
          }
          else {
            $addGroup = " INNER JOIN civicrm_group_contact grp ON c.id = grp.contact_id AND grp.status = 'Added'";
            $addWhere = " grp.group_id IN ({$actionSchedule->group_id})";
          }
        }
        if (!empty($actionSchedule->recipient_manual)) {
          $rList = CRM_Utils_Type::escape($actionSchedule->recipient_manual, 'String');
          $addWhere = "c.id IN ({$rList})";
        }
      }

      $select[] = "{$contactField} as contact_id";
      $select[] = 'e.id as entity_id';
      $select[] = "'{$mapping->entity}' as entity_table";
      $select[] = "{$actionSchedule->id} as action_schedule_id";
      $reminderJoinClause = "civicrm_action_log reminder ON reminder.contact_id = {$contactField} AND
reminder.entity_id          = e.id AND
reminder.entity_table       = '{$mapping->entity}' AND
reminder.action_schedule_id = %1";

      if ($anniversary) {
        // only consider reminders less than 11 months ago
        $reminderJoinClause .= " AND reminder.action_date_time > DATE_SUB({$now}, INTERVAL 11 MONTH)";
      }

      if ($table != 'civicrm_contact e') {
        $join[] = "INNER JOIN civicrm_contact c ON c.id = {$contactField} AND c.is_deleted = 0 AND c.is_deceased = 0 ";
      }

      $multilingual = CRM_Core_I18n::isMultilingual();
      if ($multilingual && !empty($actionSchedule->filter_contact_language)) {
        $tableAlias = ($table != 'civicrm_contact e') ? 'c' : 'e';

        // get language filter for the schedule
        $filter_contact_language = explode(CRM_Core_DAO::VALUE_SEPARATOR, $actionSchedule->filter_contact_language);
        $w = '';
        if (($key = array_search(CRM_Core_I18n::NONE, $filter_contact_language)) !== FALSE) {
          $w .= "{$tableAlias}.preferred_language IS NULL OR {$tableAlias}.preferred_language = '' OR ";
          unset($filter_contact_language[$key]);
        }
        if (count($filter_contact_language) > 0) {
          $w .= "{$tableAlias}.preferred_language IN ('" . implode("','", $filter_contact_language) . "')";
        }
        $where[] = "($w)";
      }

      if ($actionSchedule->start_action_date) {
        $startDateClause = array();
        $op = ($actionSchedule->start_action_condition == 'before' ? '<=' : '>=');
        $operator = ($actionSchedule->start_action_condition == 'before' ? 'DATE_SUB' : 'DATE_ADD');
        $date = $operator . "({$dateField}, INTERVAL {$actionSchedule->start_action_offset} {$actionSchedule->start_action_unit})";
        $startDateClause[] = "'{$now}' >= {$date}";
        if ($mapping->entity == 'civicrm_participant') {
          $startDateClause[] = $operator . "({$now}, INTERVAL 1 DAY ) {$op} " . $dateField;
        }
        else {
          $startDateClause[] = "DATE_SUB({$now}, INTERVAL 1 DAY ) <= {$date}";
        }

        $startDate = implode(' AND ', $startDateClause);
      }
      elseif ($actionSchedule->absolute_date) {
        $startDate = "DATEDIFF(DATE('{$now}'),'{$actionSchedule->absolute_date}') = 0";
      }

      // ( now >= date_built_from_start_time ) OR ( now = absolute_date )
      $dateClause = "reminder.id IS NULL AND {$startDate}";

      // start composing query
      $selectClause = 'SELECT ' . implode(', ', $select);
      $fromClause = "FROM $from";
      $joinClause = !empty($join) ? implode(' ', $join) : '';
      $whereClause = 'WHERE ' . implode(' AND ', $where);
      $limitWhereClause = '';
      if (!empty($limitWhere)) {
        $limitWhereClause = ' AND ' . implode(' AND ', $limitWhere);
      }

      $query = "
INSERT INTO civicrm_action_log ({$selectColumns})
{$selectClause}
{$fromClause}
{$joinClause}
LEFT JOIN {$reminderJoinClause}
{$whereClause} {$limitWhereClause} AND {$dateClause} {$notINClause}
";

      // In some cases reference_date got outdated due to many reason e.g. In Membership renewal end_date got extended
      // which means reference date mismatches with the end_date where end_date may be used as the start_action_date
      // criteria  for some schedule reminder so in order to send new reminder we INSERT new reminder with new reference_date
      // value via UNION operation
      if (strpos($selectColumns, 'reference_date') !== FALSE) {
        $dateClause = str_replace('reminder.id IS NULL', 'reminder.id IS NOT NULL', $dateClause);
        $referenceQuery = "
INSERT INTO civicrm_action_log ({$selectColumns})
{$selectClause}
{$fromClause}
{$joinClause}
 LEFT JOIN {$reminderJoinClause}
{$whereClause} {$limitWhereClause} {$notINClause} AND {$dateClause} AND
 reminder.action_date_time IS NOT NULL AND
 reminder.reference_date IS NOT NULL
GROUP BY reminder.id, reminder.reference_date
HAVING reminder.id = MAX(reminder.id) AND reminder.reference_date <> {$dateField}
";
      }

      CRM_Core_DAO::executeQuery($query, array(1 => array($actionSchedule->id, 'Integer')));

      if (!empty($referenceQuery)) {
        CRM_Core_DAO::executeQuery($referenceQuery, array(1 => array($actionSchedule->id, 'Integer')));
      }

      $isSendToAdditionalContacts = (!is_null($limitTo) && $limitTo == 0 && (!empty($addGroup) || !empty($addWhere))) ? TRUE : FALSE;
      if ($isSendToAdditionalContacts) {
        $contactTable = "civicrm_contact c";
        $addSelect = "SELECT c.id as contact_id, c.id as entity_id, 'civicrm_contact' as entity_table, {$actionSchedule->id} as action_schedule_id";
        $additionReminderClause = "civicrm_action_log reminder ON reminder.contact_id = c.id AND
          reminder.entity_id          = c.id AND
          reminder.entity_table       = 'civicrm_contact' AND
          reminder.action_schedule_id = {$actionSchedule->id}";
        $addWhereClause = '';
        if ($addWhere) {
          $addWhereClause = "AND {$addWhere}";
        }
        $insertAdditionalSql = "
INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id)
{$addSelect}
FROM ({$contactTable})
LEFT JOIN {$additionReminderClause}
{$addGroup}
WHERE c.is_deleted = 0 AND c.is_deceased = 0
{$addWhereClause}

AND reminder.id IS NULL
AND c.id NOT IN (
     SELECT rem.contact_id
     FROM civicrm_action_log rem INNER JOIN {$mapping->entity} e ON rem.entity_id = e.id
     WHERE rem.action_schedule_id = {$actionSchedule->id}
      AND rem.entity_table = '{$mapping->entity}'
    )
GROUP BY c.id
";
        CRM_Core_DAO::executeQuery($insertAdditionalSql);
      }
      // if repeat is turned ON:
      if ($actionSchedule->is_repeat) {
        $repeatEvent = ($actionSchedule->end_action == 'before' ? 'DATE_SUB' : 'DATE_ADD') . "({$dateField}, INTERVAL {$actionSchedule->end_frequency_interval} {$actionSchedule->end_frequency_unit})";

        if ($actionSchedule->repetition_frequency_unit == 'day') {
          $interval = "{$actionSchedule->repetition_frequency_interval} DAY";
        }
        elseif ($actionSchedule->repetition_frequency_unit == 'week') {
          $interval = "{$actionSchedule->repetition_frequency_interval} WEEK";
        }
        elseif ($actionSchedule->repetition_frequency_unit == 'month') {
          $interval = "{$actionSchedule->repetition_frequency_interval} MONTH";
        }
        elseif ($actionSchedule->repetition_frequency_unit == 'year') {
          $interval = "{$actionSchedule->repetition_frequency_interval} YEAR";
        }
        else {
          $interval = "{$actionSchedule->repetition_frequency_interval} HOUR";
        }

        // (now <= repeat_end_time )
        $repeatEventClause = "'{$now}' <= {$repeatEvent}";
        // diff(now && logged_date_time) >= repeat_interval
        $havingClause = "HAVING TIMESTAMPDIFF(HOUR, latest_log_time, CAST({$now} AS datetime)) >= TIMESTAMPDIFF(HOUR, latest_log_time, DATE_ADD(latest_log_time, INTERVAL $interval))";
        $groupByClause = 'GROUP BY reminder.contact_id, reminder.entity_id, reminder.entity_table';
        $selectClause .= ', MAX(reminder.action_date_time) as latest_log_time';
        //CRM-15376 - do not send our reminders if original criteria no longer applies
        // the first part of the startDateClause array is the earliest the reminder can be sent. If the
        // event (e.g membership_end_date) has changed then the reminder may no longer apply
        // @todo - this only handles events that get moved later. Potentially they might get moved earlier
        $originalEventStartDateClause = empty($startDateClause) ? '' : 'AND' . $startDateClause[0];
        $sqlInsertValues = "{$selectClause}
{$fromClause}
{$joinClause}
INNER JOIN {$reminderJoinClause}
{$whereClause} {$limitWhereClause} AND {$repeatEventClause} {$originalEventStartDateClause} {$notINClause}
{$groupByClause}
{$havingClause}";

        $valsqlInsertValues = CRM_Core_DAO::executeQuery($sqlInsertValues, array(
            1 => array(
              $actionSchedule->id,
              'Integer',
            ),
          )
        );

        $arrValues = array();
        while ($valsqlInsertValues->fetch()) {
          $arrValues[] = "( {$valsqlInsertValues->contact_id}, {$valsqlInsertValues->entity_id}, '{$valsqlInsertValues->entity_table}',{$valsqlInsertValues->action_schedule_id} )";
        }

        $valString = implode(',', $arrValues);

        if ($valString) {
          $query = '
              INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id) VALUES ' . $valString;
          CRM_Core_DAO::executeQuery($query, array(1 => array($actionSchedule->id, 'Integer')));
        }

        if ($isSendToAdditionalContacts) {
          $addSelect .= ', MAX(reminder.action_date_time) as latest_log_time';
          $sqlEndEventCheck = "
SELECT * FROM {$table}
{$whereClause} AND {$repeatEventClause} LIMIT 1";

          $daoCheck = CRM_Core_DAO::executeQuery($sqlEndEventCheck);
          if ($daoCheck->fetch()) {
            $valSqlAdditionInsert = "
{$addSelect}
FROM  {$contactTable}
{$addGroup}
INNER JOIN {$additionReminderClause}
WHERE {$addWhere} AND c.is_deleted = 0 AND c.is_deceased = 0
GROUP BY reminder.contact_id
{$havingClause}
";
            $daoForVals = CRM_Core_DAO::executeQuery($valSqlAdditionInsert);
            $addValues = array();
            while ($daoForVals->fetch()) {
              $addValues[] = "( {$daoForVals->contact_id}, {$daoForVals->entity_id}, '{$daoForVals->entity_table}',{$daoForVals->action_schedule_id} )";
            }
            $valString = implode(',', $addValues);

            if ($valString) {
              $query = '
                INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id) VALUES ' . $valString;
              CRM_Core_DAO::executeQuery($query);
            }
          }
        }
      }
    }
  }

  /**
   * @param $field
   *
   * @return null|string
   */
  public static function permissionedRelationships($field) {
    $query = '
SELECT    cm.id AS owner_id, cm.contact_id AS owner_contact, m.id AS slave_id, m.contact_id AS slave_contact, cmt.relationship_type_id AS relation_type, rel.contact_id_a, rel.contact_id_b, rel.is_permission_a_b, rel.is_permission_b_a
FROM      civicrm_membership m
LEFT JOIN civicrm_membership cm ON cm.id = m.owner_membership_id
LEFT JOIN civicrm_membership_type cmt ON cmt.id = m.membership_type_id
LEFT JOIN civicrm_relationship rel ON ( ( rel.contact_id_a = m.contact_id AND rel.contact_id_b = cm.contact_id AND rel.relationship_type_id = cmt.relationship_type_id )
                                        OR ( rel.contact_id_a = cm.contact_id AND rel.contact_id_b = m.contact_id AND rel.relationship_type_id = cmt.relationship_type_id ) )
WHERE     m.owner_membership_id IS NOT NULL AND
          ( rel.is_permission_a_b = 0 OR rel.is_permission_b_a = 0)

';
    $excludeIds = array();
    $dao = CRM_Core_DAO::executeQuery($query, array());
    while ($dao->fetch()) {
      if ($dao->slave_contact == $dao->contact_id_a && $dao->is_permission_a_b == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
      elseif ($dao->slave_contact == $dao->contact_id_b && $dao->is_permission_b_a == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
    }

    if (!empty($excludeIds)) {
      $clause = "AND {$field} NOT IN ( " . implode(', ', $excludeIds) . ' ) ';
      return $clause;
    }
    return NULL;
  }

  /**
   * @param null $now
   * @param array $params
   *
   * @return array
   */
  public static function processQueue($now = NULL, $params = array()) {
    $now = $now ? CRM_Utils_Time::setTime($now) : CRM_Utils_Time::getTime();

    $mappings = CRM_Core_BAO_ActionSchedule::getMappings();
    foreach ($mappings as $mappingID => $mapping) {
      CRM_Core_BAO_ActionSchedule::buildRecipientContacts($mappingID, $now, $params);
      CRM_Core_BAO_ActionSchedule::sendMailings($mappingID, $now);
    }

    $result = array(
      'is_error' => 0,
      'messages' => ts('Sent all scheduled reminders successfully'),
    );
    return $result;
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

    $params = array(
      1 => array($mappingID, 'Integer'),
      2 => array($id, 'Integer'),
    );
    return CRM_Core_DAO::singleValueQuery($queryString, $params);
  }

  /**
   * @param int $mappingID
   * @param $recipientType
   *
   * @return array
   */
  public static function getRecipientListing($mappingID, $recipientType) {
    $options = array();
    if (!$mappingID || !$recipientType) {
      return $options;
    }

    $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => $mappingID,
    )));

    switch ($mapping->entity) {
      case 'civicrm_participant':
        $eventContacts = CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'name', TRUE, FALSE, 'name');
        if (empty($eventContacts[$recipientType])) {
          return $options;
        }
        if ($eventContacts[$recipientType] == 'participant_role') {
          $options = CRM_Event_PseudoConstant::participantRole();
        }
        break;
    }

    return $options;
  }

  /**
   * @param $communication_language
   * @param $preferred_language
   */
  public static function setCommunicationLanguage($communication_language, $preferred_language) {
    $config = CRM_Core_Config::singleton();
    $language = $config->lcMessages;

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
    if (!in_array($language, $languages)) {
      $language = $config->lcMessages;
    }

    // change the language
    $i18n = CRM_Core_I18n::singleton();
    $i18n->setLanguage($language);
  }

  /**
   * Save a record about the delivery of a reminder email.
   *
   * WISHLIST: Instead of saving $actionSchedule->body_html, call this immediately after
   * sending the message and pass in the fully rendered text of the message.
   *
   * @param CRM_Core_DAO_ActionSchedule $actionSchedule
   * @param Civi\ActionSchedule\Mapping $mapping
   * @param int $contactID
   * @param int $entityID
   * @param int|NULL $caseID
   * @throws CRM_Core_Exception
   */
  protected static function createMailingActivity($actionSchedule, $mapping, $contactID, $entityID, $caseID) {
    $session = CRM_Core_Session::singleton();

    if ($mapping->entity == 'civicrm_membership') {
      $activityTypeID
        = CRM_Core_OptionGroup::getValue('activity_type', 'Membership Renewal Reminder', 'name');
    }
    else {
      $activityTypeID
        = CRM_Core_OptionGroup::getValue('activity_type', 'Reminder Sent', 'name');
    }

    $activityParams = array(
      'subject' => $actionSchedule->title,
      'details' => $actionSchedule->body_html,
      'source_contact_id' => $session->get('userID') ? $session->get('userID') : $contactID,
      'target_contact_id' => $contactID,
      'activity_date_time' => CRM_Utils_Time::getTime('YmdHis'),
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
      'activity_type_id' => $activityTypeID,
      'source_record_id' => $entityID,
    );
    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    //file reminder on case if source activity is a case activity
    if (!empty($caseID)) {
      $caseActivityParams = array();
      $caseActivityParams['case_id'] = $caseID;
      $caseActivityParams['activity_id'] = $activity->id;
      CRM_Case_BAO_Case::processCaseActivity($caseActivityParams);
    }
  }

  /**
   * @param $mapping
   * @param $actionSchedule
   * @return string
   */
  protected static function prepareMailingQuery($mapping, $actionSchedule) {
    $extraSelect = $extraJoin = $extraWhere = $extraOn = '';

    if ($mapping->entity == 'civicrm_activity') {
      $compInfo = CRM_Core_Component::getEnabledComponents();
      $extraSelect = ', ov.label as activity_type, e.id as activity_id';
      $extraJoin = "
INNER JOIN civicrm_option_group og ON og.name = 'activity_type'
INNER JOIN civicrm_option_value ov ON e.activity_type_id = ov.value AND ov.option_group_id = og.id";
      $extraOn = ' AND e.is_current_revision = 1 AND e.is_deleted = 0 ';
      if ($actionSchedule->limit_to == 0) {
        $extraJoin = "
LEFT JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT JOIN civicrm_option_value ov ON e.activity_type_id = ov.value AND ov.option_group_id = og.id";
      }

      //join for caseId
      // if CiviCase component is enabled
      if (array_key_exists('CiviCase', $compInfo)) {
        $extraSelect .= ", civicrm_case_activity.case_id as case_id";
        $extraJoin .= "
            LEFT JOIN `civicrm_case_activity` ON `e`.`id` = `civicrm_case_activity`.`activity_id`";
      }
    }

    if ($mapping->entity == 'civicrm_participant') {
      $extraSelect = ', ov.label as event_type, ev.title, ev.id as event_id, ev.start_date, ev.end_date, ev.summary, ev.description, address.street_address, address.city, address.state_province_id, address.postal_code, email.email as contact_email, phone.phone as contact_phone ';

      $extraJoin = "
INNER JOIN civicrm_event ev ON e.event_id = ev.id
INNER JOIN civicrm_option_group og ON og.name = 'event_type'
INNER JOIN civicrm_option_value ov ON ev.event_type_id = ov.value AND ov.option_group_id = og.id
LEFT  JOIN civicrm_loc_block lb ON lb.id = ev.loc_block_id
LEFT  JOIN civicrm_address address ON address.id = lb.address_id
LEFT  JOIN civicrm_email email ON email.id = lb.email_id
LEFT  JOIN civicrm_phone phone ON phone.id = lb.phone_id
";
      if ($actionSchedule->limit_to == 0) {
        $extraJoin = "
LEFT JOIN civicrm_event ev ON e.event_id = ev.id
LEFT JOIN civicrm_option_group og ON og.name = 'event_type'
LEFT JOIN civicrm_option_value ov ON ev.event_type_id = ov.value AND ov.option_group_id = og.id
LEFT JOIN civicrm_loc_block lb ON lb.id = ev.loc_block_id
LEFT JOIN civicrm_address address ON address.id = lb.address_id
LEFT JOIN civicrm_email email ON email.id = lb.email_id
LEFT JOIN civicrm_phone phone ON phone.id = lb.phone_id
";
      }
    }

    if ($mapping->entity == 'civicrm_membership') {
      $extraSelect = ', mt.minimum_fee as fee, e.id as id , e.join_date, e.start_date, e.end_date, ms.name as status, mt.name as type';
      $extraJoin = '
 INNER JOIN civicrm_membership_type mt ON e.membership_type_id = mt.id
 INNER JOIN civicrm_membership_status ms ON e.status_id = ms.id';

      if ($actionSchedule->limit_to == 0) {
        $extraJoin = '
 LEFT JOIN civicrm_membership_type mt ON e.membership_type_id = mt.id
 LEFT JOIN civicrm_membership_status ms ON e.status_id = ms.id';
      }
    }

    $entityJoinClause = "INNER JOIN {$mapping->entity} e ON e.id = reminder.entity_id";
    if ($actionSchedule->limit_to == 0) {
      $entityJoinClause = "LEFT JOIN {$mapping->entity} e ON e.id = reminder.entity_id";
      $extraWhere .= " AND (e.id = reminder.entity_id OR reminder.entity_table = 'civicrm_contact')";
    }
    $entityJoinClause .= $extraOn;

    $query = "
SELECT reminder.id as reminderID, reminder.contact_id as contactID, reminder.entity_table as entityTable, reminder.*, e.id as entityID, e.* {$extraSelect}
FROM  civicrm_action_log reminder
{$entityJoinClause}
{$extraJoin}
WHERE reminder.action_schedule_id = %1 AND reminder.action_date_time IS NULL
{$extraWhere}";
    return $query;
  }

  /**
   * @param TokenRow $tokenRow
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param int $toContactID
   * @throws CRM_Core_Exception
   * @return array
   *   List of error messages.
   */
  protected static function sendReminderSms($tokenRow, $schedule, $toContactID) {
    $toPhoneNumber = self::pickSmsPhoneNumber($toContactID);
    if (!$toPhoneNumber) {
      return array("sms_phone_missing" => "Couldn't find recipient's phone number.");
    }

    $messageSubject = $tokenRow->render('subject');
    $sms_body_text = $tokenRow->render('sms_body_text');

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID') ? $session->get('userID') : $tokenRow->context['contactId'];
    $smsParams = array(
      'To' => $toPhoneNumber,
      'provider_id' => $schedule->sms_provider_id,
      'activity_subject' => $messageSubject,
    );
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      'SMS',
      'name'
    );
    $activityParams = array(
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $messageSubject,
      'details' => $sms_body_text,
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
    );

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    CRM_Activity_BAO_Activity::sendSMSMessage($tokenRow->context['contactId'],
      $sms_body_text,
      $smsParams,
      $activity->id,
      $userID
    );

    return array();
  }

  /**
   * @param CRM_Core_DAO_ActionSchedule $actionSchedule
   * @return string
   *   Ex: "Alice <alice@example.org>".
   */
  protected static function pickFromEmail($actionSchedule) {
    $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
    $fromEmailAddress = "$domainValues[0] <$domainValues[1]>";
    if ($actionSchedule->from_email) {
      $fromEmailAddress = "$actionSchedule->from_name <$actionSchedule->from_email>";
      return $fromEmailAddress;
    }
    return $fromEmailAddress;
  }

  /**
   * @param TokenRow $tokenRow
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param int $toContactID
   * @return array
   *   List of error messages.
   */
  protected static function sendReminderEmail($tokenRow, $schedule, $toContactID) {
    $toEmail = CRM_Contact_BAO_Contact::getPrimaryEmail($toContactID);
    if (!$toEmail) {
      return array("email_missing" => "Couldn't find recipient's email address.");
    }

    $body_text = $tokenRow->render('body_text');
    $body_html = $tokenRow->render('body_html');
    if (!$schedule->body_text) {
      $body_text = CRM_Utils_String::htmlToText($body_html);
    }

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = array(
      'groupName' => 'Scheduled Reminder Sender',
      'from' => self::pickFromEmail($schedule),
      'toName' => $tokenRow->context['contact']['display_name'],
      'toEmail' => $toEmail,
      'subject' => $tokenRow->render('subject'),
      'entity' => 'action_schedule',
      'entity_id' => $schedule->id,
    );

    if (!$body_html || $tokenRow->context['contact']['preferred_mail_format'] == 'Text' ||
      $tokenRow->context['contact']['preferred_mail_format'] == 'Both'
    ) {
      // render the &amp; entities in text mode, so that the links work
      $mailParams['text'] = str_replace('&amp;', '&', $body_text);
    }
    if ($body_html && ($tokenRow->context['contact']['preferred_mail_format'] == 'HTML' ||
        $tokenRow->context['contact']['preferred_mail_format'] == 'Both'
      )
    ) {
      $mailParams['html'] = $body_html;
    }
    $result = CRM_Utils_Mail::send($mailParams);
    if (!$result || is_a($result, 'PEAR_Error')) {
      return array('email_fail' => 'Failed to send message');
    }

    return array();
  }

  /**
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param \Civi\ActionSchedule\Mapping $mapping
   * @return \Civi\Token\TokenProcessor
   */
  protected static function createTokenProcessor($schedule, $mapping) {
    $tp = new \Civi\Token\TokenProcessor(\Civi\Core\Container::singleton()->get('dispatcher'), array(
      'controller' => __CLASS__,
      'actionSchedule' => $schedule,
      'actionMapping' => $mapping,
      'smarty' => TRUE,
    ));
    $tp->addMessage('body_text', $schedule->body_text, 'text/plain');
    $tp->addMessage('body_html', $schedule->body_html, 'text/html');
    $tp->addMessage('sms_body_text', $schedule->sms_body_text, 'text/plain');
    $tp->addMessage('subject', $schedule->subject, 'text/plain');
    return $tp;
  }

  /**
   * @param $dao
   * @return string|NULL
   */
  protected static function pickSmsPhoneNumber($smsToContactId) {
    $toPhoneNumbers = CRM_Core_BAO_Phone::allPhones($smsToContactId, FALSE, 'Mobile', array(
      'is_deceased' => 0,
      'is_deleted' => 0,
      'do_not_sms' => 0,
    ));
    //to get primary mobile ph,if not get a first mobile phONE
    if (!empty($toPhoneNumbers)) {
      $toPhoneNumberDetails = reset($toPhoneNumbers);
      $toPhoneNumber = CRM_Utils_Array::value('phone', $toPhoneNumberDetails);
      return $toPhoneNumber;
    }
    return NULL;
  }

}
