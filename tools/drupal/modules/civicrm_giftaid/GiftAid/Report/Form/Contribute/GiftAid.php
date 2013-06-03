<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
require_once 'CRM/Report/Form.php';
require_once 'GiftAid/Utils/Contribution.php';
class GiftAid_Report_Form_Contribute_GiftAid extends CRM_Report_Form {
  protected $_addressField = FALSE;
  protected $_customGroupExtends = array('Contribution');

  function __construct() {
    $this->_columns = array('civicrm_entity_batch' =>
      array('dao' => 'CRM_Batch_DAO_EntityBatch',
        'filters' =>
        array(
          'batch_id' =>
          array('title' => 'Batch',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => GiftAid_Utils_Contribution::getBatchIdTitle('id desc'),
          ),
        ),
      ),
      'civicrm_contribution' =>
      array('dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_id' =>
          array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
      ),
    );

    parent::__construct();

    // set defaults
    if (is_array($this->_columns['civicrm_value_gift_aid_submission'])) {
      foreach ($this->_columns['civicrm_value_gift_aid_submission']['fields'] as $field => $values) {
        $this->_columns['civicrm_value_gift_aid_submission']['fields'][$field]['default'] = TRUE;
      }
    }
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
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
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
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "
FROM civicrm_entity_batch {$this->_aliases['civicrm_entity_batch']} 
INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
        ON {$this->_aliases['civicrm_entity_batch']}.entity_table = 'civicrm_contribution' AND 
           {$this->_aliases['civicrm_entity_batch']}.entity_id = {$this->_aliases['civicrm_contribution']}.id";
  }

  function where() {
    parent::where();

    if (empty($this->_where)) {
      $this->_where = "WHERE value_gift_aid_submission_civireport.amount IS NOT NULL";
    }
    else {
      $this->_where .= " AND value_gift_aid_submission_civireport.amount IS NOT NULL";
    }
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "
        SELECT SUM( value_gift_aid_submission_civireport.amount ) as amount,
               SUM( value_gift_aid_submission_civireport.gift_aid_amount ) as giftaid_amount";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);

    if ($dao->fetch()) {
      $statistics['counts']['amount'] = array('value' => $dao->amount,
        'title' => 'Total Amount',
        'type' => CRM_Utils_Type::T_MONEY,
      );
      $statistics['counts']['giftaid'] = array('value' => $dao->giftaid_amount,
        'title' => 'Total Gift Aid Amount',
        'type' => CRM_Utils_Type::T_MONEY,
      );
    }
    return $statistics;
  }

  function postProcess() {
    parent::postProcess();
  }
}

