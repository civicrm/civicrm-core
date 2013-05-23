<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Activity_BAO_Query {

  /**
   * build select for Case
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    if (CRM_Utils_Array::value('activity_id', $query->_returnProperties)) {
      $query->_select['activity_id'] = "civicrm_activity.id as activity_id";
      $query->_element['activity_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_type_id', $query->_returnProperties)) {
      $query->_select['activity_type_id'] = "activity_type.id as activity_type_id";
      $query->_element['activity_type_id'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_type'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_type'] = 1;
    }

    if (CRM_Utils_Array::value('activity_type', $query->_returnProperties)) {
      $query->_select['activity_type'] = "activity_type.label as activity_type";
      $query->_element['activity_type'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_type'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_type'] = 1;
    }

    if (CRM_Utils_Array::value('activity_subject', $query->_returnProperties)) {
      $query->_select['activity_subject'] = "civicrm_activity.subject as activity_subject";
      $query->_element['activity_subject'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_date_time', $query->_returnProperties)) {
      $query->_select['activity_date_time'] = "civicrm_activity.activity_date_time as activity_date_time";
      $query->_element['activity_date_time'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_status_id', $query->_returnProperties)) {
      $query->_select['activity_status_id'] = "activity_status.value as activity_status_id";
      $query->_element['activity_status_id'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_status'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_status'] = 1;
    }

    if (CRM_Utils_Array::value('activity_status', $query->_returnProperties)) {
      $query->_select['activity_status'] = "activity_status.label as activity_status";
      $query->_element['activity_status'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_tables['activity_status'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
      $query->_whereTables['activity_status'] = 1;
    }

    if (CRM_Utils_Array::value('activity_duration', $query->_returnProperties)) {
      $query->_select['activity_duration'] = "civicrm_activity.duration as activity_duration";
      $query->_element['activity_duration'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_location', $query->_returnProperties)) {
      $query->_select['activity_location'] = "civicrm_activity.location as activity_location";
      $query->_element['activity_location'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_details', $query->_returnProperties)) {
      $query->_select['activity_details'] = "civicrm_activity.details as activity_details";
      $query->_element['activity_details'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('source_record_id', $query->_returnProperties)) {
      $query->_select['source_record_id'] = "civicrm_activity.source_record_id as source_record_id";
      $query->_element['source_record_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_is_test', $query->_returnProperties)) {
      $query->_select['activity_is_test'] = "civicrm_activity.is_test as activity_is_test";
      $query->_element['activity_is_test'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_campaign_id', $query->_returnProperties)) {
      $query->_select['activity_campaign_id'] = 'civicrm_activity.campaign_id as activity_campaign_id';
      $query->_element['activity_campaign_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('activity_engagement_level', $query->_returnProperties)) {
      $query->_select['activity_engagement_level'] = 'civicrm_activity.engagement_level as activity_engagement_level';
      $query->_element['activity_engagement_level'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (CRM_Utils_Array::value('source_contact', $query->_returnProperties)) {
      $query->_select['source_contact'] = 'source_contact.display_name as source_contact';
      $query->_element['source_contact'] = 1;
      $query->_tables['source_contact'] = $query->_whereTables['source_contact'] = 1;
    }
  }

  /**
   * Given a list of conditions in query generate the required
   * where clause
   *
   * @return void
   * @access public
   */
  static function where(&$query) {
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (substr($query->_params[$id][0], 0, 9) == 'activity_') {
        if ($query->_mode == CRM_Contact_BAO_QUERY::MODE_CONTACTS) {
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
   * @return void
   * @access public
   */
  static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $query->_skipDeleteClause = TRUE;
    }

    switch ($name) {
      case 'activity_type_id':
        $types = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);

        //get the component activity types.
        $compActTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE, TRUE);

        $clause = array();
        if (is_array($value)) {
          foreach ($value as $id => $dontCare) {
            if (array_key_exists($id, $types) && $dontCare) {
              $clause[] = "'" . CRM_Utils_Type::escape($types[$id], 'String') . "'";
              if (array_key_exists($id, $compActTypes)) {
                CRM_Contact_BAO_Query::$_considerCompActivities = TRUE;
              }
            }
          }
          $activityTypes = implode(',', array_keys($value));
        }
        else {
          $clause[] = "'" . CRM_Utils_Type::escape($value, 'String') . "'";
          $activityTypes = $value;
          if (array_key_exists($value, $compActTypes)) {
            CRM_Contact_BAO_Query::$_considerCompActivities = TRUE;
          }
        }
        $query->_where[$grouping][] = ' civicrm_activity.activity_type_id IN (' . $activityTypes . ')';
        $query->_qill[$grouping][] = ts('Activity Type') . ' ' . implode(' ' . ts('or') . ' ', $clause);
        break;

      case 'activity_survey_id':
        if (!$value) {
          break;
        }
        $value = CRM_Utils_Type::escape($value, 'Integer');
        $query->_where[$grouping][] = " source_record_id = $value";
        $query->_qill[$grouping][] = ts('Survey') . ' - ' . CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $value, 'title');
        break;

      case 'activity_engagement_level':
        if (!$value) {
          break;
        }

        $value = CRM_Utils_Type::escape($value, 'Integer');
        $query->_where[$grouping][] = " engagement_level = $value";
        $query->_qill[$grouping][] = ts('Engagement Index') . ' - ' . CRM_Core_OptionGroup::getLabel('engagement_index', $value);
        break;

      case 'activity_role':
        CRM_Contact_BAO_Query::$_activityRole = $values[2];

        //for activity target name
        $activityTargetName = $query->getWhereValues('activity_contact_name', $grouping);
        if (!$activityTargetName[2]) {
          $name = NULL;
        }
        else {
          $name = trim($activityTargetName[2]);
          $name = strtolower(CRM_Core_DAO::escapeString($name));
        }

        $query->_where[$grouping][] = " contact_activity_source.is_deleted = 0 AND contact_activity_source.sort_name LIKE '%{$name}%'";
        if ($values[2] == 1) {
          $query->_where[$grouping][] = " civicrm_activity.source_contact_id = contact_activity_source.id";
          $query->_qill[$grouping][] = ts('Activity created by') . " '$name'";
          $query->_tables['civicrm_activity_contact'] = $query->_whereTables['civicrm_activity_contact'] = 1;
        }
        elseif ($values[2] == 2) {
          $query->_where[$grouping][] = " civicrm_activity_assignment.activity_id = civicrm_activity.id AND civicrm_activity_assignment.assignee_contact_id = contact_activity_source.id";
          $query->_tables['civicrm_activity_assignment'] = $query->_whereTables['civicrm_activity_assignment'] = 1;
          $query->_tables['civicrm_activity_contact'] = $query->_whereTables['civicrm_activity_contact'] = 1;
          $query->_qill[$grouping][] = ts('Activity assigned to') . " '$name'";
        }
        break;

      case 'activity_status':
        $status = CRM_Core_PseudoConstant::activityStatus();
        $clause = array();
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($k) {
              $clause[] = "'" . CRM_Utils_Type::escape($status[$k], 'String') . "'";
            }
          }
        }
        else {
          $clause[] = "'" . CRM_Utils_Type::escape($value, 'String') . "'";
        }
        $query->_where[$grouping][] = ' civicrm_activity.status_id IN (' . implode(',', array_keys($value)) . ')';
        $query->_qill[$grouping][] = ts('Activity Status') . ' - ' . implode(' ' . ts('or') . ' ', $clause);
        break;

      case 'activity_subject':
        $n = trim($value);
        $value = strtolower(CRM_Core_DAO::escapeString($n));
        if ($wildcard) {
          if (strpos($value, '%') !== FALSE) {
            // only add wild card if not there
            $value = "'$value'";
          }
          else {
            $value = "'%$value%'";
          }
          $op = 'LIKE';
        }
        else {
          $value = "'$value'";
        }
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_activity.subject)" : "civicrm_activity.subject";
        $query->_where[$grouping][] = " $wc $op $value";
        $query->_qill[$grouping][] = ts('Subject') . " $op - '$n'";
        break;

      case 'activity_test':
        // We dont want to include all tests for sql OR CRM-7827
        if (!$value || $query->getOperator() != 'OR') {
            $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_activity.is_test", $op, $value, "Boolean");
          if ($value) {
            $query->_qill[$grouping][] = ts('Activity is a Test');
          }
        }
        break;

      case 'activity_date':
      case 'activity_date_low':
      case 'activity_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_activity', 'activity_date', 'activity_date_time', ts('Activity Date')
        );
        break;

      case 'activity_id':
        if (empty($value)) {
          break;
        }

        if (is_array($value)) {
          foreach ($value as $k => $v) {
            $value[$k] = CRM_Utils_Type::escape($v, 'Integer');
          }
        }
        else {
          $value = array(CRM_Utils_Type::escape($value, 'Integer'));
        }
        $query->_where[$grouping][] = "civicrm_activity.id IN (" . implode(",", $value) . ")";
        $query->_qill[$grouping][] = ts('Activity Id(s)  %1', array(1 => implode($value)));
        break;

      case 'activity_taglist':
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
      case 'activity_tags':
        $value = array_keys($value);
        $activityTags = CRM_Core_PseudoConstant::tag();

        $names = array();
        $val = array();
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            $names[] = $activityTags[$v];
          }
        }
        $query->_where[$grouping][] = "civicrm_activity_tag.tag_id IN (" . implode(",", $value) . ")";
        $query->_qill[$grouping][] = ts('Activity Tag %1', array(1 => $op)) . ' ' . implode(' ' . ts('OR') . ' ', $names);
        $query->_tables['civicrm_activity_tag'] = $query->_whereTables['civicrm_activity_tag'] = 1;
        break;

      case 'activity_campaign_id':
        $campParams = array(
          'op' => $op,
          'campaign' => $value,
          'grouping' => $grouping,
          'tableName' => 'civicrm_activity',
        );
        CRM_Campaign_BAO_Query::componentSearchClause($campParams, $query);
        return;
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_activity':
        //CRM-7480 we are going to civicrm_activitty table either
        //from civicrm_activity_target or civicrm_activity_assignment.
        //as component specific activities does not have entry in
        //activity target table so lets consider civicrm_activity_assignment.
        if (CRM_Contact_BAO_Query::$_considerCompActivities) {
          $from .= " $side JOIN civicrm_activity_target
                                      ON ( civicrm_activity_target.target_contact_id = contact_a.id ) ";

          $from .= " $side JOIN civicrm_activity_assignment activity_assignment
                                      ON ( activity_assignment.assignee_contact_id = contact_a.id )";

          $from .= " $side JOIN civicrm_activity
                                      ON ( civicrm_activity.id = civicrm_activity_target.activity_id
                                      AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )";
        }
        elseif (CRM_Contact_BAO_Query::$_withContactActivitiesOnly) {
          //force the civicrm_activity_target table.
          $from .= " $side JOIN civicrm_activity_target ON civicrm_activity_target.target_contact_id = contact_a.id ";
          $from .= " $side JOIN civicrm_activity ON ( civicrm_activity.id = civicrm_activity_target.activity_id
                                                            AND civicrm_activity.is_deleted = 0
                                                            AND civicrm_activity.is_current_revision = 1 )";
        }
        else {
          $activityRole = CRM_Contact_BAO_Query::$_activityRole;
          switch ($activityRole) {
            case 1:
              $from .= " $side JOIN civicrm_activity ON ( civicrm_activity.source_contact_id = contact_a.id
                        AND civicrm_activity.is_deleted = 0
                        AND civicrm_activity.is_current_revision = 1 )";
              break;

            case 2:
              $from .= " $side JOIN civicrm_activity_assignment activity_assignment ON ( activity_assignment.assignee_contact_id = contact_a.id )";
              $from .= " $side JOIN civicrm_activity ON ( civicrm_activity.id = activity_assignment.activity_id
                        AND civicrm_activity.is_deleted = 0
                        AND civicrm_activity.is_current_revision = 1 )";
              break;

            default:
              $from .= " $side JOIN civicrm_activity_target ON civicrm_activity_target.target_contact_id = contact_a.id ";
              $from .= " $side JOIN civicrm_activity ON ( civicrm_activity.id = civicrm_activity_target.activity_id
                            AND civicrm_activity.is_deleted = 0
                            AND civicrm_activity.is_current_revision = 1 )";
          }
        }
        break;

      case 'civicrm_activity_contact':
        $activityRole = CRM_Contact_BAO_Query::$_activityRole;
        if ($activityRole == 1) {
          $from .= " $side JOIN civicrm_contact contact_activity_source ON civicrm_activity.source_contact_id = contact_activity_source.id
                           LEFT JOIN civicrm_email email_activity_source ON (contact_activity_source.id = email_activity_source.contact_id AND email_activity_source.is_primary = 1)";
        }
        elseif ($activityRole == 2) {
          $from .= " $side JOIN civicrm_activity_assignment ON civicrm_activity.id = civicrm_activity_assignment.activity_id ";
          $from .= " $side JOIN civicrm_contact contact_activity_source ON civicrm_activity_assignment.assignee_contact_id = contact_activity_source.id
                           LEFT JOIN civicrm_email email_activity_source ON (contact_activity_source.id = email_activity_source.contact_id AND email_activity_source.is_primary = 1)";
        }
        break;

      case 'activity_status':
        $from = " $side JOIN civicrm_option_group option_group_activity_status ON (option_group_activity_status.name = 'activity_status')";
        $from .= " $side JOIN civicrm_option_value activity_status ON (civicrm_activity.status_id = activity_status.value
                               AND option_group_activity_status.id = activity_status.option_group_id ) ";
        break;

      case 'activity_type':
        $from = " $side JOIN civicrm_option_group option_group_activity_type ON (option_group_activity_type.name = 'activity_type')";
        $from .= " $side JOIN civicrm_option_value activity_type ON (civicrm_activity.activity_type_id = activity_type.value
                               AND option_group_activity_type.id = activity_type.option_group_id ) ";
        break;

      case 'civicrm_activity_tag':
        $from .= " $side JOIN civicrm_entity_tag as civicrm_activity_tag ON ( civicrm_activity_tag.entity_table = 'civicrm_activity' AND civicrm_activity_tag.entity_id = civicrm_activity.id ) ";
        break;

      case 'source_contact':
        $from = " $side JOIN civicrm_contact source_contact ON source_contact.id = civicrm_activity.source_contact_id";
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
   * add all the elements shared between case activity search  and advanaced search
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {
    $activityOptions = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    asort($activityOptions);
    foreach ($activityOptions as $activityID => $activity) {
      $form->_activityElement =&
        $form->addElement(
          'checkbox',
          "activity_type_id[$activityID]",
          NULL,
          $activity,
          array('onClick' => 'showCustomData( this.id );')
        );
    }

    CRM_Core_Form_Date::buildDateRange($form, 'activity_date', 1, '_low', '_high', ts('From'), FALSE, FALSE);

    $activityRoles = array(1 => ts('Created by'), 2 => ts('Assigned to'));
    $form->addRadio('activity_role', NULL, $activityRoles);

    $form->addElement('text', 'activity_contact_name', ts('Contact Name'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    foreach ($activityStatus as $activityStatusID => $activityStatusName) {
      $activity_status[] = $form->createElement('checkbox', $activityStatusID, NULL, $activityStatusName);
    }
    $form->addGroup($activity_status, 'activity_status', ts('Activity Status'));
    $form->setDefaults(array('activity_status[1]' => 1, 'activity_status[2]' => 1));
    $form->addElement('text', 'activity_subject', ts('Subject'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));
    $form->addYesNo('activity_test', ts('Activity is a Test?'));
    $activity_tags = CRM_Core_BAO_Tag::getTags('civicrm_activity');
    if ($activity_tags) {
      foreach ($activity_tags as $tagID => $tagName) {
        $form->_tagElement = &$form->addElement('checkbox', "activity_tags[$tagID]",
          NULL, $tagName
        );
      }
    }

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_activity');
    CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_activity', NULL, TRUE, FALSE, TRUE);

    $surveys = CRM_Campaign_BAO_Survey::getSurveys(TRUE, FALSE, FALSE, TRUE);
    if ($surveys) $form->add('select', 'activity_survey_id', ts('Survey / Petition'),
      array(
        '' => ts('- none -')) + $surveys, FALSE
    );

    $extends = array('Activity');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('activityGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, FALSE, TRUE);
        }
      }
    }

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'activity_campaign_id');

    //add engagement level CRM-7775
    $buildEngagementLevel = FALSE;
    if (CRM_Campaign_BAO_Campaign::isCampaignEnable() &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {
      $buildEngagementLevel = TRUE;
      $form->add('select', 'activity_engagement_level',
        ts('Engagement Index'),
        array('' => ts('- any -')) + CRM_Campaign_PseudoConstant::engagementLevel()
      );
    }

    $form->assign('buildEngagementLevel', $buildEngagementLevel);
    $form->setDefaults(array('activity_test' => 0));
  }

  static function addShowHide(&$showHide) {
    $showHide->addHide('caseActivityForm');
    $showHide->addShow('caseActivityForm_show');
  }

  static function defaultReturnProperties($mode, $includeCustomFields = TRUE) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $properties = array(
        'activity_id' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'activity_type' => 1,
        'activity_subject' => 1,
        'activity_date_time' => 1,
        'activity_duration' => 1,
        'activity_location' => 1,
        'activity_details' => 1,
        'activity_status' => 1,
        'source_contact_id' => 1,
        'source_record_id' => 1,
        'activity_is_test' => 1,
        'activity_campaign_id' => 1,
        'activity_engagement_level' => 1,
      );

      if ($includeCustomFields) {
        // also get all the custom activity properties
        $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Activity');
        if (!empty($fields)) {
          foreach ($fields as $name => $dontCare) {
            $properties[$name] = 1;
          }
        }
      }
    }

    return $properties;
  }
}

