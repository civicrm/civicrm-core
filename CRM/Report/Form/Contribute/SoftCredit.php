<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_SoftCredit extends CRM_Report_Form {

  protected $_emailField = FALSE;
  protected $_emailFieldCredit = FALSE;
  protected $_phoneField = FALSE;
  protected $_phoneFieldCredit = FALSE;
  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  /**
   *
   */
  /**
   *
   */
  function __construct() {

    // Check if CiviCampaign is a) enabled and b) has active campaigns
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
    }

    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'display_name_creditor' =>
          array('title' => ts('Soft Credit Name'),
            'name' => 'sort_name',
            'alias' => 'contact_civireport',
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id_creditor' =>
          array('title' => ts('Soft Credit Id'),
            'name' => 'id',
            'alias' => 'contact_civireport',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'display_name_constituent' =>
          array('title' => ts('Contributor Name'),
            'name' => 'sort_name',
            'alias' => 'constituentname',
            'required' => TRUE,
          ),
          'id_constituent' =>
          array('title' => ts('Const Id'),
            'name' => 'id',
            'alias' => 'constituentname',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' =>
          array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' =>
          array(
            'title' => ts('Contact SubType'),
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array(
            'name' => 'sort_name',
            'title' => ts('Soft Credit Name')
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email_creditor' =>
          array('title' => ts('Soft Credit Email'),
            'name' => 'email',
            'alias' => 'emailcredit',
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'email_constituent' =>
          array('title' => ts('Contributor\'s Email'),
            'name' => 'email',
            'alias' => 'emailconst',
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone_creditor' =>
          array('title' => ts('Soft Credit Phone'),
            'name' => 'phone',
            'alias' => 'pcredit',
            'default' => TRUE,
          ),
          'phone_constituent' =>
          array('title' => ts('Contributor\'s Phone'),
            'name' => 'phone',
            'alias' => 'pconst',
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_financial_type' =>
      array('dao' => 'CRM_Financial_DAO_FinancialType',
        'fields' => array('financial_type' => null,),
        'filters' =>
        array(
          'id' =>
          array(
            'name' => 'id',
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType()
          ),
        ),
        'grouping' => 'softcredit-fields',
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_source' => NULL,
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'total_amount' =>
          array('title' => ts('Amount Statistics'),
            'default' => TRUE,
            'statistics' =>
            array('sum' => ts('Aggregate Amount'),
              'count' => ts('Donations'),
              'avg' => ts('Average'),
            ),
          ),
        ),
        'grouping' => 'softcredit-fields',
        'filters' =>
        array(
          'receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'contribution_status_id' =>
          array('title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
          'total_amount' =>
          array('title' => ts('Donation Amount'),
          ),
        ),
      ),
      'civicrm_contribution_soft' =>
      array(
        'dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' =>
        array(
          'contribution_id' =>
          array('title' => ts('Contribution ID'),
            'no_display' => TRUE,
            'default' => TRUE,
          ),
          'id' =>
          array(
            'default' => TRUE,
            'no_display' => TRUE,
          ),
          'soft_credit_type_id' => array('title' => ts('Soft Credit Type')),
        ),
        'filters' =>
        array(
          'soft_credit_type_id' =>
          array('title' => 'Soft Credit Type',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('soft_credit_type'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'grouping' => 'softcredit-fields',
      ),
    );

    // If we have a campaign, build out the relevant elements
    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_contribution']['fields']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'default' => 'false',
      );
      $this->_columns['civicrm_contribution']['filters']['campaign_id'] = array('title' => ts('Campaign'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->activeCampaigns,
      );
    }

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {

            // include email column if set
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
              $this->_emailFieldCredit = TRUE;
            }
            elseif ($tableName == 'civicrm_email_creditor') {
              $this->_emailFieldCredit = TRUE;
            }

            // include phone columns if set
            if ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
              $this->_phoneFieldCredit = TRUE;
            }
            elseif ($tableName == 'civicrm_phone_creditor') {
              $this->_phoneFieldCredit = TRUE;
            }

            // only include statistics columns if set
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
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

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  function from() {
    $alias_constituent = 'constituentname';
    $alias_creditor    = 'contact_civireport';
    $this->_from       = "
        FROM  civicrm_contribution {$this->_aliases['civicrm_contribution']}
              INNER JOIN civicrm_contribution_soft {$this->_aliases['civicrm_contribution_soft']}
                         ON {$this->_aliases['civicrm_contribution_soft']}.contribution_id =
                            {$this->_aliases['civicrm_contribution']}.id
              INNER JOIN civicrm_contact {$alias_constituent}
                         ON {$this->_aliases['civicrm_contribution']}.contact_id =
                            {$alias_constituent}.id
              LEFT  JOIN civicrm_financial_type  {$this->_aliases['civicrm_financial_type']}
                         ON {$this->_aliases['civicrm_contribution']}.financial_type_id =
                            {$this->_aliases['civicrm_financial_type']}.id
              LEFT  JOIN civicrm_contact {$alias_creditor}
                         ON {$this->_aliases['civicrm_contribution_soft']}.contact_id =
                            {$alias_creditor}.id
              {$this->_aclFrom} ";

    // include Constituent email field if email column is to be included
    if ($this->_emailField) {
      $alias = 'emailconst';
      $this->_from .= "
            LEFT JOIN civicrm_email {$alias}
                      ON {$alias_constituent}.id =
                         {$alias}.contact_id   AND
                         {$alias}.is_primary = 1\n";
    }

    // include  Creditors email field if email column is to be included
    if ($this->_emailFieldCredit) {
      $alias = 'emailcredit';
      $this->_from .= "
            LEFT JOIN civicrm_email {$alias}
                      ON {$alias_creditor}.id =
                         {$alias}.contact_id  AND
                         {$alias}.is_primary = 1\n";
    }

    // include  Constituents phone field if email column is to be included
    if ($this->_phoneField) {
      $alias = 'pconst';
      $this->_from .= "
            LEFT JOIN civicrm_phone {$alias}
                      ON {$alias_constituent}.id =
                         {$alias}.contact_id  AND
                         {$alias}.is_primary = 1\n";
    }

    // include  Creditors phone field if email column is to be included
    if ($this->_phoneFieldCredit) {
      $alias = 'pcredit';
      $this->_from .= "
            LEFT JOIN civicrm_phone pcredit
                      ON {$alias_creditor}.id =
                         {$alias}.contact_id  AND
                         {$alias}.is_primary = 1\n";
    }
  }

  function groupBy() {
    $this->_rollup     = 'WITH ROLLUP';
    $this->_groupBy    = "
GROUP BY {$this->_aliases['civicrm_contribution_soft']}.contact_id, constituentname.id {$this->_rollup}";
  }

  function where() {
    parent::where();
    $this->_where .= " AND {$this->_aliases['civicrm_contribution']}.is_test = 0 ";
  }

  /**
   * @param $rows
   *
   * @return array
   */
  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
               SUM({$this->_aliases['civicrm_contribution']}.total_amount ) as amount,
               ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg,
               {$this->_aliases['civicrm_contribution']}.currency as currency
        ";

    $sql = "{$select} {$this->_from} {$this->_where}
GROUP BY   {$this->_aliases['civicrm_contribution']}.currency
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $count = 0;
    $totalAmount = $average = array();
    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency).'('.$dao->count.')';
      $average[] =   CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
    }
    $statistics['counts']['amount'] = array(
      'title' => ts('Total Amount'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['count'] = array(
      'title' => ts('Total Donations'),
      'value' => $count,
    );
    $statistics['counts']['avg'] = array(
      'title' => ts('Average'),
      'value' => implode(',  ', $average),
      'type' => CRM_Utils_Type::T_STRING,
    );

    return $statistics;
  }

  function postProcess() {
    $this->beginPostProcess();

    $this->buildACLClause(array('constituentname', 'contact_civireport'));
    $sql = $this->buildQuery();

    $dao   = CRM_Core_DAO::executeQuery($sql);
    $rows  = $graphRows = array();
    $count = 0;
    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        $row[$key] = $dao->$key;
      }
      $rows[] = $row;
    }
    $this->formatDisplay($rows);

    // to hide the contact ID field from getting displayed
    unset($this->_columnHeaders['civicrm_contact_id_constituent']);
    unset($this->_columnHeaders['civicrm_contact_id_creditor']);

    // assign variables to templates
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound    = FALSE;
    $dispname_flag = $phone_flag = $email_flag = 0;
    $prev_email    = $prev_dispname = $prev_phone = NULL;

    foreach ($rows as $rowNum => $row) {
      // Link constituent (contributor) to contribution detail
      if (array_key_exists('civicrm_contact_display_name_constituent', $row) &&
        array_key_exists('civicrm_contact_id_constituent', $row)
      ) {

        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id_constituent'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_display_name_constituent_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_constituent_hover'] = ts('List all direct contribution(s) from this contact.');
        $entryFound = TRUE;
      }

      // convert soft credit contact name to link
      if (array_key_exists('civicrm_contact_display_name_creditor', $row) && !empty($rows[$rowNum]['civicrm_contact_display_name_creditor']) &&
        array_key_exists('civicrm_contact_id_creditor', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id_creditor'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_creditor_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_creditor_hover'] = ts("view contact summary");
      }

      // make subtotals look nicer
      if (array_key_exists('civicrm_contact_id_constituent', $row) &&
        !$row['civicrm_contact_id_constituent']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields);
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      //convert soft_credit_type_id into label
      if (array_key_exists('civicrm_contribution_soft_soft_credit_type_id', $rows[$rowNum])) {
        $rows[$rowNum]['civicrm_contribution_soft_soft_credit_type_id'] = CRM_Core_OptionGroup::getLabel('soft_credit_type',
          $row['civicrm_contribution_soft_soft_credit_type_id']);
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }

    $this->removeDuplicates($rows);
  }
}

