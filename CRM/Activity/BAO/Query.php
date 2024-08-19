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
class CRM_Activity_BAO_Query {

  /**
   * Build select for Case.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function select(&$query) {
    if (!empty($query->_returnProperties['activity_id'])) {
      $query->_select['activity_id'] = 'civicrm_activity.id as activity_id';
      $query->_element['activity_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_type_id'])
      || !empty($query->_returnProperties['activity_type'])
    ) {
      $query->_select['activity_type_id'] = 'civicrm_activity.activity_type_id';
      $query->_element['activity_type_id'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_subject'])) {
      $query->_select['activity_subject'] = 'civicrm_activity.subject as activity_subject';
      $query->_element['activity_subject'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_date_time'])) {
      $query->_select['activity_date_time'] = 'civicrm_activity.activity_date_time as activity_date_time';
      $query->_element['activity_date_time'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_status_id'])) {
      $query->_select['activity_status_id'] = 'civicrm_activity.status_id as activity_status_id';
      $query->_element['activity_status_id'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_status'])) {
      $query->_select['activity_status_id'] = 1;
      $query->_element['activity_status'] = 1;
      $query->_tables['civicrm_activity'] = 1;
      $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_duration'])) {
      $query->_select['activity_duration'] = 'civicrm_activity.duration as activity_duration';
      $query->_element['activity_duration'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_location'])) {
      $query->_select['activity_location'] = 'civicrm_activity.location as activity_location';
      $query->_element['activity_location'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_details'])) {
      $query->_select['activity_details'] = 'civicrm_activity.details as activity_details';
      $query->_element['activity_details'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['source_record_id'])) {
      $query->_select['source_record_id'] = 'civicrm_activity.source_record_id as source_record_id';
      $query->_element['source_record_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_is_test'])) {
      $query->_select['activity_is_test'] = 'civicrm_activity.is_test as activity_is_test';
      $query->_element['activity_is_test'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_campaign_id'])) {
      $query->_select['activity_campaign_id'] = 'civicrm_activity.campaign_id as activity_campaign_id';
      $query->_element['activity_campaign_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['activity_engagement_level'])) {
      $query->_select['activity_engagement_level'] = 'civicrm_activity.engagement_level as activity_engagement_level';
      $query->_element['activity_engagement_level'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['source_contact'])) {
      $query->_select['source_contact'] = 'source_contact.sort_name as source_contact';
      $query->_element['source_contact'] = 1;
      $query->_tables['civicrm_activity'] = $query->_tables['source_contact'] = $query->_whereTables['source_contact'] = 1;
    }

    if (!empty($query->_returnProperties['source_contact_id'])) {
      $query->_select['source_contact_id'] = 'source_contact.id as source_contact_id';
      $query->_element['source_contact_id'] = 1;
      $query->_tables['civicrm_activity'] = $query->_tables['source_contact'] = $query->_whereTables['source_contact'] = 1;
    }

    if (!empty($query->_returnProperties['activity_result'])) {
      $query->_select['activity_result'] = 'civicrm_activity.result as activity_result';
      $query->_element['result'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }

    if (!empty($query->_returnProperties['parent_id'])) {
      $query->_tables['parent_id'] = 1;
      $query->_whereTables['parent_id'] = 1;
      $query->_element['parent_id'] = 1;
    }

    if (!empty($query->_returnProperties['activity_priority'])) {
      $query->_select['activity_priority'] = 'activity_priority.label as activity_priority,
      civicrm_activity.priority_id as priority_id';
      $query->_element['activity_priority'] = 1;
      $query->_tables['activity_priority'] = 1;
      $query->_whereTables['activity_priority'] = 1;
      $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    }
  }

  /**
   * Given a list of conditions in query generate the required where clause.
   *
   * @param \CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function where(&$query) {
    foreach (array_keys($query->_params) as $id) {
      if (substr($query->_params[$id][0], 0, 9) === 'activity_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * Where clause for a single field.
   *
   * @param array $values
   * @param CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function whereClauseSingle(&$values, &$query) {
    [$name, $op, $value, $grouping] = $values;

    $fields = CRM_Activity_BAO_Activity::exportableFields();
    $fieldSpec = $query->getFieldSpec($name);

    $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $query->_skipDeleteClause = TRUE;
    }

    switch ($name) {
      case 'activity_type':
      case 'activity_type_id':
      case 'activity_status':
      case 'activity_status_id':
      case 'activity_engagement_level':
      case 'activity_id':
      case 'activity_campaign_id':
      case 'activity_priority_id':
        // We no longer expect "subject" as a specific criteria (as of CRM-19447),
        // but we still use activity_subject in Activity.Get API
      case 'activity_subject':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldSpec['where'], $op, $value, $query->getDataTypeForRealField($name));
        $query->_qill[$grouping][]  = $query->getQillForField($fieldSpec['is_pseudofield_for'] ?? $fieldSpec['name'], $value, $op, $fieldSpec);
        break;

      case 'activity_text':
        self::whereClauseSingleActivityText($values, $query);
        break;

      case 'activity_priority':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("$name.label", $op, $value, 'String');
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Activity_DAO_Activity', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [
          1 => $fields[$name]['title'],
          2 => $op,
          3 => $value,
        ]);
        $query->_tables[$name] = $query->_whereTables[$name] = 1;
        break;

      case 'activity_survey_id':
        if (!$value) {
          break;
        }
        $value = CRM_Utils_Type::escape($value, 'Integer');
        $query->_where[$grouping][] = " civicrm_activity.source_record_id = $value";
        $query->_qill[$grouping][] = ts('Survey') . ' - ' . CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $value, 'title');
        break;

      case 'activity_role':
        CRM_Contact_BAO_Query::$_activityRole = $values[2];
        $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
        $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
        $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

        if ($values[2]) {
          $query->_tables['civicrm_activity_contact'] = $query->_whereTables['civicrm_activity_contact'] = 1;
          if ($values[2] == 1) {
            $query->_where[$grouping][] = " civicrm_activity_contact.record_type_id = $sourceID";
            $query->_qill[$grouping][] = ts('Activity created by');
          }
          elseif ($values[2] == 2) {
            $query->_where[$grouping][] = " civicrm_activity_contact.record_type_id = $assigneeID";
            $query->_qill[$grouping][] = ts('Activity assigned to');
          }
          elseif ($values[2] == 3) {
            $query->_where[$grouping][] = " civicrm_activity_contact.record_type_id = $targetID";
            $query->_qill[$grouping][] = ts('Activity targeted to');
          }
        }
        break;

      case 'activity_test':
        // We don't want to include all tests for sql OR CRM-7827
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
      case 'activity_date_time_low':
      case 'activity_date_time_high':
        $query->dateQueryBuilder($values,
          'civicrm_activity', str_replace([
            '_high',
            '_low',
          ], '', $name), 'activity_date_time', ts('Activity Date')
        );
        break;

      case 'activity_taglist':
        $taglist = $value;
        $value = [];
        foreach ($taglist as $val) {
          if ($val) {
            $val = explode(',', $val);
            foreach ($val as $tId) {
              if (is_numeric($tId)) {
                $value[] = $tId;
              }
            }
          }
        }

      case 'activity_tags':
        $activityTags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', ['onlyActive' => FALSE]);

        if (!is_array($value)) {
          $value = explode(',', $value);
        }

        $names = [];
        foreach ($value as $k => $v) {
          $names[] = $activityTags[$v];
        }

        $query->_where[$grouping][] = "civicrm_activity_tag.tag_id IN (" . implode(",", $value) . ")";
        $query->_qill[$grouping][] = ts('Activity Tag %1', [1 => $op]) . ' ' . implode(' ' . ts('OR') . ' ', $names);
        $query->_tables['civicrm_activity_tag'] = $query->_whereTables['civicrm_activity_tag'] = 1;
        break;

      case 'activity_result':
        if (is_array($value)) {
          $safe = [];
          foreach ($value as $id => $k) {
            $safe[] = "'" . CRM_Utils_Type::escape($k, 'String') . "'";
          }
          $query->_where[$grouping][] = "civicrm_activity.result IN (" . implode(',', $safe) . ")";
          $query->_qill[$grouping][] = ts("Activity Result - %1", [1 => implode(' or ', $safe)]);
        }
        break;

      case 'parent_id':
        if ($value == 1) {
          $query->_where[$grouping][] = "civicrm_activity.parent_id IS NOT NULL";
          $query->_qill[$grouping][] = ts('Activities which have Followup Activities');
        }
        elseif ($value == 2) {
          $query->_where[$grouping][] = "civicrm_activity.parent_id IS NULL";
          $query->_qill[$grouping][] = ts('Activities without Followup Activities');
        }
        break;

      case 'followup_parent_id':
        if ($value == 1) {
          $query->_where[$grouping][] = "civicrm_activity.parent_id IS NOT NULL";
          $query->_qill[$grouping][] = ts('Activities which are Followup Activities');
        }
        elseif ($value == 2) {
          $query->_where[$grouping][] = "civicrm_activity.parent_id IS NULL";
          $query->_qill[$grouping][] = ts('Activities which are not Followup Activities');
        }
        break;

      case 'source_contact':
      case 'source_contact_id':
        $columnName = strstr($name, '_id') ? 'id' : 'sort_name';
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("source_contact.{$columnName}", $op, $value, CRM_Utils_Type::typeToString($fields[$name]['type']));
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contact_DAO_Contact', $columnName, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [
          1 => $fields[$name]['title'],
          2 => $op,
          3 => $value,
        ]);
        break;
    }
  }

  /**
   * @param string $name
   * @param int $mode
   * @param string $side
   * @param int $onlyDeleted
   *
   * @return null|string
   */
  public static function from($name, $mode, $side, $onlyDeleted = 0) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_activity':
        //CRM-7480 we are going to civicrm_activity table either
        //from civicrm_activity_target or civicrm_activity_assignment.
        //as component specific activities does not have entry in
        //activity target table so lets consider civicrm_activity_assignment.
        $from .= " $side JOIN civicrm_activity_contact
                      ON ( civicrm_activity_contact.contact_id = contact_a.id ) ";
        $from .= " $side JOIN civicrm_activity
                      ON ( civicrm_activity.id = civicrm_activity_contact.activity_id
                      AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )";
        // Do not show deleted contact's activity
        // unless we are looking at deleted contacts.
        $from .= " INNER JOIN civicrm_contact
                      ON ( civicrm_activity_contact.contact_id = civicrm_contact.id and civicrm_contact.is_deleted = $onlyDeleted )";
        break;

