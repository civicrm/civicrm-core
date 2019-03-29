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
   * @var boolean
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
   * @var array
   */
  protected $searchFieldMetadata = [];

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
   * Common buildForm tasks required by all searches.
   */
  public function buildQuickform() {
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
        if ($fieldSpec['type'] === CRM_Utils_Type::T_DATE || $fieldSpec['type'] === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          $this->addDatePickerRange($fieldName, $fieldSpec['title'], ($fieldSpec['type'] === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)));
        }
        else {
          $this->addField($fieldName, ['entity' => $entity]);
        }
      }
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    foreach ($form->getSearchFieldMetadata() as $entity => $spec) {
      foreach ($spec as $fieldName => $fieldSpec) {
        if ($fieldSpec['type'] === CRM_Utils_Type::T_DATE || $fieldSpec['type'] === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          if (isset($fields[$fieldName . '_high']) && isset($fields[$fieldName . '_low']) && empty($fields[$fieldName . '_relative'])) {
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
   */
  protected function getEntityDefaults($entity) {
    $defaults = [];
    foreach ($this->getSearchFieldMetadata()[$entity] as $fieldSpec) {
      if (empty($_POST[$fieldSpec['name']])) {
        $value = CRM_Utils_Request::retrieveValue($fieldSpec['name'], $this->getValidationTypeForField($entity, $fieldSpec['name']), FALSE, NULL, 'GET');
        if ($value !== FALSE) {
          $defaults[$fieldSpec['name']] = $value;
        }
        if ($fieldSpec['type'] === CRM_Utils_Type::T_DATE || ($fieldSpec['type'] === CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          $low = CRM_Utils_Request::retrieveValue($fieldSpec['name'] . '_low', 'Timestamp', FALSE, NULL, 'GET');
          $high = CRM_Utils_Request::retrieveValue($fieldSpec['name'] . '_high', 'Timestamp', FALSE, NULL, 'GET');
          if ($low !== FALSE || $high !== FALSE) {
            $defaults[$fieldSpec['name'] . '_relative'] = 0;
            $defaults[$fieldSpec['name'] . '_low'] = $low ? date('Y-m-d H:i:s', strtotime($low)) : NULL;
            $defaults[$fieldSpec['name'] . '_high'] = $high ? date('Y-m-d H:i:s', strtotime($high)) : NULL;
          }
        }
      }
    }
    return $defaults;
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
        if (CRM_Utils_Array::value('checkbox', $row)) {
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
    $this->addElement(
      'text',
      'sort_name',
      civicrm_api3('setting', 'getvalue', ['name' => 'includeEmailInName', 'group' => 'Search Preferences']) ? $this->getSortNameLabelWithEmail() : $this->getSortNameLabelWithOutEmail(),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );
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
