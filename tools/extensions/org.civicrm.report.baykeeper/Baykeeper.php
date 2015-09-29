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

require_once 'CRM/Report/Form.php';
require_once 'CRM/Contribute/PseudoConstant.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Event/BAO/Participant.php';
require_once 'CRM/Contact/BAO/Contact.php';

/**
 * Class CRM_Report_Form_Contribute_Baykeeper
 */
class CRM_Report_Form_Contribute_Baykeeper extends CRM_Report_Form {
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Contact', 'Contribution');

  /**
   *
   */
  function __construct() {
    $this->_columns = array('civicrm_contact' =>
      array('dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array('display_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array('no_display' => FALSE,
            'title' => ts('Contact ID'),
            'required' => TRUE,
          ),
          'contact_id' =>
          array('title' => ts('Contact ID'),
            'name' => 'id',
            'default' => FALSE,
            'no_repeat' => TRUE,
            'required' => TRUE,
          ),
          'addressee_display' => array('title' => ts('Addressee Name')),
          'postal_greeting_display' => array('title' => ts('Greeting')),
          'display_name_creditor' =>
          array('title' => ts('Soft Credit Name'),
            'name' => 'display_name',
            'alias' => 'soft_credit',
            'no_repeat' => TRUE,
          ),
          'id_creditor' =>
          array('title' => ts('Soft Credit Id'),
            'name' => 'id',
            'alias' => 'soft_credit',
          ),
          'employer_name' =>
          array('title' => ts('Employer Name'),
            'name' => 'display_name',
            'alias' => 'employer_company',
          ),
          'employer_id' =>
          array('title' => ts('Employer Id'),
            'name' => 'employer_id',
          ),
          'do_not_mail' => array('title' => ts('Do Not Mail')),
        ),
        'filters' =>
        array('sort_name' =>
          array('title' => ts('Contact Name'),
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
      array('dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array('email' =>
          array('title' => ts('Email'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array('dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array('phone' =>
          array('title' => ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_address' =>
      array('dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array('street_address' => NULL,
          'supplemental_address_1' => array('title' => ts('Sup Address 1'),
          ),
          'supplemental_address_2' => array('title' => ts('Sup Address 2'),
          ),
          'city' => NULL,
          'postal_code' => NULL,
          'location_type_id' => array('title' => ts('Location Type ID'),
          ),
          'state_province_id' =>
          array('title' => ts('State/Province'),
          ),
          'country_id' =>
          array('title' => ts('Country'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
        'filters' =>
        array('country_id' =>
          array('title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ),
          'state_province_id' =>
          array('title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ),
        ),
      ),
      'civicrm_contribution' =>
      array('dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contribution_type_id' => array('title' => ts('Contribution Type'),
            'default' => TRUE,
          ),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
          'source' => array('title' => ts('Source')),
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'non_deductible_amount' => array('title' => ts('Non Deductible Amount')),
          'total_amount' => array('title' => ts('Total Amount'),
            'required' => TRUE,
          ),
          'honor_contact_id' => array('title' => ts('Honor Contact ID'),
          ),
          'honor_type_id' => array('title' => ts('Hon/Mem Type')),
        ),
        'filters' =>
        array('receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'contribution_type_id' =>
          array('title' => ts('Contribution Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionType(),
          ),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
          ),
          'total_amount' =>
          array('title' => ts('Contribution Amount')),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_contact_hon_mem' =>
      array('dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'hon_mem',
        'fields' =>
        array('id' => array('title' => ts('Hon/Mem Contact ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'display_name' => array('title' => ts('In Honor Of'),
            'required' => FALSE,
          ),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_note' =>
      array('dao' => 'CRM_Core_DAO_Note',
        'fields' =>
        array(
          'note' =>
          array('title' => ts('Note'),
            'default' => TRUE,
            'no_repeat' => FALSE,
          ),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_contribution_soft' =>
      array('dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' =>
        array('contribution_id' =>
          array('title' => ts('Contribution ID'),
            'no_display' => TRUE,
            'default' => TRUE,
          ),
          'contact_id' =>
          array('title' => ts('Contact ID'),
            'no_display' => TRUE,
            'default' => TRUE,
          ),
          'id' =>
          array('default' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'softcredit-fields',
      ),
      'civicrm_group_field' =>
      array('dao' => 'CRM_Contact_DAO_Group',
        'fields' =>
        array('title' =>
          array('title' => ts('Groups')),
        ),
      ),
      'civicrm_group' =>
      array('dao' => 'CRM_Contact_DAO_GroupContact',
        'alias' => 'cgroup',
        'fields' =>
        array(
        ),
        'filters' =>
        array('gid' =>
          array('name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ),
        ),
      ),
      'civicrm_contribution_ordinality' =>
      array('dao' => 'CRM_Contribute_DAO_Contribution',
        'alias' => 'cordinality',
        'filters' =>
        array('ordinality' =>
          array('title' => ts('Contribution Ordinality'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => array(0 => 'First by Contributor',
              1 => 'Second or Later by Contributor',
            ),
          ),
        ),
      ),
    );

    $this->_options = array('first_contribution' => array('title' => ts('First Contribution'),
        'type' => 'checkbox',
      ),
      'last_contribution' => array('title' => ts('Last Contribution'),
        'type' => 'checkbox',
      ),
      'include_nondonors' => array('title' => ts('Include non-donors?'),
        'type' => 'checkbox',
      ),
    );

    $this->_tagFilter = TRUE;
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
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            /*
                        if ( $tableName == 'civicrm_group_field' && $fieldName == 'title' ) {
                            $select[] =  "GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$field['dbAlias']} ) as {$tableName}_{$fieldName}";
                        } else {
                            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                        }
            */


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
            elseif ($tableName == 'civicrm_group_field' && $fieldName == 'title') {
              $select[] = " GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$field['dbAlias']} SEPARATOR ' | <br>') as {$tableName}_{$fieldName} ";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }

    // insert first and last contribution at end
    if (!empty($this->_params['options']['first_contribution'])) {
      $select[] = " '' as first_contribution";
      $this->_columnHeaders['first_contribution']['title'] = ts('First Contribution');
    }

    if (!empty($this->_params['options']['last_contribution'])) {
      $select[] = " '' as last_contribution";
      $this->_columnHeaders['last_contribution']['title'] = ts('Last Contribution');
    }


    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $alias_constituent = 'constituentname';
    $alias_creditor    = 'soft_credit';
    $alias_employer    = "employer_company";

    $this->_from = NULL;

    $hackValue = CRM_Utils_Array::value('include_nondonors', $this->_params['options'], 0);
    $contribJoin = $hackValue ? "LEFT" : "INNER";

    $this->_from = "
        FROM  civicrm_contact      {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              $contribJoin JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
              $contribJoin JOIN (SELECT c.id, IF(COUNT(oc.id) = 0, 0, 1) AS ordinality FROM civicrm_contribution c LEFT JOIN civicrm_contribution oc ON c.contact_id = oc.contact_id AND oc.receive_date < c.receive_date GROUP BY c.id) {$this->_aliases['civicrm_contribution_ordinality']}
                      ON {$this->_aliases['civicrm_contribution_ordinality']}.id = {$this->_aliases['civicrm_contribution']}.id
        LEFT JOIN  civicrm_note {$this->_aliases['civicrm_note']}
                      ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_note']}.contact_id AND
              {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_note']}.entity_id )
              LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']}
                      ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                         {$this->_aliases['civicrm_phone']}.is_primary = 1)
              LEFT  JOIN civicrm_contact {$alias_employer}
                         ON {$this->_aliases['civicrm_contact']}.employer_id =
                            {$alias_employer}.id
              LEFT JOIN civicrm_contribution_soft {$this->_aliases['civicrm_contribution_soft']}
                         ON {$this->_aliases['civicrm_contribution_soft']}.contribution_id =
                            {$this->_aliases['civicrm_contribution']}.id
              LEFT  JOIN civicrm_contact {$alias_creditor}
                         ON {$this->_aliases['civicrm_contribution_soft']}.contact_id =
                            {$alias_creditor}.id
        LEFT  JOIN civicrm_contact {$this->_aliases['civicrm_contact_hon_mem']}
               ON {$this->_aliases['civicrm_contribution']}.honor_contact_id = {$this->_aliases['civicrm_contact_hon_mem']}.id
             ";
    // add group - concatenated
    $this->_from .= " LEFT JOIN civicrm_group_contact gc ON {$this->_aliases['civicrm_contact']}.id = gc.contact_id  AND gc.status = 'Added'
                      LEFT JOIN civicrm_group {$this->_aliases['civicrm_group_field']} ON {$this->_aliases['civicrm_group_field']}.id = gc.group_id ";

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
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution']}.id ";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution']}.receive_date ";
  }

  /**
   * @param $rows
   *
   * @return array
   */
  function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    // because the query returns groups, the amount is multiplied by the number of groups a contact is in
    // that's why this is disabled
    /* SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount, */


    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
               ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg
        ";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);

    if ($dao->fetch()) {
      // because the query returns groups, the amount is multiplied by the number of groups a contact is in
      // that's why this is disabled
      /* SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount, */


      /*
      $statistics['counts']['amount']    = array( 'value' => $dao->amount,
                                                        'title' => 'Total Amount',
                                                        'type'  => CRM_Utils_Type::T_MONEY );
            $statistics['counts']['avg']       = array( 'value' => $dao->avg,
                                                        'title' => 'Average',
                                                        'type'  => CRM_Utils_Type::T_MONEY );
      */
    }

    return $statistics;
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {

    require_once 'CRM/Contribute/BAO/Contribution/Utils.php';
    require_once 'CRM/Utils/Money.php';
    require_once 'CRM/Utils/Date.php';

    $config = &CRM_Core_Config::singleton();

    // custom code to alter rows
    $checkList         = array();
    $entryFound        = FALSE;
    $display_flag      = $prev_cid = $cid = 0;
    $contributionTypes = CRM_Contribute_PseudoConstant::contributionType();

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) &&
        $this->_outputMode != 'csv'
      ) {
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
                if (in_array($colName, $this->_noRepeats)) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      if (array_key_exists('first_contribution', $row) ||
        array_key_exists('last_contribution', $row)
      ) {
        $details = CRM_Contribute_BAO_Contribution_Utils::getFirstLastDetails($row['civicrm_contact_id']);
        if ($details['first']) {
          $rows[$rowNum]['first_contribution'] = CRM_Utils_Money::format($details['first']['total_amount']) . ' - ' . CRM_Utils_Date::customFormat($details['first']['receive_date'], $config->dateformatFull);
        }
        if ($details['last']) {
          $rows[$rowNum]['last_contribution'] = CRM_Utils_Money::format($details['last']['total_amount']) . ' - ' . CRM_Utils_Date::customFormat($details['last']['receive_date'], $config->dateformatFull);
        }
      }

      // handle state province
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            "reset=1&force=1&" .
            "state_province_id_op=in&state_province_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_state_province_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_state_province_id_hover'] = ts("List all contribution(s) for this State.");
        }
        $entryFound = TRUE;
      }

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            "reset=1&force=1&" .
            "country_id_op=in&country_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_country_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_country_id_hover'] = ts("List all contribution(s) for this Country.");
        }

        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_display_name', $row) && !empty($rows[$rowNum]['civicrm_contact_display_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      // convert soft credit id to link
      if (array_key_exists('soft_credit_display_name', $row) && !empty($rows[$rowNum]['soft_credit_display_name']) &&
        array_key_exists('id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['soft_credit_display_name_creditor_link'] = $url;
        $rows[$rowNum]['soft_credit_display_name_creditor_hover'] = ts("View Contact Summary for this Soft Credit.");
      }

      // convert hon/mem contact to link
      if (array_key_exists('civicrm_contact_hon_mem_display_name', $row) && !empty($rows[$rowNum]['civicrm_contribution_honor_contact_id']) &&
        array_key_exists('civicrm_contribution_honor_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contribution_honor_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_hon_mem_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_hon_mem_display_name_hover'] = ts("View Contact Summary for this Honor/Memory Contribution.");
      }

      // honor of/memory of type
      if ($value = CRM_Utils_Array::value('civicrm_contribution_honor_type_id', $row) && !empty($rows[$rowNum]['civicrm_contribution_honor_type_id'])) {
        // rather than do a join, just change the output here, since there these values are pretty static


        if ($rows[$rowNum]['civicrm_contribution_honor_type_id'] == 1) {
          $rows[$rowNum]['civicrm_contribution_honor_type_id'] = "In Honor Of";
        }
        elseif ($rows[$rowNum]['civicrm_contribution_honor_type_id'] == 2) {
          $rows[$rowNum]['civicrm_contribution_honor_type_id'] = "In Memory Of";
        }
        else {
          $rows[$rowNum]['civicrm_contribution_honor_type_id'] = "n/a";
        }
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }

      if (($value = CRM_Utils_Array::value('civicrm_contribution_total_amount', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
          "reset=1&id=" .
          $row['civicrm_contribution_contribution_id'] .
          "&cid=" .
          $row['civicrm_contact_id'] .
          "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_total_amount_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_total_amount_hover'] = ts("View Details of this Contribution.");
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }
}

