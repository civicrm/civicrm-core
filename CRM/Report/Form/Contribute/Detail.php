<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_Detail extends CRM_Report_Form {
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;
  protected $_emailFieldHonor = FALSE;

  protected $_nameFieldHonor = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array(
    'Contribution');

  function __construct() {
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
          'sort_name' =>
          array('title' => ts('Donor Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
		  'first_name' => array('title' => ts('First Name'),
          ),
		  'last_name' => array('title' => ts('Last Name'),
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Donor Name'),
            'operator' => 'like',
          ),
          'id' =>
          array('title' => ts('Contact ID'),
            'no_display' => TRUE,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'order_bys' =>
        array(
          'sort_name' => array(
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC'
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email' =>
          array('title' => ts('Donor Email'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone' =>
          array('title' => ts('Donor Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contact_honor' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name_honor' =>
          array('title' => ts('Honoree Name'),
            'name' => 'sort_name',
            'alias' => 'contacthonor',
            'default' => FALSE,
            'no_repeat' => TRUE,
          ),
          'id_honor' =>
          array(
            'no_display' => TRUE,
            'title' => ts('Honoree ID'),
            'name' => 'id',
            'alias' => 'contacthonor',
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_email_honor' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email_honor' =>
          array('title' => ts('Honoree Email'),
            'name' => 'email',
            'alias' => 'emailhonor',
            'default' => FALSE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'financial_type_id' => array('title' => ts('Financial Type'),
            'default' => TRUE,
          ),
          'contribution_status_id' => array('title' => ts('Contribution Status'),
          ),
		  'source' => array('title' => ts('Source'),
          ),
          'payment_instrument_id' => array('title' => ts('Payment Type'),
          ),
          'currency' =>
          array('required' => TRUE,
            'no_display' => TRUE,
          ),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
          'honor_type_id' => array('title' => ts('Honor Type'),
            'default' => FALSE,
          ),
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'total_amount' => array('title' => ts('Amount'),
            'required' => TRUE,
            'statistics' =>
            array('sum' => ts('Amount')),
          ),
        ),
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
          'financial_type_id'   =>
          array('title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'payment_instrument_id' =>
          array('title' => ts('Payment Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'total_amount' =>
          array('title' => ts('Contribution Amount')),
        ),
        'order_bys' => array(
          'financial_type_id' => array('title' => ts('Financial Type')),
          'contribution_status_id' => array('title' => ts('Contribution Status')),
          'payment_instrument_id' => array('title' => ts('Payment Instrument')),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_group' =>
      array(
        'dao' => 'CRM_Contact_DAO_GroupContact',
        'alias' => 'cgroup',
        'filters' =>
        array(
          'gid' =>
          array(
            'name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_contribution_ordinality' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'alias' => 'cordinality',
        'filters' =>
        array(
          'ordinality' =>
          array('title' => ts('Contribution Ordinality'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => array(
              0 => 'First by Contributor',
              1 => 'Second or Later by Contributor',
            ),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_note' =>
      array(
        'dao' => 'CRM_Core_DAO_Note',
        'fields' =>
        array(
          'contribution_note' =>
          array(
            'name' => 'note',
            'title' => ts('Contribution Note'),
          ),
        ),
        'filters' =>
        array(
          'note' =>
          array(
                'name'  => 'note',
                'title' => ts('Contribution Note'),
                'operator' => 'like',
                'type'  => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
    ) + $this->addAddressFields(FALSE);

    $this->_tagFilter = TRUE;

    // Don't show Batch display column and filter unless batches are being used
    $this->_closedBatches = CRM_Batch_BAO_Batch::getBatches();
    if (!empty($this->_closedBatches)) {
      $this->_columns['civicrm_batch']['dao'] = 'CRM_Batch_DAO_Batch';
      $this->_columns['civicrm_batch']['fields']['batch_id'] = array(
        'name' => 'id',
        'title' => ts('Batch Name'),
      );
      $this->_columns['civicrm_batch']['filters']['bid'] = array(
        'name' => 'id',
        'title' => ts('Batch Name'),
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->_closedBatches,
      );
      $this->_columns['civicrm_entity_batch']['dao'] = 'CRM_Batch_DAO_EntityBatch';
      $this->_columns['civicrm_entity_batch']['fields']['entity_batch_id'] = array(
        'name' => 'batch_id',
        'default' => TRUE,
        'no_display' => TRUE,
      );
    }

    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_contribution']['fields']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'default' => 'false',
      );
      $this->_columns['civicrm_contribution']['filters']['campaign_id'] = array('title' => ts('Campaign'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->activeCampaigns,
      );
      $this->_columns['civicrm_contribution']['order_bys']['campaign_id'] = array('title' => ts('Campaign'));
    }

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
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_email_honor') {
              $this->_emailFieldHonor = TRUE;
            }

            if ($tableName == 'civicrm_contact_honor') {
              $this->_nameFieldHonor = TRUE;
            }

            // only include statistics columns if set
            if (CRM_Utils_Array::value('statistics', $field)) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    $this->_selectAliases[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    $this->_selectAliases[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    $this->_selectAliases[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_selectAliases[] = "{$tableName}_{$fieldName}";
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = NULL;
    $this->_from = "
        FROM  civicrm_contact      {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0";

    if (!empty($this->_params['ordinality_value'])) {
      $this->_from .= "
              INNER JOIN (SELECT c.id, IF(COUNT(oc.id) = 0, 0, 1) AS ordinality FROM civicrm_contribution c LEFT JOIN civicrm_contribution oc ON c.contact_id = oc.contact_id AND oc.receive_date < c.receive_date GROUP BY c.id) {$this->_aliases['civicrm_contribution_ordinality']}
                      ON {$this->_aliases['civicrm_contribution_ordinality']}.id = {$this->_aliases['civicrm_contribution']}.id";
    }

    $this->_from .= "
               LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']}
                      ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                         {$this->_aliases['civicrm_phone']}.is_primary = 1)";

    if ($this->_addressField OR (!empty($this->_params['state_province_id_value']) OR !empty($this->_params['country_id_value']))) {
      $this->_from .= "
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    if ($this->_emailField) {
      $this->_from .= "
            LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                      {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }

    // include Honor name field
    if ($this->_nameFieldHonor) {
      $this->_from .= "
            LEFT JOIN civicrm_contact contacthonor
                      ON contacthonor.id = {$this->_aliases['civicrm_contribution']}.honor_contact_id";
    }
    // include Honor email field
    if ($this->_emailFieldHonor) {
      $this->_from .= "
            LEFT JOIN civicrm_email emailhonor
                      ON emailhonor.contact_id = {$this->_aliases['civicrm_contribution']}.honor_contact_id
                      AND emailhonor.is_primary = 1\n";
    }
    // include contribution note
    if (CRM_Utils_Array::value('contribution_note', $this->_params['fields']) ||
      CRM_Utils_Array::value('note_value', $this->_params)) {
      $this->_from.= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                      ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_contribution' AND
                           {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
    }
    //for contribution batches
    if ($this->_closedBatches && CRM_Utils_Array::value('batch_id', $this->_params['fields'])) {
      $this->_from .= "
                 LEFT JOIN  civicrm_entity_batch {$this->_aliases['civicrm_entity_batch']}
                        ON ({$this->_aliases['civicrm_entity_batch']}.entity_id = {$this->_aliases['civicrm_contribution']}.id AND
                        {$this->_aliases['civicrm_entity_batch']}.entity_table = 'civicrm_contribution')
                 LEFT JOIN civicrm_batch {$this->_aliases['civicrm_batch']}
                        ON {$this->_aliases['civicrm_batch']}.id = {$this->_aliases['civicrm_entity_batch']}.batch_id";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution']}.id ";
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $totalAmount = $average = array();
    $count = 0;
    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
               SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount,
               ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg,
               {$this->_aliases['civicrm_contribution']}.currency as currency
        ";

    $group = "\nGROUP BY {$this->_aliases['civicrm_contribution']}.currency";
    $sql = "{$select} {$this->_from} {$this->_where} {$group}";
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency)."(".$dao->count.")";
      $average[] =   CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
    }
    $statistics['counts']['amount'] = array(
      'title' => ts('Total Amount (Donations)'),
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
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $checkList          = array();
    $entryFound         = FALSE;
    $display_flag       = $prev_cid = $cid = 0;
    $contributionTypes  = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $honorTypes         = CRM_Core_OptionGroup::values('honor_type', FALSE, FALSE, FALSE, NULL, 'label');


    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                // Hide repeats in no-repeat columns, but not if the field's a section header
                if (in_array($colName, $this->_noRepeats) && !array_key_exists($colName, $this->_sections)) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }



      // convert donor sort name to link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_sort_name', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      // convert honoree sort name to link
      if (array_key_exists('civicrm_contact_honor_sort_name_honor', $row) &&
        CRM_Utils_Array::value('civicrm_contact_honor_sort_name_honor', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_honor_id_honor', $row)
      ) {

        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_honor_id_honor'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_honor_sort_name_honor_link'] = $url;
        $rows[$rowNum]['civicrm_contact_honor_sort_name_honor_hover'] = ts("View Contact Summary for Honoree.");
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_honor_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_honor_type_id'] = $honorTypes[$value];
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_batch_batch_id', $row)) {
        if ($value = $row['civicrm_batch_batch_id']) {
          $rows[$rowNum]['civicrm_batch_batch_id'] = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $value, 'title');
        }
        $entryFound = TRUE;
      }

      // Contribution amount links to viewing contribution
      if (($value = CRM_Utils_Array::value('civicrm_contribution_total_amount_sum', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
          "reset=1&id=" . $row['civicrm_contribution_contribution_id'] . "&cid=" . $row['civicrm_contact_id'] . "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_total_amount_sum_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_total_amount_sum_hover'] = ts("View Details of this Contribution.");
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }

  function sectionTotals( ) {

    // Reports using order_bys with sections must populate $this->_selectAliases in select() method.
    if (empty($this->_selectAliases)) {
      return;
    }

    if (!empty($this->_sections)) {
      // build the query with no LIMIT clause
      $select = str_ireplace( 'SELECT SQL_CALC_FOUND_ROWS ', 'SELECT ', $this->_select );
      $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";

      // pull section aliases out of $this->_sections
      $sectionAliases = array_keys($this->_sections);

      $ifnulls = array();
      foreach (array_merge($sectionAliases, $this->_selectAliases) as $alias) {
        $ifnulls[] = "ifnull($alias, '') as $alias";
      }

      /* Group (un-limited) report by all aliases and get counts. This might
      * be done more efficiently when the contents of $sql are known, ie. by
      * overriding this method in the report class.
      */

      $addtotals = '';

      if (array_search("civicrm_contribution_total_amount_sum", $this->_selectAliases) !== FALSE) {
        $addtotals = ", sum(civicrm_contribution_total_amount_sum) as sumcontribs";
        $showsumcontribs = TRUE;
      }

      $query = "select "
        . implode(", ", $ifnulls)
        ."$addtotals, count(*) as ct from ($sql) as subquery group by ".  implode(", ", $sectionAliases);
      // initialize array of total counts
      $sumcontribs = $totals = array();
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {

        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = array();
        $i = 1;
        $aliasCount = count($sectionAliases);
        foreach ($sectionAliases as $alias) {
          $values[] = $row[$alias];
          $key = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values);
          if ($i == $aliasCount) {
            // the last alias is the lowest-level section header; use count as-is
            $totals[$key] = $dao->ct;
            if ($showsumcontribs) { $sumcontribs[$key] = $dao->sumcontribs; }
          }
          else {
            // other aliases are higher level; roll count into their total
            $totals[$key] = (array_key_exists($key, $totals)) ? $totals[$key] + $dao->ct : $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = array_key_exists($key, $sumcontribs) ? $sumcontribs[$key] + $dao->sumcontribs : $dao->sumcontribs;
            }
          }
        }
      }
      if ($showsumcontribs) {
        $totalandsum = array();
        foreach ($totals as $key => $total) {
          $totalandsum[$key] = ts("%1 contributions: %2", array(
            1 => $total,
            2 => CRM_Utils_Money::format($sumcontribs[$key])
          ));
        }
        $this->assign('sectionTotals', $totalandsum);
      }
      else {
        $this->assign('sectionTotals', $totals);
      }
    }
  }
}

