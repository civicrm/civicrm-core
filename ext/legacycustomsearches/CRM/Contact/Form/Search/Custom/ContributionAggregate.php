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
class CRM_Contact_Form_Search_Custom_ContributionAggregate extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;
  public $_permissionedComponent;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_formValues = $formValues;

    // Define the columns for search result rows
    $this->_columns = [
      ts('Contact ID') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Contribution Count') => 'donation_count',
      ts('Contribution Amount') => 'donation_amount',
    ];

    // define component access permission needed
    $this->_permissionedComponent = 'CiviContribute';
  }

  /**
   * Build form.
   *
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $form->addSearchFieldMetadata(['Contribution' => self::getSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Find Contributors by Aggregate Totals'));

    /**
     * Define the search form fields here
     */
    $form->add('text',
      'min_amount',
      ts('Aggregate Total Between $')
    );
    $form->addRule('min_amount', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');

    $form->add('text',
      'max_amount',
      ts('...and $')
    );
    $form->addRule('max_amount', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');

    $form->addSelect('financial_type_id',
      ['entity' => 'contribution', 'multiple' => 'multiple', 'context' => 'search']
    );

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', ['min_amount', 'max_amount']);
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   *
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/ContributionAggregate.tpl';
  }

  /**
   * Construct the search query.
   *
   * @param int $offset
   * @param int $rowcount
   * @param string|object $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($justIDs) {
      $select = "contact_a.id as contact_id";
    }
    else {
      $select = "
DISTINCT contact_a.id as contact_id,
contact_a.sort_name as sort_name,
sum(contrib.total_amount) AS donation_amount,
count(contrib.id) AS donation_count
";
    }
    $from = $this->from();

    $where = $this->where($includeContactIDs);

    $having = $this->having();
    if ($having) {
      $having = " HAVING $having ";
    }

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY contact_a.id
$having
";
    //for only contact ids ignore order.
    if (!$justIDs) {
      // Define ORDER BY for query in $sort, with default value
      if (!empty($sort)) {
        if (is_string($sort)) {
          $sort = CRM_Utils_Type::escape($sort, 'String');
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= " ORDER BY " . trim($sort->orderBy());
        }
      }
      else {
        $sql .= "ORDER BY donation_amount desc";
      }
    }

    if ($rowcount > 0 && $offset >= 0) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowcount = CRM_Utils_Type::escape($rowcount, 'Int');
      $sql .= " LIMIT $offset, $rowcount ";
    }
    return $sql;
  }

  /**
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $from = "
civicrm_contribution AS contrib,
civicrm_contact AS contact_a {$this->_aclFrom}
";

    return $from;
  }

  /**
   * Get the metadata for fields to be included on the contact search form.
   */
  public static function getSearchFieldMetadata() {
    $fields = [
      'receive_date' => ['title' => ''],
    ];
    $metadata = civicrm_api3('Contribution', 'getfields', [])['values'];
    foreach ($fields as $fieldName => $field) {
      $fields[$fieldName] = array_merge($metadata[$fieldName] ?? [], $field);
    }
    return $fields;
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $contributionCompletedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    $clauses = [
      "contrib.contact_id = contact_a.id",
      "contrib.is_test = 0",
      "contrib.is_template = 0",
      "contrib.contribution_status_id = " . intval($contributionCompletedStatusId),
    ];

    foreach ([
      'receive_date_relative',
      'receive_date_low',
      'receive_date_high',
    ] as $dateFieldName) {
      $dateParams[$dateFieldName] = $this->_formValues[$dateFieldName] ?? NULL;
    }

    if ($dateParams['receive_date_relative']) {
      list($relativeFrom, $relativeTo) = CRM_Utils_Date::getFromTo($dateParams['receive_date_relative'], $dateParams['receive_date_low'], $dateParams['receive_date_high']);
    }
    else {
      if (strlen($dateParams['receive_date_low']) == 10) {
        $relativeFrom = $dateParams['receive_date_low'] . ' 00:00:00';
      }
      else {
        $relativeFrom = $dateParams['receive_date_low'];
      }
      if (strlen($dateParams['receive_date_high']) == 10) {
        $relativeTo = $dateParams['receive_date_high'] . ' 23:59:59';
      }
      else {
        $relativeTo = $dateParams['receive_date_high'];
      }
    }

    if ($relativeFrom) {
      $clauses[] = "contrib.receive_date >= '{$relativeFrom}'";
    }
    if ($relativeTo) {
      $clauses[] = "contrib.receive_date <= '{$relativeTo}'";
    }

    if ($includeContactIDs) {
      $contactIDs = [];
      foreach ($this->_formValues as $id => $value) {
        if ($value &&
          substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = implode(', ', $contactIDs);
        $clauses[] = "contact_a.id IN ( $contactIDs )";
      }
    }

    if (!empty($this->_formValues['financial_type_id'])) {
      $financial_type_ids = implode(',', array_values($this->_formValues['financial_type_id']));
      $clauses[] = "contrib.financial_type_id IN ($financial_type_ids)";
    }
    if ($this->_aclWhere) {
      $clauses[] = " {$this->_aclWhere} ";
    }

    return implode(' AND ', $clauses);
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function having($includeContactIDs = FALSE) {
    $clauses = [];
    $min = $this->_formValues['min_amount'] ?? NULL;
    if ($min) {
      $min = CRM_Utils_Rule::cleanMoney($min);
      $clauses[] = "sum(contrib.total_amount) >= $min";
    }

    $max = $this->_formValues['max_amount'] ?? NULL;
    if ($max) {
      $max = CRM_Utils_Rule::cleanMoney($max);
      $clauses[] = "sum(contrib.total_amount) <= $max";
    }

    return implode(' AND ', $clauses);
  }

  /*
   * Functions below generally don't need to be modified
   */

  /**
   * @inheritDoc
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL Not used; included for consistency with parent; SQL is always returned
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = TRUE) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * @return null
   */
  public function summary() {
    return NULL;
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
