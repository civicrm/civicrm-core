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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Core_Page_RecurringEntityPreview extends CRM_Core_Page {

  /**
   * Run the basic page (run essentially starts execution for that page).
   */
  public function run() {
    $parentEntityId = $startDate = $endDate = NULL;
    $dates = $original = [];
    $formValues = $_REQUEST;
    if (!empty($formValues['entity_table'])) {
      $startDateColumnName = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['dateColumns'][0];
      $endDateColumnName = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['intervalDateColumns'][0];

      $recursion = new CRM_Core_BAO_RecurringEntity();
      if (CRM_Utils_Array::value('dateColumns', CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $recursion->dateColumns = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['dateColumns'];
      }
      $recursion->scheduleFormValues = $formValues;
      if (!empty($formValues['exclude_date_list'])) {
        $recursion->excludeDates = explode(',', $formValues['exclude_date_list']);
      }
      if (CRM_Utils_Array::value('excludeDateRangeColumns', CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $recursion->excludeDateRangeColumns = CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['excludeDateRangeColumns'];
      }

      if (!empty($formValues['entity_id'])) {
        $parentEntityId = CRM_Core_BAO_RecurringEntity::getParentFor($formValues['entity_id'], $formValues['entity_table']);
      }

      // Get original entity
      $original[$startDateColumnName] = CRM_Utils_Date::processDate($formValues['repetition_start_date']);
      $daoName = CRM_Core_BAO_RecurringEntity::$_tableDAOMapper[$formValues['entity_table']];
      if ($parentEntityId) {
        $startDate = $original[$startDateColumnName] = CRM_Core_DAO::getFieldValue($daoName, $parentEntityId, $startDateColumnName);
        $endDate = $original[$startDateColumnName] = $endDateColumnName ? CRM_Core_DAO::getFieldValue($daoName, $parentEntityId, $endDateColumnName) : NULL;
      }

      //Check if there is any enddate column defined to find out the interval between the two range
      if (CRM_Utils_Array::value('intervalDateColumns', CRM_Core_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        if ($endDate) {
          $interval = $recursion->getInterval($startDate, $endDate);
          $recursion->intervalDateColumns = [$endDateColumnName => $interval];
        }
      }

      $dates = array_merge([$original], $recursion->generateRecursiveDates());

      foreach ($dates as $key => &$value) {
        if ($startDateColumnName) {
          $value['start_date'] = CRM_Utils_Date::customFormat($value[$startDateColumnName]);
        }
        if ($endDateColumnName && !empty($value[$endDateColumnName])) {
          $value['end_date'] = CRM_Utils_Date::customFormat($value[$endDateColumnName]);
          $endDates = TRUE;
        }
      }

      //Show the list of participants registered for the events if any
      if ($formValues['entity_table'] == "civicrm_event" && !empty($parentEntityId)) {
        $getConnectedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($parentEntityId, 'civicrm_event', TRUE);
        if ($getConnectedEntities) {
          $participantDetails = CRM_Event_Form_ManageEvent_Repeat::getParticipantCountforEvent($getConnectedEntities);
          if (!empty($participantDetails['countByName'])) {
            $this->assign('participantData', $participantDetails['countByName']);
          }
        }
      }
    }
    $this->assign('dates', $dates);
    $this->assign('endDates', !empty($endDates));

    return parent::run();
  }

}
