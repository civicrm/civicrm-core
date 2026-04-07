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
class CRM_Report_Form_Contact_Detail extends CRM_Report_Form {

  protected $_summary;

  private $_columnHeadersComponent;

  private $_selectComponent;

  private $_formComponent;

  private $_contactSelected;

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Household',
    'Organization',
  ];

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Store the joins for civicrm_activity_contact
   *
   * Activities are retrieved by a union of four queries in order to catch
   * activities where the contact is the source, target, assignee, or case
   * contact.
   *
   * @var array
   */
  protected $activityContactJoin = [];

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'first_name' => [
            'title' => ts('First Name'),
          ],
          'middle_name' => [
            'title' => ts('Middle Name'),
          ],
          'last_name' => [
            'title' => ts('Last Name'),
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'gender_id' => [
            'title' => ts('Gender'),
          ],
          'birth_date' => [
            'title' => ts('Birth Date'),
          ],
          'age' => [
            'title' => ts('Age'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
        'filters' => $this->getBasicContactFilters(),
        'grouping' => 'contact-fields',
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
          'first_name' => [
            'title' => ts('First Name'),
          ],
          'gender_id' => [
            'name' => 'gender_id',
            'title' => ts('Gender'),
          ],
          'birth_date' => [
            'name' => 'birth_date',
            'title' => ts('Birth Date'),
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' => [
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => [
            'title' => ts('State/Province'),
          ],
        ],
        'order_bys' => [
          'street_address' => ['title' => ts('Street Address')],
          'state_province_id' => ['title' => ts('State/Province')],
          'city' => ['title' => ts('City')],
          'postal_code' => ['title' => ts('Postal Code')],
        ],
      ],
      'civicrm_country' => [
        'dao' => 'CRM_Core_DAO_Country',
        'fields' => [
          'name' => ['title' => ts('Country'), 'default' => TRUE],
        ],
        'order_bys' => [
          'name' => ['title' => ts('Country')],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'order_bys' => [
          'email' => [
            'title' => ts('Email'),
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contribution_id' => [
            'title' => ts('Contribution'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'total_amount' => ['default' => TRUE],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'default' => TRUE,
          ],
          'trxn_id' => NULL,
          'receive_date' => ['default' => TRUE],
          'receipt_date' => NULL,
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'default' => TRUE,
          ],
          'contribution_source' => NULL,
        ],
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'membership_id' => [
            'title' => ts('Membership'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'default' => TRUE,
          ],
          'join_date' => [
            'title' => ts('Member Since'),
          ],
          'membership_start_date' => [
            'title' => ts('Membership Start Date'),
            'default' => TRUE,
          ],
          'membership_end_date' => [
            'title' => ts('Membership Expiration Date'),
            'default' => TRUE,
          ],
          'membership_status_id' => [
            'name' => 'status_id',
            'title' => ts('Membership Status'),
            'default' => TRUE,
          ],
          'source' => ['title' => ts('Membership Source')],
        ],
      ],
      'civicrm_participant' => [
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => [
          'contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'participant_id' => [
            'title' => ts('Participant'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'event_id' => ['default' => TRUE],
          'participant_status_id' => [
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'default' => TRUE,
          ],
          'role_id' => [
            'title' => ts('Role'),
            'default' => TRUE,
          ],
          'participant_register_date' => [
            'title' => ts('Register Date'),
            'default' => TRUE,
          ],
          'fee_level' => [
            'title' => ts('Fee Level'),
            'default' => TRUE,
          ],
          'fee_amount' => [
            'title' => ts('Fee Amount'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_relationship' => [
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => [
          'relationship_id' => [
            'name' => 'id',
            'title' => ts('Relationship'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'relationship_type_id' => [
            'title' => ts('Relationship Type'),
            'default' => TRUE,
          ],
          'contact_id_b' => [
            'title' => ts('Relationship With'),
            'default' => TRUE,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
            'type' => CRM_Report_Form::OP_DATE,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'type' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'id' => [
            'title' => ts('Activity'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'default' => TRUE,
          ],
          'subject' => [
            'title' => ts('Subject'),
            'default' => TRUE,
          ],
          'activity_date_time' => [
            'title' => ts('Activity Date'),
            'default' => TRUE,
          ],
          'activity_status_id' => [
            'name' => 'status_id',
            'title' => ts('Activity Status'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'activity-fields',
      ],
      'civicrm_activity_target' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => [
          'target_contact_id' => [
            'title' => ts('With Contact'),
            'name' => 'contact_id',
            'default' => TRUE,
          ],
        ],
        'grouping' => 'activity-fields',
      ],
      'civicrm_activity_assignment' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => [
          'assignee_contact_id' => [
            'title' => ts('Assigned to'),
            'name' => 'contact_id',
            'default' => TRUE,
          ],
        ],
        'grouping' => 'activity-fields',
      ],
      'civicrm_activity_source' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => [
          'source_contact_id' => [
            'title' => ts('Added by'),
            'name' => 'contact_id',
            'default' => TRUE,
          ],
        ],
        'grouping' => 'activity-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => NULL,
          'phone_ext' => [
            'title' => ts('Phone Extension'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
    ];
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
    $this->setRowCount(10);
  }

  public function preProcess(): void {
    $this->_csvSupported = FALSE;
    parent::preProcess();
  }

  public function select(): void {
    $select = [];
    $this->_columnHeaders = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            //isolate the select clause component wise
            if (in_array($table['alias'], $this->getAvailableComponents())) {
              $select[$table['alias']][] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeadersComponent[$table['alias']]["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeadersComponent[$table['alias']]["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
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
              $this->_columnHeadersComponent['activity_civireport']["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeadersComponent['activity_civireport']["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }

    foreach ($this->getAvailableComponents() as $val) {
      if (!empty($select[$val])) {
        $this->_selectComponent[$val] = 'SELECT ' . implode(', ', $select[$val]) . ' ';
        unset($select[$val]);
      }
    }

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  public function from(): void {
    $this->_from = "
      FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
    ";

    $this->joinAddressFromContact();
    $this->joinCountryFromAddress();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // only include tables that are in from clause
    $componentTables = array_intersect($this->_aliases, $this->getAvailableComponents());
    $componentTables = array_flip($componentTables);
    $this->_selectedTables = array_diff($this->_selectedTables, $componentTables);

    if (!empty($this->_selectComponent['contribution_civireport'])) {
      $this->_formComponent['contribution_civireport'] = <<<HERESQL
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']}
        INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
HERESQL;
    }
    if (!empty($this->_selectComponent['membership_civireport'])) {
      $this->_formComponent['membership_civireport'] = <<<HERESQL
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']}
        INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_membership']}.contact_id
HERESQL;
    }
    if (!empty($this->_selectComponent['participant_civireport'])) {
      $this->_formComponent['participant_civireport'] = <<<HERESQL
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']}
        INNER JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_participant']}.contact_id
HERESQL;
    }

    if (!empty($this->_selectComponent['activity_civireport'])) {

      // First, prepare all the joins to filter activities by contact
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');

      $aliasMap = [
        'Activity Assignees' => 'civicrm_activity_assignment',
        'Activity Targets' => 'civicrm_activity_target',
        'Activity Source' => 'civicrm_activity_source',
      ];

      $this->activityContactJoin['case'] = <<<HERESQL
        JOIN civicrm_case_activity
          ON civicrm_case_activity.activity_id = activity_civireport.id
        JOIN civicrm_case
          ON civicrm_case_activity.case_id = civicrm_case.id
        JOIN civicrm_case_contact
          ON civicrm_case_contact.case_id = civicrm_case.id
          AND civicrm_case_contact.contact_id IN ([FILTERCONTACTSHERE])
HERESQL;

      // Collect the joins for civicrm_contact to each of the activity contact joins
      $contactJoins = [];

      foreach ($activityContacts as $recordTypeId => $label) {
        if (empty($aliasMap[$label])) {
          continue;
        }

        // Inner join on this record type
        $this->activityContactJoin[$recordTypeId] = <<<HERESQL
          JOIN civicrm_activity_contact {$aliasMap[$label]}
            ON activity_civireport.id = {$aliasMap[$label]}.activity_id
              AND {$aliasMap[$label]}.record_type_id = $recordTypeId
              AND {$aliasMap[$label]}.contact_id IN ([FILTERCONTACTSHERE])
HERESQL;

        // Cycle through other record types to add left joins
        foreach ($activityContacts as $recordTypeIdX => $labelX) {
          if ($recordTypeIdX == $recordTypeId || empty($aliasMap[$labelX])) {
            continue;
          }
          $this->activityContactJoin[$recordTypeId] .= <<<HERESQL
            LEFT JOIN civicrm_activity_contact {$aliasMap[$labelX]}
              ON activity_civireport.id = {$aliasMap[$labelX]}.activity_id
                AND {$aliasMap[$labelX]}.record_type_id = $recordTypeIdX
HERESQL;
        }

        // Add to the joins for case activities
        $this->activityContactJoin['case'] .= <<<HERESQL
          LEFT JOIN civicrm_activity_contact {$aliasMap[$label]}
            ON activity_civireport.id = {$aliasMap[$label]}.activity_id
              AND {$aliasMap[$label]}.record_type_id = $recordTypeId
HERESQL;

        // Each activity_contact join gets joined to civicrm_contact
        $contactJoins[] = <<<HERESQL
        LEFT JOIN civicrm_contact {$this->_aliases[$aliasMap[$label]]}
          ON $aliasMap[$label].contact_id = {$this->_aliases[$aliasMap[$label]]}.id
HERESQL;
      }

      // civicrm_contact joins into a single string
      $contactJoins = implode(PHP_EOL, $contactJoins);

      // Now filter out component activities that should be suppressed
      $compInfo = CRM_Core_Component::getEnabledComponents();
      $componentsList = [];
      foreach ($compInfo as $compObj) {
        if ($compObj->info['showActivitiesInCore']) {
          $componentsList[] = $compObj->componentID;
        }
      }
      $componentClause = 'civicrm_option_value.component_id IS NULL';
      if (!empty($componentsList)) {
        $componentsIn = implode(', ', $componentsList);
        $componentClause = <<<HERESQL
        ( $componentClause
          OR civicrm_option_value.component_id IN ($componentsIn) )
HERESQL;
      }

      $this->_formComponent['activity_civireport'] = <<<HERESQL
      FROM
          civicrm_activity {$this->_aliases['civicrm_activity']}
          [ACTIVITYCONTACTJOINSHERE]
          $contactJoins
          JOIN civicrm_option_value
            ON {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value
            AND $componentClause
          JOIN civicrm_option_group
            ON civicrm_option_group.id = civicrm_option_value.option_group_id
            AND civicrm_option_group.name = 'activity_type'
HERESQL;
    }

    if (!empty($this->_selectComponent['relationship_civireport'])) {
      $this->_formComponent['relationship_civireport'] = <<<HERESQL
      FROM
        civicrm_relationship {$this->_aliases['civicrm_relationship']}
        LEFT JOIN civicrm_contact  {$this->_aliases['civicrm_contact']}
          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_relationship']}.contact_id_b
        LEFT JOIN civicrm_contact  contact_a
          ON contact_a.id = {$this->_aliases['civicrm_relationship']}.contact_id_a
HERESQL;
    }
  }

  public function where(): void {
    $clauses = [];

    foreach ($this->_columns as $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if (($field['operatorType'] ?? 0) & CRM_Report_Form::OP_DATE
          ) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            $clause = $this->whereClause($field,
              $op,
              $this->_params["{$fieldName}_value"] ?? NULL,
              $this->_params["{$fieldName}_min"] ?? NULL,
              $this->_params["{$fieldName}_max"] ?? NULL
            );
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = 'WHERE ( 1 ) ';
    }
    else {
      $this->_where = 'WHERE ' . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  /**
   * @return array
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function clauseComponent(): array {
    $selectedContacts = implode(',', $this->_contactSelected);
    $eligibleResult = $rows = $tempArray = [];
    foreach ($this->getAvailableComponents() as $val) {
      if (!empty($this->_selectComponent[$val]) &&
        ($val !== 'activity_civireport' && $val !== 'relationship_civireport')
      ) {
        $sql = <<<HERESQL
        {$this->_selectComponent[$val]} {$this->_formComponent[$val]}
        WHERE {$this->_aliases['civicrm_contact']}.id IN ( $selectedContacts )
HERESQL;

        $dao = CRM_Core_DAO::executeQuery($sql);
        while ($dao->fetch()) {
          $countRecord = 0;
          $eligibleResult[$val] = $val;
          $CC = 'civicrm_' . substr_replace($val, '', -11, 11) . '_contact_id';
          $row = [];
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

      $relTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);

      $val = 'relationship_civireport';
      $eligibleResult[$val] = $val;
      $sql = <<<HERESQL
      {$this->_selectComponent[$val]},{$this->_aliases['civicrm_contact']}.display_name as contact_b_name, contact_a.id as contact_a_id, contact_a.display_name as contact_a_name
      {$this->_formComponent[$val]}
      WHERE ({$this->_aliases['civicrm_contact']}.id IN ( $selectedContacts )
          OR contact_a.id IN ( $selectedContacts ) )
        AND {$this->_aliases['civicrm_relationship']}.is_active = 1
        AND contact_a.is_deleted = 0
        AND {$this->_aliases['civicrm_contact']}.is_deleted = 0
HERESQL;

      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        foreach ($this->_columnHeadersComponent[$val] as $key => $value) {
          if ($key === 'civicrm_relationship_contact_id_b') {
            $row[$key] = $dao->contact_b_name;
            continue;
          }

          $row[$key] = $dao->$key;
        }

        $relTitle = '' . $dao->civicrm_relationship_relationship_type_id .
          '_a_b';
        $row['civicrm_relationship_relationship_type_id'] = $relTypes[$relTitle];

        $rows[$dao->contact_a_id][$val][] = $row;

        $row['civicrm_relationship_contact_id_b'] = $dao->contact_a_name;
        $relTitle = '' . $dao->civicrm_relationship_relationship_type_id .
          '_b_a';
        if (isset($relTypes[$relTitle])) {
          $row['civicrm_relationship_relationship_type_id'] = $relTypes[$relTitle];
        }
        $rows[$dao->civicrm_relationship_contact_id_b][$val][] = $row;
      }
    }

    if (!empty($this->_selectComponent['activity_civireport'])) {
      $val = 'activity_civireport';
      $eligibleResult[$val] = $val;

      // The activities we want to show are those where the contact is the
      // target, assignee, source, or the client on a case.  Since the vast
      // majority of activities will not involve the client, it's impractical to
      // retrieve all activities and use OR clauses in the WHERE.  Instead, we
      // use a union of sub-queries for each of the four ways activities might
      // join to the contact.
      $unionParts = [];
      foreach ($this->activityContactJoin as $activityContactJoinClauses) {
        $fromClauses = str_replace(
          '[ACTIVITYCONTACTJOINSHERE]',
          str_replace('[FILTERCONTACTSHERE]', $selectedContacts, $activityContactJoinClauses),
          $this->_formComponent[$val]
        );
        $unionParts[] = <<<HERESQL
        (
          {$this->_selectComponent[$val]},
          {$this->_aliases['civicrm_activity_source']}.display_name as added_by,
          {$this->_aliases['civicrm_activity']}.activity_date_time as date_time_for_sort
          $fromClauses

          WHERE {$this->_aliases['civicrm_activity']}.is_test = 0
        )
HERESQL;
      }

      $sql = implode(' UNION ', $unionParts) . ' ORDER BY date_time_for_sort DESC';
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        foreach ($this->_columnHeadersComponent[$val] as $key => $value) {
          if ($key === 'civicrm_activity_source_contact_id') {
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
      foreach ($this->getAvailableComponents() as $val) {
        if (!in_array($val, $eligibleResult)) {

          unset($this->_columnHeadersComponent[$val]);
        }
      }
    }

    return $rows;
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows): array {
    $statistics = [];

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
   * @param int|null $rowCount
   */
  public function limit($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    parent::limit($rowCount);
  }

  /**
   * Override to set pager with limit is 10
   * @param int|null $rowCount
   */
  public function setPager($rowCount = NULL): void {
    $rowCount ??= $this->getRowCount();
    parent::setPager($rowCount);
  }

  /**
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function postProcess(): void {
    $this->beginPostProcess();
    $sql = $this->buildQuery(TRUE);

    $rows = $this->_contactSelected = [];
    $this->buildRows($sql, $rows);
    foreach ($rows as $key => $val) {
      $rows[$key]['contactID'] = $val['civicrm_contact_id'];
      $this->_contactSelected[] = $val['civicrm_contact_id'];
    }

    $this->formatDisplay($rows);

    if (!empty($this->_contactSelected)) {
      $componentRows = $this->clauseComponent();
      $this->alterComponentDisplay($componentRows);

      //unset Component id and contact id from display
      foreach ($this->_columnHeadersComponent as $componentTitle => $headers) {
        $id_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) . '_' .
          substr_replace($componentTitle, '', -11, 11) . '_id';
        $contact_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) .
          '_contact_id';
        if ($componentTitle === 'activity_civireport') {
          $id_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) . '_id';
        }

        unset($this->_columnHeadersComponent[$componentTitle][$id_header], $this->_columnHeadersComponent[$componentTitle][$contact_header]);
      }

      $this->assign('columnHeadersComponent', $this->_columnHeadersComponent);
      $this->assign('componentRows', $componentRows);
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
  public function alterDisplay(&$rows): void {

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
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Record');
        $entryFound = TRUE;
      }

      // Handle ID to label conversion for contact fields
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * @param array $componentRows
   */
  public function alterComponentDisplay(array &$componentRows): void {
    $entryFound = FALSE;
    foreach ($componentRows as $contactID => $components) {
      foreach ($components as $component => $rows) {
        foreach ($rows as $rowNum => $row) {
          // handle contribution
          if ($component === 'contribution_civireport') {
            $val = $row['civicrm_contribution_financial_type_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_contribution_financial_type_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'financial_type_id', $val);
            }

            $val = $row['civicrm_contribution_contribution_status_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $val);
            }
            $entryFound = TRUE;
          }

          if ($component === 'membership_civireport') {
            $val = $row['civicrm_membership_membership_type_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_membership_type_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'membership_type_id', $val);
            }

            $val = $row['civicrm_membership_status_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'membership_status_id', $val);
            }
            $entryFound = TRUE;
          }

          if ($component === 'participant_civireport') {
            $val = $row['civicrm_participant_event_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Event_BAO_Participant', 'event_id', $val);
              $url = CRM_Report_Utils_Report::getNextUrl('event/income',
                'reset=1&force=1&id_op=in&id_value=' . $val,
                $this->_absoluteUrl, $this->_id
              );
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id_link'] = $url;
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id_hover'] = ts('View Event Income details for this Event.');
            }

            $val = $row['civicrm_participant_participant_status_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($val, FALSE);
            }
            $val = $row['civicrm_participant_role_id'] ?? NULL;
            if ($val) {
              $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $val);
              $value = [];
              foreach ($roles as $role) {
                $value[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
              }
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_role_id'] = implode(', ', $value);
            }

            $entryFound = TRUE;
          }

          if ($component === 'activity_civireport') {
            $val = $row['civicrm_activity_activity_type_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_activity_activity_type_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $val);
            }
            $val = $row['civicrm_activity_activity_status_id'] ?? NULL;
            if ($val) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_activity_activity_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_status_id', $val);
            }

            $entryFound = TRUE;
          }
          if ($component === 'membership_civireport') {
            $val = $row['civicrm_membership_membership_status_id'] ?? NULL;
            if ($val) {
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

  protected function getAvailableComponents(): array {
    return [
      'contribution_civireport',
      'membership_civireport',
      'participant_civireport',
      'relationship_civireport',
      'activity_civireport',
    ];
  }

  public function getFormComponent(): array {
    return $this->_formComponent ?? [];
  }

}
