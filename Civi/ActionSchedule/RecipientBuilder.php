<?php
namespace Civi\ActionSchedule;

class RecipientBuilder {

  private $now;
  private $contactDateFields = array(
    'birth_date',
    'created_date',
    'modified_date',
  );

  /**
   * @var \CRM_Core_DAO_ActionSchedule
   */
  private $actionSchedule;

  /**
   * @var Mapping
   */
  private $mapping;

  public function __construct($now, $actionSchedule, $mapping) {
    $this->now = $now;
    $this->actionSchedule = $actionSchedule;
    $this->mapping = $mapping;
  }

  /**
   * Fill the civicrm_action_log with any new/missing TODOs.
   *
   * @throws \CRM_Core_Exception
   */
  public function build() {
    // Generate action_log's for new, first-time alerts to related contacts.
    $this->buildRelFirstPass();

    // Generate action_log's for new, first-time alerts to additional contacts.
    if ($this->prepareAddlFilter('c.id')) {
      $this->buildAddlFirstPass();
    }

    // Generate action_log's for repeated, follow-up alerts to related contacts.
    if ($this->actionSchedule->is_repeat) {
      $this->buildRelRepeatPass();
    }

    // Generate action_log's for repeated, follow-up alerts to additional contacts.
    if ($this->actionSchedule->is_repeat && $this->prepareAddlFilter('c.id')) {
      $this->buildAddlRepeatPass();
    }
  }

  /**
   * @throws \Exception
   */
  protected function buildRelFirstPass() {
    $query = $this->prepareQuery('rel-first');

    $startDateClauses = $this->prepareStartDateClauses($query['casDateField']);

    $firstQuery = $query->copy()
      ->merge($this->selectIntoActionLog('rel-first', $query))
      ->merge($this->joinReminder('LEFT JOIN', 'rel', $query))
      ->where("reminder.id IS NULL")
      ->where($startDateClauses)
      ->strict()
      ->toSQL();
    \CRM_Core_DAO::executeQuery($firstQuery);

    // In some cases reference_date got outdated due to many reason e.g. In Membership renewal end_date got extended
    // which means reference date mismatches with the end_date where end_date may be used as the start_action_date
    // criteria  for some schedule reminder so in order to send new reminder we INSERT new reminder with new reference_date
    // value via UNION operation
    if (!empty($query['casUseReferenceDate'])) {
      $referenceQuery = $query->copy()
        ->merge($this->selectIntoActionLog('rel-first', $query))
        ->merge($this->joinReminder('LEFT JOIN', 'rel', $query))
        ->where("reminder.id IS NOT NULL")
        ->where($startDateClauses)
        ->where("reminder.action_date_time IS NOT NULL AND reminder.reference_date IS NOT NULL")
        ->groupBy("reminder.id, reminder.reference_date")
        ->having("reminder.id = MAX(reminder.id) AND reminder.reference_date <> !casDateField")
        ->strict()
        ->toSQL();
      \CRM_Core_DAO::executeQuery($referenceQuery);
    }
  }

