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
class CRM_Report_Form_Contribute_LoggingSummary extends CRM_Logging_ReportSummary {
  /**
   *
   */
  function __construct() {
    $this->_columns = array(
      'civicrm_contact_altered_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'display_name' => array(
            'default' => TRUE,
            'title' => ts('Altered Contact'),
          ),
          'is_deleted' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'altered_contact' => array(
            'name' => 'display_name',
            'title' => ts('Altered Contact'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
      'log_civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'log_user_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'log_date' => array(
            'default' => TRUE,
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_TIME,
            'title' => ts('When'),
          ),
          'log_conn_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'log_action' => array(
            'default' => TRUE,
            'title' => ts('Action'),
          ),
          'financial_type_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contribution_status_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'aggregate_amount' => array(
            'default' => TRUE,
            'name' => 'total_amount',
            'title' => ts('Aggregate Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
          ),
        ),
        'filters' => array(
          'log_date' => array(
            'title' => ts('When'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'log_action' => array(
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => array('Insert' => ts('Insert'), 'Update' => ts('Update'), 'Delete' => ts('Delete')),
            'title' => ts('Action'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'id' => array(
            'no_display' => TRUE,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_financial_type' => array(
        'dao' => 'CRM_Financial_DAO_FinancialType',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'name' => array(
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
      'civicrm_contribution_status' => array(
        'dao' => 'CRM_Core_DAO_OptionValue',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'label' => array(
            'title' => ts('Contribution Status'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
      'civicrm_contact_altered_by' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'display_name' => array(
            'default' => TRUE,
            'title' => ts('Altered By'),
          ),
        ),
        'filters' => array(
          'altered_by' => array(
            'name' => 'display_name',
            'title' => ts('Altered By'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
    );
    parent::__construct();
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // cache for id → is_deleted mapping
    $isDeleted = array();

    foreach ($rows as & $row) {
      if (!isset($isDeleted[$row['civicrm_contact_is_deleted']])) {
        $isDeleted[$row['civicrm_contact_is_deleted']] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $row['civicrm_contact_altered_contact_id'], 'is_deleted') !== '0';
      }

      if (!$isDeleted[$row['civicrm_contact_is_deleted']]) {
        $row['civicrm_contact_altered_contact_display_name_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $row['log_civicrm_contribution_contact_id']);
        $row['civicrm_contact_altered_contact_display_name_hover'] = ts('Go to contact summary');
      }

      $row['civicrm_contact_altered_by_display_name_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $row['log_civicrm_contribution_log_user_id']);
      $row['civicrm_contact_altered_by_display_name_hover'] = ts('Go to contact summary');

      if ($row['civicrm_contact_altered_contact_is_deleted'] and ($row['log_civicrm_contribution_log_action'] == 'Update')) {
        $row['log_civicrm_contribution_log_action'] = ts('Delete');
      }

      if ($row['log_civicrm_contribution_log_action'] == 'Update') {
        $q = "reset=1&log_conn_id={$row['log_civicrm_contribution_log_conn_id']}&log_date={$row['log_civicrm_contribution_log_date']}";
        if ($this->cid) {
          $q .= '&cid=' . $this->cid;
        }

        $url = CRM_Report_Utils_Report::getNextUrl('logging/contribute/detail', $q, FALSE, TRUE);
        $row['log_civicrm_contribution_log_action_link'] = $url;
        $row['log_civicrm_contribution_log_action_hover'] = ts('View details for this update');
        $row['log_civicrm_contribution_log_action'] = '<div class="icon details-icon"></div> ' . ts('Update');
      }

      unset($row['log_civicrm_contribute_log_user_id']);
      unset($row['log_civicrm_contribute_log_conn_id']);
    }
  }

  function from() {
    $this->_from = "
            FROM `{$this->loggingDB}`.log_civicrm_contribution {$this->_aliases['log_civicrm_contribution']}
            LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact_altered_by']}
            ON ({$this->_aliases['log_civicrm_contribution']}.log_user_id = {$this->_aliases['civicrm_contact_altered_by']}.id)
            LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact_altered_contact']}
            ON ({$this->_aliases['log_civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact_altered_contact']}.id)
            LEFT JOIN civicrm_financial_type {$this->_aliases['civicrm_financial_type']}
            ON ({$this->_aliases['log_civicrm_contribution']}.financial_type_id = {$this->_aliases['civicrm_financial_type']}.id)
            LEFT JOIN civicrm_option_value {$this->_aliases['civicrm_contribution_status']}
            ON ({$this->_aliases['log_civicrm_contribution']}.contribution_status_id = {$this->_aliases['civicrm_contribution_status']}.value)
            INNER JOIN civicrm_option_group
            ON ({$this->_aliases['civicrm_contribution_status']}.option_group_id = civicrm_option_group.id
            AND civicrm_option_group.name = 'contribution_status')
        ";
  }
}

