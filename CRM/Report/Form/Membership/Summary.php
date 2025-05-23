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
class CRM_Report_Form_Membership_Summary extends CRM_Report_Form {

  protected $_summary = NULL;

  /**
   * Constructor function.
   */
  public function __construct() {
    // UI for selecting columns to appear in the report list
    // array containing the columns, group_bys and filters build and provided to Form
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Member Name'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'group_bys' => [
          'id' => ['title' => ts('Contact ID')],
          'display_name' => [
            'title' => ts('Contact Name'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_membership_type' => [
        'dao' => 'CRM_Member_DAO_MembershipType',
        'grouping' => 'member-fields',
        'filters' => [
          'gid' => [
            'name' => 'id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT + CRM_Utils_Type::T_ENUM,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ],
        ],
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'grouping' => 'member-fields',
        'fields' => [
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'required' => TRUE,
          ],
          'join_date' => NULL,
          'start_date' => [
            'title' => ts('Current Cycle Start Date'),
          ],
          'end_date' => [
            'title' => ts('Current Cycle End Date'),
          ],
        ],
        'group_bys' => [
          'membership_type_id' => ['title' => ts('Membership Type')],
        ],
        'filters' => [
          'membership_join_date' => ['type' => CRM_Utils_Type::T_DATE],
        ],
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => [
            'title' => ts('State/Province'),
          ],
          'country_id' => [
            'title' => ts('Country'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'filters' => [
          'total_amount' => [
            'title' => ts('Contribution Amount'),
          ],
        ],
      ],
    ];

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
      'pieChart' => ts('Pie Chart'),
    ];

    parent::__construct();
  }

  /**
   * Pre-process function.
   */
  public function preProcess() {
    $this->assign('reportTitle', ts('Membership Summary Report'));
    parent::preProcess();
  }

  /**
   * Generate select clause.
   */
  public function select() {
    // @todo remove this in favour of just using parent.
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * Generate from clause.
   */
  public function from() {
    $this->_from = NULL;

    $this->_from = "
FROM       civicrm_contact    {$this->_aliases['civicrm_contact']}
INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
       ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_membership']}.contact_id
LEFT  JOIN civicrm_membership_type  {$this->_aliases['civicrm_membership_type']}
       ON {$this->_aliases['civicrm_membership']}.membership_type_id = {$this->_aliases['civicrm_membership_type']}.id
LEFT  JOIN civicrm_contribution  {$this->_aliases['civicrm_contribution']}
       ON {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases['civicrm_contribution']}.contact_id
";
    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
  }

  /**
   * Generate where clause.
   *
   * @todo this looks like it duplicates the parent & could go.
   */
  public function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($field['type'] & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to);
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                $this->_params["{$fieldName}_value"] ?? NULL,
                $this->_params["{$fieldName}_min"] ?? NULL,
                $this->_params["{$fieldName}_max"] ?? NULL
              );
            }
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
  }

  /**
   * Generate statistics (bottom section of the report).
   *
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = [];
    $statistics[] = [
      'title' => ts('Row(s) Listed'),
      'value' => count($rows),
    ];
    return $statistics;
  }

  /**
   * Generate group by clause.
   *
   * @todo looks like a broken duplicate of the parent.
   */
  public function groupBy() {
    $this->_groupBy = "";
    if (is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              $this->_groupBy[] = $field['dbAlias'];
            }
          }
        }
      }

      if (!empty($this->_statFields) &&
        (($append && count($this->_groupBy) <= 1) || (!$append))
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy) .
        " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY contact.id";
    }
  }

  /**
   * PostProcess function.
   */
  public function postProcess() {
    $this->_params = $this->controller->exportValues($this->_name);
    if (empty($this->_params) &&
      $this->_force
    ) {
      $this->_params = $this->_formValues;
    }
    $this->_formValues = $this->_params;

    $this->processReportMode();

    $this->select();

    $this->from();

    $this->where();

    $this->groupBy();

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy}";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = $graphRows = [];
    $count = 0;
    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_columnHeaders as $key => $value) {
        $row[$key] = $dao->$key;
      }

      if (!empty($this->_params['charts']) &&
        $row['civicrm_contribution_receive_date_subtotal']
      ) {
        $graphRows['receive_date'][] = $row['civicrm_contribution_receive_date_start'];
        $graphRows[$this->_interval][] = $row['civicrm_contribution_receive_date_interval'];
        $graphRows['value'][] = $row['civicrm_contribution_total_amount_sum'];
        $count++;
      }

      $rows[] = $row;
    }
    $this->formatDisplay($rows);

    $this->assign('columnHeaders', $this->_columnHeaders);
    $this->assign('rows', $rows);
    $this->assign('statistics', $this->statistics($rows));

    if (!empty($this->_params['charts'])) {
      foreach (['receive_date', $this->_interval, 'value'] as $ignore) {
        unset($graphRows[$ignore][$count - 1]);
      }

      // build chart.
      CRM_Utils_Chart::chart($graphRows, $this->_params['charts'], $this->_interval);
    }
    parent::endPostProcess();
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
    $checkList = [];

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats)) {
        // not repeat contact display names if it matches with the one
        // in previous row

        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      //handle the Membership Type Ids
      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url(
          'civicrm/report/member/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name']
          = "<a href='$url'>" . $row["civicrm_contact_sort_name"] . '</a>';
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;
      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
