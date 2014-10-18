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

/**
 * Files required
 */

/**
 * Base Search / View form for *all* listing of multiple
 * contacts
 */
class CRM_Contact_Form_Search_Basic extends CRM_Contact_Form_Search {

  /*
   * csv - common search values
   *
   * @var array
   * @access protected
   * @static
   */
  static $csv = array('contact_type', 'group', 'tag');

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    // text for sort_name or email criteria
    $config = CRM_Core_Config::singleton();
    $label = empty($config->includeEmailInName) ? ts('Name') : ts('Name or Email');
    $this->add('text', 'sort_name', $label);

    $searchOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'advanced_search_options'
    );

    $shortCuts = array();
    //@todo FIXME - using the CRM_Core_DAO::VALUE_SEPARATOR creates invalid html - if you can find the form
    // this is loaded onto then replace with something like '__' & test
    $separator = CRM_Core_DAO::VALUE_SEPARATOR;
    if (!empty($searchOptions['contactType'])) {
      $contactTypes = array('' => ts('- any contact type -')) + CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, $separator);
      $this->add('select', 'contact_type',
        ts('is...'),
        $contactTypes,
        FALSE,
        array('class' => 'crm-select2')
      );
    }

    if (!empty($searchOptions['groups'])) {
      // Arrange groups into hierarchical listing (child groups follow their parents and have indentation spacing in title)
      $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($this->_group, NULL, '&nbsp;&nbsp;', TRUE);

      // add select for groups
      $group = array('' => ts('- any group -')) + $groupHierarchy;
      $this->add('select', 'group', ts('in'), $group, FALSE, array('class' => 'crm-select2'));
    }

    if (!empty($searchOptions['tags'])) {
      // tag criteria
      if (!empty($this->_tag)) {
        $tag = array('' => ts('- any tag -')) + $this->_tag;
        $this->add('select', 'tag', ts('with'), $tag, FALSE, array('class' => 'crm-select2'));
      }
    }

    parent::buildQuickForm();
  }

  /**
   * Set the default form values
   *
   * @access protected
   *
   * @return array the default array reference
   */
  function setDefaultValues() {
    $defaults = array();

    $defaults['sort_name'] = CRM_Utils_Array::value('sort_name', $this->_formValues);
    foreach (self::$csv as $v) {
      if (!empty($this->_formValues[$v]) && is_array($this->_formValues[$v])) {
        $tmpArray = array_keys($this->_formValues[$v]);
        $defaults[$v] = array_pop($tmpArray);
      }
      else {
        $defaults[$v] = '';
      }
    }

    if ($this->_context === 'amtg') {
      $defaults['task'] = CRM_Contact_Task::GROUP_CONTACTS;
    }

    if ($this->_context === 'smog') {
      $defaults['group_contact_status[Added]'] = TRUE;
    }

    return $defaults;
  }

  /**
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Search_Basic', 'formRule'));
  }

  /**
   * processing needed for buildForm and later
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->set('searchFormName', 'Basic');

    parent::preProcess();
  }

  /**
   * @return array
   */
  function &getFormValues() {
    return $this->_formValues;
  }

  /**
   * this method is called for processing a submitted search form
   *
   * @return void
   * @access public
   */
  function postProcess() {
    $this->set('isAdvanced', '0');
    $this->set('isSearchBuilder', '0');

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $this->normalizeFormValues();
    }

    if (isset($this->_groupID) && empty($this->_formValues['group'])) {
      $this->_formValues['group'][$this->_groupID] = 1;
    }
    elseif (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);

      //fix for CRM-1505
      if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'mapping_id')) {
        $this->_params = CRM_Contact_BAO_SavedSearch::getSearchParams($this->_ssID);
      }
    }

    // we dont want to store the sortByCharacter in the formValue, it is more like
    // a filter on the result set
    // this filter is reset if we click on the search button
    if ($this->_sortByCharacter !== NULL && empty($_POST)) {
      if (strtolower($this->_sortByCharacter) == 'all') {
        $this->_formValues['sortByCharacter'] = NULL;
      }
      else {
        $this->_formValues['sortByCharacter'] = $this->_sortByCharacter;
      }
    }
    else {
      $this->_sortByCharacter = NULL;
    }

    $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $this->_returnProperties = &$this->returnProperties();

    parent::postProcess();
  }

  /**
   * normalize the form values to make it look similar to the advanced form values
   * this prevents a ton of work downstream and allows us to use the same code for
   * multiple purposes (queries, save/edit etc)
   *
   * @return void
   * @access private
   */
  function normalizeFormValues() {
    $contactType = CRM_Utils_Array::value('contact_type', $this->_formValues);
    if ($contactType && !is_array($contactType)) {
      unset($this->_formValues['contact_type']);
      $this->_formValues['contact_type'][$contactType] = 1;
    }

    $config = CRM_Core_Config::singleton();

    $group = CRM_Utils_Array::value('group', $this->_formValues);
    if ($group && !is_array($group)) {
      unset($this->_formValues['group']);
      $this->_formValues['group'][$group] = 1;
    }

    $tag = CRM_Utils_Array::value('tag', $this->_formValues);
    if ($tag && !is_array($tag)) {
      unset($this->_formValues['tag']);
      $this->_formValues['tag'][$tag] = 1;
    }

    return;
  }

  /**
   * Add a form rule for this form. If Go is pressed then we must select some checkboxes
   * and an action
   */
  static function formRule($fields) {
    // check actionName and if next, then do not repeat a search, since we are going to the next page
    if (array_key_exists('_qf_Search_next', $fields)) {
      if (empty($fields['task'])) {
        return array('task' => 'Please select a valid action.');
      }

      if (CRM_Utils_Array::value('task', $fields) == CRM_Contact_Task::SAVE_SEARCH) {
        // dont need to check for selection of contacts for saving search
        return TRUE;
      }

      // if the all contact option is selected, ignore the contact checkbox validation
      if ($fields['radio_ts'] == 'ts_all') {
        return TRUE;
      }

      foreach ($fields as $name => $dontCare) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          return TRUE;
        }
      }
      return array('task' => 'Please select one or more checkboxes to perform the action on.');
    }
    return TRUE;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  /**
   * @return string
   */
  function getTitle() {
    return ts('Find Contacts');
  }
}

