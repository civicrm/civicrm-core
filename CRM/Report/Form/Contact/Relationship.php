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
class CRM_Report_Form_Contact_Relationship extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_emailField_a = FALSE;
  protected $_emailField_b = FALSE;
  protected $_phoneField_a = FALSE;
  protected $_phoneField_b = FALSE;
  protected $_customGroupExtends = array(
    'Relationship',
  );
  public $_drilldownReport = array('contact/detail' => 'Link to Detail Report');

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

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name_a' => array(
            'title' => ts('Contact A'),
            'name' => 'sort_name',
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type_a' => array(
            'title' => ts('Contact Type (Contact A)'),
            'name' => 'contact_type',
          ),
          'contact_sub_type_a' => array(
            'title' => ts('Contact Subtype (Contact A)'),
            'name' => 'contact_sub_type',
          ),
        ),
        'filters' => array(
          'sort_name_a' => array(
            'title' => ts('Contact A'),
            'name' => 'sort_name',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'contact_type_a' => array(
            'title' => ts('Contact Type A'),
            'name' => 'contact_type',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contact_type,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'grouping' => 'contact_a_fields',
      ),
      'civicrm_contact_b' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'contact_b',
        'fields' => array(
          'sort_name_b' => array(
            'title' => ts('Contact B'),
            'name' => 'sort_name',
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type_b' => array(
            'title' => ts('Contact Type (Contact B)'),
            'name' => 'contact_type',
          ),
          'contact_sub_type_b' => array(
            'title' => ts('Contact Subtype (Contact B)'),
            'name' => 'contact_sub_type',
          ),
        ),
        'filters' => array(
          'sort_name_b' => array(
            'title' => ts('Contact B'),
            'name' => 'sort_name',
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ),
          'contact_type_b' => array(
            'title' => ts('Contact Type B'),
            'name' => 'contact_type',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contact_type,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'grouping' => 'contact_b_fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email_a' => array(
            'title' => ts('Email (Contact A)'),
            'name' => 'email',
          ),
        ),
        'grouping' => 'contact_a_fields',
      ),
      'civicrm_email_b' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'alias' => 'email_b',
        'fields' => array(
          'email_b' => array(
            'title' => ts('Email (Contact B)'),
            'name' => 'email',
          ),
        ),
        'grouping' => 'contact_b_fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone_a',
        'fields' => array(
          'phone_a' => array(
            'title' => ts('Phone (Contact A)'),
            'name' => 'phone',
          ),
          'phone_ext_a' => array(
            'title' => ts('Phone Ext (Contact A)'),
            'name' => 'phone_ext',
          ),
        ),
        'grouping' => 'contact_a_fields',
      ),
      'civicrm_phone_b' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone_b',
        'fields' => array(
          'phone_b' => array(
            'title' => ts('Phone (Contact B)'),
            'name' => 'phone',
          ),
          'phone_ext_b' => array(
            'title' => ts('Phone Ext (Contact B)'),
            'name' => 'phone_ext',
          ),
        ),
        'grouping' => 'contact_b_fields',
      ),
      'civicrm_relationship_type' => array(
        'dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' => array(
          'label_a_b' => array(
            'title' => ts('Relationship A-B '),
            'default' => TRUE,
          ),
          'label_b_a' => array(
            'title' => ts('Relationship B-A '),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'relation-fields',
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => array(
          'start_date' => array(
            'title' => ts('Relationship Start Date'),
          ),
          'end_date' => array(
            'title' => ts('Relationship End Date'),
          ),
          'description' => array(
            'title' => ts('Description'),
          ),
          'relationship_id' => array(
            'title' => ts('Rel ID'),
            'name' => 'id',
          ),
        ),
        'filters' => array(
          'is_active' => array(
            'title' => ts('Relationship Status'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(
              '' => '- Any -',
              1 => 'Active',
              0 => 'Inactive',
            ),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'is_valid' => array(
            'title' => ts('Relationship Dates Validity'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(
              NULL => ts('- Any -'),
              1 => ts('Not expired'),
              0 => ts('Expired'),
            ),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'relationship_type_id' => array(
            'title' => ts('Relationship'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, NULL, TRUE),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
        'grouping' => 'relation-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'filters' => array(
          'country_id' => array(
            'title' => ts('Country'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
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
    parent::preProcess();
  }

  public function select() {
    $select = $this->_columnHeaders = array();
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
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

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

    // include Email Field
    if ($this->_emailField_a) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                       ON ( {$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_email']}.contact_id AND
                            {$this->_aliases['civicrm_email']}.is_primary = 1 )";
    }
    if ($this->_emailField_b) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email_b']}
                       ON ( {$this->_aliases['civicrm_contact_b']}.id =
                            {$this->_aliases['civicrm_email_b']}.contact_id AND
                            {$this->_aliases['civicrm_email_b']}.is_primary = 1 )";
    }
    // include Phone Field
    if ($this->_phoneField_a) {
      $this->_from .= "
             LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                       ON ( {$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_phone']}.contact_id AND
                            {$this->_aliases['civicrm_phone']}.is_primary = 1 )";
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
    $whereClauses = $havingClauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {

          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              if (($tableName == 'civicrm_contact' ||
                  $tableName == 'civicrm_contact_b') &&
                ($fieldName == 'contact_type_a' ||
                  $fieldName == 'contact_type_b')
              ) {
                $cTypes = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                $contactTypes = $contactSubTypes = array();
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
                    CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                    CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                  );
                }

                if (!empty($contactSubTypes)) {
                  $field['name'] = 'contact_sub_type';
                  $field['dbAlias'] = $field['alias'] . '.' . $field['name'];
                  $subTypeClause = $this->whereClause($field,
                    $op,
                    $contactSubTypes,
                    CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                    CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
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
                    CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                    CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                    CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
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

    if (empty($whereClauses)) {
      $this->_where = 'WHERE ( 1 ) ';
      $this->_having = '';
    }
    else {
      $this->_where = 'WHERE ' . implode(' AND ', $whereClauses);
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
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $isStatusFilter = FALSE;
    $relStatus = NULL;
    if (CRM_Utils_Array::value('is_active_value', $this->_params) == '1') {
      $relStatus = 'Is equal to Active';
    }
    elseif (CRM_Utils_Array::value('is_active_value', $this->_params) == '0') {
      $relStatus = 'Is equal to Inactive';
    }
    if (!empty($statistics['filters'])) {
      foreach ($statistics['filters'] as $id => $value) {
        //for displaying relationship type filter
        if ($value['title'] == 'Relationship') {
          $relTypes = CRM_Core_PseudoConstant::relationshipType();
          $op = CRM_Utils_array::value('relationship_type_id_op', $this->_params) == 'in' ?
            ts('Is one of') . ' ' : ts('Is not one of') . ' ';
          $relationshipTypes = array();
          foreach ($this->_params['relationship_type_id_value'] as $relationship) {
            $relationshipTypes[] = $relTypes[$relationship]['label_' . $this->relationType];
          }
          $statistics['filters'][$id]['value'] = $op .
            implode(', ', $relationshipTypes);
        }

        //for displaying relationship status
        if ($value['title'] == 'Relationship Status') {
          $isStatusFilter = TRUE;
          $statistics['filters'][$id]['value'] = $relStatus;
        }
      }
    }
    //for displaying relationship status
    if (!$isStatusFilter && $relStatus) {
      $statistics['filters'][] = array(
        'title' => 'Relationship Status',
        'value' => $relStatus,
      );
    }
    return $statistics;
  }

  public function groupBy() {
    $this->_groupBy = " ";
    $groupBy = array();
    if ($this->relationType == 'a_b') {
      $groupBy[] = " {$this->_aliases['civicrm_contact']}.id";
    }
    elseif ($this->relationType == 'b_a') {
      $groupBy[] = " {$this->_aliases['civicrm_contact_b']}.id";
    }

    if (!empty($groupBy)) {
      $this->_groupBy = " GROUP BY  " . implode(', ', $groupBy) .
        " ,  {$this->_aliases['civicrm_relationship']}.id ";
    }
    else {
      $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_relationship']}.id ";
    }
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact_b']}.sort_name ";
  }

  public function postProcess() {
    $this->beginPostProcess();

    $originalRelationshipTypeIdValue = $this->_params['relationship_type_id_value'];
    if (!empty($this->_params['relationship_type_id_value'])) {
      $relationshipTypes = array();
      $direction = array();
      $relType = array();
      foreach ($this->_params['relationship_type_id_value'] as $relationship_type) {
        $relType = explode('_', $relationship_type);
        $direction[] = $relType[1] . '_' . $relType[2];
        $relationshipTypes[] = intval($relType[0]);
      }
      // Lets take the first relationship type to guide us in the relationship direction
      // we should use.
      $this->relationType = $direction[0];
      $this->_params['relationship_type_id_value'] = $relationshipTypes;
    }

    $this->buildACLClause(array(
      $this->_aliases['civicrm_contact'],
      $this->_aliases['civicrm_contact_b'],
    ));
    $sql = $this->buildQuery();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);

    if (!empty($originalRelationshipTypeIdValue)) {
      // store its old value, CRM-5837
      $this->_params['relationship_type_id_value'] = $originalRelationshipTypeIdValue;
    }
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  public function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

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
        $rows[$rowNum]['civicrm_contact_sort_name_a_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

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
        $rows[$rowNum]['civicrm_contact_b_sort_name_b_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

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

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * @param $valid bool - set to 1 if we are looking for a valid relationship, 0 if not
   *
   * @return array
   */
  public function buildValidityQuery($valid) {
    $clause = NULL;
    if ($valid == '1') {
      // relationships dates are not expired
      $clause = "((start_date <= CURDATE() OR start_date is null) AND (end_date >= CURDATE() OR end_date is null))";
    }
    elseif ($valid == '0') {
      // relationships dates are expired or has not started yet
      $clause = "(start_date >= CURDATE() OR end_date < CURDATE())";
    }
    return $clause;
  }

}
