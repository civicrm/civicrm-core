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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
   * @access protected
   */
  protected $_force;

  /**
   * name of search button
   *
   * @var string
   * @access protected
   */
  protected $_searchButtonName;

  /**
   * name of action button
   *
   * @var string
   * @access protected
   */
  protected $_actionButtonName;

  /**
   * form values that we will be using
   *
   * @var array
   * @access public
   */
  public $_formValues;

  /**
   * have we already done this search
   *
   * @access protected
   * @var boolean
   */
  protected $_done;

  /**
   * what context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_context = NULL;

  /**
   * Common buildform tasks required by all searches
   */
  function buildQuickform() {
    $resources = CRM_Core_Resources::singleton();

    if ($resources->ajaxPopupsEnabled) {
      // Script needed by some popups
      $this->assign('includeWysiwygEditor', TRUE);
    }

    $resources->addScriptFile('civicrm', 'js/crm.searchForm.js', 1, 'html-header');

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));

    $this->addClass('crm-search-form');
  }

  /**
   * Add checkboxes for each row plus a master checkbox
   */
  function addRowSelectors($rows) {
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('class' => 'select-rows'));
    foreach ($rows as $row) {
      $this->addElement('checkbox', $row['checkbox'], NULL, NULL, array('class' => 'select-row'));
    }
  }

  /**
   * Add actions menu to search results form
   * @param $tasks
   */
  function addTaskMenu($tasks) {
    if (is_array($tasks) && !empty($tasks)) {
      $tasks = array('' => ts('Actions')) + $tasks;
      $this->add('select', 'task', NULL, $tasks, FALSE, array('class' => 'crm-select2 crm-action-menu huge crm-search-result-actions'));
      $this->add('submit', $this->_actionButtonName, ts('Go'), array('class' => 'hiddenElement crm-search-go-button'));

      // Radio to choose "All items" or "Selected items only"
      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', array('checked' => 'checked'));
      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);
    }
  }
}
