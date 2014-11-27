<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_PostalMailing extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  /**
   * @param $formValues
   */
  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_columns = array(
      ts('Contact ID') => 'contact_id',
      ts('Address') => 'address',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('State') => 'state_province',
    );
  }

  /**
   * @param $form
   */
  function buildForm(&$form) {
    $groups = array('' => ts('- select group -')) + CRM_Core_PseudoConstant::nestedGroup(FALSE);
    $form->addElement('select', 'group_id', ts('Group'), $groups, array('class' => 'crm-select2 huge'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('group_id'));
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
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
  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
      $sort = 'contact_a.id';
    }
    else {
    $selectClause = "
DISTINCT contact_a.id  as contact_id  ,
contact_a.contact_type  as contact_type,
contact_a.sort_name     as sort_name,
address.street_address  as address,
state_province.name     as state_province
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
  function from() {
    return "
FROM      civicrm_group_contact as cgc,
          civicrm_contact       as contact_a
LEFT JOIN civicrm_address address               ON (address.contact_id       = contact_a.id AND
                                                    address.is_primary       = 1 )
LEFT JOIN civicrm_state_province state_province ON  state_province.id = address.state_province_id
";
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  function where($includeContactIDs = FALSE) {
    $params = array();

    $count   = 1;
    $clause  = array();
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

    if (!empty($clause)) {
      $where = implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  /**
   * @return string
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }
}

