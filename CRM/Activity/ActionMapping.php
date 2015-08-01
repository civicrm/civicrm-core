<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

use Civi\ActionSchedule\RecipientBuilder;

class CRM_Activity_ActionMapping extends \Civi\ActionSchedule\Mapping {

  /**
   * Generate a query to locate recipients who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   * @throws \CRM_Core_Exception
   */
  public function createQuery($schedule, $phase) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e");
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
