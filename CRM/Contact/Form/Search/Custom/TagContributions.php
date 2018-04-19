<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Contact_Form_Search_Custom_TagContributions extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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
    $this->_permissionedComponent = 'CiviContribute';

    /**
     * Define the columns for search result rows
     */
    $this->_columns = array(
      ts('Contact ID') => 'contact_id',
      ts('Full Name') => 'sort_name',
      ts('First Name') => 'first_name',
      ts('Last Name') => 'last_name',
      ts('Tag') => 'tag_name',
      ts('Totals') => 'amount',
    );
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Find Contribution Amounts by Tag');

    /**
     * Define the search form fields here
     */

    $form->addDate('start_date', ts('Contribution Date From'), FALSE, array('formatType' => 'custom'));
    $form->addDate('end_date', ts('...through'), FALSE, array('formatType' => 'custom'));
    $tag = array('' => ts('- any tag -')) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
    $form->addElement('select', 'tag', ts('Tagged'), $tag);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', array('start_date', 'end_date', 'tag'));
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Construct the search query.
   *
   * @param int $offset
   * @param int $rowcount
   * @param string $sort
   * @param bool $includeContactIDs
   * @param bool $onlyIDs
   *
   * @return string
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $onlyIDs = FALSE
  ) {

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      $select = "contact_a.id as contact_id";
    }
    else {
      $select = "
DISTINCT
contact_a.id as contact_id,
contact_a.sort_name as sort_name,
contact_a.first_name as first_name,
contact_a.last_name as last_name,
GROUP_CONCAT(DISTINCT civicrm_tag.name ORDER BY  civicrm_tag.name ASC ) as tag_name,
sum(civicrm_contribution.total_amount) as amount
";
    }
    $from = $this->from();

    $where = $this->where($includeContactIDs);

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
";

    $sql .= " GROUP BY contact_a.id";
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
      $sql .= "";
    }
    return $sql;
  }

  /**
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $from = "
      civicrm_contribution,
      civicrm_contact contact_a
      LEFT JOIN civicrm_entity_tag ON ( civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                                        civicrm_entity_tag.entity_id = contact_a.id )
      LEFT JOIN civicrm_tag ON civicrm_tag.id = civicrm_entity_tag.tag_id {$this->_aclFrom}
     ";
    return $from;
  }

  /*
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   *
   */
  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $clauses = array();

    $clauses[] = "contact_a.contact_type = 'Individual'";
    $clauses[] = "civicrm_contribution.contact_id = contact_a.id";

    $startDate = CRM_Utils_Date::processDate($this->_formValues['start_date']);
    if ($startDate) {
      $clauses[] = "civicrm_contribution.receive_date >= $startDate";
    }

    $endDate = CRM_Utils_Date::processDate($this->_formValues['end_date']);
    if ($endDate) {
      $clauses[] = "civicrm_contribution.receive_date <= $endDate";
    }

    $tag = CRM_Utils_Array::value('tag', $this->_formValues);
    if ($tag) {
      $clauses[] = "civicrm_entity_tag.tag_id = $tag";
      $clauses[] = "civicrm_tag.id = civicrm_entity_tag.tag_id";
    }
    else {
      $clauses[] = "civicrm_entity_tag.tag_id IS NOT NULL";
    }

    if ($includeContactIDs) {
      $contactIDs = array();
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
    if ($this->_aclWhere) {
      $clauses[] = " {$this->_aclWhere} ";
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

    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
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