  /**
   * @throws \Exception
   */
  protected function buildAddlFirstPass() {
    $query = $this->prepareQuery('addl-first');

    $insertAdditionalSql = \CRM_Utils_SQL_Select::from("civicrm_contact c")
      ->merge($this->selectIntoActionLog('addl-first', $query))
      ->merge($this->joinReminder('LEFT JOIN', 'addl', $query))
      ->where("c.is_deleted = 0 AND c.is_deceased = 0")
      ->merge($this->prepareAddlFilter('c.id'))
      ->where("c.id NOT IN (
             SELECT rem.contact_id
             FROM civicrm_action_log rem INNER JOIN {$this->mapping->entity} e ON rem.entity_id = e.id
             WHERE rem.action_schedule_id = {$this->actionSchedule->id}
             AND rem.entity_table = '{$this->mapping->entity}'
             )")
      // Where does e.id come from here? ^^^
      ->groupBy("c.id")
      ->strict()
      ->toSQL();
    \CRM_Core_DAO::executeQuery($insertAdditionalSql);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  protected function buildRelRepeatPass() {
    $query = $this->prepareQuery('rel-repeat');
    $startDateClauses = $this->prepareStartDateClauses($query['casDateField']);

    // CRM-15376 - do not send our reminders if original criteria no longer applies
    // the first part of the startDateClause array is the earliest the reminder can be sent. If the
    // event (e.g membership_end_date) has changed then the reminder may no longer apply
    // @todo - this only handles events that get moved later. Potentially they might get moved earlier
    $repeatInsert = $query
      ->merge($this->joinReminder('INNER JOIN', 'rel', $query))
      ->merge($this->selectActionLogFields('rel-repeat', $query))
      ->select("MAX(reminder.action_date_time) as latest_log_time")
      ->merge($this->prepareRepetitionEndFilter($query['casDateField']))
      ->where($this->actionSchedule->start_action_date ? $startDateClauses[0] : array())
      ->groupBy("reminder.contact_id, reminder.entity_id, reminder.entity_table")
      ->having("TIMEDIFF(!now, latest_log_time) >= !hrs")
      ->param(array(
        '!now' => $this->now, // why not @now ?
        '!hrs' => $this->parseSqlHrs(),
      ))
      ->strict()
      ->toSQL();

    // For unknown reasons, we manually insert each row. Why not change
    // selectActionLogFields() to selectIntoActionLog() above?

    $arrValues = \CRM_Core_DAO::executeQuery($repeatInsert)->fetchAll();
    if ($arrValues) {
      \CRM_Core_DAO::executeQuery(
        \CRM_Utils_SQL_Insert::into('civicrm_action_log')
          ->columns(array('contact_id', 'entity_id', 'entity_table', 'action_schedule_id'))
          ->rows($arrValues)
          ->toSQL()
      );
    }
  }
  /**
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  protected function buildAddlRepeatPass() {
    $query = $this->prepareQuery('addl-repeat');

    $addlCheck = \CRM_Utils_SQL_Select::from($query['casAddlCheckFrom'])
      ->select('*')
      ->merge($query, array('wheres'))// why only where? why not the joins?
      ->merge($this->prepareRepetitionEndFilter($query['casDateField']))
      ->limit(1)
      ->strict()
      ->toSQL();

    $daoCheck = \CRM_Core_DAO::executeQuery($addlCheck);
    if ($daoCheck->fetch()) {
      $repeatInsertAddl = \CRM_Utils_SQL_Select::from('civicrm_contact c')
        ->merge($this->selectActionLogFields('addl-repeat', $query))
        ->merge($this->joinReminder('INNER JOIN', 'addl', $query))
        ->select("MAX(reminder.action_date_time) as latest_log_time")
        ->merge($this->prepareAddlFilter('c.id'))
        ->where("c.is_deleted = 0 AND c.is_deceased = 0")
        ->groupBy("reminder.contact_id")
        ->having("TIMEDIFF(!now, latest_log_time) >= !hrs")
        ->param(array(
          '!now' => $this->now, // FIXME: use @now ?
          '!hrs' => $this->parseSqlHrs(),
        ))
        ->strict()
        ->toSQL();

      // For unknown reasons, we manually insert each row. Why not change
      // selectActionLogFields() to selectIntoActionLog() above?

      $addValues = \CRM_Core_DAO::executeQuery($repeatInsertAddl)->fetchAll();
      if ($addValues) {
        \CRM_Core_DAO::executeQuery(
          \CRM_Utils_SQL_Insert::into('civicrm_action_log')
            ->columns(array('contact_id', 'entity_id', 'entity_table', 'action_schedule_id'))
            ->rows($addValues)
            ->toSQL()
        );
      }
    }
  }

  /**
   * @param string $phase
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   */
  protected function prepareQuery($phase) {
    /** @var \CRM_Utils_SQL_Select $query */

    if ($this->mapping->entity == 'civicrm_activity') {
      $query = $this->prepareActivityQuery($phase);
    }
    elseif ($this->mapping->entity == 'civicrm_participant') {
      $query = $this->prepareParticipantQuery($phase);
    }
    elseif ($this->mapping->entity == 'civicrm_membership') {
      $query = $this->prepareMembershipQuery($phase);
    }
    elseif ($this->mapping->entity == 'civicrm_contact') {
      $query = $this->prepareContactQuery($phase);
    }
    else {
      throw new \CRM_Core_Exception("Unrecognized entity: {$this->mapping->entity}");
    }

    $query->param(array(
      'casActionScheduleId' => $this->actionSchedule->id,
      'casMappingId' => $this->mapping->id,
      'casMappingEntity' => $this->mapping->entity,
    ));

    if ($this->actionSchedule->limit_to /*1*/) {
      $query->merge($this->prepareContactFilter($query['casContactIdField']));
    }

    if (empty($query['casContactTableAlias'])) {
      $query['casContactTableAlias'] = 'c';
      $query->join('c', "INNER JOIN civicrm_contact c ON c.id = !casContactIdField AND c.is_deleted = 0 AND c.is_deceased = 0 ");
    }
    $multilingual = \CRM_Core_I18n::isMultilingual();
    if ($multilingual && !empty($this->actionSchedule->filter_contact_language)) {
      $query->where($this->prepareLanguageFilter($query['casContactTableAlias']));
    }

    return $query;
  }

  /**
   * @param $actionSchedule
   * @return int|string
   */
  protected function parseSqlHrs() {
    $actionSchedule = $this->actionSchedule;
    if ($actionSchedule->repetition_frequency_unit == 'day') {
      $hrs = 24 * $actionSchedule->repetition_frequency_interval;
    }
    elseif ($actionSchedule->repetition_frequency_unit == 'week') {
      $hrs = 24 * $actionSchedule->repetition_frequency_interval * 7;
    }
    elseif ($actionSchedule->repetition_frequency_unit == 'month') {
      $hrs = "24*(DATEDIFF(DATE_ADD(latest_log_time, INTERVAL 1 MONTH ), latest_log_time))";
    }
    elseif ($actionSchedule->repetition_frequency_unit == 'year') {
      $hrs = "24*(DATEDIFF(DATE_ADD(latest_log_time, INTERVAL 1 YEAR ), latest_log_time))";
    }
    else {
      $hrs = $actionSchedule->repetition_frequency_interval;
    }
    return "TIME('{$hrs}:00:00')";
  }

  /**
   * Prepare filter options for limiting by contact ID or group ID.
   *
   * @param string $contactIdField
   * @return \CRM_Utils_SQL_Select
   */
  protected function prepareContactFilter($contactIdField) {
    $actionSchedule = $this->actionSchedule;

    if ($actionSchedule->group_id) {
      if ($this->isSmartGroup($actionSchedule->group_id)) {
        // Check that the group is in place in the cache and up to date
        \CRM_Contact_BAO_GroupContactCache::check($actionSchedule->group_id);
        return \CRM_Utils_SQL_Select::fragment()
          ->join('grp', "INNER JOIN civicrm_group_contact_cache grp ON {$contactIdField} = grp.contact_id")
          ->where(" grp.group_id IN ({$actionSchedule->group_id})");
      }
      else {
        return \CRM_Utils_SQL_Select::fragment()
          ->join('grp', " INNER JOIN civicrm_group_contact grp ON {$contactIdField} = grp.contact_id AND grp.status = 'Added'")
          ->where(" grp.group_id IN ({$actionSchedule->group_id})");
      }
    }
    elseif (!empty($actionSchedule->recipient_manual)) {
      $rList = \CRM_Utils_Type::escape($actionSchedule->recipient_manual, 'String');
      return \CRM_Utils_SQL_Select::fragment()
        ->where("{$contactIdField} IN ({$rList})");
    }
    return NULL;
  }

  /**
   * @param $actionSchedule
   * @param $contactTableAlias
   * @return string
   */
  protected function prepareLanguageFilter($contactTableAlias) {
    $actionSchedule = $this->actionSchedule;

    // get language filter for the schedule
    $filter_contact_language = explode(\CRM_Core_DAO::VALUE_SEPARATOR, $actionSchedule->filter_contact_language);
    $w = '';
    if (($key = array_search(\CRM_Core_I18n::NONE, $filter_contact_language)) !== FALSE) {
      $w .= "{$contactTableAlias}.preferred_language IS NULL OR {$contactTableAlias}.preferred_language = '' OR ";
      unset($filter_contact_language[$key]);
    }
    if (count($filter_contact_language) > 0) {
      $w .= "{$contactTableAlias}.preferred_language IN ('" . implode("','", $filter_contact_language) . "')";
    }
    $w = "($w)";
    return $w;
  }

  /**
   * @param $dateField
   * @return array
   */
  protected function prepareStartDateClauses($dateField) {
    $actionSchedule = $this->actionSchedule;
    $mapping = $this->mapping;
    $now = $this->now;
    $startDateClauses = array();
    if ($actionSchedule->start_action_date) {
      $op = ($actionSchedule->start_action_condition == 'before' ? '<=' : '>=');
      $operator = ($actionSchedule->start_action_condition == 'before' ? 'DATE_SUB' : 'DATE_ADD');
      $date = $operator . "({$dateField}, INTERVAL {$actionSchedule->start_action_offset} {$actionSchedule->start_action_unit})";
      $startDateClauses[] = "'{$now}' >= {$date}";
      // This is weird. Waddupwidat?
      if ($mapping->entity == 'civicrm_participant') {
        $startDateClauses[] = $operator . "({$now}, INTERVAL 1 DAY ) {$op} " . $dateField;
      }
      else {
        $startDateClauses[] = "DATE_SUB({$now}, INTERVAL 1 DAY ) <= {$date}";
      }
    }
    elseif ($actionSchedule->absolute_date) {
      $startDateClauses[] = "DATEDIFF(DATE('{$now}'),'{$actionSchedule->absolute_date}') = 0";
    }
    return $startDateClauses;
  }

  /**
   * @param int $groupId
   * @return bool
   */
  protected function isSmartGroup($groupId) {
    // Then decide which table to join onto the query
    $group = \CRM_Contact_DAO_Group::getTableName();

    // Get the group information
    $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
WHERE      $group.id = {$groupId}
";

    $groupDAO = \CRM_Core_DAO::executeQuery($sql);
    if (
      $groupDAO->fetch() &&
      !empty($groupDAO->saved_search_id)
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param string $dateField
   * @return \CRM_Utils_SQL_Select
   */
  protected function prepareRepetitionEndFilter($dateField) {
    $repeatEventDateExpr = ($this->actionSchedule->end_action == 'before' ? 'DATE_SUB' : 'DATE_ADD')
      . "({$dateField}, INTERVAL {$this->actionSchedule->end_frequency_interval} {$this->actionSchedule->end_frequency_unit})";

    return \CRM_Utils_SQL_Select::fragment()
      ->where("@now <= !repetitionEndDate")
      ->param(array(
        '@now' => $this->now,
        '!repetitionEndDate' => $repeatEventDateExpr,
      ));
  }

  /**
   * @return array
   */
  protected function prepareMembershipPermissionsFilter() {
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
    $dao = \CRM_Core_DAO::executeQuery($query, array());
    while ($dao->fetch()) {
      if ($dao->slave_contact == $dao->contact_id_a && $dao->is_permission_a_b == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
      elseif ($dao->slave_contact == $dao->contact_id_b && $dao->is_permission_b_a == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
    }

    if (!empty($excludeIds)) {
      return \CRM_Utils_SQL_Select::fragment()
        ->where("!casContactIdField NOT IN (#excludeMemberIds)")
        ->param(array(
          '#excludeMemberIds' => $excludeIds,
        ));
    }
    return NULL;
  }

  /**
   * @param string $contactIdField
   * @return \CRM_Utils_SQL_Select|null
   */
  protected function prepareAddlFilter($contactIdField) {
    $contactAddlFilter = NULL;
    if ($this->actionSchedule->limit_to !== NULL && !$this->actionSchedule->limit_to /*0*/) {
      $contactAddlFilter = $this->prepareContactFilter($contactIdField);
    }
    return $contactAddlFilter;
  }

  /**
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   */
  protected function prepareContactQuery($phase) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_status);

    // FIXME: This assumes that $values only has one field, but UI shows multiselect.
    if (count($selectedValues) != 1 || !isset($selectedValues[0])) {
      throw new \CRM_Core_Exception("Error: Scheduled reminders may only have one contact field.");
    }
    elseif (in_array($selectedValues[0], $this->contactDateFields)) {
      $dateDBField = $selectedValues[0];
      $query = \CRM_Utils_SQL_Select::from("{$this->mapping->entity} e");
      $query->param(array(
        'casAddlCheckFrom' => 'civicrm_contact e',
        'casContactIdField' => 'e.id',
        'casEntityIdField' => 'e.id',
        'casContactTableAlias' => 'e',
      ));
      $query->where('e.is_deleted = 0 AND e.is_deceased = 0');
    }
    else {
      //custom field
      $customFieldParams = array('id' => substr($selectedValues[0], 7));
      $customGroup = $customField = array();
      \CRM_Core_BAO_CustomField::retrieve($customFieldParams, $customField);
      $dateDBField = $customField['column_name'];
      $customGroupParams = array('id' => $customField['custom_group_id'], $customGroup);
      \CRM_Core_BAO_CustomGroup::retrieve($customGroupParams, $customGroup);
      $query = \CRM_Utils_SQL_Select::from("{$customGroup['table_name']} e");
      $query->param(array(
        'casAddlCheckFrom' => "{$customGroup['table_name']} e",
        'casContactIdField' => 'e.entity_id',
        'casEntityIdField' => 'e.id',
        'casContactTableAlias' => NULL,
      ));
      $query->where('1'); // possible to have no "where" in this case
    }

    $query['casDateField'] = 'e.' . $dateDBField;

    if (in_array(2, $selectedStatuses)) {
      $query['casAnniversaryMode'] = 1;
      $query['casDateField'] = 'DATE_ADD(' . $query['casDateField'] . ', INTERVAL ROUND(DATEDIFF(DATE(' . $this->now . '), ' . $query['casDateField'] . ') / 365) YEAR)';
    }

    return $query;
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  protected function prepareMembershipQuery($phase) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->mapping->entity} e");
    $query['casAddlCheckFrom'] = 'civicrm_membership e';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = str_replace('membership_', 'e.', $this->actionSchedule->start_action_date);

    if (in_array(2, $selectedStatuses)) {
      //auto-renew memberships
      $query->where("e.contribution_recur_id IS NOT NULL");
    }
    elseif (in_array(1, $selectedStatuses)) {
      $query->where("e.contribution_recur_id IS NULL");
    }

    if (!empty($selectedValues)) {
      $query->where("e.membership_type_id IN (@memberTypeValues)")
        ->param('memberTypeValues', $selectedValues);
    }
    else {
      $query->where("e.membership_type_id IS NULL");
    }

    $query->where("( e.is_override IS NULL OR e.is_override = 0 )");
    $query->merge($this->prepareMembershipPermissionsFilter());
    $query->where("e.status_id IN (#memberStatus)")
      ->param('memberStatus', \CRM_Member_PseudoConstant::membershipStatus(NULL, "(is_current_member = 1 OR name = 'Expired')", 'id'));

    // Why is this only for civicrm_membership?
    if ($this->actionSchedule->start_action_date && $this->actionSchedule->is_repeat == FALSE) {
      $query['casUseReferenceDate'] = TRUE;
    }

    return $query;
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  protected function prepareParticipantQuery($phase) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->mapping->entity} e");
    $query['casAddlCheckFrom'] = 'civicrm_event r';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = str_replace('event_', 'r.', $this->actionSchedule->start_action_date);

    $query->join('r', 'INNER JOIN civicrm_event r ON e.event_id = r.id');
    if ($this->actionSchedule->recipient_listing && $this->actionSchedule->limit_to) {
      switch (\CRM_Utils_Array::value($this->actionSchedule->recipient, $this->mapping->getRecipientOptions())) {
        case 'participant_role':
          $query->where("e.role_id IN (#recipList)")
            ->param('recipList', \CRM_Utils_Array::explodePadded($this->actionSchedule->recipient_listing));
          break;

        default:
          break;
      }
    }

    // build where clause
    if (!empty($selectedValues)) {
      $valueField = ($this->mapping->id == \CRM_Core_ActionScheduleTmp::EVENT_TYPE_MAPPING_ID) ? 'event_type_id' : 'id';
      $query->where("r.{$valueField} IN (@selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    else {
      $query->where(($this->mapping->id == \CRM_Core_ActionScheduleTmp::EVENT_TYPE_MAPPING_ID) ? "r.event_type_id IS NULL" : "r.id IS NULL");
    }

    $query->where('r.is_active = 1');
    $query->where('r.is_template = 0');

    // participant status criteria not to be implemented for additional recipients
    if (!empty($selectedStatuses)) {
      switch ($phase) {
        case 'rel-first':
        case 'rel-repeat':
          $query->where("e.status_id IN (#selectedStatuses)")
            ->param('selectedStatuses', $selectedStatuses);
          break;

      }

    }
    return $query;
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  protected function prepareActivityQuery($phase) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($this->actionSchedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->mapping->entity} e");
    $query['casAddlCheckFrom'] = 'civicrm_activity e';
    $query['casContactIdField'] = 'r.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = 'e.activity_date_time';

    if (!is_null($this->actionSchedule->limit_to)) {
      $activityContacts = \CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      if ($this->actionSchedule->limit_to == 0 || !isset($activityContacts[$this->actionSchedule->recipient])) {
        $recipientTypeId = \CRM_Utils_Array::key('Activity Targets', $activityContacts);
      }
      else {
        $recipientTypeId = $this->actionSchedule->recipient;
      }
      $query->join('r', "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$recipientTypeId}");
    }
    // build where clause
    if (!empty($selectedValues)) {
      $query->where("e.activity_type_id IN (#selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    else {
      $query->where("e.activity_type_id IS NULL");
    }

    if (!empty($selectedStatuses)) {
      $query->where("e.status_id IN (#selectedStatuss)")
        ->param('selectedStatuss', $selectedStatuses);
    }
    $query->where('e.is_current_revision = 1 AND e.is_deleted = 0');

    return $query;
  }

  /**
   * Generate a query fragment like for populating
   * action logs, e.g.
   *
   * "SELECT contact_id, entity_id, entity_table, action schedule_id"
   *
   * @param string $phase
   * @param \CRM_Utils_SQL_Select $query
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   */
  protected function selectActionLogFields($phase, $query) {
    switch ($phase) {
      case 'rel-first':
      case 'rel-repeat':
        $fragment = \CRM_Utils_SQL_Select::fragment();
        // CRM-15376: We are not tracking the reference date for 'repeated' schedule reminders.
        if (!empty($query['casUseReferenceDate'])) {
          $fragment->select($query['casDateField']);
        }
        $fragment->select(
          array(
            "!casContactIdField as contact_id",
            "!casEntityIdField as entity_id",
            "@casMappingEntity as entity_table",
            "#casActionScheduleId as action_schedule_id",
          )
        );
        break;

      case 'addl-first':
      case 'addl-repeat':
        $fragment = \CRM_Utils_SQL_Select::fragment();
        $fragment->select(
          array(
            "c.id as contact_id",
            "c.id as entity_id",
            "'civicrm_contact' as entity_table",
            "#casActionScheduleId as action_schedule_id",
          )
        );
        break;

      default:
        throw new \CRM_Core_Exception("Unrecognized phase: $phase");
    }
    return $fragment;
  }

  /**
   * Generate a query fragment like for populating
   * action logs, e.g.
   *
   * "INSERT INTO civicrm_action_log (...) SELECT (...)"
   *
   * @param string $phase
   * @param \CRM_Utils_SQL_Select $query
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   */
  protected function selectIntoActionLog($phase, $query) {
    $actionLogColumns = array(
      "contact_id",
      "entity_id",
      "entity_table",
      "action_schedule_id",
    );
    if ($phase === 'rel-first' || $phase === 'rel-repeat') {
      if (!empty($query['casUseReferenceDate'])) {
        array_unshift($actionLogColumns, 'reference_date');
      }
    }

    return $this->selectActionLogFields($phase, $query)
      ->insertInto('civicrm_action_log', $actionLogColumns);
  }

  /**
   * Add a JOIN clause like "INNER JOIN civicrm_action_log reminder ON...".
   *
   * @param string $joinType
   *   Join type (eg INNER JOIN, LEFT JOIN).
   * @param string $for
   *    Ex: 'rel', 'addl'.
   * @param \CRM_Utils_SQL_Select $query
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   */
  protected function joinReminder($joinType, $for, $query) {
    switch ($for) {
      case 'rel':
        $contactIdField = $query['casContactIdField'];
        $entityName = $this->mapping->entity;
        $entityIdField = $query['casEntityIdField'];
        break;

      case 'addl':
        $contactIdField = 'c.id';
        $entityName = 'civicrm_contact';
        $entityIdField = 'c.id';
        break;

      default:
        throw new \CRM_Core_Exception("Unrecognized 'for': $for");
    }

    $joinClause = "civicrm_action_log reminder ON reminder.contact_id = {$contactIdField} AND
reminder.entity_id          = {$entityIdField} AND
reminder.entity_table       = '{$entityName}' AND
reminder.action_schedule_id = {$this->actionSchedule->id}";

    // Why do we only include anniversary clause for 'rel' queries?
    if ($for === 'rel' && !empty($query['casAnniversaryMode'])) {
      // only consider reminders less than 11 months ago
      $joinClause .= " AND reminder.action_date_time > DATE_SUB($this->now, INTERVAL 11 MONTH)";
    }

    return \CRM_Utils_SQL_Select::fragment()->join("reminder", "$joinType $joinClause");
  }

}
