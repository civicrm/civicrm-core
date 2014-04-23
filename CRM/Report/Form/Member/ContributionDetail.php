<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.4                                                |
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
class CRM_Report_Form_Member_ContributionDetail extends CRM_Report_Form {
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;
  protected $_emailFieldHonor = FALSE;

  protected $_nameFieldHonor = FALSE;

  protected $_summary = NULL;
  protected $_allBatches = NULL;

  protected $_customGroupExtends = array(
    'Contribution', 'Membership');

  function __construct() {
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array('CiviCampaign', $config->enableComponents);
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
          'first_name' =>
          array('title' => ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'last_name' =>
          array('title' => ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'contact_type' =>
          array('title' => ts('Contact Type'),
            'no_repeat' => TRUE,
          ),
          'contact_sub_type' =>
          array(
            'title' => ts('Contact SubType'),
            'no_repeat' => TRUE,
          ),
          'do_not_email' =>
          array('title' => ts('Do Not Email'),
            'no_repeat' => TRUE,
          ),
          'is_opt_out' =>
          array('title' => ts('No Bulk Email(Is Opt Out)'),
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
            'csv_display' => TRUE,
            'title' => ts('Contact ID'),
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
      'first_donation' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'first_donation_date' => array(
            'title' => ts('First Contribution Date'),
            'base_field' => 'receive_date',
            'no_repeat' => TRUE,
          ),
          'first_donation_amount' => array(
            'title' => ts('First Contribution Amount'),
            'base_field' => 'total_amount',
            'no_repeat' => TRUE,
          ),
        ),
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
            'csv_display' => TRUE,
            'title' => ts('Contribution ID'),
          ),
          'financial_type_id' => array('title' => ts('Financial Type'),
            'default' => TRUE,
          ),
          'contribution_recur_id' => array('title' => ts('Recurring Contribution Id'),
            'name' => 'contribution_recur_id',
            'required' => TRUE,
            'no_display' => TRUE,
            'csv_display' => TRUE,
          ),
          'contribution_status_id' => array('title' => ts('Contribution Status'),
          ),
          'payment_instrument_id' => array('title' => ts('Payment Type'),
          ),
          'contribution_source' => array(
            'name' => 'source',
            'title' => ts('Contribution Source'),
          ),
          'currency' => array(
            'required' => TRUE,
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
          ),
        ),
        'filters' =>
        array(
          'receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'financial_type_id' =>
          array('title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ),
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'payment_instrument_id' =>
          array('title' => ts('Payment Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          ),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
          'total_amount' =>
          array('title' => ts('Contribution Amount')),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_product' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Product',
        'fields' =>
        array(
          'product_name' => array(
            'name' => 'name',
            'title' => ts('Premium'),
          ),
        ),
      ),
      'civicrm_contribution_product' =>
      array(
        'dao' => 'CRM_Contribute_DAO_ContributionProduct',
        'fields' =>
        array(
          'product_id' => array(
            'no_display' => TRUE,
          ),
          'product_option' => array(
            'title' => ts('Premium Option'),
          ),
          'contribution_id' => array(
            'no_display' => TRUE,
          ),
        ),
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
          ),
        ),
      ),
      'civicrm_membership' =>
      array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' =>
        array(
          'membership_type_id' => array('title' => ts('Membership Type'),
                                'required' => TRUE,
                                'no_repeat' => TRUE,
          ),
          'membership_start_date' => array('title' => ts('Start Date'),
                                   'default' => TRUE,
          ),
          'membership_end_date' => array('title' => ts('End Date'),
                                 'default' => TRUE,
          ),
          'join_date' => array('title' => ts('Join Date'),
                       'default' => TRUE,
          ),
          'source' => array('title' => ts('Membership Source')),
        ),
        'filters' => array(
          'join_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'membership_start_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'membership_end_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'owner_membership_id' =>
          array('title' => ts('Membership Owner ID'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ),
          'tid' =>
          array(
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' =>
      array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' =>
        array(
          'membership_status_name' =>
          array(
            'name' => 'name',
            'title' => ts('Membership Status'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'sid' =>
          array(
            'name' => 'id',
            'title' => ts('Membership Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),
        'grouping' => 'member-fields',
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
    $this->_allBatches = CRM_Batch_BAO_Batch::getBatches();
    if (!empty($this->_allBatches)) {
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
        'options' => $this->_allBatches,
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
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
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
            elseif ($fieldName == 'first_donation_date' || $fieldName == 'first_donation_amount') {
              $baseField = CRM_Utils_Array::value('base_field', $field);
              $select[] = "{$this->_aliases['civicrm_contribution']}.{$baseField} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
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

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  function from() {
    $this->_from = "
              FROM civireport_membership_contribution_detail
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                      ON (civireport_membership_contribution_detail.contribution_id = {$this->_aliases['civicrm_contribution']}.id)
              LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                      ON (civireport_membership_contribution_detail.membership_id = {$this->_aliases['civicrm_membership']}.id)
              INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON (civireport_membership_contribution_detail.contact_id = {$this->_aliases['civicrm_contact']}.id)
              LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id
";

    //for premiums
    if (CRM_Utils_Array::value('product_name', $this->_params['fields']) || CRM_Utils_Array::value('product_option', $this->_params['fields'])) {
      $this->_from .= "
                 LEFT JOIN  civicrm_contribution_product {$this->_aliases['civicrm_contribution_product']}
                        ON ({$this->_aliases['civicrm_contribution_product']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id)
                 LEFT JOIN  civicrm_product {$this->_aliases['civicrm_product']} ON ({$this->_aliases['civicrm_product']}.id = {$this->_aliases['civicrm_contribution_product']}.product_id)";
    }

    if (!empty($this->_params['ordinality_value'])) {
      $this->_from .= "
              INNER JOIN (SELECT c.id, IF(COUNT(oc.id) = 0, 0, 1) AS ordinality FROM civicrm_contribution c LEFT JOIN civicrm_contribution oc ON c.contact_id = oc.contact_id AND oc.receive_date < c.receive_date GROUP BY c.id) {$this->_aliases['civicrm_contribution_ordinality']}
                      ON {$this->_aliases['civicrm_contribution_ordinality']}.id = {$this->_aliases['civicrm_contribution']}.id";
    }

    // include contribution note
    if (CRM_Utils_Array::value('contribution_note', $this->_params['fields']) || !empty($this->_params['note_value'])) {
      $this->_from.= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                      ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_contribution' AND
                           {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_note']}.entity_id )";

    }

    if (CRM_Utils_Array::value('phone', $this->_params['fields'])) {
      $this->_from .= "
               LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']}
                      ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                         {$this->_aliases['civicrm_phone']}.is_primary = 1)";
    }
    //for contribution batches
    if ($this->_allBatches &&
      (CRM_Utils_Array::value('batch_id', $this->_params['fields']) || !empty($this->_params['bid_value']))) {
      $this->_from .= "
                LEFT JOIN civicrm_entity_financial_trxn tx ON (tx.entity_id = {$this->_aliases['civicrm_contribution']}.id AND
                   tx.entity_table = 'civicrm_contribution')
                 LEFT JOIN  civicrm_entity_batch {$this->_aliases['civicrm_entity_batch']}
                        ON ({$this->_aliases['civicrm_entity_batch']}.entity_id = tx.financial_trxn_id AND
                        {$this->_aliases['civicrm_entity_batch']}.entity_table = 'civicrm_financial_trxn')
                 LEFT JOIN civicrm_batch {$this->_aliases['civicrm_batch']}
                        ON {$this->_aliases['civicrm_batch']}.id = {$this->_aliases['civicrm_entity_batch']}.batch_id";
    }

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
  }

  function tempTable($applyLimit = TRUE) {
    // create temp table with contact ids,contribtuion id,membership id
    $dropTempTable = 'DROP TABLE IF EXISTS civireport_membership_contribution_detail';
    CRM_Core_DAO::executeQuery($dropTempTable);

    $sql = 'CREATE TEMPORARY TABLE civireport_membership_contribution_detail
            (contribution_id int, contact_id int, membership_id int, payment_id int) ENGINE=HEAP';
    CRM_Core_DAO::executeQuery($sql);

    $fillTemp = "
          INSERT INTO civireport_membership_contribution_detail (contribution_id, contact_id, membership_id)
          SELECT contribution.id, contact.id, m.id
          FROM civicrm_contribution contribution
          INNER JOIN civicrm_contact contact
                ON contact.id = contribution.contact_id AND contribution.is_test = 0
          {$this->_aclFrom}
          LEFT JOIN civicrm_membership_payment mp
                ON contribution.id = mp.contribution_id
          LEFT JOIN civicrm_membership m
                ON mp.membership_id = m.id AND m.is_test = 0 ";

    CRM_Core_DAO::executeQuery($fillTemp);
  }

  function buildQuery($applyLimit = TRUE) {
    $this->select();
    //create temp table to be used as base table
    $this->tempTable();
    $this->from();
    $this->customDataFrom();
    $this->where();
    $this->groupBy();
    $this->orderBy();

    // order_by columns not selected for display need to be included in SELECT
    $unselectedSectionColumns = $this->unselectedSectionColumns();
    foreach ($unselectedSectionColumns as $alias => $section) {
      $this->_select .= ", {$section['dbAlias']} as {$alias}";
    }

    if ($applyLimit && !CRM_Utils_Array::value('charts', $this->_params)) {
      $this->limit();
    }

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
    return $sql;
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution']}.id ";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id ";
    if (CRM_Utils_Array::value('first_donation_date', $this->_params['fields'])
      || CRM_Utils_Array::value('first_donation_amount', $this->_params['fields'])) {
      $this->_orderBy .= ", {$this->_aliases['civicrm_contribution']}.receive_date";
    }
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "                                                                                                                                                                                                     SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,                                                                                                                                   SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount,                                                                                                                                   ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg,                                                                                                                              {$this->_aliases['civicrm_contribution']}.currency as currency                                                                                                                                       ";

    $group = "\nGROUP BY {$this->_aliases['civicrm_contribution']}.currency";
    $sql = "{$select} {$this->_from} {$this->_where} {$group}";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $totalAmount = $average = array();
    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency)."(".$dao->count.")";
      $average[] =   CRM_Utils_Money::format($dao->avg, $dao->currency);
    }
    $statistics['counts']['amount'] = array(
      'title' => ts('Total Amount'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
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
    $checkList = array();

    $entryFound         = FALSE;
    $contributionTypes  = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $honorTypes         = CRM_Core_OptionGroup::values('honor_type', FALSE, FALSE, FALSE, NULL, 'label');

    //altering the csv display adding additional fields
    if ($this->_outputMode == 'csv') {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('fields', $table)) {
          foreach ($table['fields'] as $fieldName => $field) {
            if (CRM_Utils_Array::value('csv_display', $field) && CRM_Utils_Array::value('no_display', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }

    // allow repeat for first donation amount and date in csv
    $fAmt  = '';
    $fDate = '';
    foreach ($rows as $rowNum => $row) {
      if ($this->_outputMode == 'csv') {
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($contactId = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $pcid  = $contactId;
              $fAmt  = $row['first_donation_first_donation_amount'];
              $fDate = $row['first_donation_first_donation_date'];
            }
            else {
              if ($pcid == $contactId) {
                $rows[$rowNum]['first_donation_first_donation_amount'] = $fAmt;
                $rows[$rowNum]['first_donation_first_donation_date']   = $fDate;
                $pcid = $contactId;
              }
              else {
                $fAmt  = $row['first_donation_first_donation_amount'];
                $fDate = $row['first_donation_first_donation_date'];
                $pcid = $contactId;
              }
            }
          }
        }
      }

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        $repeatFound = FALSE;


        $display_flag = NULL;
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
                if (in_array($colName, $this->_noRepeats)) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }


      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_batch_batch_id', $row)) {
        if ($value = $row['civicrm_batch_batch_id']) {
          $rows[$rowNum]['civicrm_batch_batch_id'] = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $value, 'title');
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // convert donor sort name to link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_sort_name', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );

        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
      }

      // convert honoree sort name to link
      if (array_key_exists('civicrm_contact_honor_sort_name_honor', $row) &&
        CRM_Utils_Array::value('civicrm_contact_honor_sort_name_honor', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_honor_id_honor', $row)
      ) {

        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_honor_id_honor'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_honor_sort_name_honor_link'] = $url;
        $rows[$rowNum]['civicrm_contact_honor_sort_name_honor_hover'] = ts('View Contact Summary for Honoree.');
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
      if (($value = CRM_Utils_Array::value('civicrm_contribution_total_amount_sum', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
          'reset=1&id=' . $row['civicrm_contribution_contribution_id'] . '&cid=' . $row['civicrm_contact_id'] . '&action=view&context=contribution&selectedChild=contribute',
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_total_amount_sum_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_total_amount_sum_hover'] = ts('View Details of this Contribution.');
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'member/contributionDetail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }
}

