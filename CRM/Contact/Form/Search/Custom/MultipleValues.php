<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_MultipleValues extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_groupTree;
  protected $_tables;
  protected $_options;

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree("'Contact', 'Individual', 'Organization', 'Household'",
      CRM_Core_DAO::$_nullObject,
      NULL, -1
    );

    $this->_group = CRM_Utils_Array::value('group', $this->_formValues);

    $this->_tag = CRM_Utils_Array::value('tag', $this->_formValues);

    $this->_columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
    );

    $this->_customGroupIDs = CRM_Utils_Array::value('custom_group', $formValues);

    if (!empty($this->_customGroupIDs)) {
      $this->addColumns();
    }
  }

  function addColumns() {
    // add all the fields for chosen groups
    $this->_tables = $this->_options = array();
    foreach ($this->_groupTree as $groupID => $group) {
      if (!CRM_Utils_Array::value($groupID, $this->_customGroupIDs)) {
        continue;
      }

      // now handle all the fields
      foreach ($group['fields'] as $fieldID => $field) {
        $this->_columns[$field['label']] = "custom_{$field['id']}";
        if (!array_key_exists($group['table_name'], $this->_tables)) {
          $this->_tables[$group['table_name']] = array();
        }
        $this->_tables[$group['table_name']][$field['id']] = $field['column_name'];

        // also build the option array
        $this->_options[$field['id']] = array();
        CRM_Core_BAO_CustomField::buildOption($field,
          $this->_options[$field['id']]
        );
      }
    }
  }

  function buildForm(&$form) {

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Multiple Value Custom Group Search and Export');

    $form->add('text', 'sort_name', ts('Contact Name'), TRUE);

    // add select for contact type
    $contactTypes = array('' => ts('- any contact type -')) + CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type', ts('Find...'), $contactTypes);

    // add select for groups
    $group = array('' => ts('- any group -')) + CRM_Core_PseudoConstant::group();
    $form->addElement('select', 'group', ts('in'), $group);

    // add select for tags
    $tag = array('' => ts('- any tag -')) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
    $form->addElement('select', 'tag', ts('Tagged'), $tag);

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

  function summary() {
    return NULL;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    //redirect if custom group not select in search criteria
    if (!CRM_Utils_Array::value('custom_group', $this->_formValues)) {
      CRM_Core_Error::statusBounce(ts("You must select at least one Custom Group as a search criteria."),
        CRM_Utils_System::url('civicrm/contact/search/custom',
          "reset=1&csid={$this->_formValues['customSearchID']}",
          FALSE, NULL, FALSE, TRUE
        )
      );
    }

    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
    }
    else {
      $selectClause = "
contact_a.id           as contact_id  ,
contact_a.contact_type as contact_type,
contact_a.sort_name    as sort_name,
";
    }

    $customClauses = array();
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

  function from() {
    $from = "FROM civicrm_contact contact_a";
    $customFrom = array();
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

  function where($includeContactIDs = FALSE) {
    $count  = 1;
    $clause = array();
    $params = array();
    $name   = CRM_Utils_Array::value('sort_name',
      $this->_formValues
    );
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.sort_name LIKE %{$count}";
      $count++;
    }

    $contact_type = CRM_Utils_Array::value('contact_type',
      $this->_formValues
    );
    if ($contact_type != NULL) {
      $contactType = explode(CRM_Core_DAO::VALUE_SEPARATOR, $contact_type);
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

    $where = '( 1 )';
    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/MultipleValues.tpl';
  }

  function setDefaultValues() {
    return array();
  }

  function alterRow(&$row) {
    foreach ($this->_options as $fieldID => $values) {
      $customVal = $valueSeparatedArray = array();
      if (in_array($values['attributes']['html_type'],
          array('Radio', 'Select', 'Autocomplete-Select')
        )) {
        if ($values['attributes']['data_type'] == 'ContactReference' && $row["custom_{$fieldID}"]) {
          $row["custom_{$fieldID}"] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', (int)$row["custom_{$fieldID}"], 'display_name');
        }
        elseif ($row["custom_{$fieldID}"] &&
          array_key_exists($row["custom_{$fieldID}"],
            $values
          )
        ) {
          $row["custom_{$fieldID}"] = $values[$row["custom_{$fieldID}"]];
        }
      }
      elseif (in_array($values['attributes']['html_type'],
          array('CheckBox', 'Multi-Select', 'AdvMulti-Select')
        )) {
        $valueSeparatedArray = array_filter(explode(CRM_Core_DAO::VALUE_SEPARATOR, $row["custom_{$fieldID}"]));
        foreach ($valueSeparatedArray as $val) {
          $customVal[] = $values[$val];
        }
        $row["custom_{$fieldID}"] = implode(', ', $customVal);
      }
      elseif (in_array($values['attributes']['html_type'],
          array('Multi-Select State/Province', 'Select State/Province')
        )) {
        $valueSeparatedArray = array_filter(explode(CRM_Core_DAO::VALUE_SEPARATOR, $row["custom_{$fieldID}"]));
        $stateName = CRM_Core_PseudoConstant::stateProvince();
        foreach ($valueSeparatedArray as $val) {
          $customVal[] = $stateName[$val];
        }
        $row["custom_{$fieldID}"] = implode(', ', $customVal);
      }
      elseif (in_array($values['attributes']['html_type'],
          array('Multi-Select Country', 'Select Country')
        )) {
        $valueSeparatedArray = array_filter(explode(CRM_Core_DAO::VALUE_SEPARATOR, $row["custom_{$fieldID}"]));
        CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country',
          TRUE, 'name', 'is_active'
        );
        foreach ($valueSeparatedArray as $val) {
          $customVal[] = $countryNames[$val];
        }
        $row["custom_{$fieldID}"] = implode(', ', $customVal);
      }
    }
  }

  function setTitle($title) {
    CRM_Utils_System::setTitle($title);
  }
}

