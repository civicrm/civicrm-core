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
class CRM_Report_Form_Activity extends CRM_Report_Form {
  protected $_selectAliasesTotal = array();

  protected $_customGroupExtends = array(
    'Activity'
  );

  protected $_nonDisplayFields = array();

  /**
   *
   */
  /**
   *
   */
  function __construct() {
    // There could be multiple contacts. We not clear on which contact id to display.
    // Lets hide it for now.
    $this->_exposeContactID = FALSE;

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
            'dbAlias' => "civicrm_contact_assignee.sort_name",
            'default' => TRUE,
          ),
          'contact_target' =>
          array(
            'name' => 'sort_name',
            'title' => ts('Target Contact Name'),
            'alias' => 'civicrm_contact_target',
            'dbAlias' => "civicrm_contact_target.sort_name",
            'default' => TRUE,
          ),
         'contact_source_id' =>
          array(
            'name' => 'id',
            'alias' => 'civicrm_contact_source',
            'dbAlias' => "civicrm_contact_source.id",
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ),
          'contact_assignee_id' =>
          array(
            'name' => 'id',
            'alias' => 'civicrm_contact_assignee',
            'dbAlias' => "civicrm_contact_assignee.id",
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ),
          'contact_target_id' =>
          array(
            'name' => 'id',
            'alias' => 'civicrm_contact_target',
            'dbAlias' => "civicrm_contact_target.id",
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
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
            'alias' => 'civicrm_contact_target',
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
            'dbAlias' => 'civicrm_email_contact_source_email',
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
            'title' => ts('Activity ID'),
            'required' => TRUE,
          ),
          'source_record_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'activity_type_id' =>
          array('title' => ts('Activity Type'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'activity_subject' =>
          array('title' => ts('Subject'),
            'default' => TRUE,
          ),
          'activity_date_time' =>
          array('title' => ts('Activity Date'),
            'required' => TRUE,
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
          'details' => array(
            'title' => ts('Activity Details'),
          )
        ),
        'filters' => array(
          'activity_date_time' => array(
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
          'details' => array(
            'title' => ts('Activity Details'),
            'type' => CRM_Utils_Type::T_TEXT,
          )
        ),
        'order_bys' => array(
          'activity_date_time' =>
          array('title' => ts('Activity Date'), 'default_weight' => '1', 'dbAlias' => 'civicrm_activity_activity_date_time'),
          'activity_type_id' =>
          array('title' => ts('Activity Type'), 'default_weight' => '2', 'dbAlias' => 'civicrm_activity_activity_type_id'),
        ),
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ),
      'civicrm_activity_contact' =>
      array(
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' =>
        array(
          // so we have $this->_alias populated
        ),
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

  /**
   * @param null $recordType
   */
  function select($recordType = NULL) {
    if (!array_key_exists("contact_{$recordType}", $this->_params['fields']) && $recordType != 'final') {
      $this->_nonDisplayFields[] = "civicrm_contact_contact_{$recordType}";
    }
    parent::select();

    if ($recordType == 'final' && !empty($this->_nonDisplayFields)) {
      foreach ($this->_nonDisplayFields as $fieldName) {
        unset($this->_columnHeaders[$fieldName]);
      }
    }

    if (empty($this->_selectAliasesTotal)) {
      $this->_selectAliasesTotal = $this->_selectAliases;
    }

    $removeKeys = array();
    if ($recordType == 'target') {
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_assignee.') ||
          strstr($clause, 'civicrm_contact_source.') ||
          strstr($clause, 'civicrm_email_assignee.') ||
          strstr($clause, 'civicrm_email_source.')
        ) {
          $removeKeys[] = $key;
          unset($this->_selectClauses[$key]);
        }
      }
    } else if ($recordType == 'assignee') {
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_target.') ||
          strstr($clause, 'civicrm_contact_source.') ||
          strstr($clause, 'civicrm_email_target.') ||
          strstr($clause, 'civicrm_email_source.')
        ) {
          $removeKeys[] = $key;
          unset($this->_selectClauses[$key]);
        }
      }
    } else if ($recordType == 'source') {
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_target.') ||
          strstr($clause, 'civicrm_contact_assignee.') ||
          strstr($clause, 'civicrm_email_target.') ||
          strstr($clause, 'civicrm_email_assignee.')
        ) {
          $removeKeys[] = $key;
          unset($this->_selectClauses[$key]);
        }
      }
    } else if ($recordType == 'final') {
      $this->_selectClauses = $this->_selectAliasesTotal;
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_contact_target') ||
          strstr($clause, 'civicrm_contact_contact_assignee') ||
          strstr($clause, 'civicrm_contact_contact_source') ) {
          $this->_selectClauses[$key] = "GROUP_CONCAT($clause SEPARATOR ';') as $clause";
        }
      }
    }

    if ($recordType) {
      foreach ($removeKeys as $key) {
        unset($this->_selectAliases[$key]);
      }

      $this->_select = "SELECT " . implode(', ', $this->_selectClauses) . " ";
    }
  }

  /**
   * @param $recordType
   */
  function from($recordType) {
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID   = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $sourceID   = CRM_Utils_Array::key('Activity Source', $activityContacts);

    if ($recordType == 'target') {
      $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}
             INNER JOIN civicrm_activity_contact  {$this->_aliases['civicrm_activity_contact']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_contact']}.activity_id AND
                       {$this->_aliases['civicrm_activity_contact']}.record_type_id = {$targetID}
             INNER JOIN civicrm_contact civicrm_contact_target
                    ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_contact_target.id
             {$this->_aclFrom}";

      if ($this->isTableSelected('civicrm_email')) {
        $this->_from .= "
            LEFT JOIN civicrm_email civicrm_email_target
                   ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_email_target.contact_id AND
                      civicrm_email_target.is_primary = 1";
      }
      $this->_aliases['civicrm_contact'] = 'civicrm_contact_target';
    }

    if ($recordType == 'assignee') {
      $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}
             INNER JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_contact']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_contact']}.activity_id AND
                       {$this->_aliases['civicrm_activity_contact']}.record_type_id = {$assigneeID}
             INNER JOIN civicrm_contact civicrm_contact_assignee
                    ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_contact_assignee.id
             {$this->_aclFrom}";

      if ($this->isTableSelected('civicrm_email')) {
        $this->_from .= "
            LEFT JOIN civicrm_email civicrm_email_assignee
                   ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_email_assignee.contact_id AND
                      civicrm_email_assignee.is_primary = 1";
      }
      $this->_aliases['civicrm_contact'] = 'civicrm_contact_assignee';
    }

    if ($recordType == 'source') {
      $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}
             INNER JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_contact']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_contact']}.activity_id AND
                       {$this->_aliases['civicrm_activity_contact']}.record_type_id = {$sourceID}
             INNER JOIN civicrm_contact civicrm_contact_source
                    ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_contact_source.id
             {$this->_aclFrom}";

      if ($this->isTableSelected('civicrm_email')) {
        $this->_from .= "
            LEFT JOIN civicrm_email civicrm_email_source
                   ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_email_source.contact_id AND
                      civicrm_email_source.is_primary = 1";
      }
      $this->_aliases['civicrm_contact'] = 'civicrm_contact_source';
    }
    $this->addAddressFromClause();
  }

  /**
   * @param null $recordType
   */
  function where($recordType = NULL) {
    $this->_where = " WHERE {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($fieldName != 'contact_' . $recordType &&
            (strstr($fieldName, '_target') ||
              strstr($fieldName, '_assignee') ||
              strstr($fieldName, '_source')
            )
          ) {
            continue;
          }
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op && ($op != 'nnll' || $op != 'nll')) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
              if ($fieldName == 'activity_type_id' && empty($this->_params['activity_type_id_value'])) {
                $actTypes = array_flip(CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'label', TRUE));
                $clause = "( {$this->_aliases['civicrm_activity']}.activity_type_id IN (".implode(',',$actTypes).") )";
              }
            }
          }

          if ($field['name'] == 'current_user') {
            if (CRM_Utils_Array::value("{$fieldName}_value", $this->_params) == 1) {
              // get current user
              $session = CRM_Core_Session::singleton();
              if ($contactID = $session->get('userID')) {
                $clause = "{$this->_aliases['civicrm_activity_contact']}.activity_id IN
                           (SELECT activity_id FROM civicrm_activity_contact WHERE contact_id = {$contactID})";
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

  /**
   * @param string $tableAlias
   */
  function buildACLClause($tableAlias = 'contact_a') {
    //override for ACL( Since Contact may be source
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

  /**
   * @param $groupID
   *
   * @throws Exception
   */
  function add2group($groupID) {
    if (CRM_Utils_Array::value("contact_target_op", $this->_params) == 'nll') {
      CRM_Core_Error::fatal(ts('Current filter criteria didn\'t have any target contact to add to group'));
    }

    $query = "{$this->_select}
FROM civireport_activity_temp_target tar
GROUP BY civicrm_activity_id {$this->_having} {$this->_orderBy}";
    $select = 'AS addtogroup_contact_id';
    $query = str_ireplace('AS civicrm_contact_contact_target_id', $select, $query);
    $dao = CRM_Core_DAO::executeQuery($query);

    $contactIDs = array();
    // Add resulting contacts to group
    while ($dao->fetch()) {
      if ($dao->addtogroup_contact_id) {
        $contact_id = explode(';', $dao->addtogroup_contact_id);
        if ($contact_id[0]) {
          $contactIDs[$contact_id[0]] = $contact_id[0];
        }
      }
    }

    if ( !empty($contactIDs) ) {
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);
      CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."), ts('Contacts Added'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts("The listed records(s) cannot be added to the group."));
    }
  }

  function postProcess() {
    $this->beginPostProcess();

    //Assign those recordtype to array which have filter operator as 'Is not empty' or 'Is empty'
    $nullFilters = array();
    foreach (array('target', 'source', 'assignee') as $type) {
      if (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) == 'nnll' || !empty($this->_params["contact_{$type}_value"])) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NOT NULL ";
      }
      else if (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) == 'nll') {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NULL ";
      }
    }

      // 1. fill temp table with target results
    $this->buildACLClause(array('civicrm_contact_target'));
    $this->select('target');
    $this->from('target');
    $this->customDataFrom();
    $this->where('target');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery  = "CREATE TEMPORARY TABLE civireport_activity_temp_target CHARACTER SET utf8 COLLATE utf8_unicode_ci AS
{$this->_select} {$this->_from} {$this->_where} ";
    CRM_Core_DAO::executeQuery($tempQuery);

    // 2. add new columns to hold assignee and source results
    // fixme: add when required
    $tempQuery = "
  ALTER TABLE  civireport_activity_temp_target
  ADD COLUMN civicrm_contact_contact_assignee VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source_id VARCHAR(128),
  ADD COLUMN civicrm_email_contact_assignee_email VARCHAR(128),
  ADD COLUMN civicrm_email_contact_source_email VARCHAR(128)";
    CRM_Core_DAO::executeQuery($tempQuery);

    // 3. fill temp table with assignee results
    $this->buildACLClause(array('civicrm_contact_assignee'));
    $this->select('assignee');
    $this->from('assignee');
    $this->customDataFrom();
    $this->where('assignee');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery  = "INSERT INTO civireport_activity_temp_target ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    CRM_Core_DAO::executeQuery($tempQuery);

    // 4. fill temp table with source results
    $this->buildACLClause(array('civicrm_contact_source'));
    $this->select('source');
    $this->from('source');
    $this->customDataFrom();
    $this->where('source');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery  = "INSERT INTO civireport_activity_temp_target ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    CRM_Core_DAO::executeQuery($tempQuery);

    // 5. show final result set from temp table
    $rows = array();
    $this->select('final');
    $this->_having = "";
    if (!empty($nullFilters)) {
      $this->_having = "HAVING " . implode(' AND ', $nullFilters);
    }
    $this->orderBy();
    $this->limit();
    $sql = "{$this->_select}
FROM civireport_activity_temp_target tar
GROUP BY civicrm_activity_id {$this->_having} {$this->_orderBy} {$this->_limit}";
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound     = FALSE;
    $activityType   = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $viewLinks      = FALSE;
    $seperator      = CRM_Core_DAO::VALUE_SEPARATOR;
    $context        = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'report');
    $actUrl         = '';

    if (CRM_Core_Permission::check('access CiviCRM')) {
      $viewLinks  = TRUE;
      $onHover    = ts('View Contact Summary for this Contact');
      $onHoverAct = ts('View Activity Record');
    }

    foreach ($rows as $rowNum => $row) {
      // if we have an activity type, format the View Activity link for use in various columns
      if ($viewLinks && array_key_exists('civicrm_activity_activity_type_id', $row)) {
        // Check for target contact id(s) and use the first contact id in that list for view activity link if found,
        // else use source contact id
        if (!empty($rows[$rowNum]['civicrm_contact_contact_target_id'])) {
          $targets = explode(';', $rows[$rowNum]['civicrm_contact_contact_target_id']);
          $cid = $targets[0];
        }
        else {
          $cid = $rows[$rowNum]['civicrm_contact_contact_source_id'];
        }

        $actActionLinks = CRM_Activity_Selector_Activity::actionLinks($row['civicrm_activity_activity_type_id'],
          CRM_Utils_Array::value('civicrm_activity_source_record_id', $rows[$rowNum]),
          FALSE,
          $rows[$rowNum]['civicrm_activity_id']
        );

        $actLinkValues = array(
          'id' => $rows[$rowNum]['civicrm_activity_id'],
          'cid' => $cid,
          'cxt' => $context,
        );
        $actUrl = CRM_Utils_System::url($actActionLinks[CRM_Core_Action::VIEW]['url'],
          CRM_Core_Action::replace($actActionLinks[CRM_Core_Action::VIEW]['qs'], $actLinkValues), TRUE
        );
      }

      if (array_key_exists('civicrm_contact_contact_source', $row)) {
        if ($value = $row['civicrm_contact_contact_source_id']) {
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
        $assigneeNames = explode(';', $row['civicrm_contact_contact_assignee']);
        if ($value = $row['civicrm_contact_contact_assignee_id']) {
          $assigneeContactIds = explode(';', $value);
          $link = array();
          if ($viewLinks) {
            foreach ($assigneeContactIds as $id => $value) {
              if (isset($value) && isset($assigneeNames[$id])) {
                $url = CRM_Utils_System::url("civicrm/contact/view",
                  'reset=1&cid=' . $value,
                  $this->_absoluteUrl
                );
                $link[] = "<a title='" . $onHover . "' href='" . $url . "'>{$assigneeNames[$id]}</a>";
              }
            }
            $rows[$rowNum]['civicrm_contact_contact_assignee'] = implode('; ', $link);
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_contact_contact_target', $row)) {
        $targetNames = explode(';', $row['civicrm_contact_contact_target']);
        if ($value = $row['civicrm_contact_contact_target_id']) {
          $targetContactIds = explode(';', $value);
          $link = array();
          if ($viewLinks) {
            foreach ($targetContactIds as $id => $value) {
              if (isset($value) && isset($targetNames[$id])) {
                $url = CRM_Utils_System::url("civicrm/contact/view",
                  'reset=1&cid=' . $value,
                  $this->_absoluteUrl
                );
                $link[] = "<a title='" . $onHover . "' href='" . $url . "'>{$targetNames[$id]}</a>";
              }
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
            $rows[$rowNum]['civicrm_activity_activity_type_id_link'] = $actUrl;
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

      if (array_key_exists('civicrm_activity_details', $row)) {
        if ($value = $row['civicrm_activity_details']) {
          $fullDetails = $rows[$rowNum]['civicrm_activity_details'];
          $rows[$rowNum]['civicrm_activity_details'] = substr($fullDetails, 0, strrpos(substr($fullDetails, 0, 80), ' '));
          if ($actUrl) {
            $rows[$rowNum]['civicrm_activity_details'] .= " <a href='{$actUrl}' title='{$onHoverAct}'>(more)</a>";
          }
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
}