      case 'activity_type':
        $from .= " $side JOIN civicrm_option_group option_group_activity_type ON (option_group_activity_type.name = 'activity_type')";
        $from .= " $side JOIN civicrm_option_value activity_type ON (civicrm_activity.activity_type_id = activity_type.value
                               AND option_group_activity_type.id = activity_type.option_group_id ) ";
        break;

      case 'activity_priority':
        $from .= " $side JOIN civicrm_option_group option_group_activity_priority ON (option_group_activity_priority.name = 'priority')";
        $from .= " $side JOIN civicrm_option_value activity_priority ON (civicrm_activity.priority_id = activity_priority.value
                              AND option_group_activity_priority.id = activity_priority.option_group_id ) ";
        break;

      case 'civicrm_activity_tag':
        $from .= " $side JOIN civicrm_entity_tag as civicrm_activity_tag ON ( civicrm_activity_tag.entity_table = 'civicrm_activity' AND civicrm_activity_tag.entity_id = civicrm_activity.id ) ";
        break;

      case 'source_contact':
        $sourceID = CRM_Core_PseudoConstant::getKey(
          'CRM_Activity_BAO_ActivityContact',
          'record_type_id',
          'Activity Source'
        );
        $from = "
          LEFT JOIN civicrm_activity_contact source_activity
            ON (source_activity.activity_id = civicrm_activity_contact.activity_id
              AND source_activity.record_type_id = {$sourceID})
          LEFT JOIN civicrm_contact source_contact ON (source_activity.contact_id = source_contact.id)";
        break;

      case 'parent_id':
        $from = "$side JOIN civicrm_activity AS parent_id ON civicrm_activity.id = parent_id.parent_id";
        break;
    }

    return $from;
  }

  /**
   * Get the metadata for fields to be included on the activity search form.
   *
   * @throws \CRM_Core_Exception
   * @todo ideally this would be a trait included on the activity search & advanced search
   * rather than a static function.
   */
  public static function getSearchFieldMetadata() {
    $fields = ['activity_type_id', 'activity_date_time', 'priority_id', 'activity_location', 'activity_status_id'];
    $metadata = civicrm_api3('Activity', 'getfields', [])['values'];
    $metadata = array_intersect_key($metadata, array_flip($fields));
    $metadata['activity_text'] = [
      'title' => ts('Activity Text'),
      'type' => CRM_Utils_Type::T_STRING,
      'is_pseudofield' => TRUE,
      'html' => [
        'type' => 'Text',
      ],
    ];
    return $metadata;
  }

  /**
   * Add all the elements shared between case activity search and advanced search.
   *
   * @param CRM_Core_Form_Search $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildSearchForm(&$form) {
    $form->addSearchFieldMetadata(['Activity' => self::getSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();

    $followUpActivity = [
      1 => ts('Yes'),
      2 => ts('No'),
    ];
    $form->addRadio('parent_id', NULL, $followUpActivity, ['allowClear' => TRUE]);
    $form->addRadio('followup_parent_id', NULL, $followUpActivity, ['allowClear' => TRUE]);
    $activityRoles = [
      3 => ts('With'),
      2 => ts('Assigned to'),
      1 => ts('Added by'),
    ];
    $form->addRadio('activity_role', NULL, $activityRoles, ['allowClear' => TRUE]);

    $activityStatus = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'status_id', [
      'flip' => 1,
      'labelColumn' => 'name',
    ]);
    $ssID = $form->get('ssID');
    $status = [$activityStatus['Completed'], $activityStatus['Scheduled']];
    //If status is saved in smart group.
    if (!empty($ssID) && !empty($form->_formValues['activity_status_id'])) {
      $status = $form->_formValues['activity_status_id'];
    }

    $form->addElement('text', 'activity_text', ts('Activity Text'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $form->addRadio('activity_option', '', CRM_Core_SelectValues::activityTextOptions());
    $form->setDefaults(['activity_option' => 6]);

    $form->addYesNo('activity_test', ts('Activity is a Test?'));
    $activity_tags = CRM_Core_BAO_Tag::getColorTags('civicrm_activity');

    if ($activity_tags) {
      $form->add('select2', 'activity_tags', ts('Activity Tag(s)'),
        $activity_tags, FALSE, [
          'id' => 'activity_tags',
          'multiple' =>
          'multiple',
          'class' => 'crm-select2',
          'placeholder' => ts('- select tags -'),
        ]
      );
    }

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_activity');
    CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_activity', NULL, TRUE, TRUE);

    $surveys = CRM_Campaign_BAO_Survey::getSurveys(TRUE, FALSE, FALSE, TRUE);
    if ($surveys) {
      $form->add('select', 'activity_survey_id', ts('Survey / Petition'),
        ['' => ts('- none -')] + $surveys, FALSE,
        ['class' => 'crm-select2']
      );
    }

    CRM_Core_BAO_Query::addCustomFormFields($form, ['Activity']);

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'activity_campaign_id');

    // Add engagement level CRM-7775.
    $buildEngagementLevel = FALSE;
    $buildSurveyResult = FALSE;
    if (CRM_Core_Component::isEnabled('CiviCampaign') &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {
      $buildEngagementLevel = TRUE;
      $form->addSelect('activity_engagement_level', [
        'entity' => 'activity',
        'context' => 'search',
      ]);

      // Add survey result field.
      $optionGroups = CRM_Campaign_BAO_Survey::getResultSets('name');
      $resultOptions = [];
      foreach ($optionGroups as $gid => $name) {
        if ($name) {
          $value = CRM_Core_OptionGroup::values($name);
          if (!empty($value)) {
            foreach ($value as $k => $v) {
              $resultOptions[$v] = $v;
            }
          }
        }
      }
      // If no survey result options have been created, don't build
      // the field to avoid clutter.
      if (count($resultOptions) > 0) {
        $buildSurveyResult = TRUE;
        asort($resultOptions);
        $form->add('select', 'activity_result', ts("Survey Result"),
          $resultOptions, FALSE,
          [
            'id' => 'activity_result',
            'multiple' => 'multiple',
            'class' => 'crm-select2',
          ]
        );
      }
    }

    $form->assign('buildEngagementLevel', $buildEngagementLevel);
    $form->assign('buildSurveyResult', $buildSurveyResult);
    $form->setDefaults(['activity_test' => 0]);
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties($mode, $includeCustomFields = TRUE) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $properties = [
        'activity_id' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'activity_type' => 1,
        'activity_type_id' => 1,
        'activity_subject' => 1,
        'activity_date_time' => 1,
        'activity_duration' => 1,
        'activity_location' => 1,
        'activity_details' => 1,
        'activity_status' => 1,
        'activity_priority' => 1,
        'source_contact' => 1,
        'source_record_id' => 1,
        'activity_is_test' => 1,
        'activity_campaign_id' => 1,
        'result' => 1,
        'activity_engagement_level' => 1,
        'parent_id' => 1,
      ];

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

  /**
   * Get the list of fields required to populate the selector.
   *
   * The default return properties array returns far too many fields for 'everyday use. Every field you add to this array
   * kills a small kitten so add carefully.
   */
  public static function selectorReturnProperties() {
    $properties = [
      'activity_id' => 1,
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'activity_type_id' => 1,
      'activity_subject' => 1,
      'activity_date_time' => 1,
      'activity_status_id' => 1,
      'source_contact' => 1,
      'source_record_id' => 1,
      'activity_is_test' => 1,
      'activity_campaign_id' => 1,
      'activity_engagement_level' => 1,
    ];

    return $properties;
  }

  /**
   * Where/qill clause for notes
   *
   * @param array $values
   * @param CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function whereClauseSingleActivityText(&$values, &$query) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    $activityOptionValues = $query->getWhereValues('activity_option', $grouping);
    $activityOption = CRM_Utils_Array::value(2, $activityOptionValues, 6);

    $query->_useDistinct = TRUE;

    $label = ts('Activity Text (%1)', [1 => CRM_Utils_Array::value($activityOption, CRM_Core_SelectValues::activityTextOptions())]);
    $clauses = [];
    if ($activityOption % 2 == 0) {
      $clauses[] = $query->buildClause('civicrm_activity.details', $op, $value, 'String');
    }
    if ($activityOption % 3 == 0) {
      $clauses[] = $query->buildClause('civicrm_activity.subject', $op, $value, 'String');
    }

    $query->_where[$grouping][] = "( " . implode(' OR ', $clauses) . " )";
    [$qillOp, $qillVal] = $query->buildQillForFieldValue(NULL, $name, $value, $op);
    $query->_qill[$grouping][] = ts("%1 %2 '%3'", [
      1 => $label,
      2 => $qillOp,
      3 => $qillVal,
    ]);
  }

}
