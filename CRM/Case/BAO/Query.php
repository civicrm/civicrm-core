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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Case_BAO_Query extends CRM_Core_BAO_Query {

  /**
   * Get fields.
   *
   * @param bool $excludeActivityFields
   *
   * @return array
   */
  public static function &getFields($excludeActivityFields = FALSE) {
    $fields = CRM_Case_BAO_Case::exportableFields();

    // add activity related fields
    if (!$excludeActivityFields) {
      $fields = array_merge($fields, CRM_Activity_BAO_Activity::exportableFields('Case'));
    }

    return $fields;
  }

  /**
   * Build select for Case.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function select(&$query) {
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
      $query->_select['case_role'] = "IF(case_relationship.contact_id_b = contact_a.id, case_relation_type.label_b_a, case_relation_type.label_a_b) as case_role";
      $query->_element['case_role'] = 1;
      $query->_tables['case_relationship'] = $query->_whereTables['case_relationship'] = 1;
      $query->_tables['case_relation_type'] = $query->_whereTables['case_relation_type'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_date_time'])) {
      $query->_select['case_activity_date_time'] = "case_activity.activity_date_time as case_activity_date_time";
      $query->_element['case_activity_date_time'] = 1;
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

    // @todo switch to a more standard case_source_contact as the key where we want the name not the id.
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
      $query->_tables['case_activity_status'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }

    if (!empty($query->_returnProperties['case_activity_status'])) {
      $query->_select['case_activity_status'] = "rec_activity_status.label as case_activity_status";
      $query->_element['case_activity_status'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['case_activity_status'] = 1;
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
      $query->_select['case_activity_medium_id'] = "case_activity_medium.label as case_activity_medium_id";
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

    if (!empty($query->_returnProperties['case_activity_date_time'])) {
      $query->_select['case_activity_date_time'] = "case_activity.activity_date_time as case_activity_date_time";
      $query->_element['case_activity_date_time'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }
    if (!empty($query->_returnProperties['case_activity_type'])) {
      $query->_select['case_activity_type'] = "rec_activity_type.label as case_activity_type";
      $query->_element['case_activity_type'] = 1;
      $query->_tables['case_activity'] = 1;
      $query->_tables['case_activity_type'] = 1;
      $query->_tables['civicrm_case_contact'] = 1;
      $query->_tables['civicrm_case'] = 1;
    }
  }

  /**
   * Given a list of conditions in query generate the required where clause.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function where(&$query) {
    foreach ($query->_params as $id => $values) {
      if (!is_array($values) || count($values) != 5) {
        continue;
      }

      if (substr($query->_params[$id][0], 0, 5) == 'case_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
    // Add acl clause
    // This is new and so far only for cases - it would be good to find a more abstract
    // way to auto-apply this for all search components rather than copy-pasting this code to others
    if (isset($query->_tables['civicrm_case'])) {
      $aclClauses = array_filter(CRM_Case_BAO_Case::getSelectWhereClause());
      foreach ($aclClauses as $clause) {
        $query->_where[0][] = $clause;
      }
    }
  }

  /**
   * Where clause for a single field.
   *
   * CRM-17120 adds a test that checks the Qill on some of these parameters.
   * However, I couldn't find a way, other than via test, to access the
   * case_activity options in the code below and invalid sql was returned.
   * Perhaps the options are just legacy?
   *
   * Also, CRM-17120 locks in the Qill - but it probably is not quite right as I
   * see 'Activity Type = Scheduled' (rather than activity status).
   *
   * See CRM_Case_BAO_QueryTest for more.
   *
   * @param array $values
   * @param CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function whereClauseSingle(&$values, &$query) {
    if ($query->buildDateRangeQuery($values)) {
      // @todo - move this to Contact_Query in or near the call to
      // $this->buildRelativeDateQuery($values);
      return;
    }
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $fields = CRM_Case_BAO_Case::fields();
    $fieldSpec = $fields[$values[0]] ?? [];
    $val = $names = [];
    switch ($name) {

      case 'case_type_id':
      case 'case_type':
      case 'case_status':
      case 'case_status_id':
      case 'case_id':

        if (strpos($name, 'type')) {
          $name = 'case_type_id';
          $label = 'Case Type(s)';
        }
        elseif (strpos($name, 'status')) {
          $name = 'status_id';
          $label = 'Case Status(s)';
        }
        else {
          $name = 'id';
          $label = 'Case ID';
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case.{$name}", $op, $value, "Integer");
        $query->_qill[$grouping][] = CRM_Contact_BAO_Query::getQillValue('CRM_Case_DAO_Case', $name, $value, $op, $label);
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        return;

      case 'case_owner':
      case 'case_mycases':
        if (!empty($value)) {
          if ($value == 2) {
            $session = CRM_Core_Session::singleton();
            $userID = $session->get('userID');
            $query->_where[$grouping][] = ' (( ' . CRM_Contact_BAO_Query::buildClause("case_relationship.contact_id_b", $op, $userID, 'Int') . ' AND ' . CRM_Contact_BAO_Query::buildClause("case_relationship.is_active", '<>', 0, 'Int') . ' ) OR ( ' . CRM_Contact_BAO_Query::buildClause("case_relationship.contact_id_a", $op, $userID, 'Int') . ' AND ' . CRM_Contact_BAO_Query::buildClause("case_relationship.is_active", '<>', 0, 'Int') . ' ))';
            $query->_qill[$grouping][] = ts('Case %1 My Cases', [1 => $op]);
            $query->_tables['case_relationship'] = $query->_whereTables['case_relationship'] = 1;
          }
          elseif ($value == 1) {
            $query->_qill[$grouping][] = ts('Case %1 All Cases', [1 => $op]);
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
        $query->_qill[$grouping][] = ts("Activity Subject %1 '%2'", [1 => $op, 2 => $value]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_subject':
        $query->handleWhereFromMetadata($fieldSpec, $name, $value, $op);
        return;

      // @todo switch to a more standard case_source_contact as the key where we want the name not the id.
      case 'case_source_contact_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_case_reporter.sort_name", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Activity Reporter %1 '%2'", [1 => $op, 2 => $value]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case_reporter'] = $query->_whereTables['civicrm_case_reporter'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_date_time':
        $date = CRM_Utils_Date::format($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.activity_date_time", $op, $date, 'Date');
        if ($date) {
          $date = CRM_Utils_Date::customFormat($date);
          $query->_qill[$grouping][] = ts("Activity Date %1 %2", [1 => $op, 2 => $date]);
        }
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_type':
        $names = $value;
        if (($activityType = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $value)) != FALSE) {
          $names = $activityType;
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.activity_type_id", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Type %1 %2", [1 => $op, 2 => $names]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['case_activity_type'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_status_id':
        $names = $value;
        if (($activityStatus = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'status_id', $value)) != FALSE) {
          $names = $activityStatus;
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.status_id", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Status %1 %2", [1 => $op, 2 => $names]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['case_activity_status'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_duration':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.duration", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Duration %1 %2", [1 => $op, 2 => $value]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_medium_id':
        $names = $value;
        if (($activityMedium = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'medium_id', $value)) != FALSE) {
          $names = $activityMedium;
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.medium_id", $op, $value, 'Int');
        $query->_qill[$grouping][] = ts("Activity Medium %1 %2", [1 => $op, 2 => $names]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['case_activity_medium'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_details':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.details", $op, $value, 'String');
        $query->_qill[$grouping][] = ts("Activity Details %1 '%2'", [1 => $op, 2 => $value]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      case 'case_activity_is_auto':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("case_activity.is_auto", $op, $value, 'Boolean');
        $query->_qill[$grouping][] = ts("Activity Auto Generated %1 '%2'", [1 => $op, 2 => $value]);
        $query->_tables['case_activity'] = $query->_whereTables['case_activity'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        return;

      // adding where clause for case_role

      case 'case_role':
        $query->_qill[$grouping][] = ts("Role in Case  %1 '%2'", [1 => $op, 2 => $value]);
        $query->_tables['case_relation_type'] = $query->_whereTables['case_relationship_type'] = 1;
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
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
        $value = [];
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
        $tags = CRM_Core_DAO_EntityTag::buildOptions('tag_id', 'get');

        if (!empty($value)) {
          if (is_array($value)) {
            // Search tag(s) are part of a tag set
            $val = array_keys($value);
          }
          else {
            // Search tag(s) are part of the tag tree
            $val = explode(',', $value);
          }
          foreach ($val as $v) {
            if ($v) {
              $names[] = $tags[$v];
            }
          }
        }

        $query->_where[$grouping][] = " civicrm_case_tag.tag_id IN (" . implode(',', $val) . " )";
        $query->_qill[$grouping][] = ts('Case Tags %1', [1 => $op]) . ' ' . implode(' ' . ts('or') . ' ', $names);
        $query->_tables['civicrm_case'] = $query->_whereTables['civicrm_case'] = 1;
        $query->_tables['civicrm_case_contact'] = $query->_whereTables['civicrm_case_contact'] = 1;
        $query->_tables['civicrm_case_tag'] = $query->_whereTables['civicrm_case_tag'] = 1;
        return;
    }
  }

  /**
   * Build from clause.
   *
   * @param string $name
   * @param string $mode
   * @param string $side
   *
   * @return string
   */
  public static function from($name, $mode, $side) {
    $from = "";

    switch ($name) {
      case 'civicrm_case_contact':
        $from .= " $side JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id ";
        break;

      case 'civicrm_case_reporter':
        $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
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

      case 'case_activity_status':
        $from .= " $side JOIN civicrm_option_group option_group_activity_status ON (option_group_activity_status.name = 'activity_status')";
        $from .= " $side JOIN civicrm_option_value rec_activity_status ON (case_activity.status_id = rec_activity_status.value AND option_group_activity_status.id = rec_activity_status.option_group_id ) ";
        break;

      case 'case_relationship':
        $session = CRM_Core_Session::singleton();
        $userID = $session->get('userID');
        $from .= " $side JOIN civicrm_relationship case_relationship ON ( case_relationship.contact_id_a = civicrm_case_contact.contact_id AND case_relationship.contact_id_b = {$userID} AND case_relationship.case_id = civicrm_case.id OR case_relationship.contact_id_b = civicrm_case_contact.contact_id AND case_relationship.contact_id_a = {$userID} AND case_relationship.case_id = civicrm_case.id )";
        break;

      case 'case_relation_type':
        $from .= " $side JOIN civicrm_relationship_type case_relation_type ON ( case_relation_type.id = case_relationship.relationship_type_id AND
case_relation_type.id = case_relationship.relationship_type_id )";
        break;

      case 'case_activity_medium':
        $from .= " $side JOIN civicrm_option_group option_group_activity_medium ON (option_group_activity_medium.name = 'encounter_medium')";
        $from .= " $side JOIN civicrm_option_value case_activity_medium ON (case_activity.medium_id = case_activity_medium.value AND option_group_activity_medium.id = case_activity_medium.option_group_id ) ";
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
   * Getter for the qill object.
   *
   * @return string
   */
  public function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {

    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_CASE) {
      $properties = [
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
        'case_activity_date_time' => 1,
        'case_activity_type' => 1,
        'phone' => 1,
      ];

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
   * This includes any extra fields that might need for export etc.
   *
   * @param string $mode
   *
   * @return array|null
   */
  public static function extraReturnProperties($mode) {
    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_CASE) {
      $properties = [
        'case_start_date' => 1,
        'case_end_date' => 1,
        'case_subject' => 1,
        // @todo switch to a more standard case_source_contact as the key where we want the name not the id.
        'case_source_contact_id' => 1,
        'case_activity_status' => 1,
        'case_activity_duration' => 1,
        'case_activity_medium_id' => 1,
        'case_activity_details' => 1,
        'case_activity_is_auto' => 1,
      ];
    }
    return $properties;
  }

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {
    if (!empty($tables['civicrm_case'])) {
      $tables = array_merge(['civicrm_case_contact' => 1], $tables);
    }

    if (!empty($tables['case_relation_type'])) {
      $tables = array_merge(['case_relationship' => 1], $tables);
    }
  }

  /**
   * Get the metadata for fields to be included on the case search form.
   *
   * @todo ideally this would be a trait included on the case search & advanced search
   * rather than a static function.
   */
  public static function getSearchFieldMetadata() {
    $fields = ['case_type_id', 'case_status_id', 'case_start_date', 'case_end_date', 'case_subject', 'case_id', 'case_deleted'];
    $metadata = civicrm_api3('Case', 'getfields', [])['values'];
    $metadata['case_id'] = $metadata['id'];
    $metadata = array_intersect_key($metadata, array_flip($fields));
    $metadata['case_tags'] = [
      'title' => ts('Case Tag(s)'),
      'type' => CRM_Utils_Type::T_INT,
      'is_pseudofield' => TRUE,
      'html' => ['type' => 'Select2'],
    ];
    if (CRM_Core_Permission::check('access all cases and activities')) {
      $metadata['case_owner'] = [
        'title' => ts('Cases'),
        'type' => CRM_Utils_Type::T_INT,
        'is_pseudofield' => TRUE,
        'html' => ['type' => 'Radio'],
      ];
    }
    if (!CRM_Core_Permission::check('administer CiviCase')) {
      unset($metadata['case_deleted']);
    }
    return $metadata;
  }

  /**
   * Add all the elements shared between case search and advanced search.
   *
   * @param CRM_Case_Form_Search $form
   */
  public static function buildSearchForm(&$form): void {
    $form->addOptionalQuickFormElement('upcoming');
    //validate case configuration.
    $configured = CRM_Case_BAO_Case::isCaseConfigured();
    $form->assign('notConfigured', !$configured['configured']);

    $form->addSearchFieldMetadata(['Case' => self::getSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();

    $form->assign('validCiviCase', TRUE);

    //give options when all cases are accessible.
    $accessAllCases = FALSE;
    if (CRM_Core_Permission::check('access all cases and activities')) {
      $accessAllCases = TRUE;
      $caseOwner = [1 => ts('Search All Cases'), 2 => ts('Only My Cases')];
      $form->addRadio('case_owner', ts('Cases'), $caseOwner);
      if ($form->get('context') != 'dashboard') {
        $form->add('checkbox', 'upcoming', ts('Search Cases with Upcoming Activities'));
      }
    }
    $form->assign('accessAllCases', $accessAllCases);

    $caseTags = CRM_Core_BAO_Tag::getColorTags('civicrm_case');

    if ($caseTags) {
      $form->add('select2', 'case_tags', ts('Case Tag(s)'), $caseTags, FALSE, ['class' => 'big', 'placeholder' => ts('- select -'), 'multiple' => TRUE]);
    }

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_case');
    CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_case', NULL, TRUE, FALSE);

    self::addCustomFormFields($form, ['Case']);

    $form->setDefaults(['case_owner' => 1]);
  }

}
