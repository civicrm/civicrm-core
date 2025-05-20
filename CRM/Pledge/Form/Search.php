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
 * This file is for Pledge search
 */
class CRM_Pledge_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * @return string
   */
  public function getDefaultEntity() {
    return 'Pledge';
  }

  /**
   * Prefix for the controller.
   * @var string
   */
  protected $_prefix = "pledge_";

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {

    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;

    parent::preProcess();

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Pledge_Selector_Search($this->_queryParams,
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
    $this->addContactSearchFields();

    CRM_Pledge_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $this->addTaskMenu(CRM_Pledge_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()));
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
    return ts('Pledger Name or Email');
  }

  /**
   * Get the label for the sortName field if email searching is off.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithOutEmail() {
    return ts('Pledger Name');
  }

  /**
   * Get the label for the tag field.
   *
   * We do this in a function so the 'ts' wraps the whole string to allow
   * better translation.
   *
   * @return string
   */
  protected function getTagLabel() {
    return ts('Pledger Tag(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getGroupLabel() {
    return ts('Pledger Group(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getContactTypeLabel() {
    return ts('Pledger Contact Type');
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

    $this->fixFormValues();

    foreach (['pledge_amount_low', 'pledge_amount_high'] as $f) {
      if (isset($this->_formValues[$f])) {
        $this->_formValues[$f] = CRM_Utils_Rule::cleanMoney($this->_formValues[$f]);
      }
    }

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

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

    $selector = new CRM_Pledge_Selector_Search($this->_queryParams,
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

  /**
   * add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @see valid_date
   */
  public function addRules() {
    $this->addFormRule(['CRM_Pledge_Form_Search', 'formRule']);
  }

  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    // set pledge payment related fields
    $status = CRM_Utils_Request::retrieve('status', 'String');
    if ($status) {
      $this->_formValues['pledge_payment_status_id'] = [$status => 1];
      $this->_defaults['pledge_payment_status_id'] = [$status => 1];
    }

    $fromDate = CRM_Utils_Request::retrieve('start', 'Date');
    if ($fromDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($fromDate);
      $this->_formValues['pledge_payment_date_low'] = $date;
      $this->_defaults['pledge_payment_date_low'] = $date;
    }

    $toDate = CRM_Utils_Request::retrieve('end', 'Date');
    if ($toDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($toDate);
      $this->_formValues['pledge_payment_date_high'] = $date;
      $this->_defaults['pledge_payment_date_high'] = $date;
    }

    // set pledge related fields
    $pledgeStatus = CRM_Utils_Request::retrieve('pstatus', 'String');

    if ($pledgeStatus) {
      $statusValues = CRM_Pledge_BAO_Pledge::buildOptions('status_id');

      // we need set all statuses except Cancelled
      unset($statusValues[$pledgeStatus]);

      $statuses = [];
      foreach ($statusValues as $statusId => $value) {
        $statuses[$statusId] = 1;
      }

      $this->_formValues['pledge_status_id'] = $statuses;
      $this->_defaults['pledge_status_id'] = $statuses;
    }

    $pledgeFromDate = CRM_Utils_Request::retrieve('pstart', 'Date');
    if ($pledgeFromDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($pledgeFromDate);
      $this->_formValues['pledge_create_date_low'] = $this->_defaults['pledge_create_date_low'] = $date;
    }

    $pledgeToDate = CRM_Utils_Request::retrieve('pend', 'Date');
    if ($pledgeToDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($pledgeToDate);
      $this->_formValues['pledge_create_date_high'] = $this->_defaults['pledge_create_date_high'] = $date;
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
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Pledges');
  }

  /**
   * Set the metadata for the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Pledge' => CRM_Pledge_BAO_Query::getSearchFieldMetadata()]);
    $this->addSearchFieldMetadata(['PledgePayment' => CRM_Pledge_BAO_Query::getPledgePaymentSearchFieldMetadata()]);
  }

}
