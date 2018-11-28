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
class CRM_Report_Form_Walklist_Walklist extends CRM_Report_Form {

  protected $_summary = NULL;

  public $_drilldownReport = array('contact/detail' => 'Link to Detail Report');

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
  );

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'operator' => 'like',
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array('email' => array('default' => TRUE)),
        'grouping' => 'location-fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array('phone' => NULL),
        'grouping' => 'location-fields',
      ),
    ) + $this->getAddressColumns(array('group_bys' => FALSE));
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    // @todo remove this function & use parent.
    $select = array();

    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      foreach ($table['fields'] as $fieldName => $field) {
        if (!empty($field['required']) ||
          !empty($this->_params['fields'][$fieldName])
        ) {

          $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
          $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
        }
      }
    }

    $this->_select = "SELECT " . implode(",\n", $select) . " ";
  }

  public function from() {
    $this->_from = NULL;

    $this->_from = "
FROM       civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();
  }

  public function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if ($field['type'] & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

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
  }

  public function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
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
      // handle state province
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value);
        }
        $entryFound = TRUE;
      }

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
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
