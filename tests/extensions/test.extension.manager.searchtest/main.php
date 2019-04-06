<?php

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

/**
 * Class test_extension_manager_searchtest
 */
class test_extension_manager_searchtest extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  /**
   * @param $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields.
   *
   * @param CRM_Core_Form $form
   *   Modifiable.
   * @return void
   */
  public function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('My Search Title'));

    $form->add('text',
      'household_name',
      ts('Household Name'),
      TRUE
    );

    $stateProvince = array('' => ts('- any state/province -')) + CRM_Core_PseudoConstant::stateProvince();
    $form->addElement('select', 'state_province_id', ts('State/Province'), $stateProvince);

    // Optionally define default search values
    $form->setDefaults(array(
      'household_name' => '',
      'state_province_id' => NULL,
    ));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('household_name', 'state_province_id'));
  }

  /**
   * Get a list of summary data points.
   *
   * @return mixed
   *   - NULL or array with keys:
   *     - summary: string
   *     - total: numeric
   */
  public function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns.
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  public function &columns() {
    // return by reference
    $columns = array(
      ts('Contact ID') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('State') => 'state_province',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   *
   * @return string, sql
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause.
   *
   * @return string, sql fragment with SELECT arguments
   */
  public function select() {
    return "
      contact_a.id           as contact_id  ,
      contact_a.contact_type as contact_type,
      contact_a.sort_name    as sort_name,
      state_province.name    as state_province
    ";
  }

  /**
   * Construct a SQL FROM clause.
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  public function from() {
    return "
      FROM      civicrm_contact contact_a
      LEFT JOIN civicrm_address address ON ( address.contact_id       = contact_a.id AND
                                             address.is_primary       = 1 )
      LEFT JOIN civicrm_email           ON ( civicrm_email.contact_id = contact_a.id AND
                                             civicrm_email.is_primary = 1 )
      LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id
    ";
  }

  /**
   * Construct a SQL WHERE clause.
   *
   * @param bool $includeContactIDs
   *
   * @return string, sql fragment with conditional expressions
   */
  public function where($includeContactIDs = FALSE) {
    $params = array();
    $where = "contact_a.contact_type   = 'Household'";

    $count  = 1;
    $clause = array();
    $name   = CRM_Utils_Array::value('household_name',
      $this->_formValues
    );
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.household_name LIKE %{$count}";
      $count++;
    }

    $state = CRM_Utils_Array::value('state_province_id',
      $this->_formValues
    );
    if (!$state &&
      $this->_stateID
    ) {
      $state = $this->_stateID;
    }

    if ($state) {
      $params[$count] = array($state, 'Integer');
      $clause[] = "state_province.id = %{$count}";
    }

    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen.
   *
   * @return string, template path (findable through Smarty template path)
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row.
   *
   * @param array $row
   *   Modifiable SQL result row.
   * @return void
   */
  public function alterRow(&$row) {
    $row['sort_name'] .= ' ( altered )';
  }

}
