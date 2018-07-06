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
class CRM_Report_Form_Contribute_HouseholdSummary extends CRM_Report_Form {

  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  protected $_summary = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    self::validRelationships();

    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
    }

    $this->_columns = array(
      'civicrm_contact_household' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'household_name' => array(
            'title' => ts('Household Name'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
        'filters' => array(
          'household_name' => array(
            'title' => ts('Household Name'),
          ),
          'is_deleted' => array(
            'default' => 0,
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ),
        ),
        'grouping' => 'household-fields',
      ),
      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => array(
          'relationship_type_id' => array(
            'title' => ts('Relationship Type'),
          ),
        ),
        'filters' => array(
          'relationship_type_id' => array(
            'title' => ts('Relationship Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->relationTypes,
            'default' => key($this->relationTypes),
          ),
        ),
        'grouping' => 'household-fields',
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'total_amount' => array(
            'title' => ts('Amount'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'default' => TRUE,
          ),
          'check_number' => array(
            'title' => ts('Check Number'),
          ),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
          ),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
        ),
        'filters' => array(
          'receive_date' => array('operatorType' => CRM_Report_Form::OP_DATE),
          'total_amount' => array('title' => ts('Amount Between')),
          'currency' => array(
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'card_type_id' => array(
            'title' => ts('Credit Card Type'),
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_civireport.card_type_id SEPARATOR ",")',
          ),
        ),
        'filters' => array(
          'card_type_id' => array(
            'title' => ts('Credit Card Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array(
            'title' => ts('State/Province'),
          ),
          'country_id' => array(
            'title' => ts('Country'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => NULL,
        ),
        'grouping' => 'contact-fields',
      ),
    );

    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_contribution']['fields']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'default' => 'false',
      );
      $this->_columns['civicrm_contribution']['filters']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->activeCampaigns,
        'type' => CRM_Utils_Type::T_INT,
      );
    }
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function select() {
    // @todo remove this & use parent select.
    $this->_columnHeaders = $select = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

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
    $this->_from = "
        FROM  civicrm_relationship {$this->_aliases['civicrm_relationship']}
            LEFT  JOIN civicrm_contact {$this->_aliases['civicrm_contact_household']} ON
                      ({$this->_aliases['civicrm_contact_household']}.id = {$this->_aliases['civicrm_relationship']}.$this->householdContact AND {$this->_aliases['civicrm_contact_household']}.contact_type='Household')
            LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON
                      ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_relationship']}.$this->otherContact )
            {$this->_aclFrom}
            INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} ON
                      ({$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_relationship']}.$this->otherContact ) AND {$this->_aliases['civicrm_contribution']}.is_test = 0 ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function where() {
    $clauses = array();
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
    $groupBy = array(
      "{$this->_aliases['civicrm_relationship']}.$this->householdContact",
      "{$this->_aliases['civicrm_relationship']}.$this->otherContact",
      "{$this->_aliases['civicrm_contribution']}.id",
      "{$this->_aliases['civicrm_relationship']}.relationship_type_id",
    );
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact_household']}.household_name, {$this->_aliases['civicrm_relationship']}.$this->householdContact, {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_relationship']}.$this->otherContact";
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
      if (
        $value['title'] == 'Relationship Type' &&
        isset($this->relationTypes[$this->relationshipId . '_' . $type])
      ) {
        $statistics['filters'][$id]['value'] = 'Is equal to ' .
          $this->relationTypes[$this->relationshipId . '_' . $type];
      }
    }
    return $statistics;
  }

  public function postProcess() {

    $this->beginPostProcess();
    $this->buildACLClause(array(
      $this->_aliases['civicrm_contact'],
      $this->_aliases['civicrm_contact_household'],
    ));
    $sql = $this->buildQuery(TRUE);
    $rows = array();

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
      $this->householdContact = 'contact_id_b';
      $this->otherContact = 'contact_id_a';
    }
    else {
      $this->householdContact = 'contact_id_a';
      $this->otherContact = 'contact_id_b';
    }
  }

  public function validRelationships() {
    $this->relationTypes = $relationTypes = array();

    $params = array('contact_type_b' => 'Household', 'version' => 3);
    $typesA = civicrm_api('relationship_type', 'get', $params);
    if (empty($typesA['is_error'])) {
      foreach ($typesA['values'] as $rel) {
        $relationTypes[$rel['id']][$rel['id'] . '_b_a'] = $rel['label_b_a'];
      }
    }
    $params = array('contact_type_a' => 'Household', 'version' => 3);
    $typesB = civicrm_api('relationship_type', 'get', $params);
    if (empty($typesB['is_error'])) {
      foreach ($typesB['values'] as $rel) {
        $relationTypes[$rel['id']][$rel['id'] . '_a_b'] = $rel['label_a_b'];
        //$this->relationTypes[$rel['id'].'_a_b'] = $rel['label_a_b'];
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
    $flagHousehold = $flagContact = 0;
    foreach ($rows as $rowNum => $row) {

      //replace retionship id by relationship name
      if (array_key_exists('civicrm_relationship_relationship_type_id', $row)) {
        if ($value = $row['civicrm_relationship_relationship_type_id']) {
          $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = $this->relationTypes[$value . '_' . $type];
          $entryFound = TRUE;
        }
      }

      //remove duplicate Organization names
      if (array_key_exists('civicrm_contact_household_household_name', $row) &&
        $this->_outputMode != 'csv'
      ) {
        if ($value = $row['civicrm_contact_household_household_name']) {
          if ($rowNum == 0) {
            $priviousHousehold = $value;
          }
          else {
            if ($priviousHousehold == $value) {
              $flagHousehold = 1;
              $priviousHousehold = $value;
            }
            else {
              $flagHousehold = 0;
              $priviousHousehold = $value;
            }
          }

          if ($flagHousehold == 1) {
            $rows[$rowNum]['civicrm_contact_household_household_name'] = "";
          }
          else {
            $url = CRM_Utils_System::url('civicrm/contact/view',
              'reset=1&cid=' . $rows[$rowNum]['civicrm_contact_household_id'],
              $this->_absoluteUrl
            );

            $rows[$rowNum]['civicrm_contact_household_household_name'] = "<a href='$url' title='" . ts('View contact summary for this househould') . "'>" . $value . '</a>';
          }
          $entryFound = TRUE;
        }
      }

      //remove duplicate Contact names and relationship type
      if (array_key_exists('civicrm_contact_id', $row) &&
        $this->_outputMode != 'csv'
      ) {
        if ($value = $row['civicrm_contact_id']) {
          if ($rowNum == 0) {
            $priviousContact = $value;
          }
          else {
            if ($priviousContact == $value) {
              $flagContact = 1;
              $priviousContact = $value;
            }
            else {
              $flagContact = 0;
              $priviousContact = $value;
            }
          }

          if ($flagContact == 1 && $flagHousehold == 1) {
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

      if (array_key_exists('civicrm_contribution_financial_type_id', $row)) {
        if ($value = $row['civicrm_contribution_financial_type_id']) {
          $rows[$rowNum]['civicrm_contribution_financial_type_id'] = CRM_Contribute_PseudoConstant::financialType($value);
        }
      }

      // handle state province
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View contribution details for this individual');

        $entryFound = TRUE;
      }

      if (!empty($row['civicrm_contribution_total_amount'])) {
        $row['civicrm_contribution_total_amount'] = CRM_Utils_Money::format($row['civicrm_contribution_total_amount'], $row['civicrm_contribution_currency']);
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // Contribution amount links to view contribution
      if (($value = CRM_Utils_Array::value('civicrm_contribution_total_amount', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
          "reset=1&id=" . $row['civicrm_contribution_id'] . "&cid=" .
          $row['civicrm_contact_id'] .
          "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_total_amount_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_total_amount_hover'] = ts("View this contribution.");
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      // skip looking further in rows, if first row itself doesn't
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }

}
