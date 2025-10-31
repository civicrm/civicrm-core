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
class CRM_Contact_Form_Search_Custom_MultipleValues extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_groupTree;
  protected $_tables;
  protected $_options;
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;
  protected $fieldInfo = [];

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree("'Contact', 'Individual', 'Organization', 'Household'", NULL, NULL, -1,
      [],
      NULL,
      TRUE,
      NULL,
      FALSE,
      CRM_Core_Permission::VIEW
    );

    $this->_group = $this->_formValues['group'] ?? NULL;

    $this->_tag = $this->_formValues['tag'] ?? NULL;

    $this->_columns = [
      ts('Contact ID') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
    ];

    $this->_customGroupIDs = $formValues['custom_group'] ?? NULL;

    if (!empty($this->_customGroupIDs)) {
      $this->addColumns();
    }
  }

  /**
   * Add all the fields for chosen groups
   */
  public function addColumns() {
    $this->_tables = [];
    foreach ($this->_groupTree as $groupID => $group) {
      if (empty($this->_customGroupIDs[$groupID])) {
        continue;
      }

      $this->fieldInfo += $group['fields'];

      // now handle all the fields
      foreach ($group['fields'] as $fieldID => $field) {
        $this->_columns[$field['label']] = "custom_{$field['id']}";
        if (!array_key_exists($group['table_name'], $this->_tables)) {
          $this->_tables[$group['table_name']] = [];
        }
        $this->_tables[$group['table_name']][$field['id']] = $field['column_name'];
      }
    }
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    $this->setTitle(ts('Multiple Value Custom Group Search and Export'));

    $form->add('text', 'sort_name', ts('Contact Name'));

    $contactTypes = ['' => ts('- any contact type -')] + CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type', ts('Find...'), $contactTypes, ['class' => 'crm-select2 huge']);

    // add select for groups
    $group = ['' => ts('- any group -')] + CRM_Core_PseudoConstant::group();
    $form->addElement('select', 'group', ts('in'), $group, ['class' => 'crm-select2 huge']);

    // add select for tags
    $tag = ['' => ts('- any tag -')] + CRM_Core_DAO_EntityTag::buildOptions('tag_id', 'get');
    $form->addElement('select', 'tag', ts('Tagged'), $tag, ['class' => 'crm-select2 huge']);

    if (empty($this->_groupTree)) {
      CRM_Core_Error::statusBounce(ts("Atleast one Custom Group must be present, for Custom Group search."),
        CRM_Utils_System::url('civicrm/contact/search/custom/list',
          'reset=1'
        )
      );
    }
    // add the checkbox for custom_groups
    foreach ($this->_groupTree as $groupID => $group) {
      if ($groupID == 'info') {
        continue;
      }
      $form->addElement('checkbox', "custom_group[$groupID]", NULL, $group['title']);
    }
  }

  /**
   * @return null
   */
  public function summary() {
    return NULL;
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
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    //redirect if custom group not select in search criteria
    if (empty($this->_formValues['custom_group'])) {
      CRM_Core_Error::statusBounce(ts("You must select at least one Custom Group as a search criteria."),
        CRM_Utils_System::url('civicrm/contact/search/custom',
          "reset=1&csid={$this->_formValues['customSearchID']}",
          FALSE, NULL, FALSE, TRUE
        )
      );
    }

    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
      $sort = "contact_a.id";

      return $this->sql($selectClause, $offset, $rowcount, $sort, $includeContactIDs, NULL);
    }
    else {
      $selectClause = "
contact_a.id           as contact_id  ,
contact_a.contact_type as contact_type,
contact_a.sort_name    as sort_name,
";
    }

    $customClauses = [];
    foreach ($this->_tables as $tableName => $fields) {
      foreach ($fields as $fieldID => $fieldName) {
        $customClauses[] = "{$tableName}.{$fieldName} as custom_{$fieldID}";
      }
    }
    $selectClause .= implode(',', $customClauses);

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
    $from = "FROM civicrm_contact contact_a {$this->_aclFrom}";
    $customFrom = [];
    // lets do an INNER JOIN so we get only relevant values rather than all values
    if (!empty($this->_tables)) {
      foreach ($this->_tables as $tableName => $fields) {
        $customFrom[] = " INNER JOIN $tableName ON {$tableName}.entity_id = contact_a.id ";
      }
      $from .= implode(' ', $customFrom);
    }

    // This prevents duplicate rows when contacts have more than one tag any you select "any tag"
    if ($this->_tag) {
      $from .= " LEFT JOIN civicrm_entity_tag t ON (t.entity_table='civicrm_contact'
                       AND contact_a.id = t.entity_id)";
    }

    if ($this->_group) {
      $from .= " LEFT JOIN civicrm_group_contact cgc ON ( cgc.contact_id = contact_a.id
                       AND cgc.status = 'Added')";
    }

    return $from;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $count = 1;
    $clause = [];
    $params = [];
    $name = $this->_formValues['sort_name'] ?? NULL;
    if ($name != NULL) {
      if (!str_contains($name, '%')) {
        $name = "%{$name}%";
      }
      $params[$count] = [$name, 'String'];
      $clause[] = "contact_a.sort_name LIKE %{$count}";
      $count++;
    }

    $contact_type = $this->_formValues['contact_type'] ?? NULL;
    if ($contact_type != NULL) {
      $contactType = explode('__', $contact_type, 2);
      if (count($contactType) > 1) {
        $clause[] = "contact_a.contact_type = '$contactType[0]' AND contact_a.contact_sub_type = '$contactType[1]'";
      }
      else {
        $clause[] = "contact_a.contact_type = '$contactType[0]'";
      }
    }

    if ($this->_tag) {
      $clause[] = "t.tag_id = {$this->_tag}";
    }

    if ($this->_group) {
      $clause[] = "cgc.group_id = {$this->_group}";
    }
    if ($this->_aclWhere) {
      $clause[] = " {$this->_aclWhere}";
    }

    $where = '( 1 )';
    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/MultipleValues.tpl';
  }

  /**
   * @param $row
   */
  public function alterRow(&$row) {
    foreach ($row as $fieldName => &$field) {
      if (str_starts_with($fieldName, 'custom_')) {
        $field = CRM_Core_BAO_CustomField::displayValue($field, $fieldName);
      }
    }
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
