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
class CRM_Contact_Form_Search_Custom_PostalMailing extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
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
      // The only alias you can (and should!) use in the
      // columns, is contact_id. Because contact_id is used
      // in a lot of places, and it seems to be important that
      // it is called contact_id_a.
      //
      // For the other fields, if possible, you should prefix
      // their names with the table alias you are selecting them
      // from. This way, you can work around CRM-16587.
      //
      // This approach wil not work if you want to select fields
      // with the same name from different tables, this will generate
      // an invalid query somewhere. In that case, you can
      // use column aliases in your SELECT clause and in the array
      // below, but you will still hit CRM-16587 when sorting on these
      // fields.
      ts('Contact ID') => 'contact_id',
      ts('Address') => 'address.street_address',
      ts('Contact Type') => 'contact_a.contact_type',
      ts('Name') => 'contact_a.sort_name',
      ts('State') => 'state_province.name',
    );
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $groups = array('' => ts('- select group -')) + CRM_Core_PseudoConstant::nestedGroup(FALSE);
    $form->addElement('select', 'group_id', ts('Group'), $groups, array('class' => 'crm-select2 huge'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('group_id'));
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
      //$sort = 'contact_a.id';
    }
    else {
      $selectClause = "
DISTINCT contact_a.id  as contact_id,
contact_a.contact_type,
contact_a.sort_name,
address.street_address,
state_province.name
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
FROM      civicrm_group_contact as cgc,
          civicrm_contact       as contact_a
LEFT JOIN civicrm_address address               ON (address.contact_id       = contact_a.id AND
                                                    address.is_primary       = 1 )
LEFT JOIN civicrm_state_province state_province ON  state_province.id = address.state_province_id {$this->_aclFrom}
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

    $count = 1;
    $clause = array();
    $groupID = CRM_Utils_Array::value('group_id',
      $this->_formValues
    );
    if ($groupID) {
      $params[$count] = array($groupID, 'Integer');
      $clause[] = "cgc.group_id = %{$count}";
    }

    $clause[] = "cgc.status   = 'Added'";
    $clause[] = "contact_a.id = IF( EXISTS(select cr.id from civicrm_relationship cr where (cr.contact_id_a = cgc.contact_id AND (cr.relationship_type_id = 7 OR cr.relationship_type_id = 6))),
                                       (select cr.contact_id_b from civicrm_relationship cr where (cr.contact_id_a = cgc.contact_id AND (cr.relationship_type_id = 7 OR cr.relationship_type_id = 6))),
                                        cgc.contact_id )";
    $clause[] = "contact_a.contact_type IN ('Individual','Household')";

    if ($this->_aclWhere) {
      $clause[] = " {$this->_aclWhere} ";
    }

    if (!empty($clause)) {
      $where = implode(' AND ', $clause);
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
