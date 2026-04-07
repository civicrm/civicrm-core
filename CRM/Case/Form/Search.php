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
   * Prefix for the controller
   * @var string
   */
  protected $_prefix = 'case_';

  /**
   * @return string
   */
  public function getDefaultEntity() {
    return 'Case';
  }

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    // SearchFormName is deprecated & to be removed - the replacement is for the task to
    // call $this->form->getSearchFormValues()
    // A couple of extensions use it.
    $this->set('searchFormName', 'Search');

    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
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
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->sortNameOnly = TRUE;

    parent::preProcess();

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
      $this->getSortID(),
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

      $tasks = CRM_Case_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());

      if (!empty($this->_formValues['case_deleted'])) {
        unset($tasks[CRM_Case_Task::TASK_DELETE]);
      }
      else {
        unset($tasks[CRM_Case_Task::RESTORE_CASES]);
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
    $this->setFormValues();
    // @todo - stop changing formValues - respect submitted form values, change a working array.
    $this->fixFormValues();
    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    //search for civicase
    if (!$this->_force) {
      // @todo - stop changing formValues - respect submitted form values, change a working array.
      if (array_key_exists('case_owner', $this->_formValues) && !$this->_formValues['case_owner']) {
        $this->_formValues['case_owner'] = 0;
      }
    }

    // @todo - stop changing formValues - respect submitted form values, change a working array.
    if (empty($this->_formValues['case_deleted'])) {
      $this->_formValues['case_deleted'] = 0;
    }

    // @todo - stop changing formValues - respect submitted form values, change a working array.
    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

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
      $this->getSortID(),
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

  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $caseStatus = CRM_Utils_Request::retrieve('status', 'Positive');
    if ($caseStatus) {
      $this->_formValues['case_status_id'] = $caseStatus;
      $this->_defaults['case_status_id'] = $caseStatus;
    }
    $caseType = CRM_Utils_Request::retrieve('type', 'Positive');
    if ($caseType) {
      $this->_formValues['case_type_id'] = (array) $caseType;
      $this->_defaults['case_type_id'] = (array) $caseType;
    }

    $caseFromDate = CRM_Utils_Request::retrieve('pstart', 'Date');
    if ($caseFromDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($caseFromDate);
      $this->_formValues['case_start_date_low'] = $date;
      $this->_defaults['case_start_date_low'] = $date;
    }

    $caseToDate = CRM_Utils_Request::retrieve('pend', 'Date');
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
      $caseOwner = CRM_Utils_Request::retrieve('case_owner', 'Positive');
      if ($caseOwner) {
        $this->_formValues['case_owner'] = $caseOwner;
        $this->_defaults['case_owner'] = $caseOwner;
      }
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Cases');
  }

  /**
   * Set the metadata for the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Case' => CRM_Case_BAO_Query::getSearchFieldMetadata()]);
  }

}
