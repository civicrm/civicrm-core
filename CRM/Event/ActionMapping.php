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
    $registrations->register(CRM_Event_ActionMapping::create(array(
      'id' => CRM_Event_ActionMapping::EVENT_TYPE_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Type'),
      'entity_value' => 'event_type',
      'entity_value_label' => ts('Event Type'),
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => ts('Participant Status'),
    )));
    $registrations->register(CRM_Event_ActionMapping::create(array(
      'id' => CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Name'),
      'entity_value' => 'civicrm_event',
      'entity_value_label' => ts('Event Name'),
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => ts('Participant Status'),
    )));
    $registrations->register(CRM_Event_ActionMapping::create(array(
      'id' => CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Template'),
      'entity_value' => 'event_template',
      'entity_value_label' => ts('Event Template'),
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => ts('Participant Status'),
    )));
  }

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    return array(
      'start_date' => ts('Event Start Date'),
      'end_date' => ts('Event End Date'),
      'registration_start_date' => ts('Registration Start Date'),
      'registration_end_date' => ts('Registration End Date'),
    );
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
        return array();
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

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e")->param($defaultParams);;
    $query['casAddlCheckFrom'] = 'civicrm_event r';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = str_replace('event_', 'r.', $schedule->start_action_date);

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

}
