<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Report_Form_Contribute_OrganizationSummary extends CRM_Report_Form {

  public $_drilldownReport = ['contribute/detail' => 'Link to Detail Report'];

  protected $_summary = NULL;

  /**
   * Organisation contact ie. 'contact_id_b' or 'contact_id_a'
   * @var string
   */
  protected $orgContact;

  /**
   * Related Contact ie. 'contact_id_b' or 'contact_id_a'
   * @var string
   */
  protected $otherContact;

  /**
   * Class constructor.
   */
  public function __construct() {
    self::validRelationships();

    $this->_columns = [
      'civicrm_contact_organization' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'organization_name' => [
            'title' => ts('Organization Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
        'filters' => [
          'organization_name' => [
            'title' => ts('Organization Name'),
          ],
          'is_deleted' => [
            'default' => 0,
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
        ],
        'grouping' => 'organization-fields',
      ],
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
      ],
      'civicrm_relationship' => [
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => [
          'relationship_type_id' => [
            'title' => ts('Relationship Type'),
          ],
        ],
        'filters' => [
          'relationship_type_id' => [
            'title' => ts('Relationship Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->relationTypes,
            'default' => key($this->relationTypes),
          ],
        ],
        'grouping' => 'organization-fields',
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'total_amount' => [
            'title' => ts('Amount'),
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'default' => TRUE,
          ],
          'check_number' => [
            'title' => ts('Check Number'),
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'trxn_id' => NULL,
          'receive_date' => ['default' => TRUE],
          'receipt_date' => NULL,
        ],
        'filters' => [
          'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'total_amount' => ['title' => ts('Amount Between')],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
        ],
        'grouping' => 'contri-fields',
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
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_financial_trxn' => [
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => [
          'card_type_id' => [
            'title' => ts('Credit Card Type'),
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_civireport.card_type_id SEPARATOR ",")',
          ],
        ],
        'filters' => [
          'card_type_id' => [
            'title' => ts('Credit Card Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
    ];

    // If we have a campaign, build out the relevant elements
    $this->addCampaignFields('civicrm_contribution');

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    // @todo remove this in favour of using parent function
    $this->_columnHeaders = $select = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {

            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}_{$stat}";
                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";

              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {

    $this->_from = NULL;
    $this->_from = "
        FROM  civicrm_relationship  {$this->_aliases['civicrm_relationship']}
            LEFT  JOIN civicrm_contact {$this->_aliases['civicrm_contact_organization']} ON
                      ({$this->_aliases['civicrm_contact_organization']}.id = {$this->_aliases['civicrm_relationship']}.$this->orgContact AND {$this->_aliases['civicrm_contact_organization']}.contact_type='Organization')
            LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON
                      ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_relationship']}.$this->otherContact )
            {$this->_aclFrom}
            INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} ON
                      ({$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_relationship']}.$this->otherContact ) AND {$this->_aliases['civicrm_contribution']}.is_test = 0  ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function where() {
    $clauses = [];
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
              if ($fieldName == 'relationship_type_id') {
                $clause = "{$this->_aliases['civicrm_relationship']}.relationship_type_id=" . $this->relationshipId;
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

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 )";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  public function groupBy() {
    $groupBy = [
      "{$this->_aliases['civicrm_relationship']}.$this->orgContact",
      "{$this->_aliases['civicrm_relationship']}.$this->otherContact",
      "{$this->_aliases['civicrm_contribution']}.id",
      "{$this->_aliases['civicrm_relationship']}.relationship_type_id",
    ];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact_organization']}.organization_name, {$this->_aliases['civicrm_relationship']}.$this->orgContact, {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_relationship']}.$this->otherContact";
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    //hack filter display for relationship type
    $type = substr($this->_params['relationship_type_id_value'], -3);
    foreach ($statistics['filters'] as $id => $value) {
      if ($value['title'] == 'Relationship Type' && !empty($id)) {
        $statistics['filters'][$id]['value'] = 'Is equal to ' .
          $this->relationTypes[$this->relationshipId . '_' . $type];
      }
    }
    return $statistics;
  }

  public function postProcess() {
    $this->beginPostProcess();
    $this->buildACLClause([$this->_aliases['civicrm_contact'], $this->_aliases['civicrm_contact_organization']]);
    $sql = $this->buildQuery(TRUE);
    $rows = [];
    $this->buildRows($sql, $rows);
    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * Set variables to be accessed by API and form layer in processing.
   */
  public function beginPostProcessCommon() {
    $getRelationship = $this->_params['relationship_type_id_value'];
    $type = substr($getRelationship, -3);
    $this->relationshipId = intval((substr($getRelationship, 0, strpos($getRelationship, '_'))));
    if ($type == 'b_a') {
      $this->orgContact = 'contact_id_b';
      $this->otherContact = 'contact_id_a';
    }
    else {
      $this->orgContact = 'contact_id_a';
      $this->otherContact = 'contact_id_b';
    }
  }

  public function validRelationships() {
    $this->relationTypes = $relationTypes = [];

    $params = ['contact_type_b' => 'Organization', 'version' => 3];
    $typesA = civicrm_api('relationship_type', 'get', $params);

    if (empty($typesA['is_error'])) {
      foreach ($typesA['values'] as $rel) {
        $relationTypes[$rel['id']][$rel['id'] . '_b_a'] = $rel['label_b_a'];
      }
    }

    $params = ['contact_type_a' => 'Organization', 'version' => 3];
    $typesB = civicrm_api('relationship_type', 'get', $params);

    if (empty($typesB['is_error'])) {
      foreach ($typesB['values'] as $rel) {
        $relationTypes[$rel['id']][$rel['id'] . '_a_b'] = $rel['label_a_b'];
      }
    }

    ksort($relationTypes);
    foreach ($relationTypes as $relationship) {
      foreach ($relationship as $index => $label) {
        $this->relationTypes[$index] = $label;
      }
    }
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
    $type = substr($this->_params['relationship_type_id_value'], -3);

    $entryFound = FALSE;
    $flagOrganization = $flagContact = 0;

    foreach ($rows as $rowNum => $row) {

      //replace retionship id by relationship name
      if (array_key_exists('civicrm_relationship_relationship_type_id', $row)) {
        if ($value = $row['civicrm_relationship_relationship_type_id']) {
          $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = $this->relationTypes[$value . '_' . $type];
          $entryFound = TRUE;
        }
      }

      //remove duplicate Organization names
      if (array_key_exists('civicrm_contact_organization_id', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_organization_id']) {
          if ($rowNum == 0) {
            $previousOrganization = $value;
          }
          else {
            if ($previousOrganization == $value) {
              $flagOrganization = 1;
              $previousOrganization = $value;
            }
            else {
              $flagOrganization = 0;
              $previousOrganization = $value;
            }
          }

          if ($flagOrganization == 1) {
            foreach ($row as $colName => $colVal) {
              if (in_array($colName, $this->_noRepeats)) {
                unset($rows[$rowNum][$colName]);
              }
            }
          }
          $entryFound = TRUE;
        }
      }

      // convert Organization display name to links
      if (array_key_exists('civicrm_contact_organization_organization_name', $row) && !empty($rows[$rowNum]['civicrm_contact_organization_organization_name']) &&
        array_key_exists('civicrm_contact_organization_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' .
          $rows[$rowNum]['civicrm_contact_organization_id'],
          $this->_absoluteUrl
        );

        $rows[$rowNum]['civicrm_contact_organization_organization_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_organization_organization_name_hover'] = ts('View contact summary for this organization.');
      }

      //remove duplicate Contact names and relationship type
      if (array_key_exists('civicrm_contact_id', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_id']) {
          if ($rowNum == 0) {
            $previousContact = $value;
          }
          else {
            if ($previousContact == $value) {
              $flagContact = 1;
              $previousContact = $value;
            }
            else {
              $flagContact = 0;
              $previousContact = $value;
            }
          }

          if ($flagContact == 1 && $flagOrganization == 1) {
            $rows[$rowNum]['civicrm_contact_sort_name'] = "";
            $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = "";
          }

          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_contribution_contribution_status_id', $row)) {
        if ($value = $row['civicrm_contribution_contribution_status_id']) {
          $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($value);
        }
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // convert Individual display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View contribution details for this individual');

        $entryFound = TRUE;
      }

      // Contribution amount links to view contribution
      if (($value = CRM_Utils_Array::value('civicrm_contribution_total_amount', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
          "reset=1&id=" . $row['civicrm_contribution_id'] . "&cid=" . $row['civicrm_contact_id'] .
          "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_total_amount_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_total_amount_hover'] = ts('View this contribution.');
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;
      // skip looking further in rows, if first row itself doesn't
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }

}
