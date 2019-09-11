<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Base class for most search forms
 */
class CRM_Core_Form_Search extends CRM_Core_Form {

  /**
   * Are we forced to run a search
   *
   * @var int
   */
  protected $_force;

  /**
   * Name of search button
   *
   * @var string
   */
  protected $_searchButtonName;

  /**
   * Name of action button
   *
   * @var string
   */
  protected $_actionButtonName;

  /**
   * Form values that we will be using
   *
   * @var array
   */
  public $_formValues;

  /**
   * Have we already done this search
   *
   * @var bool
   */
  protected $_done;

  /**
   * What context are we being invoked from
   *
   * @var string
   */
  protected $_context = NULL;

  /**
   * The list of tasks or actions that a searcher can perform on a result set.
   *
   * @var array
   */
  protected $_taskList = [];

  /**
   * Declare entity reference fields as they will need to be converted.
   *
   * The entity reference format looks like '2,3' whereas the Query object expects array(2, 3)
   * or array('IN' => array(2, 3). The latter is the one we are moving towards standardising on.
   *
   * @var array
   */
  protected $entityReferenceFields = [];

  /**
   * Builds the list of tasks or actions that a searcher can perform on a result set.
   *
   * To modify the task list, child classes should alter $this->_taskList,
   * preferably by extending this method.
   *
   * @return array
   */
  protected function buildTaskList() {
    return $this->_taskList;
  }

  /**
   * Metadata for fields on the search form.
   *
   * Instantiate with empty array for contact to prevent e-notices.
   *
   * @var array
   */
  protected $searchFieldMetadata = ['Contact' => []];

  /**
   * @return array
   */
  public function getSearchFieldMetadata() {
    return $this->searchFieldMetadata;
  }

  /**
   * @param array $searchFieldMetadata
   */
  public function addSearchFieldMetadata($searchFieldMetadata) {
    $this->searchFieldMetadata = array_merge($this->searchFieldMetadata, $searchFieldMetadata);
  }

  /**
   * This virtual function is used to set the default values of various form elements.
   *
   * @return array|NULL
   *   reference to the array of default values
   * @throws \Exception
   */
  public function setDefaultValues() {
    $defaults = (array) $this->_formValues;
    foreach (array_keys($this->getSearchFieldMetadata()) as $entity) {
      $defaults = array_merge($this->getEntityDefaults($entity), $defaults);
    }
    return $defaults;
  }

  /**
   * Set the form values based on input and preliminary processing.
   *
   * @throws \Exception
   */
  protected function setFormValues() {
    if (!empty($_POST) && !$this->_force) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    elseif ($this->_force) {
      $this->_formValues = $this->setDefaultValues();
    }
    $this->convertTextStringsToUseLikeOperator();
  }

