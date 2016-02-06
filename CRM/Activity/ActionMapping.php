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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

use Civi\ActionSchedule\RecipientBuilder;

/**
 * Class CRM_Activity_ActionMapping
 *
 * This defines the scheduled-reminder functionality for contact
 * entities. It is useful for, e.g., sending a reminder based on
 * birth date, modification date, or other custom dates on
 * the contact record.
 */
class CRM_Activity_ActionMapping extends \Civi\ActionSchedule\Mapping {

  /**
   * The value for civicrm_action_schedule.mapping_id which identifies the
   * "Activity" mapping.
   *
   * Note: This value is chosen to match legacy DB IDs.
   */
  const ACTIVITY_MAPPING_ID = 1;

  /**
   * Register Activity-related action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(CRM_Activity_ActionMapping::create(array(
      'id' => CRM_Activity_ActionMapping::ACTIVITY_MAPPING_ID,
      'entity' => 'civicrm_activity',
      'entity_label' => ts('Activity'),
      'entity_value' => 'activity_type',
      'entity_value_label' => ts('Activity Type'),
      'entity_status' => 'activity_status',
      'entity_status_label' => ts('Activity Status'),
      'entity_date_start' => 'activity_date_time',
    )));
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
    return \CRM_Core_OptionGroup::values('activity_contacts');
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
  public function createQuery($schedule, $phase, $defaultParams) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e")->param($defaultParams);
    $query['casAddlCheckFrom'] = 'civicrm_activity e';
    $query['casContactIdField'] = 'r.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = 'e.activity_date_time';

    if (!is_null($schedule->limit_to)) {
      $activityContacts = \CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      if ($schedule->limit_to == 0 || !isset($activityContacts[$schedule->recipient])) {
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
