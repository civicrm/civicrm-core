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
 * @code
 * CRM_Utils_SQL_Select::from('civicrm_participant e')
 *   ->join('event', 'INNER JOIN civicrm_event event ON e.event_id = event.id')
 *   ->where('e.is_pay_later = 1')
 *   ->where('event.event_type_id IN (#myEventTypes)')
 *   ->param('myEventTypes', array(2, 5))
 *   ->param('casDateField', 'e.register_date')
 *   ->param($defaultParams)
 *   ...etc...
 * @endcode
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
 *  - casUseReferenceDate: bool
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

    if ($this->prepareAddlFilter('c.id')) {
      $this->buildAddlFirstPass();
    }

    if ($this->actionSchedule->is_repeat) {
      $this->buildRelRepeatPass();
    }

    if ($this->actionSchedule->is_repeat && $this->prepareAddlFilter('c.id')) {
      $this->buildAddlRepeatPass();
    }
  }

  /**
   * Generate action_log's for new, first-time alerts to related contacts.
   *
   * @throws \Exception
   */
  protected function buildRelFirstPass() {
    $query = $this->prepareQuery(self::PHASE_RELATION_FIRST);

    $startDateClauses = $this->prepareStartDateClauses();

    // In some cases reference_date got outdated due to many reason e.g. In Membership renewal end_date got extended
    // which means reference date mismatches with the end_date where end_date may be used as the start_action_date
    // criteria  for some schedule reminder so in order to send new reminder we INSERT new reminder with new reference_date
    // value via UNION operation
    $referenceReminderIDs = array();
    $referenceDate = NULL;
    if (!empty($query['casUseReferenceDate'])) {
      // First retrieve all the action log's ids which are outdated or in other words reference_date now don't match with entity date.
      // And the retrieve the updated entity date which will later used below to update all other outdated action log records
      $sql = $query->copy()
        ->select('reminder.id as id')
        ->select($query['casDateField'] . ' as reference_date')
        ->merge($this->joinReminder('INNER JOIN', 'rel', $query))
        ->where("reminder.id IS NOT NULL AND reminder.reference_date IS NOT NULL AND reminder.reference_date <> !casDateField")
        ->where($startDateClauses)
        ->orderBy("reminder.id desc")
        ->strict()
        ->toSQL();
      $dao = \CRM_Core_DAO::executeQuery($sql);

      while ($dao->fetch()) {
        $referenceReminderIDs[] = $dao->id;
        $referenceDate = $dao->reference_date;
      }
    }

    if (empty($referenceReminderIDs)) {
      $firstQuery = $query->copy()
        ->merge($this->selectIntoActionLog(self::PHASE_RELATION_FIRST, $query))
        ->merge($this->joinReminder('LEFT JOIN', 'rel', $query))
        ->where("reminder.id IS NULL")
        ->where($startDateClauses)
        ->strict()
        ->toSQL();
      \CRM_Core_DAO::executeQuery($firstQuery);
    }
    else {
      // INSERT new log to send reminder as desired entity date got updated
      $referenceQuery = $query->copy()
        ->merge($this->selectIntoActionLog(self::PHASE_RELATION_FIRST, $query))
        ->merge($this->joinReminder('LEFT JOIN', 'rel', $query))
        ->where("reminder.id = !reminderID")
        ->where($startDateClauses)
        ->param('reminderID', $referenceReminderIDs[0])
        ->strict()
        ->toSQL();
      \CRM_Core_DAO::executeQuery($referenceQuery);

      // Update all the previous outdated reference date valued, action_log rows to the latest changed entity date
      $updateQuery = "UPDATE civicrm_action_log SET reference_date = '" . $referenceDate . "' WHERE id IN (" . implode(', ', $referenceReminderIDs) . ")";
      \CRM_Core_DAO::executeQuery($updateQuery);
    }
  }

  /**
   * Generate action_log's for new, first-time alerts to additional contacts.
   *
   * @throws \Exception
   */
  protected function buildAddlFirstPass() {
    $query = $this->prepareQuery(self::PHASE_ADDITION_FIRST);

    $insertAdditionalSql = \CRM_Utils_SQL_Select::from("civicrm_contact c")
      ->merge($query, array('params'))
      ->merge($this->selectIntoActionLog(self::PHASE_ADDITION_FIRST, $query))
      ->merge($this->joinReminder('LEFT JOIN', 'addl', $query))
      ->where('reminder.id IS NULL')
      ->where("c.is_deleted = 0 AND c.is_deceased = 0")
      ->merge($this->prepareAddlFilter('c.id'))
      ->where("c.id NOT IN (
             SELECT rem.contact_id
             FROM civicrm_action_log rem INNER JOIN {$this->mapping->getEntity()} e ON rem.entity_id = e.id
             WHERE rem.action_schedule_id = {$this->actionSchedule->id}
             AND rem.entity_table = '{$this->mapping->getEntity()}'
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
      ->merge($this->selectActionLogFields(self::PHASE_RELATION_REPEAT, $query))
      ->select("MAX(reminder.action_date_time) as latest_log_time")
      ->merge($this->prepareRepetitionEndFilter($query['casDateField']))
      ->where($this->actionSchedule->start_action_date ? $startDateClauses[0] : array())
      ->groupBy("reminder.contact_id, reminder.entity_id, reminder.entity_table")
      // @todo replace use of timestampdiff with a direct comparison as TIMESTAMPDIFF cannot use an index.
      ->having("TIMESTAMPDIFF(HOUR, latest_log_time, CAST(!casNow AS datetime)) >= TIMESTAMPDIFF(HOUR, latest_log_time, DATE_ADD(latest_log_time, INTERVAL !casRepetitionInterval))")
      ->param(array(
        'casRepetitionInterval' => $this->parseRepetitionInterval(),
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
   * Generate action_log's for repeated, follow-up alerts to additional contacts.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  protected function buildAddlRepeatPass() {
    $query = $this->prepareQuery(self::PHASE_ADDITION_REPEAT);

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
        ->merge($this->selectActionLogFields(self::PHASE_ADDITION_REPEAT, $query))
        ->merge($this->joinReminder('INNER JOIN', 'addl', $query))
        ->select("MAX(reminder.action_date_time) as latest_log_time")
        ->merge($this->prepareAddlFilter('c.id'))
        ->where("c.is_deleted = 0 AND c.is_deceased = 0")
        ->groupBy("reminder.contact_id")
        // @todo replace use of timestampdiff with a direct comparison as TIMESTAMPDIFF cannot use an index.
        ->having("TIMESTAMPDIFF(HOUR, latest_log_time, CAST(!casNow AS datetime)) >= TIMESTAMPDIFF(HOUR, latest_log_time, DATE_ADD(latest_log_time, INTERVAL !casRepetitionInterval)")
        ->param(array(
          'casRepetitionInterval' => $this->parseRepetitionInterval(),
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
    $defaultParams = array(
      'casActionScheduleId' => $this->actionSchedule->id,
      'casMappingId' => $this->mapping->getId(),
      'casMappingEntity' => $this->mapping->getEntity(),
      'casNow' => $this->now,
    );

    /** @var \CRM_Utils_SQL_Select $query */
    $query = $this->mapping->createQuery($this->actionSchedule, $phase, $defaultParams);

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
    $startDateClauses = array();
    if ($actionSchedule->start_action_date) {
      $op = ($actionSchedule->start_action_condition == 'before' ? '<=' : '>=');
      $operator = ($actionSchedule->start_action_condition == 'before' ? 'DATE_SUB' : 'DATE_ADD');
      $date = $operator . "(!casDateField, INTERVAL {$actionSchedule->start_action_offset} {$actionSchedule->start_action_unit})";
      $startDateClauses[] = "'!casNow' >= {$date}";
      // This is weird. Waddupwidat?
      if ($this->mapping->getEntity() == 'civicrm_participant') {
        $startDateClauses[] = $operator . "(!casNow, INTERVAL 1 DAY ) {$op} " . '!casDateField';
      }
      else {
        $startDateClauses[] = "DATE_SUB(!casNow, INTERVAL 1 DAY ) <= {$date}";
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
      ->param(array(
        '!repetitionEndDate' => $repeatEventDateExpr,
      ));
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
      case self::PHASE_RELATION_FIRST:
      case self::PHASE_RELATION_REPEAT:
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

      case self::PHASE_ADDITION_FIRST:
      case self::PHASE_ADDITION_REPEAT:
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
    if ($phase === self::PHASE_RELATION_FIRST || $phase === self::PHASE_RELATION_REPEAT) {
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
        $entityName = $this->mapping->getEntity();
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
      $joinClause .= " AND reminder.action_date_time > DATE_SUB(!casNow, INTERVAL 11 MONTH)";
    }

    return \CRM_Utils_SQL_Select::fragment()->join("reminder", "$joinType $joinClause");
  }

}
