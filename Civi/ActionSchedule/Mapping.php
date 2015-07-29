<?php
namespace Civi\ActionSchedule;

class Mapping {

  private static $fields = array(
    'id',
    'entity',
    'entity_label',
    'entity_value',
    'entity_value_label',
    'entity_status',
    'entity_status_label',
    'entity_date_start',
    'entity_date_end',
    'entity_recipient',
  );

  public static function create($params) {
    return new static($params);
  }

  public function __construct($params) {
    foreach (self::$fields as $field) {
      if (isset($params[$field])) {
        $this->{$field} = $params[$field];
      }
    }
  }

  public $id;

  /**
   * The basic entity to query (table name).
   *
   * @var string
   *   Ex: 'civicrm_activity', 'civicrm_event'.
   */
  public $entity;

  /**
   * The basic entity to query (label).
   *
   * @var
   *   Ex: 'Activity', 'Event'
   */
  public $entity_label;

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
  public $entity_value_label;

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
  public $entity_status_label;

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
   * Contact selector -- The field/relationship/option-group name.
   * @var string|NULL
   *   Ex: 'activity_contacts', 'event_contacts'.
   */
  public $entity_recipient;

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
        return array();
      }
    }
    return self::getValueLabelMap($this->entity_status);
  }

  /**
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    $dateFieldLabels = array();
    if (!empty($this->entity_date_start)) {
      $dateFieldLabels[$this->entity_date_start] = ucwords(str_replace('_', ' ', $this->entity_date_start));
    }
    if (!empty($this->entity_date_end)) {
      $dateFieldLabels[$this->entity_date_end] = ucwords(str_replace('_', ' ', $this->entity_date_end));
    }
    return $dateFieldLabels;
  }

  public function getRecipientListing($recipientType) {
    if (!$recipientType) {
      return array();
    }

    $options = array();
    switch ($this->entity) {
      case 'civicrm_participant':
        $eventContacts = \CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'name', TRUE, FALSE, 'name');
        if (!empty($eventContacts[$recipientType]) && $eventContacts[$recipientType] == 'participant_role') {
          $options = \CRM_Event_PseudoConstant::participantRole();
        }
        break;
    }
    return $options;
  }

  /**
   * @param bool|NULL $noThanksJustKidding
   *   This is ridiculous and should not exist.
   *   If true, don't do our main job.
   * @return array
   *   array(string $value => string $label).
   *   Ex: array('assignee' => 'Activity Assignee').
   */
  public function getRecipientTypes($noThanksJustKidding = FALSE) {
    $entityRecipientLabels = array();
    switch ($this->entity_recipient) {
      case 'activity_contacts':
        $entityRecipientLabels = \CRM_Core_OptionGroup::values('activity_contacts');
        break;

      case 'event_contacts':
        if (!$noThanksJustKidding) {
          $entityRecipientLabels = \CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');
        }
        break;

      default:
    }
    $entityRecipientLabels += array(
      'manual' => ts('Choose Recipient(s)'),
      'group' => ts('Select Group'),
    );
    return $entityRecipientLabels;
  }

  /**
   * FIXME: Seems to duplicate getRecipientTypes?
   * @return array|null
   */
  public function getRecipientOptions() {
    $recipientOptions = NULL;
    if (!\CRM_Utils_System::isNull($this->entity_recipient)) {
      if ($this->entity_recipient == 'event_contacts') {
        $recipientOptions = \CRM_Core_OptionGroup::values($this->entity_recipient, FALSE, FALSE, FALSE, NULL, 'name', TRUE, FALSE, 'name');
      }
      else {
        $recipientOptions = \CRM_Core_OptionGroup::values($this->entity_recipient, FALSE, FALSE, FALSE, NULL, 'name');
      }
    }
    return $recipientOptions;
  }

  protected static function getValueLabelMap($name) {
    static $valueLabelMap = NULL;
    if ($valueLabelMap === NULL) {
      $valueLabelMap['activity_type'] = \CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
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
      $dateFields = array(
        'birth_date' => ts('Birth Date'),
        'created_date' => ts('Created Date'),
        'modified_date' => ts('Modified Date'),
      );
      foreach ($allCustomFields as $fieldID => $field) {
        if ($field['data_type'] == 'Date') {
          $dateFields["custom_$fieldID"] = $field['label'];
        }
      }
      $valueLabelMap['civicrm_contact'] = $dateFields;
    }

    return $valueLabelMap[$name];
  }

}
