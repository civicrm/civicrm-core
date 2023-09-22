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

namespace Civi\ActionSchedule;

/**
 * Class RecipientBuilder
 * @package Civi\ActionSchedule
 *
 * The RecipientBuilder prepares a list of recipients based on an action-schedule.
 *
 * This is a four-step process, with different steps depending on:
 *
 * (a) How the recipient is identified. Sometimes recipients are identified based
 *     on their relations (e.g. selecting the assignees of an activity or the
 *     participants of an event), and sometimes they are manually added using
 *     a flat contact list (e.g. with a contact ID or group ID).
 * (b) Whether this is the first reminder or a follow-up/repeated reminder.
 *
 * The permutations of these (a)+(b) produce four phases -- RELATION_FIRST,
 * RELATION_REPEAT, ADDITION_FIRST, ADDITION_REPEAT.
 *
 * Each phase requires running a complex query. As a general rule,
 * MappingInterface::createQuery() produces a base query, and the RecipientBuilder
 * appends extra bits (JOINs/WHEREs/GROUP BYs) depending on which step is running.
 *
 * For example, suppose we want to send reminders to anyone who registers for
 * a "Conference" or "Exhibition" event with the 'pay later' option, and we want
 * to fire the reminders X days after the registration date. The
 * MappingInterface::createQuery() could return a query like:
 *
 * ```
 * CRM_Utils_SQL_Select::from('civicrm_participant e')
 *   ->join('event', 'INNER JOIN civicrm_event event ON e.event_id = event.id')
 *   ->where('e.is_pay_later = 1')
 *   ->where('event.event_type_id IN (#myEventTypes)')
 *   ->param('myEventTypes', array(2, 5))
 *   ->param('casDateField', 'e.register_date')
 *   ->param($defaultParams)
 *   ...etc...
 * ```
 *
 * In the RELATION_FIRST phase, RecipientBuilder adds a LEFT-JOIN+WHERE to find
 * participants who have *not* yet received any reminder, and filters those
 * participants based on whether X days have passed since "e.register_date".
 *
 * Notice that the query may define several SQL elements directly (eg
 * via `from()`, `where()`, `join()`, `groupBy()`). Additionally, it
 * must define some parameters (eg `casDateField`). These parameters will be
 * read by RecipientBuilder and used in other parts of the query.
 *
 * At time of writing, these parameters are required:
 *  - casAddlCheckFrom: string, SQL FROM expression
 *  - casContactIdField: string, SQL column expression
 *  - casDateField: string, SQL column expression
 *  - casEntityIdField: string, SQL column expression
 *
 * Some parameters are optional:
 *  - casContactTableAlias: string, SQL table alias
 *  - casAnniversaryMode: bool
 *
 * Additionally, some parameters are automatically predefined:
 *  - casNow
 *  - casMappingEntity: string, SQL table name
 *  - casMappingId: int
 *  - casActionScheduleId: int
 *
 * Note: Any parameters defined by the core Civi\ActionSchedule subsystem
 * use the prefix `cas`. If you define new parameters (like `myEventTypes`
 * above), then use a different name (to avoid conflicts).
 */
class RecipientBuilder {

  private $now;

  /**
   * Generate action_log's for new, first-time alerts to related contacts.
   *
   * @see buildRelFirstPass
   */
  const PHASE_RELATION_FIRST = 'rel-first';

  /**
   * Generate action_log's for new, first-time alerts to additional contacts.
   *
   * @see buildAddlFirstPass
   */
  const PHASE_ADDITION_FIRST = 'addl-first';

  /**
   * Generate action_log's for repeated, follow-up alerts to related contacts.
   *
   * @see buildRelRepeatPass
   */
  const PHASE_RELATION_REPEAT = 'rel-repeat';

  /**
   * Generate action_log's for repeated, follow-up alerts to additional contacts.
   *
   * @see buildAddlRepeatPass
   */
  const PHASE_ADDITION_REPEAT = 'addl-repeat';

  /**
   * @var \CRM_Core_DAO_ActionSchedule
   */
  private $actionSchedule;

  /**
   * @var MappingInterface
   */
  private $mapping;

  /**
   * @param $now
   * @param \CRM_Core_DAO_ActionSchedule $actionSchedule
   * @param MappingInterface $mapping
   */
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
    $this->buildRelFirstPass();

