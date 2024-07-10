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
class CRM_Report_Form_Contact_Relationship extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_emailField_a = FALSE;
  protected $_emailField_b = FALSE;
  protected $_phoneField_a = FALSE;
  protected $_phoneField_b = FALSE;
  protected $_customGroupExtends = [
    'Relationship',
  ];
  public $_drilldownReport = ['contact/detail' => 'Link to Detail Report'];

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
   * This will be a_b or b_a.
   *
   * @var string
   */
  protected $relationType;

  /**
   * Class constructor.
   */
  public function __construct() {

    $contact_type = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, '_');

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name_a' => [
            'title' => ts('Contact A'),
            'name' => 'sort_name',
            'required' => TRUE,
          ],
          'display_name_a' => [
            'title' => ts('Contact A Full Name'),
            'name' => 'display_name',
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contact_type_a' => [
            'title' => ts('Contact Type (Contact A)'),
            'name' => 'contact_type',
          ],
          'contact_sub_type_a' => [
            'title' => ts('Contact Subtype (Contact A)'),
            'name' => 'contact_sub_type',
          ],
        ],
        'filters' => [
          'sort_name_a' => [
            'title' => ts('Contact A'),
            'name' => 'sort_name',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'contact_type_a' => [
            'title' => ts('Contact Type A'),
            'name' => 'contact_type',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contact_type,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'order_bys' => [
          'sort_name_a' => [
            'title' => ts('Contact A'),
            'name' => 'sort_name',
            'default_weight' => '1',
          ],
        ],
        'grouping' => 'contact_a_fields',
      ],
      'civicrm_contact_b' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'contact_b',
        'fields' => [
          'sort_name_b' => [
            'title' => ts('Contact B'),
            'name' => 'sort_name',
            'required' => TRUE,
          ],
          'display_name_b' => [
            'title' => ts('Contact B Full Name'),
            'name' => 'display_name',
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contact_type_b' => [
            'title' => ts('Contact Type (Contact B)'),
            'name' => 'contact_type',
          ],
          'contact_sub_type_b' => [
            'title' => ts('Contact Subtype (Contact B)'),
            'name' => 'contact_sub_type',
          ],
        ],
        'filters' => [
          'sort_name_b' => [
            'title' => ts('Contact B'),
            'name' => 'sort_name',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'contact_type_b' => [
            'title' => ts('Contact Type B'),
            'name' => 'contact_type',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contact_type,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'order_bys' => [
          'sort_name_b' => [
            'title' => ts('Contact B'),
            'name' => 'sort_name',
            'default_weight' => '2',
          ],
        ],
        'grouping' => 'contact_b_fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email_a' => [
            'title' => ts('Email (Contact A)'),
            'name' => 'email',
          ],
        ],
        'grouping' => 'contact_a_fields',
      ],
      'civicrm_email_b' => [
        'dao' => 'CRM_Core_DAO_Email',
        'alias' => 'email_b',
        'fields' => [
          'email_b' => [
            'title' => ts('Email (Contact B)'),
            'name' => 'email',
          ],
        ],
        'grouping' => 'contact_b_fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone_a',
        'fields' => [
          'phone_a' => [
            'title' => ts('Phone (Contact A)'),
            'name' => 'phone',
          ],
          'phone_ext_a' => [
            'title' => ts('Phone Ext (Contact A)'),
            'name' => 'phone_ext',
          ],
        ],
        'grouping' => 'contact_a_fields',
      ],
      'civicrm_phone_b' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone_b',
        'fields' => [
          'phone_b' => [
            'title' => ts('Phone (Contact B)'),
            'name' => 'phone',
          ],
          'phone_ext_b' => [
            'title' => ts('Phone Ext (Contact B)'),
            'name' => 'phone_ext',
          ],
        ],
        'grouping' => 'contact_b_fields',
      ],
      'civicrm_relationship_type' => [
        'dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' => [
          'label_a_b' => [
            'title' => ts('Relationship A-B '),
            'default' => TRUE,
          ],
          'label_b_a' => [
            'title' => ts('Relationship B-A '),
            'default' => TRUE,
          ],
        ],
        'order_bys' => [
          'label_a_b' => [
            'title' => ts('Relationship A-B'),
            'name' => 'label_a_b',
          ],
          'label_b_A' => [
            'title' => ts('Relationship B-A'),
            'name' => 'label_b_a',
          ],
        ],
        'grouping' => 'relation-fields',
      ],
      'civicrm_relationship' => [
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => [
          'start_date' => [
            'title' => ts('Relationship Start Date'),
          ],
          'end_date' => [
            'title' => ts('Relationship End Date'),
          ],
          'is_permission_a_b' => [
            'title' => ts('Permission A has to access B'),
          ],
          'is_permission_b_a' => [
            'title' => ts('Permission B has to access A'),
          ],
          'description' => [
            'title' => ts('Description'),
          ],
          'is_active' => [
            'title' => ts('Is active?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
          'relationship_id' => [
            'title' => ts('Rel ID'),
            'name' => 'id',
          ],
        ],
        'filters' => [
          'is_active' => [
            'title' => ts('Relationship Status'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              '' => ts('- Any -'),
              1 => ts('Active'),
              0 => ts('Inactive'),
            ],
            'type' => CRM_Utils_Type::T_INT,
          ],
          'is_valid' => [
            'title' => ts('Relationship Dates Validity'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              NULL => ts('- Any -'),
              1 => ts('Not expired'),
              0 => ts('Expired'),
            ],
            'type' => CRM_Utils_Type::T_INT,
          ],
          'relationship_type_id' => [
            'title' => ts('Relationship'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'active_period_date' => [
            'title' => ts('Active Period'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'is_permission_a_b' => [
            'title' => ts('Does contact A have permission over contact B?'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Relationship::buildOptions('is_permission_a_b'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'is_permission_b_a' => [
            'title' => ts('Does contact B have permission over contact A?'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Relationship::buildOptions('is_permission_b_a'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],

        'order_bys' => [
          'start_date' => [
            'title' => ts('Start Date'),
            'name' => 'start_date',
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'name' => 'end_date',
          ],
        ],
        'grouping' => 'relation-fields',
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'filters' => [
          'country_id' => [
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ],
          'state_province_id' => [
            'title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            if ($fieldName == 'email_a') {
              $this->_emailField_a = TRUE;
            }
            if ($fieldName == 'email_b') {
              $this->_emailField_b = TRUE;
            }
            if ($fieldName == 'phone_a') {
              $this->_phoneField_a = TRUE;
            }
            if ($fieldName == 'phone_b') {
              $this->_phoneField_b = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->_from = "
        FROM civicrm_relationship {$this->_aliases['civicrm_relationship']}

             INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                        ON ( {$this->_aliases['civicrm_relationship']}.contact_id_a =
                             {$this->_aliases['civicrm_contact']}.id )

             INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact_b']}
                        ON ( {$this->_aliases['civicrm_relationship']}.contact_id_b =
                             {$this->_aliases['civicrm_contact_b']}.id )

             {$this->_aclFrom} ";

    if (!empty($this->_params['country_id_value']) ||
      !empty($this->_params['state_province_id_value'])
    ) {
      $this->_from .= "
            INNER  JOIN civicrm_address {$this->_aliases['civicrm_address']}
                         ON (( {$this->_aliases['civicrm_address']}.contact_id =
                               {$this->_aliases['civicrm_contact']}.id  OR
                               {$this->_aliases['civicrm_address']}.contact_id =
                               {$this->_aliases['civicrm_contact_b']}.id ) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";
    }

    $this->_from .= "
        INNER JOIN civicrm_relationship_type {$this->_aliases['civicrm_relationship_type']}
                        ON ( {$this->_aliases['civicrm_relationship']}.relationship_type_id  =
                             {$this->_aliases['civicrm_relationship_type']}.id  ) ";

    // Include Email Field.
    if ($this->_emailField_a) {
      $this->joinEmailFromContact();
    }
    if ($this->_emailField_b) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email_b']}
                       ON ( {$this->_aliases['civicrm_contact_b']}.id =
                            {$this->_aliases['civicrm_email_b']}.contact_id AND
                            {$this->_aliases['civicrm_email_b']}.is_primary = 1 )";
    }
    // Include Phone Field.
    if ($this->_phoneField_a) {
      $this->joinPhoneFromContact();
    }
    if ($this->_phoneField_b) {
      $this->_from .= "
             LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone_b']}
                       ON ( {$this->_aliases['civicrm_contact_b']}.id =
                            {$this->_aliases['civicrm_phone_b']}.contact_id AND
                            {$this->_aliases['civicrm_phone_b']}.is_primary = 1 )";
    }
  }

  public function where() {
    $whereClauses = $havingClauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {

          $clause = NULL;
          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            if ($fieldName == 'active_period_date') {
              $clause = $this->activeClause($field['name'], $relative, $from, $to, $field['type']);
            }
            else {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              if (($tableName == 'civicrm_contact' ||
                  $tableName == 'civicrm_contact_b') &&
                ($fieldName == 'contact_type_a' ||
                  $fieldName == 'contact_type_b')
              ) {
                $cTypes = $this->_params["{$fieldName}_value"] ?? NULL;
                $contactTypes = $contactSubTypes = [];
                if (!empty($cTypes)) {
                  foreach ($cTypes as $ctype) {
                    $getTypes = CRM_Utils_System::explode('_', $ctype, 2);
                    if ($getTypes[1] &&
                      !in_array($getTypes[1], $contactSubTypes)
                    ) {
                      $contactSubTypes[] = $getTypes[1];
                    }
                    elseif ($getTypes[0] &&
                      !in_array($getTypes[0], $contactTypes)
                    ) {
                      $contactTypes[] = $getTypes[0];
                    }
                  }
                }

                if (!empty($contactTypes)) {
                  $clause = $this->whereClause($field,
                    $op,
                    $contactTypes,
                    $this->_params["{$fieldName}_min"] ?? NULL,
                    $this->_params["{$fieldName}_max"] ?? NULL
                  );
                }

                if (!empty($contactSubTypes)) {
                  $field['name'] = 'contact_sub_type';
                  $field['dbAlias'] = $field['alias'] . '.' . $field['name'];
                  $subTypeClause = $this->whereClause($field,
                    $op,
                    $contactSubTypes,
                    $this->_params["{$fieldName}_min"] ?? NULL,
                    $this->_params["{$fieldName}_max"] ?? NULL
                  );
                  if ($clause) {
                    $clause = '(' . $clause . ' OR ' . $subTypeClause . ')';
                  }
                  else {
                    $clause = $subTypeClause;
                  }
                }
              }
              else {
                if ($fieldName == 'is_valid') {
                  $clause = $this->buildValidityQuery(CRM_Utils_Array::value("{$fieldName}_value", $this->_params));
                }
                else {
                  $clause = $this->whereClause($field,
                    $op,
                    $this->_params["{$fieldName}_value"] ?? NULL,
                    $this->_params["{$fieldName}_min"] ?? NULL,
                    $this->_params["{$fieldName}_max"] ?? NULL
                  );
                }
              }
            }
          }

          if (!empty($clause)) {
            if (!empty($field['having'])) {
              $havingClauses[] = $clause;
            }
            else {
              $whereClauses[] = $clause;
            }
          }
        }
      }
    }
    $this->_where = "WHERE ( {$this->_aliases['civicrm_contact']}.is_deleted = 0 AND {$this->_aliases['civicrm_contact_b']}.is_deleted = 0 ) ";
    if ($whereClauses) {
      $this->_where .= ' AND ' . implode(' AND ', $whereClauses);
    }
    else {
      $this->_having = '';
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = 'HAVING ' . implode(' AND ', $havingClauses);
    }
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $isStatusFilter = FALSE;
    $relStatus = NULL;
    if (($this->_params['is_active_value'] ?? NULL) == '1') {
      $relStatus = ts('Is equal to Active');
    }
    elseif (($this->_params['is_active_value'] ?? NULL) == '0') {
      $relStatus = ts('Is equal to Inactive');
    }
    if (!empty($statistics['filters'])) {
      foreach ($statistics['filters'] as $id => $value) {
        // For displaying relationship type filter.
        if ($value['title'] == 'Relationship') {
          $relTypes = CRM_Core_PseudoConstant::relationshipType();
          $op = ($this->_params['relationship_type_id_op'] ?? NULL) == 'in' ? ts('Is one of') . ' ' : ts('Is not one of') . ' ';
          $relationshipTypes = [];
          foreach ($this->_params['relationship_type_id_value'] as $relationship) {
            $relationshipTypes[] = $relTypes[$relationship]['label_' . $this->relationType];
          }
          $statistics['filters'][$id]['value'] = $op .
            implode(', ', $relationshipTypes);
        }

        // For displaying relationship status.
        if ($value['title'] == 'Relationship Status') {
          $isStatusFilter = TRUE;
          $statistics['filters'][$id]['value'] = $relStatus;
        }
      }
    }
    // For displaying relationship status.
    if (!$isStatusFilter && $relStatus) {
      $statistics['filters'][] = [
        'title' => ts('Relationship Status'),
        'value' => $relStatus,
      ];
    }
    return $statistics;
  }

  public function groupBy() {
    $this->_groupBy = " ";
    $groupBy = [];
    if ($this->relationType == 'a_b') {
      $groupBy[] = " {$this->_aliases['civicrm_contact']}.id";
    }
    elseif ($this->relationType == 'b_a') {
      $groupBy[] = " {$this->_aliases['civicrm_contact_b']}.id";
    }

    if (!empty($groupBy)) {
      $groupBy[] = "{$this->_aliases['civicrm_relationship']}.id";
    }
    else {
      $groupBy = ["{$this->_aliases['civicrm_relationship']}.id"];
    }

    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function beginPostProcessCommon() {
    $originalRelationshipTypeIdValue = $this->_params['relationship_type_id_value'] ?? NULL;
    if ($originalRelationshipTypeIdValue) {
      $relationshipTypes = [];
      $direction = [];
      foreach ((array) $originalRelationshipTypeIdValue as $relationship_type) {
        $relType = explode('_', $relationship_type);
        $direction[] = $relType[1] . '_' . $relType[2];
        $relationshipTypes[] = intval($relType[0]);
      }
      // Lets take the first relationship type to guide us in the relationship
      // direction we should use.
      $this->relationType = $direction[0];
      $this->_params['relationship_type_id_value'] = $relationshipTypes;
    }
  }

  public function postProcess() {
    $this->beginPostProcess();

    $this->buildACLClause([
      $this->_aliases['civicrm_contact'],
      $this->_aliases['civicrm_contact_b'],
    ]);

    $sql = $this->buildQuery();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);

    if (!empty($originalRelationshipTypeIdValue)) {
      // Store its old value, CRM-5837.
      $this->_params['relationship_type_id_value'] = $originalRelationshipTypeIdValue;
    }
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  public function alterDisplay(&$rows) {
    // Custom code to alter rows.
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {

      // Handle ID to label conversion for contact fields
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contact/relationship', 'View Relationships') ? TRUE : $entryFound;

      // Handle contact subtype A
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_contact_sub_type_a', $row)) {
        if ($value = $row['civicrm_contact_contact_sub_type_a']) {
          $rowValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $rowLabels = [];
          foreach ($rowValues as $rowValue) {
            if ($rowValue) {
              $rowLabels[] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', 'contact_sub_type', $rowValue);
            }
          }
          $rows[$rowNum]['civicrm_contact_contact_sub_type_a'] = implode(', ', $rowLabels);
        }
        $entryFound = TRUE;
      }

      // Handle contact subtype B
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_b_contact_sub_type_b', $row)) {
        if ($value = $row['civicrm_contact_b_contact_sub_type_b']) {
          $rowValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $rowLabels = [];
          foreach ($rowValues as $rowValue) {
            if ($rowValue) {
              $rowLabels[] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', 'contact_sub_type', $rowValue);
            }
          }
          $rows[$rowNum]['civicrm_contact_b_contact_sub_type_b'] = implode(', ', $rowLabels);
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      // Handle contact name A
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_sort_name_a', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_a']
          = $rows[$rowNum]['civicrm_contact_sort_name_a'] . ' (' .
          $rows[$rowNum]['civicrm_contact_id'] . ')';
        $rows[$rowNum]['civicrm_contact_sort_name_a_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_a_hover'] = ts('View Contact Detail Report for this contact');
        $entryFound = TRUE;
      }

      // Handle contact name B
      // @todo refactor into separate function
      if (array_key_exists('civicrm_contact_b_sort_name_b', $row) &&
        array_key_exists('civicrm_contact_b_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_b_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_b_sort_name_b']
          = $rows[$rowNum]['civicrm_contact_b_sort_name_b'] . ' (' .
          $rows[$rowNum]['civicrm_contact_b_id'] . ')';
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_link'] = $url;
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_hover'] = ts('View Contact Detail Report for this contact');
        $entryFound = TRUE;
      }

      // Handle relationship
      if (array_key_exists('civicrm_relationship_relationship_id', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = "/civicrm/contact/view/rel?reset=1&action=update&rtype=a_b&cid=" .
          $row['civicrm_contact_id'] . "&id=" .
          $row['civicrm_relationship_relationship_id'];
        $rows[$rowNum]['civicrm_relationship_relationship_id_link'] = $url;
        $rows[$rowNum]['civicrm_relationship_relationship_id_hover'] = ts("Edit this relationship.");
        $entryFound = TRUE;
      }

      // Handle permissioned relationships
      if (array_key_exists('civicrm_relationship_is_permission_a_b', $row)) {
        $rows[$rowNum]['civicrm_relationship_is_permission_a_b']
          = _ts(self::permissionedRelationship($row['civicrm_relationship_is_permission_a_b']));
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_relationship_is_permission_b_a', $row)) {
        $rows[$rowNum]['civicrm_relationship_is_permission_b_a']
          = _ts(self::permissionedRelationship($row['civicrm_relationship_is_permission_b_a']));
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
   * Convert values to permissioned relationship descriptions
   * @param int[] $key
   * @return string[]
   */
  public static function permissionedRelationship($key) {
    static $lookup;
    if (!$lookup) {
      $lookup = CRM_Contact_BAO_Relationship::buildOptions("is_permission_a_b");
    };
    return $lookup[$key] ?? NULL;
  }

  /**
   * @param $valid bool - set to 1 if we are looking for a valid relationship, 0 if not
   *
   * @return array
   */
  public function buildValidityQuery($valid) {
    $clause = NULL;
    if ($valid == '1') {
      // Relationships dates are not expired.
      $clause = "((start_date <= CURDATE() OR start_date is null) AND (end_date >= CURDATE() OR end_date is null))";
    }
    elseif ($valid == '0') {
      // Relationships dates are expired or has not started yet.
      $clause = "(start_date >= CURDATE() OR end_date < CURDATE())";
    }
    return $clause;
  }

  /**
   * Get SQL where clause for a active period field.
   *
   * @param string $fieldName
   * @param string $relative
   * @param string $from
   * @param string $to
   * @param string $type
   *
   * @return null|string
   */
  public function activeClause(
    $fieldName,
    $relative, $from, $to, $type = NULL) {
    $clauses = [];
    if (array_key_exists($relative, $this->getOperationPair(CRM_Report_Form::OP_DATE))) {
      return NULL;
    }

    list($from, $to) = $this->getFromTo($relative, $from, $to);

    if ($from) {
      $from = ($type == CRM_Utils_Type::T_DATE) ? substr($from, 0, 8) : $from;
    }

    if ($to) {
      $to = ($type == CRM_Utils_Type::T_DATE) ? substr($to, 0, 8) : $to;
    }

    if ($from || $to) {
      return CRM_Contact_BAO_Query::getRelationshipActivePeriodClauses($from, $to, FALSE);
    }
    return NULL;
  }

}