  /**
   * Common buildForm tasks required by all searches.
   */
  public function buildQuickForm() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'js/crm.searchForm.js', 1, 'html-header')
      ->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);

    $this->addClass('crm-search-form');

    $tasks = $this->buildTaskList();
    $this->addTaskMenu($tasks);
  }

  /**
   * Add any fields described in metadata to the form.
   *
   * The goal is to describe all fields in metadata and handle from metadata rather
   * than existing ad hoc handling.
   */
  public function addFormFieldsFromMetadata() {
    $this->addFormRule(['CRM_Core_Form_Search', 'formRule'], $this);
    $this->_action = CRM_Core_Action::ADVANCED;
    foreach ($this->getSearchFieldMetadata() as $entity => $fields) {
      foreach ($fields as $fieldName => $fieldSpec) {
        $fieldType = $fieldSpec['type'] ?? '';
        if ($fieldType === CRM_Utils_Type::T_DATE || $fieldType === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          $title = empty($fieldSpec['unique_title']) ? $fieldSpec['title'] : $fieldSpec['unique_title'];
          $this->addDatePickerRange($fieldName, $title, ($fieldType === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)));
        }
        else {
          // Not quite sure about moving to a mix of keying by entity vs permitting entity to
          // be passed in. The challenge of the former is that it doesn't permit ordering.
          // Perhaps keying was the wrong starting point & we should do a flat array as all
          // fields eventually need to be unique.
          $props = ['entity' => $fieldSpec['entity'] ?? $entity];
          if (isset($fields[$fieldName]['unique_title'])) {
            $props['label'] = $fields[$fieldName]['unique_title'];
          }
          elseif (isset($fields[$fieldName]['title'])) {
            $props['label'] = $fields[$fieldName]['title'];
          }
          if (empty($fieldSpec['is_pseudofield'])) {
            $this->addField($fieldName, $props);
          }
        }
      }
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $files
   * @param object $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    if (!is_a($form, 'CRM_Core_Form_Search')) {
      // So this gets hit with a form object when doing an activity date search from
      // advanced search, but a NULL object when doing a pledge search.
      return $errors;
    }
    foreach ($form->getSearchFieldMetadata() as $entity => $spec) {
      foreach ($spec as $fieldName => $fieldSpec) {
        if ($fieldSpec['type'] === CRM_Utils_Type::T_DATE || $fieldSpec['type'] === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          if (!empty($fields[$fieldName . '_high']) && !empty($fields[$fieldName . '_low']) && empty($fields[$fieldName . '_relative'])) {
            if (strtotime($fields[$fieldName . '_low']) > strtotime($fields[$fieldName . '_high'])) {
              $errors[$fieldName . '_low'] = ts('%1: Please check that your date range is in correct chronological order.', [1 => $fieldSpec['title']]);
            }
          }
        }
      }
    }
    return $errors;
  }

  /**
   * Get the validation rule to apply to a function.
   *
   * Alphanumeric is designed to always be safe & for now we just return
   * that but in future we can use tighter rules for types like int, bool etc.
   *
   * @param string $entity
   * @param string $fieldName
   *
   * @return string
   */
  protected function getValidationTypeForField($entity, $fieldName) {
    switch ($this->getSearchFieldMetadata()[$entity][$fieldName]['type']) {
      case CRM_Utils_Type::T_BOOLEAN:
        return 'Boolean';

      case CRM_Utils_Type::T_INT:
        return 'CommaSeparatedIntegers';

      case CRM_Utils_Type::T_DATE:
      case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
        return 'Timestamp';

      default:
        return 'Alphanumeric';
    }
  }

  /**
   * Get the defaults for the entity for any fields described in metadata.
   *
   * @param string $entity
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getEntityDefaults($entity) {
    $defaults = [];
    foreach (CRM_Utils_Array::value($entity, $this->getSearchFieldMetadata(), []) as $fieldName => $fieldSpec) {
      if (empty($_POST[$fieldName])) {
        $value = CRM_Utils_Request::retrieveValue($fieldName, $this->getValidationTypeForField($entity, $fieldName), NULL, NULL, 'GET');
        if ($value !== NULL) {
          $defaults[$fieldName] = $value;
        }
        if ($fieldSpec['type'] === CRM_Utils_Type::T_DATE || ($fieldSpec['type'] === CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          $low = CRM_Utils_Request::retrieveValue($fieldName . '_low', 'Timestamp', NULL, NULL, 'GET');
          $high = CRM_Utils_Request::retrieveValue($fieldName . '_high', 'Timestamp', NULL, NULL, 'GET');
          if ($low !== NULL || $high !== NULL) {
            $defaults[$fieldName . '_relative'] = 0;
            $defaults[$fieldName . '_low'] = $low ? date('Y-m-d H:i:s', strtotime($low)) : NULL;
            $defaults[$fieldName . '_high'] = $high ? date('Y-m-d H:i:s', strtotime($high)) : NULL;
          }
          else {
            $relative = CRM_Utils_Request::retrieveValue($fieldName . '_relative', 'String', NULL, NULL, 'GET');
            if (!empty($relative) && isset(CRM_Core_OptionGroup::values('relative_date_filters')[$relative])) {
              $defaults[$fieldName . '_relative'] = $relative;
            }
          }
        }
      }
    }
    return $defaults;
  }

  /**
   * Convert any submitted text fields to use 'like' rather than '=' as the operator.
   *
   * This excludes any with options.
   *
   * Note this will only pick up fields declared via metadata.
   */
  protected function convertTextStringsToUseLikeOperator() {
    foreach ($this->getSearchFieldMetadata() as $entity => $fields) {
      foreach ($fields as $fieldName => $field) {
        if (!empty($this->_formValues[$fieldName]) && empty($field['options']) && empty($field['pseudoconstant'])) {
          if (in_array($field['type'], [CRM_Utils_Type::T_STRING, CRM_Utils_Type::T_TEXT])) {
            $this->_formValues[$fieldName] = ['LIKE' => CRM_Contact_BAO_Query::getWildCardedValue(TRUE, 'LIKE', trim($this->_formValues[$fieldName]))];
          }
        }
      }
    }
  }

  /**
   * Add checkboxes for each row plus a master checkbox.
   *
   * @param array $rows
   */
  public function addRowSelectors($rows) {
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, ['class' => 'select-rows']);
    if (!empty($rows)) {
      foreach ($rows as $row) {
        if (!empty($row['checkbox'])) {
          $this->addElement('checkbox', $row['checkbox'], NULL, NULL, ['class' => 'select-row']);
        }
      }
    }
  }

  /**
   * Add actions menu to search results form.
   *
   * @param array $tasks
   */
  public function addTaskMenu($tasks) {
    $taskMetaData = [];
    foreach ($tasks as $key => $task) {
      $taskMetaData[$key] = ['title' => $task];
    }
    parent::addTaskMenu($taskMetaData);
  }

  /**
   * Add the sort-name field to the form.
   *
   * There is a setting to determine whether email is included in the search & we look this up to determine
   * which text to choose.
   *
   * Note that for translation purposes the full string works better than using 'prefix' hence we use override-able functions
   * to define the string.
   */
  protected function addSortNameField() {
    $title = civicrm_api3('setting', 'getvalue', ['name' => 'includeEmailInName', 'group' => 'Search Preferences']) ? $this->getSortNameLabelWithEmail() : $this->getSortNameLabelWithOutEmail();
    $this->addElement(
      'text',
      'sort_name',
      $title,
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );
    $this->searchFieldMetadata['Contact']['sort_name'] = ['name' => 'sort_name', 'title' => $title, 'type' => CRM_Utils_Type::T_STRING];
  }

  /**
   * Get the label for the sortName field if email searching is on.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithEmail() {
    return ts('Name or Email');
  }

  /**
   * Get the label for the sortName field if email searching is off.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithOutEmail() {
    return ts('Name');
  }

  /**
   * Explicitly declare the form context for addField().
   */
  public function getDefaultContext() {
    return 'search';
  }

  /**
   * Add generic fields that specify the contact.
   */
  protected function addContactSearchFields() {
    if (!$this->isFormInViewOrEditMode()) {
      return;
    }
    $this->addSortNameField();

    $this->_group = CRM_Core_PseudoConstant::nestedGroup();
    if ($this->_group) {
      $this->add('select', 'group', $this->getGroupLabel(), $this->_group, FALSE,
        [
          'id' => 'group',
          'multiple' => 'multiple',
          'class' => 'crm-select2',
        ]
      );
    }

    $contactTags = CRM_Core_BAO_Tag::getTags();
    if ($contactTags) {
      $this->add('select', 'contact_tags', $this->getTagLabel(), $contactTags, FALSE,
        [
          'id' => 'contact_tags',
          'multiple' => 'multiple',
          'class' => 'crm-select2',
        ]
      );
    }
    $this->addField('contact_type', ['entity' => 'Contact']);

    if (CRM_Core_Permission::check('access deleted contacts') && Civi::settings()->get('contact_undelete')) {
      $this->addElement('checkbox', 'deleted_contacts', ts('Search in Trash') . '<br />' . ts('(deleted contacts)'));
    }

  }

  /**
   * we allow the controller to set force/reset externally, useful when we are being
   * driven by the wizard framework
   */
  protected function loadStandardSearchOptionsFromUrl() {
    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean');
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'search');
    $this->_ssID = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
    $this->assign("context", $this->_context);
  }

  /**
   * Get user submitted values.
   *
   * Get it from controller only if form has been submitted, else preProcess has set this
   */
  protected function loadFormValues() {
    if (!empty($_POST)  && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    if (empty($this->_formValues)) {
      if (isset($this->_ssID)) {
        $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
      }
    }
  }

}
