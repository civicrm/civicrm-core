<?php
// $Id$

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
class CRM_Report_Form_Activity extends CRM_Report_Form {

  protected $_customGroupExtends = array(
    'Activity'
  );

  function __construct() {
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
      $this->engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
    }
    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'label', TRUE);
    asort($this->activityTypes);

    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'source_contact_id' =>
          array(
            'name' => 'id',
            'alias' => 'civicrm_contact_source',
            'no_display' => TRUE,
          ),
          'contact_source' =>
          array(
            'name' => 'sort_name',
            'title' => ts('Source Contact Name'),
            'alias' => 'civicrm_contact_source',
            'no_repeat' => TRUE,
          ),
          'contact_assignee' =>
          array(
            'name' => 'sort_name',
            'title' => ts('Assignee Contact Name'),
            'alias' => 'civicrm_contact_assignee',
            'default' => TRUE,
          ),
          'contact_target' =>
          array(
            'name' => 'sort_name',
            'title' => ts('Target Contact Name'),
            'alias' => 'contact_civireport',
            'default' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'contact_source' =>
          array(
            'name' => 'sort_name',
            'alias' => 'civicrm_contact_source',
            'title' => ts('Source Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'contact_assignee' =>
          array(
            'name' => 'sort_name',
            'alias' => 'civicrm_contact_assignee',
            'title' => ts('Assignee Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'contact_target' =>
          array(
            'name' => 'sort_name',
            'alias' => 'contact_civireport',
            'title' => ts('Target Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'current_user' =>
          array(
            'name' => 'current_user',
            'title' => ts('Limit To Current User'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array('0' => ts('No'), '1' => ts('Yes')),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'contact_source_email' =>
          array(
            'name' => 'email',
            'title' => ts('Source Contact Email'),
            'alias' => 'civicrm_email_source',
          ),
          'contact_assignee_email' =>
          array(
            'name' => 'email',
            'title' => ts('Assignee Contact Email'),
            'alias' => 'civicrm_email_assignee',
          ),
          'contact_target_email' =>
          array(
            'name' => 'email',
            'title' => ts('Target Contact Email'),
            'alias' => 'civicrm_email_target',
          ),
        ),
        'order_bys' =>
        array(
          'source_contact_email' =>
          array(
            'name' => 'email',
            'title' => ts('Source Contact Email'),
            'alias' => 'civicrm_email_source',
          ),
        ),
      ),
      'civicrm_activity' =>
      array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        array(
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'source_record_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'activity_type_id' =>
          array('title' => ts('Activity Type'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'activity_subject' =>
          array('title' => ts('Subject'),
            'default' => TRUE,
          ),
          'source_contact_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'activity_date_time' =>
          array('title' => ts('Activity Date'),
            'default' => TRUE,
          ),
          'status_id' =>
          array('title' => ts('Activity Status'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'duration' =>
          array('title' => ts('Duration'),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'filters' =>
        array(
          'activity_date_time' =>
          array(
            'default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'activity_subject' =>
          array('title' => ts('Activity Subject')),
          'activity_type_id' =>
          array('title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ),
          'status_id' =>
          array('title' => ts('Activity Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ),
        ),
        'order_bys' =>
        array(
          'source_contact_id' =>
          array('title' => ts('Source Contact'), 'default_weight' => '0'),
          'activity_date_time' =>
          array('title' => ts('Activity Date'), 'default_weight' => '1'),
          'activity_type_id' =>
          array('title' => ts('Activity Type'), 'default_weight' => '2'),
        ),
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ),
      'civicrm_activity_assignment' =>
      array(
        'dao' => 'CRM_Activity_DAO_ActivityAssignment',
        'fields' =>
        array(
          'assignee_contact_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'alias' => 'activity_assignment',
      ),
      'civicrm_activity_target' =>
      array(
        'dao' => 'CRM_Activity_DAO_ActivityTarget',
        'fields' =>
        array(
          'target_contact_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'alias' => 'activity_target',
      ),
      'civicrm_case_activity' =>
      array(
        'dao' => 'CRM_Case_DAO_CaseActivity',
        'fields' =>
        array(
          'case_id' =>
          array(
            'name' => 'case_id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'alias' => 'case_activity',
      ),
    ) + $this->addAddressFields(FALSE, TRUE);

    if ($campaignEnabled) {
      // Add display column and filter for Survey Results, Campaign and Engagement Index if CiviCampaign is enabled

      $this->_columns['civicrm_activity']['fields']['result'] = array(
        'title' => 'Survey Result',
        'default' => 'false',
      );
      $this->_columns['civicrm_activity']['filters']['result'] = array('title' => ts('Survey Result'),
        'operator' => 'like',
        'type' => CRM_Utils_Type::T_STRING,
      );
      if (!empty($this->activeCampaigns)) {
        $this->_columns['civicrm_activity']['fields']['campaign_id'] = array(
          'title' => 'Campaign',
          'default' => 'false',
        );
        $this->_columns['civicrm_activity']['filters']['campaign_id'] = array('title' => ts('Campaign'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->activeCampaigns,
        );
      }
      if (!empty($this->engagementLevels)) {
        $this->_columns['civicrm_activity']['fields']['engagement_level'] = array(
          'title' => 'Engagement Index',
          'default' => 'false',
        );
        $this->_columns['civicrm_activity']['filters']['engagement_level'] = array('title' => ts('Engagement Index'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->engagementLevels,
        );
      }
    }
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function select() {
    $select = array();
    $seperator = CRM_CORE_DAO::VALUE_SEPARATOR;
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            if (isset($this->_params['group_bys']) &&
                !CRM_Utils_Array::value('activity_type_id', $this->_params['group_bys']) &&
              (in_array($fieldName, array(
                'contact_assignee', 'assignee_contact_id')) ||
                in_array($fieldName, array('contact_target', 'target_contact_id'))
              )
            ) {
              $orderByRef = "activity_assignment_civireport.assignee_contact_id";
              if (in_array($fieldName, array(
                'contact_target', 'target_contact_id'))) {
                $orderByRef = "activity_target_civireport.target_contact_id";
              }
              $select[] = "GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$orderByRef} SEPARATOR '{$seperator}') as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}

             LEFT JOIN civicrm_activity_target  {$this->_aliases['civicrm_activity_target']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_target']}.activity_id
             LEFT JOIN civicrm_activity_assignment {$this->_aliases['civicrm_activity_assignment']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_assignment']}.activity_id
             LEFT JOIN civicrm_contact civicrm_contact_source
                    ON {$this->_aliases['civicrm_activity']}.source_contact_id = civicrm_contact_source.id
             LEFT JOIN civicrm_contact contact_civireport
                    ON {$this->_aliases['civicrm_activity_target']}.target_contact_id = contact_civireport.id
             LEFT JOIN civicrm_contact civicrm_contact_assignee
                    ON {$this->_aliases['civicrm_activity_assignment']}.assignee_contact_id = civicrm_contact_assignee.id

             {$this->_aclFrom}
             LEFT JOIN civicrm_option_value
                    ON ( {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value )
             LEFT JOIN civicrm_option_group
                    ON civicrm_option_group.id = civicrm_option_value.option_group_id
             LEFT JOIN civicrm_case_activity case_activity_civireport
                    ON case_activity_civireport.activity_id = {$this->_aliases['civicrm_activity']}.id
             LEFT JOIN civicrm_case
                    ON case_activity_civireport.case_id = civicrm_case.id
             LEFT JOIN civicrm_case_contact
                    ON civicrm_case_contact.case_id = civicrm_case.id ";

    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
            LEFT JOIN civicrm_email civicrm_email_source
                   ON {$this->_aliases['civicrm_activity']}.source_contact_id = civicrm_email_source.contact_id AND
                      civicrm_email_source.is_primary = 1

            LEFT JOIN civicrm_email civicrm_email_target
                   ON {$this->_aliases['civicrm_activity_target']}.target_contact_id = civicrm_email_target.contact_id AND
                      civicrm_email_target.is_primary = 1

            LEFT JOIN civicrm_email civicrm_email_assignee
                   ON {$this->_aliases['civicrm_activity_assignment']}.assignee_contact_id = civicrm_email_assignee.contact_id AND
                      civicrm_email_assignee.is_primary = 1 ";
    }
    $this->addAddressFromClause();
  }

  function where() {
    $this->_where = " WHERE civicrm_option_group.name = 'activity_type' AND
                                {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if ($field['name'] == 'current_user') {
            if (CRM_Utils_Array::value("{$fieldName}_value", $this->_params) == 1) {
              // get current user
              $session = CRM_Core_Session::singleton();
              if ($contactID = $session->get('userID')) {
                $clause = "( civicrm_contact_source.id = " . $contactID . " OR civicrm_contact_assignee.id = " . $contactID . " OR contact_civireport.id = " . $contactID . " )";
              }
              else {
                $clause = NULL;
              }
            }
            else {
              $clause = NULL;
            }
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where .= " ";
    }
    else {
      $this->_where .= " AND " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_activity']}.id";
  }

  function buildACLClause($tableAlias = 'contact_a') {
    //override for ACL( Since Cotact may be source
    //contact/assignee or target also it may be null )

    if (CRM_Core_Permission::check('view all contacts')) {
      $this->_aclFrom = $this->_aclWhere = NULL;
      return;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }
    $contactID = CRM_Utils_Type::escape($contactID, 'Integer');

    CRM_Contact_BAO_Contact_Permission::cache($contactID);
    $clauses = array();
    foreach ($tableAlias as $k => $alias) {
      $clauses[] = " INNER JOIN civicrm_acl_contact_cache aclContactCache_{$k} ON ( {$alias}.id = aclContactCache_{$k}.contact_id OR {$alias}.id IS NULL ) AND aclContactCache_{$k}.user_id = $contactID ";
    }

    $this->_aclFrom = implode(" ", $clauses);
    $this->_aclWhere = NULL;
  }

  function postProcess() {

    $this->buildACLClause(array('civicrm_contact_source', 'contact_civireport', 'civicrm_contact_assignee'));
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound     = FALSE;
    $activityType   = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $viewLinks      = FALSE;
    $seperator      = CRM_CORE_DAO::VALUE_SEPARATOR;
    $context        = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'report');

    if (CRM_Core_Permission::check('access CiviCRM')) {
      $viewLinks  = TRUE;
      $onHover    = ts('View Contact Summary for this Contact');
      $onHoverAct = ts('View Activity Record');
    }
    foreach ($rows as $rowNum => $row) {

      if (array_key_exists('civicrm_contact_contact_source', $row)) {
        if ($value = $row['civicrm_activity_source_contact_id']) {
          if ($viewLinks) {
            $url = CRM_Utils_System::url("civicrm/contact/view",
              'reset=1&cid=' . $value,
              $this->_absoluteUrl
            );
            $rows[$rowNum]['civicrm_contact_contact_source_link'] = $url;
            $rows[$rowNum]['civicrm_contact_contact_source_hover'] = $onHover;
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_contact_contact_assignee', $row)) {
        $assigneeNames = explode($seperator, $row['civicrm_contact_contact_assignee']);
        if ($value = $row['civicrm_activity_assignment_assignee_contact_id']) {
          $assigneeContactIds = explode($seperator, $value);
          $link = array();
          if ($viewLinks) {
            foreach ($assigneeContactIds as $id => $value) {
              $url = CRM_Utils_System::url("civicrm/contact/view",
                'reset=1&cid=' . $value,
                $this->_absoluteUrl
              );
              $link[] = "<a title='" . $onHover . "' href='" . $url . "'>{$assigneeNames[$id]}</a>";
            }
            $rows[$rowNum]['civicrm_contact_contact_assignee'] = implode('; ', $link);
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_contact_contact_target', $row)) {
        $targetNames = explode($seperator, $row['civicrm_contact_contact_target']);
        if ($value = $row['civicrm_activity_target_target_contact_id']) {
          $targetContactIds = explode($seperator, $value);
          $link = array();
          if ($viewLinks) {
            foreach ($targetContactIds as $id => $value) {
              $url = CRM_Utils_System::url("civicrm/contact/view",
                'reset=1&cid=' . $value,
                $this->_absoluteUrl
              );
              $link[] = "<a title='" . $onHover . "' href='" . $url . "'>{$targetNames[$id]}</a>";
            }
            $rows[$rowNum]['civicrm_contact_contact_target'] = implode('; ', $link);
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {
          $rows[$rowNum]['civicrm_activity_activity_type_id'] = $activityType[$value];
          if ($viewLinks) {
            // Check for target contact id(s) and use the first contact id in that list for view activity link if found,
            // else use source contact id
            if (!empty($rows[$rowNum]['civicrm_activity_target_target_contact_id'])) {
              $targets = explode($seperator, $rows[$rowNum]['civicrm_activity_target_target_contact_id']);
              $cid = $targets[0];
            }
            else {
              $cid = $rows[$rowNum]['civicrm_activity_source_contact_id'];
            }

            $actionLinks = CRM_Activity_Selector_Activity::actionLinks($row['civicrm_activity_activity_type_id'],
              CRM_Utils_Array::value('civicrm_activity_source_record_id', $rows[$rowNum]),
              FALSE,
              $rows[$rowNum]['civicrm_activity_id']
            );

            $linkValues = array(
              'id' => $rows[$rowNum]['civicrm_activity_id'],
              'cid' => $cid,
              'cxt' => $context,
            );
            $url = CRM_Utils_System::url($actionLinks[CRM_Core_Action::VIEW]['url'],
              CRM_Core_Action::replace($actionLinks[CRM_Core_Action::VIEW]['qs'], $linkValues), TRUE
            );
            $rows[$rowNum]['civicrm_activity_activity_type_id_link'] = $url;
            $rows[$rowNum]['civicrm_activity_activity_type_id_hover'] = $onHoverAct;
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_status_id', $row)) {
        if ($value = $row['civicrm_activity_status_id']) {
          $rows[$rowNum]['civicrm_activity_status_id'] = $activityStatus[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_campaign_id', $row)) {
        if ($value = $row['civicrm_activity_campaign_id']) {
          $rows[$rowNum]['civicrm_activity_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_engagement_level', $row)) {
        if ($value = $row['civicrm_activity_engagement_level']) {
          $rows[$rowNum]['civicrm_activity_engagement_level'] = $this->engagementLevels[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_activity_date_time', $row) && array_key_exists('civicrm_activity_status_id', $row)) {
        if (CRM_Utils_Date::overdue($rows[$rowNum]['civicrm_activity_activity_date_time']) &&
          $activityStatus[$row['civicrm_activity_status_id']] != 'Completed'
        ) {
          $rows[$rowNum]['class'] = "status-overdue";
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'activity', 'List all activities for this ') ? TRUE : $entryFound;

      if (!$entryFound) {
        break;
      }
    }
  }
  
  
   /*
   * Add Target Contact Address into From Table if required
   */
  function addAddressFromClause() {
    // include address field if address column is to be included
    if ((isset($this->_addressField) &&
        $this->_addressField
      ) ||
      $this->isTableSelected('civicrm_address')
    ) {
      $this->_from .= "
                 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                           ON ({$this->_aliases['civicrm_activity_target']}.target_contact_id =
                               {$this->_aliases['civicrm_address']}.contact_id) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }
}

