<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class Mapping
 * @package Civi\ActionSchedule
 *
 * This is the initial implementation of MappingInterface; it was
 * constructed by cutting out swaths from CRM_Core_BAO_ActionSchedule.
 * New implementers should consider implementing MappingInterface on
 * their own.
 *
 * Background: The original designers of ActionMappings intended that
 * one could create and configure new mappings through the database.
 * To, e.g., define the filtering options for CiviEvent, you
 * would insert a record in "civicrm_action_mapping" with fields like
 * "entity" (a table name, eg "civicrm_event"), "entity_value" (an
 * option-group name, eg "event_types").
 *
 * Unfortunately, the metadata in "civicrm_action_mapping" proved
 * inadequate and was not updated to cope. Instead, a number
 * of work-arounds for specific entities were hard-coded into
 * the core action-scheduling code. Ultimately, to add a new
 * mapping type, one needed to run around and patch a dozen
 * places.
 *
 * The new MappingInterface makes no pretense of database-driven
 * configuration. The dozen places have been consolidated and
 * replaced with functions in MappingInterface.
 *
 * This "Mapping" implementation is a refactoring of the old
 * hard-coded bits. Internally, it uses the concepts from
 * "civicrm_action_mapping". The resulting code is more
 * convoluted than a clean implementation of MappingInterface, but
 * it strictly matches the old behavior (based on logging/comparing
 * the queries produced through ActionScheduleTest).
 */
abstract class Mapping implements MappingInterface {

  private static $fields = [
    'id',
    'entity',
    'entity_label',
    'entity_value',
    'entity_value_label',
    'entity_status',
    'entity_status_label',
    'entity_date_start',
    'entity_date_end',
  ];

  /**
   * Create mapping.
   *
   * @param array $params
   *
   * @return static
   */
  public static function create($params) {
    return new static($params);
  }

  /**
   * Class constructor.
   *
   * @param array $params
   */
  public function __construct($params) {
    foreach (self::$fields as $field) {
      if (isset($params[$field])) {
        $this->{$field} = $params[$field];
      }
    }
  }

  protected $id;

  /**
   * The basic entity to query (table name).
   *
   * @var string
   *   Ex: 'civicrm_activity', 'civicrm_event'.
   */
  protected $entity;

  /**
   * The basic entity to query (label).
   *
   * @var string
   *   Ex: 'Activity', 'Event'
   */
  private $entity_label;

  /**
   * Level 1 filter -- the field/option-list to filter on.
   *
   * @var string
   *   Ex: 'activity_type', 'civicrm_event', 'event_template'.
   */
  private $entity_value;

  /**
   * Level 1 filter -- The field label.
   *
   * @var string
   *   Ex: 'Activity Type', 'Event Name', 'Event Template'.
   */
  private $entity_value_label;

  /**
   * Level 2 filter -- the field/option-list to filter on.
   * @var string
   *   Ex: 'activity_status, 'civicrm_participant_status_type', 'auto_renew_options'.
   */
  private $entity_status;

  /**
   * Level 2 filter -- the field label.
   * @var string
   *   Ex: 'Activity Status', 'Participant Status', 'Auto Rewnewal Options'.
   */
  private $entity_status_label;

  /**
   * Date filter -- the field name.
   * @var string|NULL
   *   Ex: 'event_start_date'
   */
  private $entity_date_start;

  /**
   * Date filter -- the field name.
   * @var string|NULL
   *   Ex: 'event_end_date'.
   */
  private $entity_date_end;

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get a printable label for this mapping type.
   *
   * @return string
   */
  public function getLabel() {
    return $this->entity_label;
  }

  /**
   * Get a printable label to use a header on the 'value' filter.
   *
   * @return string
   */
  public function getValueHeader() {
    return $this->entity_value_label;
  }

  /**
   * Get a printable label to use a header on the 'status' filter.
   *
   * @return string
   */
  public function getStatusHeader() {
    return $this->entity_status_label;
  }

  /**
   * Get a list of value options.
   *
   * @return array
   *   Array(string $value => string $label).
   *   Ex: array(123 => 'Phone Call', 456 => 'Meeting').
   */
  public function getValueLabels() {
    return self::getValueLabelMap($this->entity_value);
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
  public function getStatusLabels($value) {
    if ($this->entity_status === 'auto_renew_options') {
      if ($value && \CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $value, 'auto_renew')) {
        return \CRM_Core_OptionGroup::values('auto_renew_options');
      }
      else {
        return [];
      }
    }
    return self::getValueLabelMap($this->entity_status);
  }

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    $dateFieldLabels = [];
    if (!empty($this->entity_date_start)) {
      $dateFieldLabels[$this->entity_date_start] = ucwords(str_replace('_', ' ', $this->entity_date_start));
    }
    if (!empty($this->entity_date_end)) {
      $dateFieldLabels[$this->entity_date_end] = ucwords(str_replace('_', ' ', $this->entity_date_end));
    }
    return $dateFieldLabels;
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

  protected static function getValueLabelMap($name) {
    static $valueLabelMap = NULL;
    if ($valueLabelMap === NULL) {
      // CRM-20510: Include CiviCampaign activity types along with CiviCase IF component is enabled
      $valueLabelMap['activity_type'] = \CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
      asort($valueLabelMap['activity_type']);

      $valueLabelMap['activity_status'] = \CRM_Core_PseudoConstant::activityStatus();
      $valueLabelMap['event_type'] = \CRM_Event_PseudoConstant::eventType();
      $valueLabelMap['civicrm_event'] = \CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template IS NULL OR is_template != 1 )");
      $valueLabelMap['civicrm_participant_status_type'] = \CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
      $valueLabelMap['event_template'] = \CRM_Event_PseudoConstant::eventTemplates();
      $valueLabelMap['auto_renew_options'] = \CRM_Core_OptionGroup::values('auto_renew_options');
      $valueLabelMap['contact_date_reminder_options'] = \CRM_Core_OptionGroup::values('contact_date_reminder_options');
      $valueLabelMap['civicrm_membership_type'] = \CRM_Member_PseudoConstant::membershipType();

      $allCustomFields = \CRM_Core_BAO_CustomField::getFields('');
      $dateFields = [
        'birth_date' => ts('Birth Date'),
        'created_date' => ts('Created Date'),
        'modified_date' => ts('Modified Date'),
      ];
      foreach ($allCustomFields as $fieldID => $field) {
        if ($field['data_type'] == 'Date') {
          $dateFields["custom_$fieldID"] = $field['label'];
        }
      }
      $valueLabelMap['civicrm_contact'] = $dateFields;
    }

    return $valueLabelMap[$name];
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
   * @return \CRM_Utils_SQL_Select
   */
  abstract public function createQuery($schedule, $phase, $defaultParams);

}
