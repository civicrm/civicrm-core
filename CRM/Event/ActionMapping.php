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

use Civi\ActionSchedule\RecipientBuilder;

/**
 * Class CRM_Event_ActionMapping
 *
 * This defines the scheduled-reminder functionality for CiviEvent
 * participants. It allows one to target messages on the
 * event's start-date/end-date, with additional filtering by
 * event-type, event-template, or event-id.
 */
class CRM_Event_ActionMapping extends \Civi\ActionSchedule\Mapping {

  /**
   * The value for civicrm_action_schedule.mapping_id which identifies the
   * "Event Type" mapping.
   *
   * Note: This value is chosen to match legacy DB IDs.
   */
  const EVENT_TYPE_MAPPING_ID = 2;
  const EVENT_NAME_MAPPING_ID = 3;
  const EVENT_TPL_MAPPING_ID = 5;

  /**
   * Register CiviEvent-related action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(CRM_Event_ActionMapping::create([
      'id' => CRM_Event_ActionMapping::EVENT_TYPE_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Type'),
      'entity_value' => 'event_type',
      'entity_value_label' => ts('Event Type'),
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => ts('Participant Status'),
    ]));
    $registrations->register(CRM_Event_ActionMapping::create([
      'id' => CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Name'),
      'entity_value' => 'civicrm_event',
      'entity_value_label' => ts('Event Name'),
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => ts('Participant Status'),
    ]));
    $registrations->register(CRM_Event_ActionMapping::create([
      'id' => CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Template'),
      'entity_value' => 'event_template',
      'entity_value_label' => ts('Event Template'),
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => ts('Participant Status'),
    ]));
  }

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    return [
      'start_date' => ts('Event Start'),
      'end_date' => ts('Event End'),
      'registration_start_date' => ts('Registration Start'),
      'registration_end_date' => ts('Registration End'),
    ];
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
    return \CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');
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
    switch ($recipientType) {
      case 'participant_role':
        return \CRM_Event_PseudoConstant::participantRole();

      default:
        return [];
    }
  }

  /**
   * Generate a query to locate recipients who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @param array $defaultParams
   *
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e")->param($defaultParams);
    $query['casAddlCheckFrom'] = 'civicrm_event r';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = str_replace('event_', 'r.', $schedule->start_action_date);
    if (empty($query['casDateField']) && $schedule->absolute_date) {
      $query['casDateField'] = "'" . CRM_Utils_Type::escape($schedule->absolute_date, 'String') . "'";
    }

    $query->join('r', 'INNER JOIN civicrm_event r ON e.event_id = r.id');
    if ($schedule->recipient_listing && $schedule->limit_to) {
      switch ($schedule->recipient) {
        case 'participant_role':
          $query->where("e.role_id IN (#recipList)")
            ->param('recipList', \CRM_Utils_Array::explodePadded($schedule->recipient_listing));
          break;

        default:
          break;
      }
    }

    // build where clause
    // FIXME: This handles scheduled reminder of type "Event Name" and "Event Type", gives incorrect result on "Event Template".
    if (!empty($selectedValues)) {
      $valueField = ($this->id == \CRM_Event_ActionMapping::EVENT_TYPE_MAPPING_ID) ? 'event_type_id' : 'id';
      $query->where("r.{$valueField} IN (@selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    else {
      $query->where(($this->id == \CRM_Event_ActionMapping::EVENT_TYPE_MAPPING_ID) ? "r.event_type_id IS NULL" : "r.id IS NULL");
    }

    $query->where('r.is_active = 1');
    $query->where('r.is_template = 0');

    // participant status criteria not to be implemented for additional recipients
    // ... why not?
    if (!empty($selectedStatuses)) {
      switch ($phase) {
        case RecipientBuilder::PHASE_RELATION_FIRST:
        case RecipientBuilder::PHASE_RELATION_REPEAT:
          $query->where("e.status_id IN (#selectedStatuses)")
            ->param('selectedStatuses', $selectedStatuses);
          break;

      }
    }
    return $query;
  }

  /**
   * Determine whether a schedule based on this mapping should
   * send to additional contacts.
   *
   * @param string $entityId Either an event ID/event type ID, or a set of event IDs/types separated
   *  by the separation character.
   */
  public function sendToAdditional($entityId): bool {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($entityId);
    switch ($this->id) {
      case self::EVENT_TYPE_MAPPING_ID:
        $valueTable = 'e';
        $valueField = 'event_type_id';
        $templateReminder = FALSE;
        break;

      case self::EVENT_NAME_MAPPING_ID:
        $valueTable = 'e';
        $valueField = 'id';
        $templateReminder = FALSE;
        break;

      case self::EVENT_TPL_MAPPING_ID:
        $valueTable = 't';
        $valueField = 'id';
        $templateReminder = TRUE;
        break;
    }
    // Don't send to additional recipients if this event is deleted or a template.
    $query = new \CRM_Utils_SQL_Select('civicrm_event e');
    $query
      ->select('e.id')
      ->where("e.is_template = 0")
      ->where("e.is_active = 1");
    if ($templateReminder) {
      $query->join('r', 'INNER JOIN civicrm_event t ON e.template_title = t.template_title AND t.is_template = 1');
    }
    $sql = $query
      ->where("{$valueTable}.{$valueField} IN (@selectedValues)")
      ->param('selectedValues', $selectedValues)
      ->toSQL();
    $dao = \CRM_Core_DAO::executeQuery($sql);
    return (bool) $dao->N;
  }

}
