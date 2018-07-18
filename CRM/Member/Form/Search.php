<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Membership search.
 *
 * Class is a pane in advanced search and the membership search page.
 */
class CRM_Member_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var bool
   */
  protected $_limit = NULL;

  /**
   * Prefix for the controller.
   */
  protected $_prefix = "member_";

  /**
   * Declare entity reference fields as they will need to be converted to using 'IN'.
   *
   * @var array
   */
  protected $entityReferenceFields = array('membership_type_id');

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;

    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean');
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'search');

    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
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

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
    $selector = new CRM_Member_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $prefix = NULL;
    if ($this->_context == 'basic') {
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
    $this->addContactSearchFields();

    CRM_Member_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $this->addTaskMenu(CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()));
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
    return ts('Member Name or Email');
  }

  /**
   * Get the label for the sortName field if email searching is off.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithOutEmail() {
    return ts('Member Name');
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
    return ts('Member Tag(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getGroupLabel() {
    return ts('Member Group(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getContactTypeLabel() {
    return ts('Member Contact Type');
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

    // We don't show test records in summaries or dashboards
    if (empty($this->_formValues['member_test']) && $this->_force) {
      $this->_formValues["member_test"] = 0;
    }

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);

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

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);

    $selector = new CRM_Member_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'basic') {
      $prefix = $this->_prefix;
    }

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
    $controller->run();
  }

  /**
   * Set default values.
   *
   * @todo - can this function override be removed?
   *
   * @return array
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * If this search has been forced then see if there are any get values, and if so over-ride the post values.
   *
   * Note that this means that GET over-rides POST :) & that force with no parameters can be very destructive.
   */
  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $status = CRM_Utils_Request::retrieve('status', 'String');
    if ($status) {
      $status = explode(',', $status);
      $this->_formValues['membership_status_id'] = $this->_defaults['membership_status_id'] = (array) $status;
    }

    $membershipType = CRM_Utils_Request::retrieve('type', 'String');

    if ($membershipType) {
      $this->_formValues['membership_type_id'] = array($membershipType);
      $this->_defaults['membership_type_id'] = array($membershipType);
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');

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

    $fromDate = CRM_Utils_Request::retrieve('start', 'Date');
    if ($fromDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($fromDate);
      $this->_formValues['member_start_date_low'] = $this->_defaults['member_start_date_low'] = $date;
    }

    $toDate = CRM_Utils_Request::retrieve('end', 'Date');
    if ($toDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($toDate);
      $this->_formValues['member_start_date_high'] = $this->_defaults['member_start_date_high'] = $date;
    }
    $joinDate = CRM_Utils_Request::retrieve('join', 'Date');
    if ($joinDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($joinDate);
      $this->_formValues['member_join_date_low'] = $this->_defaults['member_join_date_low'] = $date;
    }

    $joinEndDate = CRM_Utils_Request::retrieve('joinEnd', 'Date');
    if ($joinEndDate) {
      list($date) = CRM_Utils_Date::setDateDefaults($joinEndDate);
      $this->_formValues['member_join_date_high'] = $this->_defaults['member_join_date_high'] = $date;
    }

    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive',
      $this
    );

    //LCD also allow restrictions to membership owner via GET
    $owner = CRM_Utils_Request::retrieve('owner', 'String');
    if (in_array($owner, array('0', '1'))) {
      $this->_formValues['member_is_primary'] = $this->_defaults['member_is_primary'] = $owner;
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Memberships');
  }

}
