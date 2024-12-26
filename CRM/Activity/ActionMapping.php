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

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


/**
 * This defines the scheduled-reminder functionality for Activities.
 * It is useful for, e.g., sending a reminder based on scheduled
 * date or other custom dates on the activity record.
 */
class CRM_Activity_ActionMapping extends \Civi\ActionSchedule\MappingBase {

  /**
   * Note: This value is an integer for legacy reasons; but going forward any new
   * action mapping classes should return a string from `getId` instead of using a constant.
   */
  const ACTIVITY_MAPPING_ID = 1;

  public function getId() {
    return self::ACTIVITY_MAPPING_ID;
  }

  public function getName(): string {
    return 'activity_type';
  }

  public function getEntityName(): string {
    return 'Activity';
  }

  public function modifyApiSpec(\Civi\Api4\Service\Spec\RequestSpec $spec) {
    $spec->getFieldByName('entity_value')
      ->setLabel(ts('Activity Type'));
    $spec->getFieldByName('entity_status')
      ->setLabel(ts('Activity Status'));
    $spec->getFieldByName('recipient')
      ->setLabel(ts('Recipients'));
  }

  public function getValueLabels(): array {
    // CRM-20510: Include CiviCampaign activity types along with CiviCase IF component is enabled
    $activityTypes = \CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    asort($activityTypes);
    return $activityTypes;
  }

  public function getStatusLabels(?array $entityValue): array {
    return CRM_Core_PseudoConstant::activityStatus();
  }

  public function getDateFields(?array $entityValue = NULL): array {
    return [
      'activity_date_time' => ts('Activity Date'),
    ];
  }

  public static function getLimitToOptions(): array {
    return [
      [
        'id' => 1,
        'name' => 'limit',
        'label' => ts('Recipients'),
      ],
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
  public static function getRecipientTypes(): array {
    return \CRM_Core_OptionGroup::values('activity_contacts') + parent::getRecipientTypes();
  }

  /**
   * Generate a query to locate recipients who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   *
   * @param array $defaultParams
   *
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams): CRM_Utils_SQL_Select {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("civicrm_activity e")->param($defaultParams);
    $query['casAddlCheckFrom'] = 'civicrm_activity e';
    $query['casContactIdField'] = 'r.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = 'e.activity_date_time';

    if ($schedule->limit_to) {
      $activityContacts = \CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      if ($schedule->limit_to == 2 || !isset($activityContacts[$schedule->recipient])) {
        $recipientTypeId = \CRM_Utils_Array::key('Activity Targets', $activityContacts);
      }
      else {
        $recipientTypeId = $schedule->recipient;
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

}
