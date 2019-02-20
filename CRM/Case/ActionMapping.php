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

use Civi\ActionSchedule\RecipientBuilder;

/**
 * Class CRM_Event_ActionMapping
 *
 * This defines the scheduled-reminder functionality for CiviCase entities.
 */
class CRM_Case_ActionMapping extends \Civi\ActionSchedule\Mapping {

  /**
   * The value for civicrm_action_schedule.mapping_id which identifies the
   * "Case" mapping.
   *
   * Note: This value is chosen to match legacy DB IDs.
   */
  const CASE_MAPPING_ID = 99;

  /**
   * Register Activity-related action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(CRM_Case_ActionMapping::create(array(
      'id' => CRM_Case_ActionMapping::CASE_MAPPING_ID,
      'entity' => 'civicrm_case',
      'entity_label' => ts('Case'),
      'entity_value' => 'case_type',
      'entity_value_label' => ts('Case Type'),
      'entity_status' => 'case_status',
      'entity_status_label' => ts('Case Status'),
      'entity_date_start' => 'start_date',
      'entity_date_end' => 'end_date',
    )));
  }

  public function getRecipientListing($type) {
    if ($type == 'case_roles') {
      $result = civicrm_api3('RelationshipType', 'get', array(
        'sequential' => 1,
        'options' => array('limit' => 0, 'sort' => "label_b_a"),
      ))['values'];
      $cRoles = array();
      foreach ($result as $each) {
        $cRoles[$each['id']] = $each['label_b_a'];
      }
      return $cRoles;
    }
    return array();
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
    return array('case_roles' => 'Case Roles');
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
    $caseRoles = (array) \CRM_Utils_Array::explodePadded($schedule->recipient_listing);

    $query = \CRM_Utils_SQL_Select::from("civicrm_case c")->param($defaultParams);
    $query->join('r', "INNER JOIN civicrm_relationship r ON c.id = r.case_id");
    $query->join('cs', "INNER JOIN civicrm_contact ct ON r.contact_id_b = ct.id");
    $query->join('ce', "INNER JOIN civicrm_email ce ON ce.contact_id = ct.id");
    if (!empty($selectedValues)) {
      $query->where("c.case_type_id IN (#caseId)")
        ->param('caseId', $selectedValues);
    }
    if (!empty($selectedStatuses)) {
      $query->where("c.status_id IN (#selectedStatuss)")
        ->param('selectedStatuss', $selectedStatuses);
    }
    $query->where("r.relationship_type_id IN (#caseRoles)")
      ->param('caseRoles', $caseRoles);
    $query['casAddlCheckFrom'] = 'civicrm_case c';
    $query['casContactIdField'] = 'ct.id';
    $query['casEntityIdField'] = 'r.case_id';
    $query['casContactTableAlias'] = 'ct';
    $query['casDateField'] = 'c.start_date';

    $query->where('r.is_active = 1 AND c.is_deleted = 0');
    return $query;
  }

}
