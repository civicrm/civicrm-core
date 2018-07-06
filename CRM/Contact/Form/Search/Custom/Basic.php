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
class CRM_Contact_Form_Search_Custom_Basic extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_query;
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
      '' => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Address') => 'street_address',
      ts('City') => 'city',
      ts('State') => 'state_province',
      ts('Postal') => 'postal_code',
      ts('Country') => 'country',
      ts('Email') => 'email',
      ts('Phone') => 'phone',
    );

    $params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $returnProperties = array();
    $returnProperties['contact_sub_type'] = 1;

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    foreach ($this->_columns as $name => $field) {
      if (in_array($field, array(
          'street_address',
          'city',
          'state_province',
          'postal_code',
          'country',
        )) && empty($addressOptions[$field])
      ) {
        unset($this->_columns[$name]);
        continue;
      }
      $returnProperties[$field] = 1;
    }

    $this->_query = new CRM_Contact_BAO_Query($params, $returnProperties, NULL,
      FALSE, FALSE, 1, FALSE, FALSE
    );
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $contactTypes = array('' => ts('- any contact type -')) + CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type', ts('Find...'), $contactTypes, FALSE, array('class' => 'crm-select2 huge'));

    // add select for groups
    $group = array('' => ts('- any group -')) + CRM_Core_PseudoConstant::nestedGroup();
    $form->addElement('select', 'group', ts('in'), $group, array('class' => 'crm-select2 huge'));

    // add select for categories
    $tag = array('' => ts('- any tag -')) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
    $form->addElement('select', 'tag', ts('Tagged'), $tag, array('class' => 'crm-select2 huge'));

    // text for sort_name
    $form->add('text', 'sort_name', ts('Name'));

    $form->assign('elements', array('sort_name', 'contact_type', 'group', 'tag'));
  }

  /**
   * @return CRM_Contact_DAO_Contact
   */
  public function count() {
    return $this->_query->searchQuery(0, 0, NULL, TRUE);
  }

  /**
   * @param int $offset
   * @param int $rowCount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return CRM_Contact_DAO_Contact
   */
  public function all(
    $offset = 0,
    $rowCount = 0,
    $sort = NULL,
    $includeContactIDs = FALSE,
    $justIDs = FALSE
  ) {
    return $this->_query->searchQuery(
      $offset,
      $rowCount,
      $sort,
      FALSE,
      $includeContactIDs,
      FALSE,
      $justIDs,
      TRUE
    );
  }

  /**
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $from = $this->_query->_fromClause;
    $from .= "{$this->_aclFrom}";
    return $from;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string|void
   */
  public function where($includeContactIDs = FALSE) {
    if ($whereClause = $this->_query->whereClause()) {
      if ($this->_aclWhere) {
        $whereClause .= " AND {$this->_aclWhere}";
      }
      return $whereClause;
    }
    return ' (1) ';
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Basic.tpl';
  }

  /**
   * @return CRM_Contact_BAO_Query
   */
  public function getQueryObj() {
    return $this->_query;
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
