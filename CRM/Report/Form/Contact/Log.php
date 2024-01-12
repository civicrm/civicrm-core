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
class CRM_Report_Form_Contact_Log extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $activityTypes = [];

  /**
   * Class constructor.
   */
  public function __construct() {

    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    asort($this->activityTypes);

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Modified By'),
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Modified By'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contact_touched' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name_touched' => [
            'title' => ts('Touched Contact'),
            'name' => 'sort_name',
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name_touched' => [
            'title' => ts('Touched Contact'),
            'name' => 'sort_name',
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'id' => [
            'title' => ts('Activity ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'subject' => [
            'title' => ts('Touched Activity'),
            'required' => TRUE,
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_activity_source' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => [
          'contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_log' => [
        'dao' => 'CRM_Core_DAO_Log',
        'fields' => [
          'modified_date' => [
            'title' => ts('Modified Date'),
            'required' => TRUE,
          ],
          'data' => [
            'title' => ts('Description'),
          ],
        ],
        'filters' => [
          'modified_date' => [
            'title' => ts('Modified Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
            'default' => 'this.week',
          ],
        ],
      ],
    ];

    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    return $errors;
  }

  public function from() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $this->_from = "
        FROM civicrm_log {$this->_aliases['civicrm_log']}
        inner join civicrm_contact {$this->_aliases['civicrm_contact']} on {$this->_aliases['civicrm_log']}.modified_id = {$this->_aliases['civicrm_contact']}.id
        left join civicrm_contact {$this->_aliases['civicrm_contact_touched']} on ({$this->_aliases['civicrm_log']}.entity_table='civicrm_contact' AND {$this->_aliases['civicrm_log']}.entity_id = {$this->_aliases['civicrm_contact_touched']}.id)
        left join civicrm_activity {$this->_aliases['civicrm_activity']} on ({$this->_aliases['civicrm_log']}.entity_table='civicrm_activity' AND {$this->_aliases['civicrm_log']}.entity_id = {$this->_aliases['civicrm_activity']}.id)
        LEFT JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_source']} ON
          {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_source']}.activity_id AND
          {$this->_aliases['civicrm_activity_source']}.record_type_id = {$sourceID}
        ";
  }

  public function where() {
    $clauses = [];
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (($field['operatorType'] ?? 0) & CRM_Report_Form::OP_DATE
          ) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
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

    $clauses[] = "({$this->_aliases['civicrm_log']}.entity_table <> 'civicrm_domain')";
    $this->_where = "WHERE " . implode(' AND ', $clauses);
  }

  public function orderBy() {
    $this->_orderBy = "
ORDER BY {$this->_aliases['civicrm_log']}.modified_date DESC, {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact_touched']}.sort_name
";
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
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_touched_sort_name_touched', $row) &&
        array_key_exists('civicrm_contact_touched_id', $row) &&
        $row['civicrm_contact_touched_sort_name_touched'] !== ''
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_touched_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_touched_sort_name_touched_link'] = $url;
        $rows[$rowNum]['civicrm_contact_touched_sort_name_touched_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_activity_subject', $row) &&
        array_key_exists('civicrm_activity_id', $row) &&
        $row['civicrm_activity_subject'] !== ''
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/activity',
          'reset=1&action=view&id=' . $row['civicrm_activity_id'] . '&cid=' .
          $row['civicrm_activity_source_contact_id'] . '&atype=' .
          $row['civicrm_activity_activity_type_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_activity_subject_link'] = $url;
        $rows[$rowNum]['civicrm_activity_subject_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {
          $rows[$rowNum]['civicrm_activity_activity_type_id'] = $this->activityTypes[$value];
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
