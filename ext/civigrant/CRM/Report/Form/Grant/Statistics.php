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
class CRM_Report_Form_Grant_Statistics extends CRM_Report_Form {

  protected $_customGroupExtends = ['Grant'];

  protected $_add2groupSupported = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [
      'civicrm_grant' => [
        'dao' => 'CRM_Grant_DAO_Grant',
        'fields' => [
          'summary_statistics' => [
            'name' => 'id',
            'title' => ts('Summary Statistics'),
            'required' => TRUE,
          ],
          'grant_type_id' => [
            'name' => 'grant_type_id',
            'title' => ts('By Grant Type'),
          ],
          'status_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'amount_total' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'grant_report_received' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'currency' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'application_received_date' => [
            'name' => 'application_received_date',
            'title' => ts('Application Received'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'decision_date' => [
            'name' => 'decision_date',
            'title' => ts('Grant Decision'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'money_transfer_date' => [
            'name' => 'money_transfer_date',
            'title' => ts('Money Transferred'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'grant_due_date' => [
            'name' => 'grant_due_date',
            'title' => ts('Grant Report Due'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'grant_type' => [
            'name' => 'grant_type_id',
            'title' => ts('Grant Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Grant_DAO_Grant::buildOptions('grant_type_id'),
          ],
          'status_id' => [
            'name' => 'status_id',
            'title' => ts('Grant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Grant_DAO_Grant::buildOptions('status_id'),
          ],
          'amount_requested' => [
            'name' => 'amount_requested',
            'title' => ts('Amount Requested'),
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'amount_granted' => [
            'name' => 'amount_granted',
            'title' => ts('Amount Granted'),
          ],
          'grant_report_received' => [
            'name' => 'grant_report_received',
            'title' => ts('Report Received'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              '' => ts('- select -'),
              0 => ts('No'),
              1 => ts('Yes'),
            ],
          ],
        ],
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'gender_id' => [
            'name' => 'gender_id',
            'title' => ts('By Gender'),
          ],
          'contact_type' => [
            'name' => 'contact_type',
            'title' => ts('By Contact Type'),
          ],
        ],
        'filters' => [
          'gender_id' => [
            'name' => 'gender_id',
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_DAO_Contact::buildOptions('gender_id'),
          ],
          'contact_type' => [
            'name' => 'contact_type',
            'title' => ts('Contact Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_ContactType::basicTypePairs(),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_worldregion' => [
        'dao' => 'CRM_Core_DAO_Worldregion',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
          ],
          'name' => [
            'name' => 'name',
            'title' => ts('By World Region'),
          ],
        ],
        'filters' => [
          'region_id' => [
            'name' => 'id',
            'title' => ts('World Region'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::worldRegion(),
          ],
        ],
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'country_id' => [
            'name' => 'country_id',
            'title' => ts('By Country'),
          ],
        ],
        'filters' => [
          'country_id' => [
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ],
        ],
      ],
    ];
    parent::__construct();
  }

  public function select() {
    $select = [];

    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->_from = "
        FROM civicrm_grant {$this->_aliases['civicrm_grant']}
                        LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON ({$this->_aliases['civicrm_grant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  ) ";

    $this->joinAddressFromContact();
    $this->joinCountryFromAddress();
    if ($this->isTableSelected('civicrm_worldregion')) {
      $this->_from .= "
                  LEFT JOIN civicrm_worldregion {$this->_aliases['civicrm_worldregion']}
                         ON {$this->_aliases['civicrm_country']}.region_id =
                            {$this->_aliases['civicrm_worldregion']}.id";
    }
  }

  public function where() {
    $whereClause = "
WHERE {$this->_aliases['civicrm_grant']}.amount_total IS NOT NULL
  AND {$this->_aliases['civicrm_grant']}.amount_total > 0";
    $this->_where = $whereClause;

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {

          $clause = NULL;
          if (($field['type'] ?? NULL) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if (($fieldName == 'grant_report_received') &&
              (($this->_params["{$fieldName}_value"] ?? NULL) === 0)
            ) {
              $op = 'nll';
              $this->_params["{$fieldName}_value"] = NULL;
            }
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                $this->_params["{$fieldName}_value"] ?? NULL,
                $this->_params["{$fieldName}_min"] ?? NULL,
                $this->_params["{$fieldName}_max"] ?? NULL
              );
            }
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    if (!empty($clauses)) {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
      $this->_whereClause = $whereClause . " AND " . implode(' AND ', $clauses);
    }
  }

  public function groupBy() {
    $this->_groupBy = '';

    if (!empty($this->_params['fields']) &&
      is_array($this->_params['fields']) &&
      !empty($this->_params['fields'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('fields', $table)) {
          foreach ($table['fields'] as $fieldName => $field) {
            if (!empty($this->_params['fields'][$fieldName])) {
              $groupBy[] = $field['dbAlias'];
            }
          }
        }
      }
    }
    if (!empty($groupBy)) {
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
    }
  }

  public function preProcess() {
    \Civi::resources()->addBundle('visual');
    parent::preProcess();
  }

  public function postProcess() {
    // get ready with post process params
    $this->beginPostProcess();

    // build query, do not apply limit
    $sql = $this->buildQuery(FALSE);

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
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
    $totalStatistics = $grantStatistics = [];
    $totalStatistics = parent::statistics($rows);
    $awardedGrantsAmount = $grantsReceived = $totalAmount = $awardedGrants = $grantReportsReceived = 0;
    $grantStatistics = [];

    $grantTypes = CRM_Grant_DAO_Grant::buildOptions('grant_type_id');
    $countries = CRM_Core_PseudoConstant::country();
    $gender = CRM_Contact_DAO_Contact::buildOptions('gender_id');

    $grantAmountTotal = "
SELECT COUNT({$this->_aliases['civicrm_grant']}.id) as count ,
         SUM({$this->_aliases['civicrm_grant']}.amount_total) as totalAmount
  {$this->_from} ";

    if (!empty($this->_whereClause)) {
      $grantAmountTotal .= " {$this->_whereClause}";
    }

    $result = CRM_Core_DAO::executeQuery($grantAmountTotal);
    while ($result->fetch()) {
      $grantsReceived = $result->count;
      $totalAmount = $result->totalAmount;
    }

    if (!$grantsReceived) {
      return;
    }

    $grantAmountAwarded = "
SELECT COUNT({$this->_aliases['civicrm_grant']}.id) as count ,
         SUM({$this->_aliases['civicrm_grant']}.amount_granted) as grantedAmount,
         SUM({$this->_aliases['civicrm_grant']}.amount_total) as totalAmount
  {$this->_from} ";

    if (!empty($this->_where)) {
      $grantAmountAwarded .= " {$this->_where}";
    }
    $values = CRM_Core_DAO::executeQuery($grantAmountAwarded);
    while ($values->fetch()) {
      $awardedGrants = $values->count;
      $awardedGrantsAmount = $values->totalAmount;
      $amountGranted = $values->grantedAmount;
    }

    foreach ($rows as $key => $values) {
      if (!empty($values['civicrm_grant_grant_report_received'])) {
        $grantReportsReceived++;
      }

      if (!empty($values['civicrm_grant_grant_type_id'])) {
        $grantType = $grantTypes[$values['civicrm_grant_grant_type_id']] ?? NULL;
        $grantStatistics['civicrm_grant_grant_type_id']['title'] = ts('By Grant Type');
        self::getStatistics($grantStatistics['civicrm_grant_grant_type_id'], $grantType, $values,
          $awardedGrants, $awardedGrantsAmount
        );
      }

      if (array_key_exists('civicrm_worldregion_name', $values)) {
        $region = $values['civicrm_worldregion_name'] ?: 'Unassigned';
        $grantStatistics['civicrm_worldregion_name']['title'] = ts('By Region');
        self::getStatistics($grantStatistics['civicrm_worldregion_name'], $region, $values,
          $awardedGrants, $awardedGrantsAmount
        );
      }

      if (array_key_exists('civicrm_address_country_id', $values)) {
        $country = $countries[$values['civicrm_address_country_id']] ?? 'Unassigned';
        $grantStatistics['civicrm_address_country_id']['title'] = ts('By Country');
        self::getStatistics($grantStatistics['civicrm_address_country_id'], $country, $values,
          $awardedGrants, $awardedGrantsAmount
        );
      }

      $type = $values['civicrm_contact_contact_type'] ?? NULL;
      if ($type) {
        $grantStatistics['civicrm_contact_contact_type']['title'] = ts('By Contact Type');
        $title = "Total Number of {$type}(s)";
        self::getStatistics($grantStatistics['civicrm_contact_contact_type'], $title, $values,
          $awardedGrants, $awardedGrantsAmount
        );
      }

      if (array_key_exists('civicrm_contact_gender_id', $values)) {
        $genderLabel = $gender[$values['civicrm_contact_gender_id']] ?? 'Unassigned';
        $grantStatistics['civicrm_contact_gender_id']['title'] = ts('By Gender');
        self::getStatistics($grantStatistics['civicrm_contact_gender_id'], $genderLabel, $values,
          $awardedGrants, $awardedGrantsAmount
        );
      }

      foreach ($values as $customField => $customValue) {
        if (str_contains($customField, 'civicrm_value_')) {
          $customFieldTitle = $this->_columnHeaders[$customField]['title'] ?? NULL;
          $customGroupTitle = explode('_custom', strstr($customField, 'civicrm_value_'));
          $customGroupTitle = $this->_columns[$customGroupTitle[0]]['group_title'];
          $grantStatistics[$customGroupTitle]['title'] = ts('By %1', [1 => $customGroupTitle]);

          self::getStatistics($grantStatistics[$customGroupTitle], $customFieldTitle, $values,
            $awardedGrants, $awardedGrantsAmount, !$customValue
          );
        }
      }
    }

    $totalStatistics['total_statistics'] = [
      'grants_received' => [
        'title' => ts('Grant Requests Received'),
        'count' => $grantsReceived,
        'amount' => $totalAmount,
      ],
      'grants_awarded' => [
        'title' => ts('Grants Awarded'),
        'count' => $awardedGrants,
        'amount' => $amountGranted,
      ],
      'grants_report_received' => [
        'title' => ts('Grant Reports Received'),
        'count' => $grantReportsReceived,
      ],
    ];

    $this->assign('totalStatistics', $totalStatistics);
    $this->assign('grantStatistics', $grantStatistics);

    if ($this->_outputMode == 'csv' ||
      $this->_outputMode == 'pdf'
    ) {
      $row = [];
      $this->_columnHeaders = [
        'civicrm_grant_total_grants' => ['title' => ts('Summary')],
        'civicrm_grant_count' => ['title' => ts('Count')],
        'civicrm_grant_amount' => ['title' => ts('Amount')],
      ];
      foreach ($totalStatistics['total_statistics'] as $title => $value) {
        $row[] = [
          'civicrm_grant_total_grants' => $value['title'],
          'civicrm_grant_count' => $value['count'],
          'civicrm_grant_amount' => $value['amount'],
        ];
      }

      if (!empty($grantStatistics)) {
        foreach ($grantStatistics as $key => $value) {
          $row[] = [
            'civicrm_grant_total_grants' => $value['title'],
            'civicrm_grant_count' => ts('Number of Grants') . ' (%)',
            'civicrm_grant_amount' => ts('Total Amount') . ' (%)',
          ];

          foreach ($value['value'] as $field => $values) {
            foreach ($values['currency'] as $currency => $amount) {
              $totalAmount[$currency] = $currency . $amount['value'] .
                "({$values['percentage']}%)";
            }
            $totalAmt = implode(', ', $totalAmount);
            $count = empty($values['count']) ? '' : "{$values['count']} ({$values['percentage']}%)";
            $row[] = [
              'civicrm_grant_total_grants' => $field,
              'civicrm_grant_count' => $count,
              'civicrm_grant_amount' => $totalAmt,
            ];
          }
        }
      }
      $rows = $row;
    }
  }

  /**
   * @param $grantStatistics
   * @param $fieldValue
   * @param $values
   * @param $awardedGrants
   * @param $awardedGrantsAmount
   * @param bool $customData
   */
  public static function getStatistics(
    &$grantStatistics, $fieldValue, $values,
    $awardedGrants, $awardedGrantsAmount, $customData = FALSE
  ) {
    if (!$awardedGrantsAmount) {
      return;
    }

    $currency = CRM_Core_PseudoConstant::getName('CRM_Grant_DAO_Grant', 'currency', $values['civicrm_grant_currency']);

    if (!$customData) {
      if (!isset($grantStatistics['value'][$fieldValue]['currency'][$currency])
        ||
        !isset($grantStatistics['value'][$fieldValue]['currency'][$currency]['value'])
      ) {
        $grantStatistics['value'][$fieldValue]['currency'][$currency]['value'] = 0;
      }
      $grantStatistics['value'][$fieldValue]['currency'][$currency]['value'] += $values['civicrm_grant_amount_total'];
      $grantStatistics['value'][$fieldValue]['currency'][$currency]['percentage'] = round(($grantStatistics['value'][$fieldValue]['currency'][$currency]['value'] /
          $awardedGrantsAmount) * 100);
      if (!isset($grantStatistics['value'][$fieldValue]['count'])) {
        $grantStatistics['value'][$fieldValue]['count'] = 0;
      }
      $grantStatistics['value'][$fieldValue]['count']++;
      $grantStatistics['value'][$fieldValue]['percentage'] = round(($grantStatistics['value'][$fieldValue]['count'] /
          $awardedGrants) * 100);
    }
    else {
      if (!isset($grantStatistics['value'][$fieldValue]['unassigned_currency'][$currency])
        ||
        !isset($grantStatistics['value'][$fieldValue]['unassigned_currency'][$currency]['value'])
      ) {
        $grantStatistics['value'][$fieldValue]['unassigned_currency'][$currency]['value'] = 0;
      }
      $grantStatistics['value'][$fieldValue]['unassigned_currency'][$currency]['value'] += $values['civicrm_grant_amount_total'];
      $grantStatistics['value'][$fieldValue]['unassigned_currency'][$currency]['percentage'] = round(($grantStatistics['value'][$fieldValue]['unassigned_currency'][$currency]['value'] /
          $awardedGrantsAmount) * 100);
      $grantStatistics['value'][$fieldValue]['unassigned_count']++;
      $grantStatistics['value'][$fieldValue]['unassigned_percentage'] = round(($grantStatistics['value'][$fieldValue]['unassigned_count'] /
          $awardedGrants) * 100);
    }
  }

}
