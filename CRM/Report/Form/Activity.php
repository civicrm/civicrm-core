<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Report_Form_Activity extends CRM_Report_Form {
  protected $_selectAliasesTotal = array();

  protected $_customGroupExtends = array(
    'Activity',
  );

  protected $_nonDisplayFields = array();

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Class constructor.
   */
  public function __construct() {
    // There could be multiple contacts. We not clear on which contact id to display.
    // Lets hide it for now.
    $this->_exposeContactID = FALSE;
    // if navigated from count link of activity summary reports.
    $this->_resetDateFilter = CRM_Utils_Request::retrieve('resetDateFilter', 'Boolean');

    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    $caseEnabled = in_array("CiviCase", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
      $this->engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
    }

    $components = CRM_Core_Component::getEnabledComponents();
    foreach ($components as $componentName => $componentInfo) {
      // CRM-19201: Add support for reporting CiviCampaign activities
      // For CiviCase, "access all cases and activities" is required here
      // rather than "access my cases and activities" to prevent those with
      // only the later permission from seeing a list of all cases which might
      // present a privacy issue.
      if (CRM_Core_Permission::access($componentName, TRUE, TRUE)) {
        $accessAllowed[] = $componentInfo->componentID;
      }
    }

    $include = '';
    if (!empty($accessAllowed)) {
      $include = 'OR v.component_id IN (' . implode(', ', $accessAllowed) . ')';
    }
    $condition = " AND ( v.component_id IS NULL {$include} )";
    $this->activityTypes = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $condition);
    asort($this->activityTypes);

    // @todo split the 3 different contact tables into their own array items.
    // this will massively simplify the needs of this report.
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'contact_source' => array(
            'name' => 'sort_name',
            'title' => ts('Source Name'),
            'alias' => 'civicrm_contact_source',
            'no_repeat' => TRUE,
          ),
          'contact_assignee' => array(
            'name' => 'sort_name',
            'title' => ts('Assignee Name'),
            'alias' => 'civicrm_contact_assignee',
            'dbAlias' => "civicrm_contact_assignee.sort_name",
            'default' => TRUE,
          ),
          'contact_target' => array(
            'name' => 'sort_name',
            'title' => ts('Target Name'),
            'alias' => 'civicrm_contact_target',
            'dbAlias' => "civicrm_contact_target.sort_name",
            'default' => TRUE,
          ),
          'contact_source_id' => array(
            'name' => 'id',
            'alias' => 'civicrm_contact_source',
            'dbAlias' => "civicrm_contact_source.id",
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ),
          'contact_assignee_id' => array(
            'name' => 'id',
            'alias' => 'civicrm_contact_assignee',
            'dbAlias' => "civicrm_contact_assignee.id",
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ),
          'contact_target_id' => array(
            'name' => 'id',
            'alias' => 'civicrm_contact_target',
            'dbAlias' => "civicrm_contact_target.id",
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'contact_source' => array(
            'name' => 'sort_name',
            'alias' => 'civicrm_contact_source',
            'title' => ts('Source Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'contact_assignee' => array(
            'name' => 'sort_name',
            'alias' => 'civicrm_contact_assignee',
            'title' => ts('Assignee Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'contact_target' => array(
            'name' => 'sort_name',
            'alias' => 'civicrm_contact_target',
            'title' => ts('Target Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'current_user' => array(
            'name' => 'current_user',
            'title' => ts('Limit To Current User'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array('0' => ts('No'), '1' => ts('Yes')),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'contact_source_email' => array(
            'name' => 'email',
            'title' => ts('Source Email'),
            'alias' => 'civicrm_email_source',
          ),
          'contact_assignee_email' => array(
            'name' => 'email',
            'title' => ts('Assignee Email'),
            'alias' => 'civicrm_email_assignee',
          ),
          'contact_target_email' => array(
            'name' => 'email',
            'title' => ts('Target Email'),
            'alias' => 'civicrm_email_target',
          ),
        ),
        'order_bys' => array(
          'source_contact_email' => array(
            'name' => 'email',
            'title' => ts('Source Email'),
            'dbAlias' => 'civicrm_email_contact_source_email',
          ),
        ),
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'contact_source_phone' => array(
            'name' => 'phone',
            'title' => ts('Source Phone'),
            'alias' => 'civicrm_phone_source',
          ),
          'contact_assignee_phone' => array(
            'name' => 'phone',
            'title' => ts('Assignee Phone'),
            'alias' => 'civicrm_phone_assignee',
          ),
          'contact_target_phone' => array(
            'name' => 'phone',
            'title' => ts('Target Phone'),
            'alias' => 'civicrm_phone_target',
          ),
        ),
      ),
      'civicrm_activity' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'title' => ts('Activity ID'),
            'required' => TRUE,
          ),
          'source_record_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'activity_subject' => array(
            'title' => ts('Subject'),
            'default' => TRUE,
          ),
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'required' => TRUE,
          ),
          'status_id' => array(
            'title' => ts('Activity Status'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'duration' => array(
            'title' => ts('Duration'),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'location' => array(
            'title' => ts('Location'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'details' => array(
            'title' => ts('Activity Details'),
          ),
          'priority_id' => array(
            'title' => ts('Priority'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'filters' => array(
          'activity_date_time' => array(
            'default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'activity_subject' => array('title' => ts('Activity Subject')),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ),
          'status_id' => array(
            'title' => ts('Activity Status'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ),
          'location' => array(
            'title' => ts('Location'),
            'type' => CRM_Utils_Type::T_TEXT,
          ),
          'details' => array(
            'title' => ts('Activity Details'),
            'type' => CRM_Utils_Type::T_TEXT,
          ),
          'priority_id' => array(
            'title' => ts('Activity Priority'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id'),
          ),
        ),
        'order_bys' => array(
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'default_weight' => '1',
            'dbAlias' => 'civicrm_activity_activity_date_time',
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'default_weight' => '2',
            'dbAlias' => 'field(civicrm_activity_activity_type_id, ' . implode(', ', array_keys($this->activityTypes)) . ')',
          ),
        ),
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ),
      // Hack to get $this->_alias populated for the table.
      'civicrm_activity_contact' => array(
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => array(),
      ),
    ) + $this->addressFields(TRUE);

    if ($caseEnabled && CRM_Core_Permission::check('access all cases and activities')) {
      $this->_columns['civicrm_activity']['filters']['include_case_activities'] = array(
        'name' => 'include_case_activities',
        'title' => ts('Include Case Activities'),
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_SELECT,
        'options' => array('0' => ts('No'), '1' => ts('Yes')),
      );
    }

    if ($campaignEnabled) {
      // Add display column and filter for Survey Results, Campaign and Engagement Index if CiviCampaign is enabled

      $this->_columns['civicrm_activity']['fields']['result'] = array(
        'title' => ts('Survey Result'),
        'default' => 'false',
      );
      $this->_columns['civicrm_activity']['filters']['result'] = array(
        'title' => ts('Survey Result'),
        'operator' => 'like',
        'type' => CRM_Utils_Type::T_STRING,
      );
      if (!empty($this->activeCampaigns)) {
        $this->_columns['civicrm_activity']['fields']['campaign_id'] = array(
          'title' => ts('Campaign'),
          'default' => 'false',
        );
        $this->_columns['civicrm_activity']['filters']['campaign_id'] = array(
          'title' => ts('Campaign'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->activeCampaigns,
        );
      }
      if (!empty($this->engagementLevels)) {
        $this->_columns['civicrm_activity']['fields']['engagement_level'] = array(
          'title' => ts('Engagement Index'),
          'default' => 'false',
        );
        $this->_columns['civicrm_activity']['filters']['engagement_level'] = array(
          'title' => ts('Engagement Index'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->engagementLevels,
        );
      }
    }
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_tagFilterTable = 'civicrm_activity';
    parent::__construct();
  }

  /**
   * Adding address fields with dbAlias for order clause.
   *
   * @param bool $orderBy
   *
   * @return array
   *   Address fields
   */
  public function addressFields($orderBy = FALSE) {
    $address = parent::addAddressFields(FALSE, TRUE);
    if ($orderBy) {
      foreach ($address['civicrm_address']['order_bys'] as $fieldName => $field) {
        $address['civicrm_address']['order_bys'][$fieldName]['dbAlias'] = "civicrm_address_{$fieldName}";
      }
    }
    return $address;
  }

  /**
   * Build select clause.
   *
   * @todo get rid of $recordType param. It's only because 3 separate contact tables
   * are mis-declared as one that we need it.
   *
   * @param null $recordType deprecated
   *   Parameter to hack around the bad decision made in construct to misrepresent
   *   different tables as the same table.
   */
  public function select($recordType = 'target') {
    if (!array_key_exists("contact_{$recordType}", $this->_params['fields']) &&
      $recordType != 'final'
    ) {
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
      // @todo - fix up the way the tables are declared in construct & remove this.
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_assignee.') ||
          strstr($clause, 'civicrm_contact_source.') ||
          strstr($clause, 'civicrm_email_assignee.') ||
          strstr($clause, 'civicrm_email_source.') ||
          strstr($clause, 'civicrm_phone_assignee.') ||
          strstr($clause, 'civicrm_phone_source.')
        ) {
          $removeKeys[] = $key;
          unset($this->_selectClauses[$key]);
        }
      }
    }
    elseif ($recordType == 'assignee') {
      // @todo - fix up the way the tables are declared in construct & remove this.
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_target.') ||
          strstr($clause, 'civicrm_contact_source.') ||
          strstr($clause, 'civicrm_email_target.') ||
          strstr($clause, 'civicrm_email_source.') ||
          strstr($clause, 'civicrm_phone_target.') ||
          strstr($clause, 'civicrm_phone_source.') ||
          strstr($clause, 'civicrm_address_')
        ) {
          $removeKeys[] = $key;
          unset($this->_selectClauses[$key]);
        }
      }
    }
    elseif ($recordType == 'source') {
      // @todo - fix up the way the tables are declared in construct & remove this.
      foreach ($this->_selectClauses as $key => $clause) {
        if (strstr($clause, 'civicrm_contact_target.') ||
          strstr($clause, 'civicrm_contact_assignee.') ||
          strstr($clause, 'civicrm_email_target.') ||
          strstr($clause, 'civicrm_email_assignee.') ||
          strstr($clause, 'civicrm_phone_target.') ||
          strstr($clause, 'civicrm_phone_assignee.') ||
          strstr($clause, 'civicrm_address_')
        ) {
          $removeKeys[] = $key;
          unset($this->_selectClauses[$key]);
        }
      }
    }
    elseif ($recordType == 'final') {
      $this->_selectClauses = $this->_selectAliasesTotal;
      foreach ($this->_selectClauses as $key => $clause) {
        // @todo - fix up the way the tables are declared in construct & remove this.
        if (strstr($clause, 'civicrm_contact_contact_target') ||
          strstr($clause, 'civicrm_contact_contact_assignee') ||
          strstr($clause, 'civicrm_contact_contact_source') ||
          strstr($clause, 'civicrm_phone_contact_source_phone') ||
          strstr($clause, 'civicrm_phone_contact_assignee_phone') ||
          strstr($clause, 'civicrm_email_contact_source_email') ||
          strstr($clause, 'civicrm_email_contact_assignee_email') ||
          strstr($clause, 'civicrm_email_contact_target_email') ||
          strstr($clause, 'civicrm_phone_contact_target_phone') ||
          strstr($clause, 'civicrm_address_')
        ) {
          $this->_selectClauses[$key] = "GROUP_CONCAT(DISTINCT $clause SEPARATOR ';') as $clause";
        }
      }
    }

    if ($recordType) {
      foreach ($removeKeys as $key) {
        unset($this->_selectAliases[$key]);
      }

      if ($recordType == 'target') {
        foreach ($this->_columns['civicrm_address']['order_bys'] as $fieldName => $field) {
          $orderByFld = $this->_columns['civicrm_address']['order_bys'][$fieldName];
          $fldInfo = $this->_columns['civicrm_address']['fields'][$fieldName];
          $this->_selectAliases[] = $orderByFld['dbAlias'];
          $this->_selectClauses[] = "{$fldInfo['dbAlias']} as {$orderByFld['dbAlias']}";
        }
        $this->_selectAliases = array_unique($this->_selectAliases);
        $this->_selectClauses = array_unique($this->_selectClauses);
      }
      $this->_select = "SELECT " . implode(', ', $this->_selectClauses) . " ";
    }
  }

  /**
   * Build from clause.
   * @todo remove this function & declare the 3 contact tables separately
   */
  public function from() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

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

    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
          LEFT JOIN civicrm_phone civicrm_phone_target
                 ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_phone_target.contact_id AND
                    civicrm_phone_target.is_primary = 1 ";
    }
    $this->_aliases['civicrm_contact'] = 'civicrm_contact_target';

    $this->joinAddressFromContact();
  }

  /**
   * Build where clause.
   *
   * @todo get rid of $recordType param. It's only because 3 separate contact tables
   * are mis-declared as one that we need it.
   *
   * @param string $recordType
   */
  public function where($recordType = NULL) {
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
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op && ($op != 'nnll' && $op != 'nll')) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
              if ($field['name'] == 'include_case_activities') {
                $clause = NULL;
              }
              if ($fieldName == 'activity_type_id' &&
                empty($this->_params['activity_type_id_value'])
              ) {
                if (empty($this->_params['include_case_activities_value'])) {
                  $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'label', TRUE);
                }
                $actTypes = array_flip($this->activityTypes);
                $clause = "( {$this->_aliases['civicrm_activity']}.activity_type_id IN (" .
                  implode(',', $actTypes) . ") )";
              }
            }
          }

          if ($field['name'] == 'current_user') {
            if (CRM_Utils_Array::value("{$fieldName}_value", $this->_params) ==
              1
            ) {
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

  /**
   * Override group by function.
   */
  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_activity']}.id");
  }

  /**
   * Build ACL clause.
   *
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact_a') {
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
   * @param int $groupID
   *
   * @throws Exception
   */
  public function add2group($groupID) {
    if (CRM_Utils_Array::value("contact_target_op", $this->_params) == 'nll') {
      CRM_Core_Error::fatal(ts('Current filter criteria didn\'t have any target contact to add to group'));
    }

    $new_select = 'AS addtogroup_contact_id';
    $select = str_ireplace('AS civicrm_contact_contact_target_id', $new_select, $this->_select);
    $new_having = ' addtogroup_contact_id';
    $having = str_ireplace(' civicrm_contact_contact_target_id', $new_having, $this->_having);
    $query = "$select
FROM {$this->temporaryTables['activity_temp_table']} tar
GROUP BY civicrm_activity_id $having {$this->_orderBy}";
    $select = 'AS addtogroup_contact_id';
    $query = str_ireplace('AS civicrm_contact_contact_target_id', $select, $query);
    $dao = $this->executeReportQuery($query);

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

    if (!empty($contactIDs)) {
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);
      CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."), ts('Contacts Added'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts("The listed records(s) cannot be added to the group."));
    }
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    $config = CRM_Core_Config::singleton();
    if (in_array("CiviCase", $config->enableComponents)) {
      $componentId = CRM_Core_Component::getComponentID('CiviCase');
      $caseActivityTypes = CRM_Core_OptionGroup::values('activity_type', TRUE, FALSE, FALSE, " AND v.component_id={$componentId}");
      if (!empty($fields['activity_type_id_value']) && is_array($fields['activity_type_id_value']) && empty($fields['include_case_activities_value'])) {
        foreach ($fields['activity_type_id_value'] as $activityTypeId) {
          if (in_array($activityTypeId, $caseActivityTypes)) {
            $errors['fields'] = ts("Please enable 'Include Case Activities' to filter with Case Activity types.");
          }
        }
      }
    }
    return $errors;
  }

  /**
   * @param $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

    //Assign those recordtype to array which have filter operator as 'Is not empty' or 'Is empty'
    $nullFilters = array();
    foreach (array('target', 'source', 'assignee') as $type) {
      if (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) ==
        'nnll' || !empty($this->_params["contact_{$type}_value"])
      ) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NOT NULL ";
      }
      elseif (CRM_Utils_Array::value("contact_{$type}_op", $this->_params) ==
        'nll'
      ) {
        $nullFilters[] = " civicrm_contact_contact_{$type}_id IS NULL ";
      }
    }

    // @todo - all this temp table stuff is here because pre 4.4 the activity contact
    // form did not exist.
    // Fixing the way the construct method declares them will make all this redundant.
    // 1. fill temp table with target results
    $this->buildACLClause(array('civicrm_contact_target'));
    $this->select('target');
    $this->from();
    $this->customDataFrom();
    $this->where('target');
    $tempTableName = $this->createTemporaryTable('activity_temp_table', "{$this->_select} {$this->_from} {$this->_where}");

    // 2. add new columns to hold assignee and source results
    // fixme: add when required
    $tempQuery = "
  ALTER TABLE  $tempTableName
  MODIFY COLUMN civicrm_contact_contact_target_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_assignee_id VARCHAR(128),
  ADD COLUMN civicrm_contact_contact_source_id VARCHAR(128),
  ADD COLUMN civicrm_phone_contact_assignee_phone VARCHAR(128),
  ADD COLUMN civicrm_phone_contact_source_phone VARCHAR(128),
  ADD COLUMN civicrm_email_contact_assignee_email VARCHAR(128),
  ADD COLUMN civicrm_email_contact_source_email VARCHAR(128)";
    $this->executeReportQuery($tempQuery);

    // 3. fill temp table with assignee results
    $this->buildACLClause(array('civicrm_contact_assignee'));
    $this->select('assignee');
    $this->buildAssigneeFrom();

    $this->customDataFrom();
    $this->where('assignee');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery = "INSERT INTO $tempTableName ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    $this->executeReportQuery($tempQuery);

    // 4. fill temp table with source results
    $this->buildACLClause(array('civicrm_contact_source'));
    $this->select('source');
    $this->buildSourceFrom();
    $this->customDataFrom();
    $this->where('source');
    $insertCols = implode(',', $this->_selectAliases);
    $tempQuery = "INSERT INTO $tempTableName ({$insertCols})
{$this->_select}
{$this->_from} {$this->_where}";
    $this->executeReportQuery($tempQuery);

    // 5. show final result set from temp table
    $rows = array();
    $this->select('final');
    $this->_having = "";
    if (!empty($nullFilters)) {
      $this->_having = "HAVING " . implode(' AND ', $nullFilters);
    }
    $this->orderBy();
    foreach ($this->_sections as $alias => $section) {
      if (!empty($section) && $section['name'] == 'activity_date_time') {
        $this->alterSectionHeaderForDateTime($tempTableName, $section['tplField']);
      }
    }

    if ($applyLimit) {
      $this->limit();
    }

    $groupByFromSelect = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, 'civicrm_activity_id');

    $this->_where = " WHERE (1)";
    $this->buildPermissionClause();
    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    $sql = "{$this->_select}
      FROM $tempTableName tar
      INNER JOIN civicrm_activity {$this->_aliases['civicrm_activity']} ON {$this->_aliases['civicrm_activity']}.id = tar.civicrm_activity_id
      INNER JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_contact']} ON {$this->_aliases['civicrm_activity_contact']}.activity_id = {$this->_aliases['civicrm_activity']}.id
      AND {$this->_aliases['civicrm_activity_contact']}.record_type_id = {$sourceID}
      LEFT JOIN civicrm_contact contact_civireport ON contact_civireport.id = {$this->_aliases['civicrm_activity_contact']}.contact_id
      {$this->_where} {$groupByFromSelect} {$this->_having} {$this->_orderBy} {$this->_limit}";

    CRM_Utils_Hook::alterReportVar('sql', $this, $this);
    $this->addToDeveloperTab($sql);

    return $sql;
  }

  public function postProcess() {
    //reset value of activity_date
    if (!empty($this->_resetDateFilter)) {
      $this->_formValues["activity_date_time_relative"] = NULL;
    }

    $this->beginPostProcess();
    $sql = $this->buildQuery(TRUE);
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
    $viewLinks = FALSE;

    // Would we ever want to retrieve from the form controller??
    $form = $this->noController ? NULL : $this;
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $form, FALSE, 'report');
    $actUrl = '';

    if (CRM_Core_Permission::check('access CiviCRM')) {
      $viewLinks = TRUE;
      $onHover = ts('View Contact Summary for this Contact');
      $onHoverAct = ts('View Activity Record');
    }
    foreach ($rows as $rowNum => $row) {
      // if we have an activity type, format the View Activity link for use in various columns
      if ($viewLinks &&
        array_key_exists('civicrm_activity_activity_type_id', $row)
      ) {
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
                $link[] = "<a title='" . $onHover . "' href='" . $url .
                  "'>{$assigneeNames[$id]}</a>";
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
                $link[] = "<a title='" . $onHover . "' href='" . $url .
                  "'>{$targetNames[$id]}</a>";
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

      if (array_key_exists('civicrm_activity_priority_id', $row)) {
        if ($value = $row['civicrm_activity_priority_id']) {
          $rows[$rowNum]['civicrm_activity_priority_id'] = $priority[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_details', $row) && $this->_outputMode == 'html') {
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

      if (array_key_exists('civicrm_activity_activity_date_time', $row) &&
        array_key_exists('civicrm_activity_status_id', $row)
      ) {
        if (CRM_Utils_Date::overdue($rows[$rowNum]['civicrm_activity_activity_date_time']) &&
          $activityStatus[$row['civicrm_activity_status_id']] != 'Completed'
        ) {
          $rows[$rowNum]['class'] = "status-overdue";
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'activity', 'List all activities for this', ';') ? TRUE : $entryFound;

      if (!$entryFound) {
        break;
      }
    }
  }

  public function sectionTotals() {
    // Reports using order_bys with sections must populate $this->_selectAliases in select() method.
    if (empty($this->_selectAliases)) {
      return;
    }

    if (!empty($this->_sections)) {
      // pull section aliases out of $this->_sections
      $sectionAliases = array_keys($this->_sections);

      $ifnulls = array();
      foreach (array_merge($sectionAliases, $this->_selectAliases) as $alias) {
        $ifnulls[] = "ifnull($alias, '') as $alias";
      }
      $this->_select = "SELECT " . implode(", ", $ifnulls);
      $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($ifnulls, $sectionAliases);

      $query = $this->_select .
        ", count(DISTINCT civicrm_activity_id) as ct from {$this->temporaryTables['activity_temp_table']} group by " .
        implode(", ", $sectionAliases);

      // initialize array of total counts
      $totals = array();
      $dao = $this->executeReportQuery($query);
      while ($dao->fetch()) {
        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = array();
        $i = 1;
        $aliasCount = count($sectionAliases);
        foreach ($sectionAliases as $alias) {
          $values[] = $row[$alias];
          $key = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values);
          if ($i == $aliasCount) {
            // the last alias is the lowest-level section header; use count as-is
            $totals[$key] = $dao->ct;
          }
          else {
            // other aliases are higher level; roll count into their total
            $totals[$key] += $dao->ct;
          }
        }
      }
      $this->assign('sectionTotals', $totals);
    }
  }

  /**
   * @todo remove this function & declare the 3 contact tables separately
   *
   * (Currently the construct method incorrectly melds them - this is an interim
   * refactor in order to get this under ReportTemplateTests)
   */
  protected function buildAssigneeFrom() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
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
    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
            LEFT JOIN civicrm_phone civicrm_phone_assignee
                   ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_phone_assignee.contact_id AND
                      civicrm_phone_assignee.is_primary = 1 ";
    }
    $this->_aliases['civicrm_contact'] = 'civicrm_contact_assignee';
    $this->joinAddressFromContact();
  }

  /**
   * @todo remove this function & declare the 3 contact tables separately
   *
   * (Currently the construct method incorrectly melds them - this is an interim
   * refactor in order to get this under ReportTemplateTests)
   */
  protected function buildSourceFrom() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
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
    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
            LEFT JOIN civicrm_phone civicrm_phone_source
                   ON {$this->_aliases['civicrm_activity_contact']}.contact_id = civicrm_phone_source.contact_id AND
                      civicrm_phone_source.is_primary = 1 ";
    }
    $this->_aliases['civicrm_contact'] = 'civicrm_contact_source';
    $this->joinAddressFromContact();
  }

}
