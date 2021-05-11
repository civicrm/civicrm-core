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

use Civi\ActionSchedule\Event\MappingRegisterEvent;
use Civi\API\Request;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\SearchDisplay;
use CRM_SearchKitReminders_ExtensionUtil as E;

/**
 * Class Mapping
 * @package Civi\ActionSchedule
 *
 */
class SearchKitMapping implements MappingInterface {

  /**
   * Search to be used for reminder.
   *
   * @var array
   */
  protected $search;

  /**
   * Class constructor.
   *
   * @param array $search
   */
  public function __construct(array $search) {
    $this->search = $search;
  }

  /**
   * Get reference ID.
   *
   * @return string
   */
  public function getId(): string {
    return 'search_kit';
  }

  /**
   * @return string
   */
  public function getEntity(): string {
    return \CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getSearch()['saved_search.api_entity']);
  }

  /**
   * Get a printable label for this mapping type.
   *
   * @return string
   */
  public function getLabel(): string {
    return E::ts('Search kit') . ' - ' . $this->getSearch()['label'];
  }

  /**
   * Get a printable label to use as the header on the 'value' filter.
   *
   * @return string
   */
  public function getValueHeader(): string {
    return '-configure via search kit-';
  }

  /**
   * Get a printable label to use as the header on the 'status' filter.
   *
   * @return string
   */
  public function getStatusHeader() {
    return E::ts('configure via search kit');
  }

  /**
   * Get a list of value options.
   *
   * @return array
   *   Array(string $value => string $label).
   *   Ex: array(123 => 'Phone Call', 456 => 'Meeting').
   */
  public function getValueLabels() {
    return [$this->getSearch()['id'] => 'click here - just because'];
  }

  /**
   * Get a list of status options.
   *
   * @param string|int $value
   *   The list of status options may be contingent upon the selected filter value.
   *   This is the selected filter value.
   * @return array
   *   Array(string $value => string $label).
   *   Ex: Array(123 => 'Completed', 456 => 'Scheduled').
   */
  public function getStatusLabels($value): array {
    return [];
  }

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    $return = [];
    foreach ($this->getSearch()['date_columns'] as $column) {
      $return[$column['key']] = $column['label'];
    }
    return $return;
  }

  /**
   * Get a list of recipient types.
   *
   * Note: A single schedule may filter on *zero* or *one* recipient types.
   * When an admin chooses a value, it's stored in $schedule->recipient.
   *
   * @return array
   *   array(string $value => string $label).
   *   Ex: array('assignee' => 'Activity Assignee').
   */
  public function getRecipientTypes() {
    return [];
  }

  /**
   * Get a list of recipients which match the given type.
   *
   * Note: A single schedule may filter on *multiple* recipients.
   * When an admin chooses value(s), it's stored in $schedule->recipient_listing.
   *
   * @param string $recipientType
   *   Ex: 'participant_role'.
   * @return array
   *   Array(mixed $name => string $label).
   *   Ex: array(1 => 'Attendee', 2 => 'Volunteer').
   * @see getRecipientTypes
   */
  public function getRecipientListing($recipientType) {
    return [];
  }

  /**
   * Determine whether a schedule based on this mapping is sufficiently
   * complete.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @return array
   *   Array (string $code => string $message).
   *   List of error messages.
   */
  public function validateSchedule($schedule) {
    if (!isset($this->getDateFields()[$schedule->start_action_date])) {
      return ['start_action_date' => E::ts('valid field required')];
    }
    return [];
  }

  /**
   * Generate a query to locate contacts who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @param array $defaultParams
   *   Default parameters that should be included with query.
   *
   * @return \CRM_Utils_SQL_Select
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws \API_Exception
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams) {
    $apiParams = $this->getSearch()['saved_search.api_params'];
    $entity = $this->getSearch()['saved_search.api_entity'];
    if (!in_array('id', $apiParams['select'], TRUE)) {
      // Add id if not present - really we want to be able to select any fk but for now...
      $apiParams['select'][] = 'id';
    }
    /** @var \Civi\Api4\Generic\DAOGetAction $api */
    $api = Request::create($entity, 'get', $apiParams);
    $apiQuery = new Api4SelectQuery($api);
    $query = $apiQuery->getQuery();
    $query['casContactTableAlias'] = 'a';
    $query['casEntityIdField'] = 'a.' . SqlExpression::convert('id', TRUE)->getAlias();
    $query['casMappingId'] = $defaultParams['casMappingId'];
    $query['casActionScheduleId'] = $defaultParams['casActionScheduleId'];
    $query['casNow'] = $defaultParams['casNow'];
    // As we are only doing contacts for now.
    // but we need to think about joins that are more than one per contact.
    $query['casContactIdField'] = $query['casEntityIdField'];
    $query['casMappingEntity'] = \CRM_Core_DAO_AllCoreTables::getTableForEntityName($entity);
    $query['casDateField'] = $schedule->start_action_date;
    return $query;
  }

  /**
   * Determine whether a schedule based on this mapping should
   * reset the reminder state if the trigger date changes.
   *
   * @return bool
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   */
  public function resetOnTriggerDateChange($schedule) {
    return FALSE;
  }

  /**
   * Determine whether a schedule based on this mapping should
   * send to additional contacts.
   */
  public function sendToAdditional($entityId): bool {
    return TRUE;
  }

  /**
   * Create mapping.
   *
   * @param array $params
   *
   * @return static
   */
  public static function create($search) {
    return new static($search);
  }

  /**
   * Register action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(MappingRegisterEvent $registrations): void {
    try {
      foreach (self::getUsableSearches() as $search) {
        $registrations->register(self::create($search));
      }
    }
    catch (\API_Exception $e) {
      \Civi::log('scheduled_reminders')->alert('unable to load saved search. Error {$error}', ['error' => $e->getMessage()]);
      \CRM_Core_Session::setStatus('unable to load saved search:' . $e->getMessage());
    }
  }

  /**
   * Get the relevant search.
   *
   * @return array
   */
  protected function getSearch(): array {
    return $this->search;
  }

  /**
   * Get usable searches.
   *
   * @return array
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getUsableSearches(): array {
    if (!isset(\Civi::$statics[__CLASS__]['usable_searches'])) {
      $searches = (array) SearchDisplay::get()
        ->addSelect('name', 'saved_search.name', 'saved_search.api_params', 'saved_search.api_entity', 'settings', 'label')
        ->setJoin([
          ['SavedSearch AS saved_search', 'LEFT'],
        ])
        ->addWhere('type', '=', 'schedulable')
        ->addWhere('saved_search.api_entity', '=', 'Contact')
        ->setLimit(25)
        ->execute();
      foreach ($searches as $index => $search) {
        foreach ($search['settings']['columns'] as $column) {
          if ($column['dataType'] === 'Timestamp') {
            $searches[$index]['date_columns'][$column['key']] = $column;
          }
        }
        if (empty($searches[$index]['date_columns'])) {
          unset($searches[$index]);
        }
      }
      \Civi::$statics[__CLASS__]['usable_searches'] = $searches;
    }
    return \Civi::$statics[__CLASS__]['usable_searches'];
  }

}
