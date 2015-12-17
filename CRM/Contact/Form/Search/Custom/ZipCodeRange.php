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

    $this->_columns = array(
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
    );
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $form->add('text',
      'postal_code_low',
      ts('Postal Code Start'),
      TRUE
    );

    $form->add('text',
      'postal_code_high',
      ts('Postal Code End'),
      TRUE
    );

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Zip Code Range Search');

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('postal_code_low', 'postal_code_high'));
  }

  /**
   * @return array
   */
  public function summary() {
    $summary = array();
    return $summary;
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
    $params = array();

    $low = CRM_Utils_Array::value('postal_code_low',
      $this->_formValues
    );
    $high = CRM_Utils_Array::value('postal_code_high',
      $this->_formValues
    );
    if ($low == NULL || $high == NULL) {
      CRM_Core_Error::statusBounce(ts('Please provide start and end postal codes'),
        CRM_Utils_System::url('civicrm/contact/search/custom',
          "reset=1&csid={$this->_formValues['customSearchID']}",
          FALSE, NULL, FALSE, TRUE
        )
      );
    }

    $where = "ROUND(address.postal_code) >= %1 AND ROUND(address.postal_code) <= %2";
    $params = array(
      1 => array(trim($low), 'Integer'),
      2 => array(trim($high), 'Integer'),
    );

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
   * @param $title
   */
  public function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