    if ($this->prepareAddlFilter('c.id') && $this->mapping->sendToAdditional($this->actionSchedule->entity_value)) {
      $this->buildAddlFirstPass();
    }

    if ($this->actionSchedule->is_repeat) {
      $this->buildRelRepeatPass();
    }

    if ($this->actionSchedule->is_repeat && $this->prepareAddlFilter('c.id') && $this->mapping->sendToAdditional($this->actionSchedule->entity_value)) {
      $this->buildAddlRepeatPass();
    }
  }

  /**
   * Generate action_log's for new, first-time alerts to related contacts,
   * and contacts who are again eligible to receive the alert e.g. membership
   * renewal reminders.
   *
   * @throws \Exception
   */
  protected function buildRelFirstPass() {
    $query = $this->prepareQuery(self::PHASE_RELATION_FIRST);

    $startDateClauses = $this->prepareStartDateClauses();
    // Send reminder to all contacts who have never received this scheduled reminder
    $firstInstanceQuery = $query->copy()
      ->merge($this->selectIntoActionLog(self::PHASE_RELATION_FIRST, $query))
      ->merge($this->joinReminder('LEFT JOIN', 'rel', $query))
      ->where("reminder.id IS NULL")
      ->where($startDateClauses)
      ->strict()
      ->toSQL();
    \CRM_Core_DAO::executeQuery($firstInstanceQuery);
  }

  /**
   * Generate action_log's for new, first-time alerts to additional contacts.
   *
   * @throws \Exception
   */
  protected function buildAddlFirstPass() {
    $query = $this->prepareQuery(self::PHASE_ADDITION_FIRST);

    $insertAdditionalSql = \CRM_Utils_SQL_Select::from("civicrm_contact c")
      ->merge($query, ['params'])
      ->merge($this->selectIntoActionLog(self::PHASE_ADDITION_FIRST, $query))
      ->merge($this->joinReminder('LEFT JOIN', 'addl', $query))
      ->where('reminder.id IS NULL')
      ->where("c.is_deleted = 0 AND c.is_deceased = 0")
      ->merge($this->prepareAddlFilter('c.id'))
      ->where("c.id NOT IN (
             SELECT rem.contact_id
             FROM civicrm_action_log rem INNER JOIN {$this->getMappingTable()} e ON rem.entity_id = e.id
             WHERE rem.action_schedule_id = {$this->actionSchedule->id}
             AND rem.entity_table = '{$this->getMappingTable()}'
             )")
      // Where does e.id come from here? ^^^
      ->groupBy("c.id")
      ->strict()
      ->toSQL();
    \CRM_Core_DAO::executeQuery($insertAdditionalSql);
  }

  /**
   * Generate action_log's for repeated, follow-up alerts to related contacts.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  protected function buildRelRepeatPass() {
    $query = $this->prepareQuery(self::PHASE_RELATION_REPEAT);
    $startDateClauses = $this->prepareStartDateClauses();

    // CRM-15376 - do not send our reminders if original criteria no longer applies
    // the first part of the startDateClause array is the earliest the reminder can be sent. If the
    // event (e.g membership_end_date) has changed then the reminder may no longer apply
    // @todo - this only handles events that get moved later. Potentially they might get moved earlier
    $repeatInsert = $query
      ->merge($this->joinReminder('INNER JOIN', 'rel', $query))
      ->merge($this->selectIntoActionLog(self::PHASE_RELATION_REPEAT, $query))
      ->merge($this->prepareRepetitionEndFilter($query['casDateField']))
      ->where($this->actionSchedule->start_action_date ? $startDateClauses[0] : [])
      ->groupBy("reminder.contact_id, reminder.entity_id, reminder.entity_table")
      ->having("TIMESTAMPDIFF(HOUR, MAX(reminder.action_date_time), CAST(!casNow AS datetime)) >= TIMESTAMPDIFF(HOUR, MAX(reminder.action_date_time), DATE_ADD(MAX(reminder.action_date_time), INTERVAL !casRepetitionInterval))")
      ->param([
        'casRepetitionInterval' => $this->parseRepetitionInterval(),
      ])
      ->strict()
      ->toSQL();

    \CRM_Core_DAO::executeQuery($repeatInsert);
  }

  /**
   * Generate action_log's for repeated, follow-up alerts to additional contacts.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  protected function buildAddlRepeatPass() {
    $query = $this->prepareQuery(self::PHASE_ADDITION_REPEAT);

    $addlCheck = \CRM_Utils_SQL_Select::from($query['casAddlCheckFrom'])
      ->select('*')
      ->merge($query, ['params', 'wheres', 'joins'])
      ->merge($this->prepareRepetitionEndFilter($query['casDateField']))
      ->limit(1)
      ->strict()
      ->toSQL();

    $daoCheck = \CRM_Core_DAO::executeQuery($addlCheck);
    if ($daoCheck->fetch()) {
      $repeatInsertAddl = \CRM_Utils_SQL_Select::from('civicrm_contact c')
        ->merge($this->selectIntoActionLog(self::PHASE_ADDITION_REPEAT, $query))
        ->merge($this->joinReminder('INNER JOIN', 'addl', $query))
        ->merge($this->prepareAddlFilter('c.id'), ['params'])
        ->where("c.is_deleted = 0 AND c.is_deceased = 0")
        ->groupBy("reminder.contact_id")
        ->having("TIMESTAMPDIFF(HOUR, MAX(reminder.action_date_time), CAST(!casNow AS datetime)) >= TIMESTAMPDIFF(HOUR, MAX(reminder.action_date_time), DATE_ADD(MAX(reminder.action_date_time), INTERVAL !casRepetitionInterval))")
        ->param([
          'casRepetitionInterval' => $this->parseRepetitionInterval(),
        ])
        ->strict()
        ->toSQL();

      \CRM_Core_DAO::executeQuery($repeatInsertAddl);
    }
  }

  /**
   * @param string $phase
   * @return \CRM_Utils_SQL_Select
   * @throws \CRM_Core_Exception
   */
  protected function prepareQuery($phase) {
    $defaultParams = [
      'casActionScheduleId' => $this->actionSchedule->id,
      'casMappingId' => $this->mapping->getId(),
      'casMappingEntity' => $this->getMappingTable(),
      'casNow' => $this->now,
    ];

    $query = $this->mapping->createQuery($this->actionSchedule, $phase, $defaultParams);

    if ($this->actionSchedule->limit_to == 1) {
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
   * Parse repetition interval.
   *
   * @return int|string
   */
  protected function parseRepetitionInterval() {
    $actionSchedule = $this->actionSchedule;
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
    return $interval;
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
      $regularGroupIDs = $smartGroupIDs = $groupWhereCLause = [];
      $query = \CRM_Utils_SQL_Select::fragment();

      // get child group IDs if any
      $childGroupIDs = \CRM_Contact_BAO_Group::getChildGroupIds($actionSchedule->group_id);
      foreach (array_merge([$actionSchedule->group_id], $childGroupIDs) as $groupID) {
        if ($this->isSmartGroup($groupID)) {
          // Check that the group is in place in the cache and up to date
          \CRM_Contact_BAO_GroupContactCache::check($groupID);
          $smartGroupIDs[] = $groupID;
        }
        else {
          $regularGroupIDs[] = $groupID;
        }
      }

      if (!empty($smartGroupIDs)) {
        $query->join('sg', "LEFT JOIN civicrm_group_contact_cache sg ON {$contactIdField} = sg.contact_id");
        $groupWhereCLause[] = " sg.group_id IN ( " . implode(', ', $smartGroupIDs) . " ) ";
      }
      if (!empty($regularGroupIDs)) {
        $query->join('rg', " LEFT JOIN civicrm_group_contact rg ON {$contactIdField} = rg.contact_id AND rg.status = 'Added'");
        $groupWhereCLause[] = " rg.group_id IN ( " . implode(', ', $regularGroupIDs) . " ) ";
      }
      return $query->where(implode(" OR ", $groupWhereCLause));
    }
    elseif (!empty($actionSchedule->recipient_manual)) {
      $rList = \CRM_Utils_Type::escape($actionSchedule->recipient_manual, 'String');
      return \CRM_Utils_SQL_Select::fragment()
        ->where("{$contactIdField} IN ({$rList})");
    }
    return NULL;
  }

  /**
   * Prepare language filter.
   *
   * @param string $contactTableAlias
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
   * @return array
   */
  protected function prepareStartDateClauses() {
    $actionSchedule = $this->actionSchedule;
    $startDateClauses = [];
    if ($actionSchedule->start_action_date) {
      $op = ($actionSchedule->start_action_condition == 'before' ? '<=' : '>=');
      $operator = ($actionSchedule->start_action_condition == 'before' ? 'DATE_SUB' : 'DATE_ADD');
      $date = $operator . "(!casDateField, INTERVAL {$actionSchedule->start_action_offset} {$actionSchedule->start_action_unit})";
      $startDateClauses[] = "'!casNow' >= {$date}";
      // This is weird. Waddupwidat?
      if ($this->getMappingTable() == 'civicrm_participant') {
        $startDateClauses[] = $operator . "(!casNow, INTERVAL 1 DAY ) {$op} " . '!casDateField';
      }
      else {
        $startDateClauses[] = "DATE_SUB(!casNow, INTERVAL 1 DAY ) <= {$date}";
      }
      if (!empty($actionSchedule->effective_start_date) && $actionSchedule->effective_start_date !== '0000-00-00 00:00:00') {
        $startDateClauses[] = "'{$actionSchedule->effective_start_date}' <= {$date}";
      }
      if (!empty($actionSchedule->effective_end_date) && $actionSchedule->effective_end_date !== '0000-00-00 00:00:00') {
        $startDateClauses[] = "'{$actionSchedule->effective_end_date}' > {$date}";
      }
    }
    elseif ($actionSchedule->absolute_date) {
      $startDateClauses[] = "DATEDIFF(DATE('!casNow'),'{$actionSchedule->absolute_date}') = 0";
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
      ->where("@casNow <= !repetitionEndDate")
      ->param([
        '!repetitionEndDate' => $repeatEventDateExpr,
      ]);
  }

  /**
   * @param string $contactIdField
   * @return \CRM_Utils_SQL_Select|null
   */
  protected function prepareAddlFilter($contactIdField) {
    $contactAddlFilter = NULL;
    if ($this->actionSchedule->limit_to == 2) {
      $contactAddlFilter = $this->prepareContactFilter($contactIdField);
    }
    return $contactAddlFilter;
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
    $selectArray = [];
    switch ($phase) {
      case self::PHASE_RELATION_FIRST:
      case self::PHASE_RELATION_REPEAT:
        $fragment = \CRM_Utils_SQL_Select::fragment();
        $selectArray = [
          "!casContactIdField as contact_id",
          "!casEntityIdField as entity_id",
          "@casMappingEntity as entity_table",
          "#casActionScheduleId as action_schedule_id",
        ];
        if ($this->resetOnTriggerDateChange()) {
          $selectArray[] = "!casDateField as reference_date";
        }
        break;

      case self::PHASE_ADDITION_FIRST:
      case self::PHASE_ADDITION_REPEAT:
        //CRM-19017: Load default params for fragment query object.
        $params = [
          'casActionScheduleId' => $this->actionSchedule->id,
          'casNow' => $this->now,
        ];
        $fragment = \CRM_Utils_SQL_Select::fragment()->param($params);
        $selectArray = [
          "c.id as contact_id",
          "c.id as entity_id",
          "'civicrm_contact' as entity_table",
          "#casActionScheduleId as action_schedule_id",
        ];
        break;

      default:
        throw new \CRM_Core_Exception("Unrecognized phase: $phase");
    }
    $fragment->select($selectArray);
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
    $actionLogColumns = [
      "contact_id",
      "entity_id",
      "entity_table",
      "action_schedule_id",
    ];

    if ($this->resetOnTriggerDateChange() && ($phase == self::PHASE_RELATION_FIRST || $phase == self::PHASE_RELATION_REPEAT)) {
      $actionLogColumns[] = "reference_date";
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
        $entityName = $this->getMappingTable();
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

    if ($for == 'rel' && $this->resetOnTriggerDateChange()) {
      $joinClause .= " AND\nreminder.reference_date = !casDateField";
    }

    // Why do we only include anniversary clause for 'rel' queries?
    if ($for === 'rel' && !empty($query['casAnniversaryMode'])) {
      // only consider reminders less than 11 months ago
      $joinClause .= " AND reminder.action_date_time > DATE_SUB(!casNow, INTERVAL 11 MONTH)";
    }

    return \CRM_Utils_SQL_Select::fragment()->join("reminder", "$joinType $joinClause");
  }

  /**
   * Should we use the reference date when checking to see if we already
   * sent reminders.
   *
   * @return bool
   */
  protected function resetOnTriggerDateChange() {
    return $this->mapping->resetOnTriggerDateChange($this->actionSchedule);
  }

  protected function getMappingTable(): string {
    return $this->mapping->getEntityTable($this->actionSchedule);
  }

}
