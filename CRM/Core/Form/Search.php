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
  protected $_taskList = array();

  /**
   * Declare entity reference fields as they will need to be converted.
   *
   * The entity reference format looks like '2,3' whereas the Query object expects array(2, 3)
   * or array('IN' => array(2, 3). The latter is the one we are moving towards standardising on.
   *
   * @var array
   */
  protected $entityReferenceFields = array();

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
   * Common buildForm tasks required by all searches.
   */
  public function buildQuickform() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'js/crm.searchForm.js', 1, 'html-header')
      ->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));

    $this->addClass('crm-search-form');

    // for backwards compatibility we pass an argument to addTaskMenu even though
    // it could just as well access $this->_taskList internally
    $tasks = $this->buildTaskList();
    $this->addTaskMenu($tasks);
  }

  /**
   * Add checkboxes for each row plus a master checkbox.
   *
   * @param array $rows
   */
  public function addRowSelectors($rows) {
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('class' => 'select-rows'));
    if (!empty($rows)) {
      foreach ($rows as $row) {
        if (CRM_Utils_Array::value('checkbox', $row)) {
          $this->addElement('checkbox', $row['checkbox'], NULL, NULL, array('class' => 'select-row'));
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
    if (is_array($tasks) && !empty($tasks)) {
      $tasks = array('' => ts('Actions')) + $tasks;
      $this->add('select', 'task', NULL, $tasks, FALSE, array('class' => 'crm-select2 crm-action-menu fa-check-circle-o huge crm-search-result-actions'));
      $this->add('submit', $this->_actionButtonName, ts('Go'), array('class' => 'hiddenElement crm-search-go-button'));

      // Radio to choose "All items" or "Selected items only"
      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', array('checked' => 'checked'));
      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);
    }
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
      civicrm_api3('setting', 'getvalue', array('name' => 'includeEmailInName', 'group' => 'Search Preferences')) ? $this->getSortNameLabelWithEmail() : $this->getSortNameLabelWithOutEmail(),
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

}
