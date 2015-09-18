<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Report_Form_Contact_Detail extends CRM_Report_Form {
  const ROW_COUNT_LIMIT = 10;

  protected $_summary = NULL;

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
  );

  /**
   */
  /**
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
          ),
          'middle_name' => array(
            'title' => ts('Middle Name'),
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'gender_id' => array(
            'title' => ts('Gender'),
          ),
          'birth_date' => array(
            'title' => ts('Birth Date'),
          ),
          'age' => array(
            'title' => ts('Age'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
        'filters' => array(
          'id' => array(
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
          ),
          'sort_name' => array(
            'title' => ts('Contact Name'),
          ),
          'gender_id' => array(
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ),
          'birth_date' => array(
            'title' => ts('Birth Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ),
          'first_name' => array(
            'title' => ts('First Name'),
          ),
          'gender_id' => array(
            'name' => 'gender_id',
            'title' => ts('Gender'),
          ),
          'birth_date' => array(
            'name' => 'birth_date',
            'title' => ts('Birth Date'),
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array(
            'title' => ts('State/Province'),
          ),
        ),
        'order_bys' => array(
          'state_province_id' => array('title' => 'State/Province'),
          'city' => array('title' => 'City'),
          'postal_code' => array('title' => 'Postal Code'),
        ),
      ),
      'civicrm_country' => array(
        'dao' => 'CRM_Core_DAO_Country',
        'fields' => array(
          'name' => array('title' => 'Country', 'default' => TRUE),
        ),
        'order_bys' => array(
          'name' => array('title' => 'Country'),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' => array(
          'email' => array(
            'title' => ts('Email'),
          ),
        ),
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contact_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contribution_id' => array(
            'title' => ts('Contribution'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'total_amount' => array('default' => TRUE),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'default' => TRUE,
          ),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'default' => TRUE,
          ),
          'contribution_source' => NULL,
        ),
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(
          'contact_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'membership_id' => array(
            'title' => ts('Membership'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'membership_type_id' => array(
            'title' => ts('Membership Type'),
            'default' => TRUE,
          ),
          'join_date' => NULL,
          'membership_start_date' => array(
            'title' => ts('Start Date'),
            'default' => TRUE,
          ),
          'membership_end_date' => array(
            'title' => ts('End Date'),
            'default' => TRUE,
          ),
          'membership_status_id' => array(
            'name' => 'status_id',
            'title' => ts('Membership Status'),
            'default' => TRUE,
          ),
          'source' => array('title' => 'Membership Source'),
        ),
      ),
      'civicrm_participant' => array(
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => array(
          'contact_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'participant_id' => array(
            'title' => ts('Participant'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'event_id' => array('default' => TRUE),
          'participant_status_id' => array(
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'default' => TRUE,
          ),
          'role_id' => array(
            'title' => ts('Role'),
            'default' => TRUE,
          ),
          'participant_register_date' => array(
            'title' => ts('Register Date'),
            'default' => TRUE,
          ),
          'fee_level' => array(
            'title' => ts('Fee Level'),
            'default' => TRUE,
          ),
          'fee_amount' => array(
            'title' => ts('Fee Amount'),
            'default' => TRUE,
          ),
        ),
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => array(
          'relationship_id' => array(
            'name' => 'id',
            'title' => ts('Relationship'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'relationship_type_id' => array(
            'title' => ts('Relationship Type'),
            'default' => TRUE,
          ),
          'contact_id_b' => array(
            'title' => ts('Relationship With'),
            'default' => TRUE,
          ),
          'start_date' => array(
            'title' => 'Start Date ',
            'type' => CRM_Report_Form::OP_DATE,
          ),
          'end_date' => array(
            'title' => 'End Date ',
            'type' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
      'civicrm_activity' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => array(
          'id' => array(
            'title' => ts('Activity'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'default' => TRUE,
          ),
          'subject' => array(
            'title' => ts('Subject'),
            'default' => TRUE,
          ),
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'default' => TRUE,
          ),
          'activity_status_id' => array(
            'name' => 'status_id',
            'title' => ts('Activity Status'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'activity-fields',
      ),
      'civicrm_activity_target' => array(
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => array(
          'target_contact_id' => array(
            'title' => ts('With Contact'),
            'name' => 'contact_id',
            'default' => TRUE,
          ),
        ),
        'grouping' => 'activity-fields',
      ),
      'civicrm_activity_assignment' => array(
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => array(
          'assignee_contact_id' => array(
            'title' => ts('Assigned to'),
            'name' => 'contact_id',
            'default' => TRUE,
          ),
        ),
        'grouping' => 'activity-fields',
      ),
      'civicrm_activity_source' => array(
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => array(
          'source_contact_id' => array(
            'title' => ts('Added By'),
            'name' => 'contact_id',
            'default' => TRUE,
          ),
        ),
        'grouping' => 'activity-fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => NULL,
          'phone_ext' => array(
            'title' => ts('Phone Extension'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
    );
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  public function preProcess() {
    $this->_csvSupported = FALSE;
    parent::preProcess();
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    $this->_component = array(
      'contribution_civireport',
      'membership_civireport',
      'participant_civireport',
      'relationship_civireport',
      'activity_civireport',
    );
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            //isolate the select clause compoenent wise
            if (in_array($table['alias'], $this->_component)) {
              $select[$table['alias']][] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeadersComponent[$table['alias']]["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeadersComponent[$table['alias']]["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            }
            elseif ($table['alias'] ==
              $this->_aliases['civicrm_activity_target'] ||
              $table['alias'] ==
              $this->_aliases['civicrm_activity_assignment'] ||
              $table['alias'] == $this->_aliases['civicrm_activity_source']
            ) {
              if ($table['alias'] == $this->_aliases['civicrm_activity_target']
              ) {
                $addContactId = 'civicrm_activity_target.contact_id as target_contact_id';
              }
              elseif ($table['alias'] ==
                $this->_aliases['civicrm_activity_source']
              ) {
                $addContactId = 'civicrm_activity_source.contact_id';
              }
              else {
                $addContactId = 'civicrm_activity_assignment.contact_id as assignee_contact_id';
              }

              $tableName = $table['alias'];
              $select['activity_civireport'][] = "$tableName.display_name as {$tableName}_{$fieldName}, $addContactId ";
              $this->_columnHeadersComponent['activity_civireport']["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeadersComponent['activity_civireport']["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }

    foreach ($this->_component as $val) {
      if (!empty($select[$val])) {
        $this->_selectComponent[$val] = "SELECT " . implode(', ', $select[$val]) . " ";
        unset($select[$val]);
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
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
    return $errors;
  }

  public function from() {
    $group = " ";
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}";

    if ($this->isTableSelected('civicrm_country') ||
      $this->isTableSelected('civicrm_address')
    ) {
      $this->_from .= "
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";
    }

    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
            LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                      {$this->_aliases['civicrm_email']}.is_primary = 1) ";
    }

    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }

    if ($this->isTableSelected('civicrm_country')) {
      $this->_from .= "
            LEFT JOIN civicrm_country {$this->_aliases['civicrm_country']}
                   ON {$this->_aliases['civicrm_address']}.country_id = {$this->_aliases['civicrm_country']}.id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ";
    }

    $this->_from .= "{$group}";

    foreach ($this->_component as $val) {
      if (!empty($this->_selectComponent['contribution_civireport'])) {
        $this->_formComponent['contribution_civireport'] = " FROM
                            civicrm_contact  {$this->_aliases['civicrm_contact']}
                            INNER JOIN civicrm_contribution       {$this->_aliases['civicrm_contribution']}
                                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
                            {$group}
                    ";
      }
      if (!empty($this->_selectComponent['membership_civireport'])) {
        $this->_formComponent['membership_civireport'] = " FROM
                            civicrm_contact  {$this->_aliases['civicrm_contact']}
                            INNER JOIN civicrm_membership       {$this->_aliases['civicrm_membership']}
                                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_membership']}.contact_id
                            {$group} ";
      }
      if (!empty($this->_selectComponent['participant_civireport'])) {
        $this->_formComponent['participant_civireport'] = " FROM
                            civicrm_contact  {$this->_aliases['civicrm_contact']}
                            INNER JOIN civicrm_participant       {$this->_aliases['civicrm_participant']}
                                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_participant']}.contact_id
                            {$group} ";
      }

      if (!empty($this->_selectComponent['activity_civireport'])) {
        $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
        $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
        $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

        $this->_formComponent['activity_civireport'] = "FROM
                        civicrm_activity {$this->_aliases['civicrm_activity']}
                        LEFT JOIN civicrm_activity_contact civicrm_activity_target ON
                            {$this->_aliases['civicrm_activity']}.id = civicrm_activity_target.activity_id AND
                            civicrm_activity_target.record_type_id = {$targetID}
                        LEFT JOIN civicrm_activity_contact civicrm_activity_assignment ON
                            {$this->_aliases['civicrm_activity']}.id = civicrm_activity_assignment.activity_id AND                                                                      civicrm_activity_assignment.record_type_id = {$assigneeID}
                        LEFT JOIN civicrm_activity_contact civicrm_activity_source
                            ON {$this->_aliases['civicrm_activity']}.id = civicrm_activity_source.activity_id AND
                            civicrm_activity_source.record_type_id = {$sourceID}
                        LEFT JOIN civicrm_contact {$this->_aliases['civicrm_activity_target']} ON
                            civicrm_activity_target.contact_id = {$this->_aliases['civicrm_activity_target']}.id

                        LEFT JOIN civicrm_contact {$this->_aliases['civicrm_activity_assignment']} ON
                            civicrm_activity_assignment.contact_id = {$this->_aliases['civicrm_activity_assignment']}.id
                        LEFT JOIN civicrm_contact {$this->_aliases['civicrm_activity_source']} ON
                            civicrm_activity_source.contact_id = {$this->_aliases['civicrm_activity_source']}.id
                        LEFT JOIN civicrm_option_value ON
                            ( {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value )
                        LEFT JOIN civicrm_option_group ON
                            civicrm_option_group.id = civicrm_option_value.option_group_id
                        LEFT JOIN civicrm_case_activity ON
                            civicrm_case_activity.activity_id = {$this->_aliases['civicrm_activity']}.id
                        LEFT JOIN civicrm_case ON
                            civicrm_case_activity.case_id = civicrm_case.id
                        LEFT JOIN civicrm_case_contact ON
                            civicrm_case_contact.case_id = civicrm_case.id ";
      }

      if (!empty($this->_selectComponent['relationship_civireport'])) {
        $this->_formComponent['relationship_civireport'] = "FROM
                            civicrm_relationship {$this->_aliases['civicrm_relationship']}

                            LEFT JOIN civicrm_contact  {$this->_aliases['civicrm_contact']} ON
                                {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_relationship']}.contact_id_b
                            LEFT JOIN civicrm_contact  contact_a ON
                               contact_a.id = {$this->_aliases['civicrm_relationship']}.contact_id_a ";
      }
    }
  }

  public function where() {
    $clauses = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Report_Form::OP_DATE
          ) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            $clause = $this->whereClause($field,
              $op,
              CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
              CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
              CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
            );
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    $this->_where .= " GROUP BY {$this->_aliases['civicrm_contact']}.id ";
  }

  /**
   * @return array
   */
  public function clauseComponent() {
    $selectedContacts = implode(',', $this->_contactSelected);
    $contribution = $membership = $participant = NULL;
    $eligibleResult = $rows = $tempArray = array();
    foreach ($this->_component as $val) {
      if (!empty($this->_selectComponent[$val]) &&
        ($val != 'activity_civireport' && $val != 'relationship_civireport')
      ) {
        $sql = "{$this->_selectComponent[$val]} {$this->_formComponent[$val]}
                         WHERE    {$this->_aliases['civicrm_contact']}.id IN ( $selectedContacts )
                         GROUP BY {$this->_aliases['civicrm_contact']}.id,{$val}.id ";

        $dao = CRM_Core_DAO::executeQuery($sql);
        while ($dao->fetch()) {
          $countRecord = 0;
          $eligibleResult[$val] = $val;
          $CC = 'civicrm_' . substr_replace($val, '', -11, 11) . '_contact_id';
          $row = array();
          foreach ($this->_columnHeadersComponent[$val] as $key => $value) {
            $countRecord++;
            $row[$key] = $dao->$key;
          }

          //if record exist for component(except contact_id)
          //since contact_id is selected for every component
          if ($countRecord > 1) {
            $rows[$dao->$CC][$val][] = $row;
          }
          $tempArray[$dao->$CC] = $dao->$CC;
        }
      }
    }

    if (!empty($this->_selectComponent['relationship_civireport'])) {

      $relTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, NULL, TRUE);

      $val = 'relationship_civireport';
      $eligibleResult[$val] = $val;
      $sql = "{$this->_selectComponent[$val]},{$this->_aliases['civicrm_contact']}.display_name as contact_b_name,  contact_a.id as contact_a_id , contact_a.display_name  as contact_a_name  {$this->_formComponent[$val]}
                         WHERE    ({$this->_aliases['civicrm_contact']}.id IN ( $selectedContacts )
                                  OR
                                  contact_a.id IN ( $selectedContacts ) ) AND
                                  {$this->_aliases['civicrm_relationship']}.is_active = 1 AND
                                  contact_a.is_deleted = 0 AND
                                  {$this->_aliases['civicrm_contact']}.is_deleted = 0
                         GROUP BY {$this->_aliases['civicrm_relationship']}.id";

      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        foreach ($this->_columnHeadersComponent[$val] as $key => $value) {
          if ($key == 'civicrm_relationship_contact_id_b') {
            $row[$key] = $dao->contact_b_name;
            continue;
          }

          $row[$key] = $dao->$key;
        }

        $relTitle = "" . $dao->civicrm_relationship_relationship_type_id .
          '_a_b';
        $row['civicrm_relationship_relationship_type_id'] = $relTypes[$relTitle];

        $rows[$dao->contact_a_id][$val][] = $row;

        $row['civicrm_relationship_contact_id_b'] = $dao->contact_a_name;
        $relTitle = "" . $dao->civicrm_relationship_relationship_type_id .
          '_b_a';
        if (isset($relTypes[$relTitle])) {
          $row['civicrm_relationship_relationship_type_id'] = $relTypes[$relTitle];
        }
        $rows[$dao->civicrm_relationship_contact_id_b][$val][] = $row;
      }
    }

    if (!empty($this->_selectComponent['activity_civireport'])) {

      $componentClause = "civicrm_option_value.component_id IS NULL";
      $componentsIn = NULL;
      $compInfo = CRM_Core_Component::getEnabledComponents();
      foreach ($compInfo as $compObj) {
        if ($compObj->info['showActivitiesInCore']) {
          $componentsIn = $componentsIn ? ($componentsIn . ', ' .
            $compObj->componentID) : $compObj->componentID;
        }
      }
      if ($componentsIn) {
        $componentClause = "( $componentClause OR
                                      civicrm_option_value.component_id IN ($componentsIn) )";
      }

      $val = 'activity_civireport';
      $eligibleResult[$val] = $val;
      $sql = "{$this->_selectComponent[$val]} ,
                 {$this->_aliases['civicrm_activity_source']}.display_name as added_by {$this->_formComponent[$val]}

                 WHERE ( civicrm_activity_source.contact_id IN ($selectedContacts) OR
                         civicrm_activity_target.contact_id IN ($selectedContacts) OR
                         civicrm_activity_assignment.contact_id IN ($selectedContacts) OR
                         civicrm_case_contact.contact_id IN ($selectedContacts) ) AND
                         civicrm_option_group.name = 'activity_type' AND
                         {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                         ($componentClause)

                 GROUP BY {$this->_aliases['civicrm_activity']}.id

                 ORDER BY {$this->_aliases['civicrm_activity']}.activity_date_time desc  ";

      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        foreach ($this->_columnHeadersComponent[$val] as $key => $value) {
          if ($key == 'civicrm_activity_source_contact_id') {
            $row[$key] = $dao->added_by;
            continue;
          }
          $row[$key] = $dao->$key;
        }

        if (isset($dao->civicrm_activity_source_contact_id)) {
          $rows[$dao->civicrm_activity_source_contact_id][$val][] = $row;
        }
        if (isset($dao->target_contact_id)) {
          $rows[$dao->target_contact_id][$val][] = $row;
        }
        if (isset($dao->assignee_contact_id)) {
          $rows[$dao->assignee_contact_id][$val][] = $row;
        }
      }

      //unset the component header if data is not present
      foreach ($this->_component as $val) {
        if (!in_array($val, $eligibleResult)) {

          unset($this->_columnHeadersComponent[$val]);
        }
      }
    }

    return $rows;
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = array();

    $count = count($rows);
    if ($this->_rollup && ($this->_rollup != '')) {
      $count++;
    }

    $this->countStat($statistics, $count);
    $this->filterStat($statistics);

    return $statistics;
  }

  /**
   * Override to set limit is 10
   * @param int $rowCount
   */
  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    parent::limit($rowCount);
  }

  /**
   * Override to set pager with limit is 10
   * @param int $rowCount
   */
  public function setPager($rowCount = self::ROW_COUNT_LIMIT) {
    parent::setPager($rowCount);
  }

  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = $this->_contactSelected = array();
    $this->buildRows($sql, $rows);
    foreach ($rows as $key => $val) {
      $rows[$key]['contactID'] = $val['civicrm_contact_id'];
      $this->_contactSelected[] = $val['civicrm_contact_id'];
    }

    $this->formatDisplay($rows);

    if (!empty($this->_contactSelected)) {
      $componentRows = $this->clauseComponent();
      $this->alterComponentDisplay($componentRows);

      //unset Conmponent id and contact id from display
      foreach ($this->_columnHeadersComponent as $componentTitle => $headers) {
        $id_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) . '_' .
          substr_replace($componentTitle, '', -11, 11) . '_id';
        $contact_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) .
          '_contact_id';
        if ($componentTitle == 'activity_civireport') {
          $id_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) . '_id';
        }

        unset($this->_columnHeadersComponent[$componentTitle][$id_header]);
        unset($this->_columnHeadersComponent[$componentTitle][$contact_header]);
      }

      $this->assign_by_ref('columnHeadersComponent', $this->_columnHeadersComponent);
      $this->assign_by_ref('componentRows', $componentRows);
    }

    $this->doTemplateAssignment($rows);
    $this->endPostProcess();
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

    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report

      // change contact name with link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {

        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact');
        $entryFound = TRUE;
      }

      //handle gender
      if (array_key_exists('civicrm_contact_gender_id', $row)) {
        if ($value = $row['civicrm_contact_gender_id']) {
          $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
          $rows[$rowNum]['civicrm_contact_gender_id'] = $gender[$value];
        }
        $entryFound = TRUE;
      }

      // display birthday in the configured custom format
      if (array_key_exists('civicrm_contact_birth_date', $row)) {
        $birthDate = $row['civicrm_contact_birth_date'];
        if ($birthDate) {
          $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($birthDate, '%Y%m%d');
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * @param $componentRows
   */
  public function alterComponentDisplay(&$componentRows) {
    // custom code to alter rows
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();

    $entryFound = FALSE;
    foreach ($componentRows as $contactID => $components) {
      foreach ($components as $component => $rows) {
        foreach ($rows as $rowNum => $row) {
          // handle contribution
          if ($component == 'contribution_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_contribution_financial_type_id'] = CRM_Contribute_PseudoConstant::financialType($val, FALSE);
            }

            if ($val = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($val);
            }
            $entryFound = TRUE;
          }

          if ($component == 'membership_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_membership_membership_type_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($val, FALSE);
            }

            if ($val = CRM_Utils_Array::value('civicrm_membership_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_status_id'] = CRM_Member_PseudoConstant::membershipStatus($val, FALSE);
            }
            $entryFound = TRUE;
          }

          if ($component == 'participant_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_participant_event_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($val, FALSE);
              $url = CRM_Report_Utils_Report::getNextUrl('event/income',
                'reset=1&force=1&id_op=in&id_value=' . $val,
                $this->_absoluteUrl, $this->_id
              );
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id_link'] = $url;
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id_hover'] = ts('View Event Income details for this Event.');
              $entryFound = TRUE;
            }

            if ($val = CRM_Utils_Array::value('civicrm_participant_participant_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($val, FALSE);
            }
            if ($val = CRM_Utils_Array::value('civicrm_participant_role_id', $row)) {
              $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $val);
              $value = array();
              foreach ($roles as $role) {
                $value[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
              }
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_role_id'] = implode(', ', $value);
            }

            $entryFound = TRUE;
          }

          if ($component == 'activity_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_activity_activity_type_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_activity_activity_type_id'] = $activityTypes[$val];
            }
            if ($val = CRM_Utils_Array::value('civicrm_activity_activity_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_activity_activity_status_id'] = $activityStatus[$val];
            }

            $entryFound = TRUE;
          }
          if ($component == 'membership_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_membership_membership_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_membership_status_id'] = CRM_Member_PseudoConstant::membershipStatus($val);
            }
            $entryFound = TRUE;
          }

          // skip looking further in rows, if first row itself doesn't
          // have the column we need
          if (!$entryFound) {
            break;
          }
        }
      }
    }
  }

}
