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
class CRM_Report_Form_Grant_Detail extends CRM_Report_Form {

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Household',
    'Organization',
    'Grant',
  ];

  /**
   * Class constructor.
   */
  public function __construct() {
    $contactCols = $this->getColumns('Contact', [
      'order_bys_defaults' => ['sort_name' => 'ASC '],
      'fields_defaults' => ['sort_name'],
      'fields_excluded' => ['id'],
      'fields_required' => ['id'],
      'filters_defaults' => ['is_deleted' => 0],
      'no_field_disambiguation' => TRUE,
    ]);
    $specificCols = [
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_grant' => [
        'dao' => 'CRM_Grant_DAO_Grant',
        'fields' => [
          'grant_type_id' => [
            'name' => 'grant_type_id',
            'title' => ts('Grant Type'),
          ],
          'status_id' => [
            'name' => 'status_id',
            'title' => ts('Grant Status'),
          ],
          'amount_total' => [
            'name' => 'amount_total',
            'title' => ts('Amount Requested'),
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'amount_granted' => [
            'name' => 'amount_granted',
            'title' => ts('Amount Granted'),
          ],
          'application_received_date' => [
            'name' => 'application_received_date',
            'title' => ts('Application Received'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'money_transfer_date' => [
            'name' => 'money_transfer_date',
            'title' => ts('Money Transfer Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'grant_due_date' => [
            'name' => 'grant_due_date',
            'title' => ts('Grant Report Due'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'decision_date' => [
            'name' => 'decision_date',
            'title' => ts('Grant Decision Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'rationale' => [
            'name' => 'rationale',
            'title' => ts('Rationale'),
          ],
          'grant_report_received' => [
            'name' => 'grant_report_received',
            'title' => ts('Grant Report Received'),
          ],
        ],
        'filters' => [
          'grant_type' => [
            'name' => 'grant_type_id',
            'title' => ts('Grant Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'grant_type_id'),
          ],
          'status_id' => [
            'name' => 'status_id',
            'title' => ts('Grant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id'),
          ],
          'amount_granted' => [
            'title' => ts('Amount Granted'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'amount_total' => [
            'title' => ts('Amount Requested'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'application_received_date' => [
            'title' => ts('Application Received'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'money_transfer_date' => [
            'title' => ts('Money Transfer Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'grant_due_date' => [
            'title' => ts('Grant Report Due'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'decision_date' => [
            'title' => ts('Grant Decision Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
        'group_bys' => [
          'grant_type_id' => [
            'title' => ts('Grant Type'),
          ],
          'status_id' => [
            'title' => ts('Grant Status'),
          ],
          'application_received_date' => [
            'title' => ts('Application Received Date'),
          ],
          'money_transfer_date' => [
            'title' => ts('Money Transfer Date'),
          ],
          'decision_date' => [
            'title' => ts('Grant Decision Date'),
          ],
        ],
        'order_bys' => [
          'grant_type_id' => [
            'title' => ts('Grant Type'),
          ],
          'status_id' => [
            'title' => ts('Grant Status'),
          ],
          'amount_total' => [
            'title' => ts('Amount Requested'),
          ],
          'amount_granted' => [
            'title' => ts('Amount Granted'),
          ],
          'application_received_date' => [
            'title' => ts('Application Received Date'),
          ],
          'money_transfer_date' => [
            'title' => ts('Money Transfer Date'),
          ],
          'decision_date' => [
            'title' => ts('Grant Decision Date'),
          ],
        ],
      ],
    ];

    $this->_columns = array_merge($contactCols, $specificCols, $this->addAddressFields(FALSE));

    parent::__construct();
  }

  public function from() {
    $this->setFromBase('civicrm_contact');
    $this->_from .= <<<HERESQL
    INNER JOIN civicrm_grant {$this->_aliases['civicrm_grant']}
      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_grant']}.contact_id
HERESQL;

    $this->joinEmailFromContact();
    $this->joinPhoneFromContact();
    $this->joinAddressFromContact();
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
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_grant_grant_type_id', $row)) {
        if ($value = $row['civicrm_grant_grant_type_id']) {
          $rows[$rowNum]['civicrm_grant_grant_type_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Grant_DAO_Grant', 'grant_type_id', $value);
        }
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_grant_status_id', $row)) {
        if ($value = $row['civicrm_grant_status_id']) {
          $rows[$rowNum]['civicrm_grant_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Grant_DAO_Grant', 'status_id', $value);
        }
        $entryFound = TRUE;
      }
      if (!$entryFound) {
        break;
      }
    }
  }

}
