<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Report_Form_Member_Detail extends CRM_Report_Form {

  protected $_summary;

  protected $_customGroupExtends = [
    'Membership',
    'Contribution',
    'Contact',
    'Individual',
    'Household',
    'Organization',
  ];

  protected $_customGroupGroupBy;

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => $this->getBasicContactFields(),
        'filters' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'operator' => 'like',
          ],
          'is_deleted' => [
            'title' => ts('Is Deleted'),
            'default' => 0,
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
          'id' => ['no_display' => TRUE],
        ],
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'membership_start_date' => [
            'title' => ts('Membership Start Date'),
            'default' => TRUE,
          ],
          'membership_end_date' => [
            'title' => ts('Membership Expiration Date'),
            'default' => TRUE,
          ],
          'owner_membership_id' => [
            'title' => ts('Primary/Inherited?'),
            'default' => TRUE,
          ],
          'membership_join_date' => [
            'title' => ts('Member Since'),
            'default' => TRUE,
          ],
          'source' => ['title' => ts('Membership Source')],
        ],
        'filters' => [
          'membership_join_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'membership_start_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'membership_end_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'owner_membership_id' => [
            'title' => ts('Primary Membership'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'tid' => [
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ],
        ],
        'order_bys' => [
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'default' => '0',
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
          'status_id' => [
            'title' => ts('Membership Status'),
          ],
          'membership_start_date' => [
            'title' => ts('Membership Start Date'),
          ],
          'membership_end_date' => [
            'title' => ts('Membership End Date'),
          ],
          'contribution_recur_id' => [
            'title' => ts('Auto-renew'),
          ],
        ],
        'grouping' => 'member-fields',
        'group_bys' => [
          'id' => [
            'title' => ts('Membership'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_membership_status' => [
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' => [
          'name' => [
            'title' => ts('Status'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'sid' => [
            'name' => 'id',
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ],
        ],
        'grouping' => 'member-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => ['phone' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contribution_id' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'financial_type_id' => ['title' => ts('Financial Type')],
          'contribution_status_id' => ['title' => ts('Contribution Status')],
          'payment_instrument_id' => ['title' => ts('Payment Type')],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'trxn_id' => NULL,
          'receive_date' => NULL,
          'receipt_date' => NULL,
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'total_amount' => NULL,
        ],
        'filters' => [
          'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'payment_instrument_id' => [
            'title' => ts('Payment Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
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
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'total_amount' => ['title' => ts('Contribution Amount')],
        ],
        'order_bys' => [
          'receive_date' => [
            'title' => ts('Contribution Date'),
            'default_weight' => '2',
            'default_order' => 'DESC',
          ],
        ],
        'grouping' => 'contri-fields',
      ],
      'civicrm_contribution_recur' => [
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'fields' => [
          'autorenew_status_id' => [
            'name' => 'contribution_status_id',
            'title' => ts('Auto-Renew Subscription Status'),
          ],
        ],
        'filters' => [
          'autorenew_status_id' => [
            'name' => 'contribution_status_id',
            'title' => ts('Auto-Renew Subscription Status?'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => [0 => ts('None'), -1 => ts('Ended')] + CRM_Contribute_BAO_ContributionRecur::buildOptions('contribution_status_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'order_bys' => [
          'autorenew_status_id' => [
            'name' => 'contribution_status_id',
            'title' => ts('Auto-Renew Subscription Status'),
          ],
        ],
        'grouping' => 'member-fields',
      ],
    ] + $this->getAddressColumns([
      // These options are only excluded because they were not previously present.
      'order_by' => FALSE,
      'group_by' => FALSE,
    ]);
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    // If we have campaigns enabled, add those elements to both the fields, filters and sorting
    $this->addCampaignFields('civicrm_membership', FALSE, TRUE);

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function preProcess(): void {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }

  public function select() {
    parent::select();
    if (in_array('civicrm_contribution_recur_autorenew_status_id', $this->_selectAliases)) {
      // If we're getting auto-renew status we'll want to know if auto-renew has
      // ended.
      $this->_selectClauses[] = "{$this->_aliases['civicrm_contribution_recur']}.end_date as civicrm_contribution_recur_end_date";
      $this->_selectAliases[] = 'civicrm_contribution_recur_end_date';
      // Regenerate SELECT part of query
      $this->_select = "SELECT " . implode(', ', $this->_selectClauses) . " ";
      $this->_columnHeaders["civicrm_contribution_recur_end_date"] = [
        'title' => NULL,
        'type' => NULL,
        'no_display' => TRUE,
      ];
    }
  }

  public function from(): void {
    $this->setFromBase('civicrm_contact');
    $this->_from .= "
         {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    //used when contribution field is selected.
    if ($this->isTableSelected('civicrm_contribution')) {
      // if we're grouping (by membership), we need to make sure the inner join picks the most recent contribution.
      $groupedBy = !empty($this->_params['group_bys']['id']);
      $this->_from .= "
             LEFT JOIN civicrm_membership_payment cmp
                 ON ({$this->_aliases['civicrm_membership']}.id = cmp.membership_id";
      $this->_from .= $groupedBy ? "
                 AND cmp.id = (SELECT MAX(id) FROM civicrm_membership_payment WHERE civicrm_membership_payment.membership_id = {$this->_aliases['civicrm_membership']}.id))"
                 : ')';
      $this->_from .= "
             LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                 ON cmp.contribution_id={$this->_aliases['civicrm_contribution']}.id\n";
    }
    if ($this->isTableSelected('civicrm_contribution_recur')) {
      $this->_from .= <<<HERESQL
            LEFT JOIN civicrm_contribution_recur {$this->_aliases['civicrm_contribution_recur']}
                ON {$this->_aliases['civicrm_membership']}.contribution_recur_id = {$this->_aliases['civicrm_contribution_recur']}.id
HERESQL;
    }
  }

  /**
   * Override to add handling for autorenew status.
   */
  public function whereClause(&$field, $op, $value, $min, $max) {
    if ($field['dbAlias'] === "{$this->_aliases['civicrm_contribution_recur']}.contribution_status_id") {
      $clauseParts = [];
      switch ($op) {
        case 'in':
          if ($value !== NULL && is_array($value) && count($value) > 0) {
            $regularOptions = implode(', ', array_diff($value, [0, -1]));
            // None: is null
            if (in_array(0, $value)) {
              $clauseParts[] = "{$this->_aliases['civicrm_membership']}.contribution_recur_id IS NULL";
            }
            // Ended: not null, end_date in past
            if (in_array(-1, $value)) {
              $clauseParts[] = <<<HERESQL
                {$this->_aliases['civicrm_membership']}.contribution_recur_id IS NOT NULL
                  AND {$this->_aliases['civicrm_contribution_recur']}.end_date < NOW()
HERESQL;
            }
            // Normal statuses: IN()
            if (!empty($regularOptions)) {
              $clauseParts[] = "{$this->_aliases['civicrm_contribution_recur']}.contribution_status_id IN ($regularOptions)";
            }
            // Double parentheses b/c ORs should be treated as a group
            return '((' . implode(') OR (', $clauseParts) . '))';
          }
          return;

        case 'notin':
          if ($value !== NULL && is_array($value) && count($value) > 0) {
            $regularOptions = implode(', ', array_diff($value, [0, -1]));
            // None: is not null
            if (in_array(0, $value)) {
              $clauseParts[] = "{$this->_aliases['civicrm_membership']}.contribution_recur_id IS NOT NULL";
            }
            // Ended: null or end_date in future
            if (in_array(-1, $value)) {
              $clauseParts[] = <<<HERESQL
                {$this->_aliases['civicrm_membership']}.contribution_recur_id IS NULL
                  OR {$this->_aliases['civicrm_contribution_recur']}.end_date >= NOW()
                  OR {$this->_aliases['civicrm_contribution_recur']}.end_date IS NULL
HERESQL;
            }
            // Normal statuses: null or NOT IN()
            if (!empty($regularOptions)) {
              $clauseParts[] = <<<HERESQL
                {$this->_aliases['civicrm_membership']}.contribution_recur_id IS NULL
                  OR {$this->_aliases['civicrm_contribution_recur']}.contribution_status_id NOT IN ($regularOptions)
HERESQL;
            }
            return '(' . implode(') AND (', $clauseParts) . ')';
          }
          return;

        case 'nll':
          return "{$this->_aliases['civicrm_membership']}.contribution_recur_id IS NULL";

        case 'nnll':
          return "{$this->_aliases['civicrm_membership']}.contribution_recur_id IS NOT NULL";
      }
    }
    else {
      return parent::whereClause($field, $op, $value, $min, $max);
    }
  }

  public function getOperationPair($type = 'string', $fieldName = NULL): array {
    //re-name IS NULL/IS NOT NULL for clarity
    if ($fieldName === 'owner_membership_id') {
      $result = [];
      $result[''] = ts('Any');
      $result['nll'] = ts('Primary members only');
      $result['nnll'] = ts('Non-primary members only');
    }
    else {
      $result = parent::getOperationPair($type, $fieldName);
    }
    return $result;
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
  public function alterDisplay(&$rows): void {
    $entryFound = FALSE;
    $checkList = [];

    $repeatFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if ($repeatFound == FALSE ||
        $repeatFound < $rowNum - 1
      ) {
        unset($checkList);
        $checkList = [];
      }
      if (!empty($this->_noRepeats) && $this->_outputMode !== 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        foreach ($row as $colName => $colVal) {
          if (in_array($colName, $this->_noRepeats) &&
            $rowNum > 0
          ) {
            if ($rows[$rowNum][$colName] == $rows[$rowNum - 1][$colName] ||
              (!empty($checkList[$colName]) &&
              in_array($colVal, $checkList[$colName]))
              ) {
              $rows[$rowNum][$colName] = '';
              // CRM-15917: Don't blank the name if it's a different contact
              if ($colName === 'civicrm_contact_exposed_id') {
                $rows[$rowNum]['civicrm_contact_sort_name'] = '';
              }
              $repeatFound = $rowNum;
            }
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
        $entryFound = TRUE;
      }

      $value = $row['civicrm_contribution_financial_type_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'financial_type_id', $value);
        $entryFound = TRUE;
      }
      $value = $row['civicrm_contribution_contribution_status_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $value);
        $entryFound = TRUE;
      }
      $value = $row['civicrm_contribution_payment_instrument_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $value);
        $entryFound = TRUE;
      }
      if ($value = $row['civicrm_contribution_recur_autorenew_status_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_contribution_recur_autorenew_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $value);
        if (!empty($row['civicrm_contribution_recur_end_date'])
          && strtotime($row['civicrm_contribution_recur_end_date']) < time()) {
          $ended = ts('ended');
          $rows[$rowNum]['civicrm_contribution_recur_autorenew_status_id'] .= " ($ended)";
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_membership_owner_membership_id', $row)) {
        $value = $row['civicrm_membership_owner_membership_id'];
        $rows[$rowNum]['civicrm_membership_owner_membership_id'] = ($value != '') ? 'Inherited' : 'Primary';
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      if (array_key_exists('civicrm_membership_campaign_id', $row)) {
        if ($value = $row['civicrm_membership_campaign_id']) {
          $rows[$rowNum]['civicrm_membership_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }
      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'member/detail', 'List all memberships(s) for this ') ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'member/detail', 'List all memberships(s) for this ') ? TRUE : $entryFound;

      if (!$entryFound) {
        break;
      }
    }
  }

}
