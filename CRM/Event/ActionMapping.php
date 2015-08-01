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
   * Generate a query to locate recipients who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e");
    $query['casAddlCheckFrom'] = 'civicrm_event r';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = str_replace('event_', 'r.', $schedule->start_action_date);

    $query->join('r', 'INNER JOIN civicrm_event r ON e.event_id = r.id');
    if ($schedule->recipient_listing && $schedule->limit_to) {
      switch (\CRM_Utils_Array::value($schedule->recipient, $this->getRecipientOptions())) {
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
      $valueField = ($this->id == \CRM_Core_ActionScheduleTmp::EVENT_TYPE_MAPPING_ID) ? 'event_type_id' : 'id';
      $query->where("r.{$valueField} IN (@selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    else {
      $query->where(($this->id == \CRM_Core_ActionScheduleTmp::EVENT_TYPE_MAPPING_ID) ? "r.event_type_id IS NULL" : "r.id IS NULL");
    }

    $query->where('r.is_active = 1');
    $query->where('r.is_template = 0');

    // participant status criteria not to be implemented for additional recipients
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

}
