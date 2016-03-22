<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 | see the CiviCRM license FAQ at http://civicrm.org/licensing
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This file is for Case search.
 */
class CRM_Case_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * Prefix for the controller
   */
  protected $_prefix = 'case_';

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }

    //validate case configuration.
    $configured = CRM_Case_BAO_Case::isCaseConfigured();
    $this->assign('notConfigured', !$configured['configured']);
    if (!$configured['configured']) {
      return;
    }

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');

    $this->assign('context', $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST) && !$this->controller->isModal()) {
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

    if ($this->_force) {
      $this->postProcess();
      $this->set('force', 0);
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Case_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );

    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $controller->moveFromSessionToTemplate();

    $this->assign('summary', $this->get('summary'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addSortNameField();

    CRM_Case_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $permission = CRM_Core_Permission::getPermission();

      $tasks = CRM_Case_Task::permissionedTaskTitles($permission);

      if (!empty($this->_formValues['case_deleted'])) {
        unset($tasks[1]);
      }
      else {
        unset($tasks[4]);
      }

      $this->addTaskMenu($tasks);
    }

  }

  /**
   * Get the label for the sortName field if email searching is on.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithEmail() {
    return ts('Client Name or Email');
  }

  /**
   * Get the label for the sortName field if email searching is off.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithOutEmail() {
    return ts('Client Name');
  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post procesing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submissing is
   *        done.
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;
    $this->_formValues = $this->controller->exportValues($this->_name);
    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    //search for civicase
    if (!$this->_force) {
      if (array_key_exists('case_owner', $this->_formValues) && !$this->_formValues['case_owner']) {
        $this->_formValues['case_owner'] = 0;
      }
    }

    if (empty($this->_formValues['case_deleted'])) {
      $this->_formValues['case_deleted'] = 0;
    }
    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $selector = new CRM_Case_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);

    $query = &$selector->getQuery();
    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  /**
   * Add the rules (mainly global rules) for form.
   *
   * All local rules are added near the element
   *
   * @see valid_date
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Case_Form_Search', 'formRule'));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    $errors = array();

    if (!empty($errors)) {
      return $errors;
    }

    return TRUE;
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults = $this->_formValues;
    return $defaults;
  }

  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $caseStatus = CRM_Utils_Request::retrieve('status', 'Positive',
      CRM_Core_DAO::$_nullObject
    );
    if ($caseStatus) {
      $this->_formValues['case_status_id'] = $caseStatus;
      $this->_defaults['case_status_id'] = $caseStatus;
    }
    $caseType = CRM_Utils_Request::retrieve('type', 'Positive',
      CRM_Core_DAO::$_nullObject
    );
    if ($caseType) {
      $this->_formValues['case_type_id'] = (array) $caseType;
      $this->_defaults['case_type_id'] = (array) $caseType;
    }

    $caseFromDate = CRM_Utils_Request::retrieve('pstart', 'Date',
      CRM_Core_DAO::$_nullObject
    );
    if ($caseFromDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($caseFromDate);
      $this->_formValues['case_start_date_low'] = $date;
      $this->_defaults['case_start_date_low'] = $date;
    }

    $caseToDate = CRM_Utils_Request::retrieve('pend', 'Date',
      CRM_Core_DAO::$_nullObject
    );
    if ($caseToDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($caseToDate);
      $this->_formValues['case_start_date_high'] = $date;
      $this->_defaults['case_start_date_high'] = $date;
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;
        list($display, $image) = CRM_Contact_BAO_Contact::getDisplayAndImage($cid);
        $this->_defaults['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid,
          'sort_name'
        );
        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }
    else {
      // First, if "all" is stored in the session, default to all cases, otherwise default to no selection.
      $session = CRM_Core_Session::singleton();
      if (CRM_Utils_Request::retrieve('all', 'Positive', $session)) {
        $this->_formValues['case_owner'] = 1;
        $this->_defaults['case_owner'] = 1;
      }
      else {
        $this->_formValues['case_owner'] = 0;
        $this->_defaults['case_owner'] = 0;
      }

      // Now if case_owner is set in the url/post, use that instead.
      $caseOwner = CRM_Utils_Request::retrieve('case_owner', 'Positive',
        CRM_Core_DAO::$_nullObject
      );
      if ($caseOwner) {
        $this->_formValues['case_owner'] = $caseOwner;
        $this->_defaults['case_owner'] = $caseOwner;
      }
    }
  }

  /**
   * @return null
   */
  public function getFormValues() {
    return NULL;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Cases');
  }

}
