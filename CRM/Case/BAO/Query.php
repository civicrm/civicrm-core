<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Case_BAO_Query {

  /**
   * @param bool $excludeActivityFields
   *
   * @return array
   */
  static function &getFields($excludeActivityFields = FALSE) {
    $fields = array();
    $fields = CRM_Case_BAO_Case::exportableFields();

    // add activity related fields
    if (!$excludeActivityFields) {
      $fields = array_merge($fields, CRM_Activity_BAO_Activity::exportableFields('Case'));
    }

    return $fields;
  }

  /**
   * build select for Case
   *
   * @param $query
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    if (($query->_mode & CRM_Contact_BAO_Query::MODE_CASE) || !empty($query->_returnProperties['case_id'])) {
      $query->_select['case_id'] = "civicrm_case.id as case_id";
      $query->_element['case_id'] = 1;
      $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
      $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
    }

    if (!empty($query->_returnProperties['case_type_id'])) {
      $query->_select['case_type_id'] = "civicrm_case_type.id as case_type_id";
      $query->_element['case_type_id'] = 1;
      $query->_tables['case_type'] = $query->_whereTables['case_type'] = 1;
      $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_type'])) {
      $query->_select['case_type'] = "civicrm_case_type.title as case_type";
      $query->_element['case_type'] = 1;
      $query->_tables['case_type'] = $query->_whereTables['case_type'] = 1;
      $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_start_date'])) {
      $query->_select['case_start_date'] = "civicrm_case.start_date as case_start_date";
      $query->_element['case_start_date'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_end_date'])) {
      $query->_select['case_end_date'] = "civicrm_case.end_date as case_end_date";
      $query->_element['case_end_date'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_status_id'])) {
      $query->_select['case_status_id'] = "case_status.id as case_status_id";
      $query->_element['case_status_id'] = 1;
      $query->_tables['case_status_id'] = $query->_whereTables['case_status_id'] = 1;
      $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_status'])) {
      $query->_select['case_status'] = "case_status.label as case_status";
      $query->_element['case_status'] = 1;
      $query->_tables['case_status_id'] = $query->_whereTables['case_status_id'] = 1;
      $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_deleted'])) {
      $query->_select['case_deleted'] = "civicrm_case.is_deleted as case_deleted";
      $query->_element['case_deleted'] = 1;
      $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_role'])) {
      $query->_select['case_role'] = "case_relation_type.label_b_a as case_role";
      $query->_element['case_role'] = 1;
      $query->_tables['case_relationship'] = $query->_whereTables['case_relationship'] = 1;
      $query->_tables['case_relation_type'] = $query->_whereTables['case_relation_type'] = 1;
    }

    if (!empty($query->_returnProperties['case_recent_activity_date'])) {
      $query->_select['case_recent_activity_date'] = "case_activity.activity_date_time as case_recent_activity_date";
      $query->_element['case_recent_activity_date'] = 1;
      $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_subject'])) {
      $query->_select['case_activity_subject'] = "case_activity.subject as case_activity_subject";
      $query->_element['case_activity_subject'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_subject'])) {
      $query->_select['case_subject'] = "civicrm_case.subject as case_subject";
      $query->_element['case_subject'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_source_contact_id'])) {
      $query->_select['case_source_contact_id'] = "civicrm_case_reporter.sort_name as case_source_contact_id";
      $query->_element['case_source_contact_id'] = 1;
      $query->_tables['civicrm_case_reporter'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_status_id'])) {
      $query->_select['case_activity_status_id'] = "rec_activity_status.id as case_activity_status_id";
      $query->_element['case_activity_status_id'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['recent_activity_status'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_status'])) {
      $query->_select['case_activity_status'] = "rec_activity_status.label as case_activity_status";
      $query->_element['case_activity_status'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['recent_activity_status'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_duration'])) {
      $query->_select['case_activity_duration'] = "case_activity.duration as case_activity_duration";
      $query->_element['case_activity_duration'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_medium_id'])) {
      $query->_select['case_activity_medium_id'] = "recent_activity_medium.label as case_activity_medium_id";
      $query->_element['case_activity_medium_id'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['case_activity_medium'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_details'])) {
      $query->_select['case_activity_details'] = "case_activity.details as case_activity_details";
      $query->_element['case_activity_details'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_is_auto'])) {
      $query->_select['case_activity_is_auto'] = "case_activity.is_auto as case_activity_is_auto";
      $query->_element['case_activity_is_auto'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_scheduled_activity_date'])) {
      $query->_select['case_scheduled_activity_date'] = "case_activity.activity_date_time as case_scheduled_activity_date";
      $query->_element['case_scheduled_activity_date'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }
    if (!empty($query->_returnProperties['case_recent_activity_type'])) {
      $query->_select['case_recent_activity_type'] = "rec_activity_type.label as case_recent_activity_type";
      $query->_element['case_recent_activity_type'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['case_activity_type'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }
  }

  /**
   * Given a list of conditions in query generate the required
   * where clause
   *
   * @param $query
   *
   * @return void
   * @access public
   */
  static function where(&$query) {
    foreach ($query->_params as $id => $values) {
      if (!is_array($values) || count($values) != 5) {
        continue;
      }

      if (substr($query->_params[$id][0], 0, 5) == 'case_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * where clause for a single field
   *
   * @param $values
   * @param $query
   *
   * @return void
   * @access public
   */
  static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $val = $names = array();
    switch ($name) {
      case 'case_status':
      case 'case_status_id':
        $statuses = CRM_Case_PseudoConstant::caseStatus();
        // Standardize input from checkboxes or single value
        if (is_array($value)) {
          $value = array_keys($value, 1);
        }
        foreach ((array) $value as $k) {
          if ($k && isset($statuses[$k])) {
            $val[$k] = $k;
            $names[] = $statuses[$k];
          }
          elseif ($k && ($v = CRM_Utils_Array::key($k, $statuses))) {
            $val[$v] = $v;
            $names[] = $k;
          }
        }
        if ($val) {
          $query->_where[$grouping][] = "civicrm_case.status_id IN (" . implode(',', $val) . ")";
          $query->_qill[$grouping][] = ts('Case Status is %1', array(1 => implode(' ' . ts('or') . ' ', $names)));
          $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        }
        return;

      case 'case_type_id':
        $caseTypes = CRM_Case_PseudoConstant::caseType('title', FALSE);

        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$k] = $k;
              $names[] = $caseTypes[$k];
            }
          }
        }
        elseif (is_numeric($value)) {
          $val[$value] = $value;
          $names[] = $value;
        }
        elseif ($caseTypeId = CRM_Utils_Array::key($value, $caseTypes)) {
          $val[$caseTypeId] = $caseTypeId;
          $names[] = $caseTypes[$caseTypeId];
        }

        $query->_where[$grouping][] = "(civicrm_case.case_type_id IN (" . implode(',', $val) . "))";

        $query->_qill[$grouping][] = ts('Case Type is %1', array(1 => implode(' ' . ts('or') . ' ', $names)));
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        return;

      case 'case_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case.id", $op, $value, 'Int');
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        return;

      case 'case_owner':
      case 'case_mycases':
        if (!empty($value)) {
          if ($value == 2) {
            $session = CRM_Core_Session::singleton();
            $userID = $session->get('userID');
            $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_relationship.contact_id_b", $op, $userID, 'Int');
            $query->_qill[$grouping][] = ts('Case %1 My Cases', array(1 => $op));
            $query->_tables['case_relationship'] = $query->_whereTables['case_relationship'] = 1;
          }
          elseif ($value == 1) {
            $query->_qill[$grouping][] = ts('Case %1 All Cases', array(1 => $op));
            $query->_where[$grouping][] = "civicrm_case_contact.contact_id = contact_a.id";
          }
          $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
          $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        }
        return;

      case 'case_deleted':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case.is_deleted", $op, $value, 'Boolean');
        if ($value) {
          $query->_qill[$grouping][] = ts("Find Deleted Cases");
        }
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        return;

      case 'case_activity_subject':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.subject", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Activity Subject %1 '%2'", array(1 => $op, 2 => $value));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_subject':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case.subject", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Case Subject %1 '%2'", array(1 => $op, 2 => $value));
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_source_contact_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case_reporter.sort_name", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Activity Reporter %1 '%2'", array(1 => $op, 2 => $value));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case_reporter'] = $query->_whereTables['civicrm_case_reporter'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_recent_activity_date':
        $date = CRM_Utils_Date::format($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.activity_date_time", $op, $date, 'Date');
        if ($date) {
          $date = CRM_Utils_Date::customFormat($date);
          $query->_qill[$grouping][] = ts("Activity Actual Date %1 %2", array(1 => $op, 2 => $date));
        }
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_scheduled_activity_date':
        $date = CRM_Utils_Date::format($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.activity_date_time", $op, $date, 'Date');
        if ($date) {
          $date = CRM_Utils_Date::customFormat($date);
          $query->_qill[$grouping][] = ts("Activity Schedule Date %1 %2", array(1 => $op, 2 => $date));
        }
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_recent_activity_type':
        $names = $value;
        if ($activityType = CRM_Core_OptionGroup::getLabel('activity_type', $value, 'value')) {
          $names = $activityType;
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.activity_type_id", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Type %1 %2", array(1 => $op, 2 => $names));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['case_activity_type'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_status_id':
        $names = $value;
        if ($activityStatus = CRM_Core_OptionGroup::getLabel('activity_status', $value, 'value')) {
          $names = $activityStatus;
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.status_id", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Type %1 %2", array(1 => $op, 2 => $names));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['case_activity_status'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_duration':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.duration", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Duration %1 %2", array(1 => $op, 2 => $value));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_medium_id':
        $names = $value;
        if ($activityMedium = CRM_Core_OptionGroup::getLabel('encounter_medium', $value, 'value')) {
          $names = $activityMedium;
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.medium_id", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Medium %1 %2", array(1 => $op, 2 => $names));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['case_activity_medium'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_details':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.details", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Activity Details %1 '%2'", array(1 => $op, 2 => $value));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_is_auto':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.is_auto", $op, $value, 'Boolean');
        $query->_qill[$grouping][] = ts("Activity Auto Genrated %1 '%2'", array(1 => $op, 2 => $value));
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      // adding where clause for case_role

      case 'case_role':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_relation_type.name_b_a", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Role in Case  %1 '%2'", array(1 => $op, 2 => $value));
        $query->_tables['case_relation_type'] = $query->_whereTables['case_relationship_type'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_from_start_date_low':
      case 'case_from_start_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_case', 'case_from_start_date', 'start_date', 'Start Date'
        );
        return;

      case 'case_to_end_date_low':
      case 'case_to_end_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_case', 'case_to_end_date', 'end_date', 'End Date'
        );
        return;

      case 'case_start_date':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case.start_date", $op, $value, 'Int');
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        return;

      case 'case_end_date':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case.end_date", $op, $value, 'Int');
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        return;

      case 'case_taglist':
        $taglist = $value;
        $value = array();
        foreach ($taglist as $val) {
          if ($val) {
            $val = explode(',', $val);
            foreach ($val as $tId) {
              if (is_numeric($tId)) {
                $value[$tId] = 1;
              }
            }
          }
        }
      case 'case_tags':
        $tags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));

        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$k] = $k;
              $names[] = $tags[$k];
            }
          }
        }

        $query->_where[$grouping][] = " civicrm_case_tag.tag_id IN (" . implode(',', $val) . " )";
        $query->_qill[$grouping][] = ts('Case Tags %1', array(1 => $op)) . ' ' . implode(' ' . ts('or') . ' ', $names);
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        $query->_tables['civicrm_case_tag'] = $query->_whereTables['civicrm_case_tag'] = 1;
        return;
    }
  }

  /**
   * @param $name
   * @param $mode
   * @param $side
   *
   * @return string
   */
  static function from($name, $mode, $side) {
    $from = "";

    switch ($name) {
      case 'civicrm_case_contact':
        $from .= " $side JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id ";
        break;

      case 'civicrm_case_reporter':
        $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
        $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
        $from .= " $side JOIN civicrm_activity_contact as case_activity_contact ON (case_activity.id = case_activity_contact.activity_id AND  case_activity_contact.record_type_id = {$sourceID} ) ";
        $from .= " $side JOIN civicrm_contact as civicrm_case_reporter ON case_activity_contact.contact_id = civicrm_case_reporter.id ";
        break;

      case 'civicrm_case':
        $from .= " INNER JOIN civicrm_case ON civicrm_case_contact.case_id = civicrm_case.id";
        break;

      case 'case_status_id':
        $from .= " $side JOIN civicrm_option_group option_group_case_status ON (option_group_case_status.name = 'case_status')";
        $from .= " $side JOIN civicrm_option_value case_status ON (civicrm_case.status_id = case_status.value AND option_group_case_status.id = case_status.option_group_id ) ";
        break;

      case 'case_type':
        $from .= " $side JOIN civicrm_case_type ON civicrm_case.case_type_id = civicrm_case_type.id ";
        break;

      case 'case_activity_type':
        $from .= " $side JOIN civicrm_option_group option_group_activity_type ON (option_group_activity_type.name = 'activity_type')";
        $from .= " $side JOIN civicrm_option_value rec_activity_type ON (case_activity.activity_type_id = rec_activity_type.value AND option_group_activity_type.id = rec_activity_type.option_group_id ) ";
        break;

      case 'recent_activity_status':
        $from .= " $side JOIN civicrm_option_group option_group_activity_status ON (option_group_activity_status.name = 'activity_status')";
        $from .= " $side JOIN civicrm_option_value rec_activity_status ON (case_activity.status_id = rec_activity_status.value AND option_group_activity_status.id = rec_activity_status.option_group_id ) ";
        break;

      case 'case_relationship':
        $session = CRM_Core_Session::singleton();
        $userID  = $session->get('userID');
        $from   .= " $side JOIN civicrm_relationship case_relationship ON ( case_relationship.contact_id_a = civicrm_case_contact.contact_id AND case_relationship.contact_id_b = {$userID} AND case_relationship.case_id = civicrm_case.id )";
        break;

      case 'case_relation_type':
        $from .= " $side JOIN civicrm_relationship_type case_relation_type ON ( case_relation_type.id = case_relationship.relationship_type_id AND
case_relation_type.id = case_relationship.relationship_type_id )";
        break;

      case 'case_activity_medium':
        $from .= " $side JOIN civicrm_option_group option_group_activity_medium ON (option_group_activity_medium.name = 'encounter_medium')";
        $from .= " $side JOIN civicrm_option_value recent_activity_medium ON (case_activity.medium_id = recent_activity_medium.value AND option_group_activity_medium.id = recent_activity_medium.option_group_id ) ";
        break;

      case 'case_activity':
        $from .= " INNER JOIN civicrm_case_activity ON civicrm_case_activity.case_id = civicrm_case.id ";
        $from .= " INNER JOIN civicrm_activity case_activity ON ( civicrm_case_activity.activity_id = case_activity.id
                                                                AND case_activity.is_current_revision = 1 )";
        break;

      case 'civicrm_case_tag':
        $from .= " $side JOIN civicrm_entity_tag as civicrm_case_tag ON ( civicrm_case_tag.entity_table = 'civicrm_case' AND civicrm_case_tag.entity_id = civicrm_case.id ) ";
        break;
    }
    return $from;
  }

  /**
   * getter for the qill object
   *
   * @return string
   * @access public
   */
  function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  static function defaultReturnProperties($mode,
    $includeCustomFields = TRUE
  ) {

    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_CASE) {
      $properties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'contact_id' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'case_id' => 1,
        'case_activity_subject' => 1,
        'case_subject' => 1,
        'case_status' => 1,
        'case_type' => 1,
        'case_role' => 1,
        'case_deleted' => 1,
        'case_recent_activity_date' => 1,
        'case_recent_activity_type' => 1,
        'case_scheduled_activity_date' => 1,
        'phone' => 1,
        // 'case_scheduled_activity_type'=>      1
      );

      if ($includeCustomFields) {
        // also get all the custom case properties
        $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Case');
        if (!empty($fields)) {
          foreach ($fields as $name => $dontCare) {
            $properties[$name] = 1;
          }
        }
      }
    }

    return $properties;
  }

  /**
   * This includes any extra fields that might need for export etc
   */
  static function extraReturnProperties($mode) {
    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_CASE) {
      $properties = array(
        'case_start_date' => 1,
        'case_end_date' => 1,
        'case_subject' => 1,
        'case_source_contact_id' => 1,
        'case_activity_status' => 1,
        'case_activity_duration' => 1,
        'case_activity_medium_id' => 1,
        'case_activity_details' => 1,
        'case_activity_is_auto' => 1,
      );
    }
    return $properties;
  }

  /**
   * @param $tables
   */
  static function tableNames(&$tables) {
    if (!empty($tables['civicrm_case'])) {
      $tables = array_merge(array('civicrm_case_contact' => 1), $tables);
    }

    if (!empty($tables['case_relation_type'])) {
      $tables = array_merge(array('case_relationship' => 1), $tables);
    }
  }

  /**
   * add all the elements shared between case search and advanaced search
   *
   * @access public
   *
   * @param $form
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {
    $config = CRM_Core_Config::singleton();

    //validate case configuration.
    $configured = CRM_Case_BAO_Case::isCaseConfigured();
    $form->assign('notConfigured', !$configured['configured']);

    $caseTypes = CRM_Case_PseudoConstant::caseType('title', FALSE);
    foreach ($caseTypes as $id => $name) {
      $form->addElement('checkbox', "case_type_id[$id]", NULL, $name);
    }

    $statuses = CRM_Case_PseudoConstant::caseStatus('label', FALSE);
    foreach ($statuses as $id => $name) {
      $form->addElement('checkbox', "case_status_id[$id]", NULL, $name);
    }

    CRM_Core_Form_Date::buildDateRange($form, 'case_from', 1, '_start_date_low', '_start_date_high', ts('From'), FALSE);
    CRM_Core_Form_Date::buildDateRange($form, 'case_to',   1, '_end_date_low',   '_end_date_high',   ts('From'), FALSE);

    $form->assign('validCiviCase', TRUE);

    //give options when all cases are accessible.
    $accessAllCases = FALSE;
    if (CRM_Core_Permission::check('access all cases and activities')) {
      $accessAllCases = TRUE;
      $caseOwner = array(1 => ts('Search All Cases'), 2 => ts('Only My Cases'));
      $form->addRadio('case_owner', ts('Cases'), $caseOwner);
    }
    $form->assign('accessAllCases', $accessAllCases);

    $caseTags = CRM_Core_BAO_Tag::getTags('civicrm_case');

    if ($caseTags) {
      foreach ($caseTags as $tagID => $tagName) {
        $form->_tagElement = &$form->addElement('checkbox', "case_tags[$tagID]", NULL, $tagName);
      }
    }

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_case');
    CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_case', NULL, TRUE, FALSE);

    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $form->addElement('checkbox', 'case_deleted', ts('Deleted Cases'));
    }

    // add all the custom  searchable fields
    $extends = array('Case');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('caseGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }
    $form->setDefaults(array('case_owner' => 1));
  }

  /**
   * @param $row
   * @param $id
   */
  static function searchAction(&$row, $id) {}
}

