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
class CRM_Contact_Form_Search_Custom_ZipCodeRange extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = [
      // If possible, don't use aliases for the columns you select.
      // You can prefix columns with table aliases, if needed.
      //
      // If you don't do this, selecting individual records from the
      // custom search result won't work if your results are sorted on the
      // aliased colums.
      // (This is why we map Contact ID on contact_a.id, and not on contact_id).
      ts('Contact ID') => 'contact_a.id',
      ts('Name') => 'sort_name',
      ts('Email') => 'email',
      ts('Zip') => 'postal_code',
    ];
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $form->add('text',
      'postal_code_low',
      ts('Postal Code Start')
    );

    $form->add('text',
      'postal_code_high',
      ts('Postal Code End')
    );

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Zip Code Range Search'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', ['postal_code_low', 'postal_code_high']);
  }

  /**
   * @return array
   */
  public function summary() {
    return [];
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
      // Don't change sort order when $justIDs is TRUE, see CRM-14920.
    }
    else {
      // We select contact_a.id twice. Once as contact_a.id,
      // because it is used to fill the prevnext_cache. And once
      // as contact_a.id, for the patch of CRM-16587 to work when
      // the results are sorted on contact ID.
      $selectClause = "
contact_a.id           as contact_id ,
contact_a.id           as id ,
contact_a.sort_name    as sort_name  ,
email.email            as email   ,
address.postal_code    as postal_code
";
    }
    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  /**
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $from = "
FROM      civicrm_contact contact_a
LEFT JOIN civicrm_address address ON ( address.contact_id       = contact_a.id AND
                                       address.is_primary       = 1 )
LEFT JOIN civicrm_email   email   ON ( email.contact_id = contact_a.id AND
                                       email.is_primary = 1 ) {$this->_aclFrom}
";
    return $from;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $low = $this->_formValues['postal_code_low'] ?? NULL;
    $high = $this->_formValues['postal_code_high'] ?? NULL;
    $errorMessage = NULL;
    if ($low == NULL || $high == NULL) {
      $errorMessage = ts('Please provide start and end postal codes.');
    }

    if (!is_numeric($low) || !is_numeric($high)) {
      $errorMessage = ts('This search only supports numeric postal codes.');
    }
    if ($errorMessage) {
      CRM_Core_Error::statusBounce($errorMessage,
        CRM_Utils_System::url('civicrm/contact/search/custom',
          "reset=1&csid={$this->_formValues['customSearchID']}",
          FALSE, NULL, FALSE, TRUE
        )
      );
    }

    $where = "ROUND(address.postal_code) >= %1 AND ROUND(address.postal_code) <= %2";
    $params = [
      1 => [trim($low), 'Integer'],
      2 => [trim($high), 'Integer'],
    ];

    if ($this->_aclWhere) {
      $where .= " AND {$this->_aclWhere} ";
    }
    return $this->whereClause($where, $params);
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
