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
   * Prefix for the controller.
   * @var string
   */
  protected $_prefix = "member_";

  /**
   * Declare entity reference fields as they will need to be converted to using 'IN'.
   *
   * @var array
   */
  protected $entityReferenceFields = ['membership_type_id'];

  /**
   * Processing needed for buildForm and later.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // SearchFormName is deprecated & to be removed - the replacement is for the task to
    // call $this->form->getSearchFormValues()
    // A couple of extensions use it.
    $this->set('searchFormName', 'Search');

    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;

    parent::preProcess();

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
    $selector = new CRM_Member_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $prefix = NULL;
    if ($this->_context === 'basic') {
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
   *
   * @throws \CRM_Core_Exception
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
   * Set defaults.
   *
   * @return array
   * @throws \Exception
   */
  public function setDefaultValues() {
    $this->_defaults = parent::setDefaultValues();
    //LCD also allow restrictions to membership owner via GET
    $owner = CRM_Utils_Request::retrieve('owner', 'String');
    if (in_array($owner, ['0', '1'])) {
      $this->_defaults['member_is_primary'] = $owner;
    }
    return $this->_defaults;
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
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;
    $this->setFormValues();

    $this->fixFormValues();

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);

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
    if ($this->_context === 'basic') {
      $prefix = $this->_prefix;
    }

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $this->getSortID(),
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * If this search has been forced then see if there are any get values, and if so over-ride the post values.
   *
   * Note that this means that GET over-rides POST :) & that force with no parameters can be very destructive.
   *
   * @throws \CRM_Core_Exception
   */
  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    // @todo Most of the  below is likely no longer required.
    $status = CRM_Utils_Request::retrieve('membership_status_id', 'String');
    if ($status) {
      $status = explode(',', $status);
      $this->_formValues['membership_status_id'] = $this->_defaults['membership_status_id'] = (array) $status;
    }

    $membershipType = CRM_Utils_Request::retrieve('type', 'String');

    if ($membershipType) {
      $this->_formValues['membership_type_id'] = [$membershipType];
      $this->_defaults['membership_type_id'] = [$membershipType];
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;
        [$display, $image] = CRM_Contact_BAO_Contact::getDisplayAndImage($cid);
        $this->_defaults['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid,
          'sort_name'
        );
        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }

    $fromDate = CRM_Utils_Request::retrieve('start', 'Date');
    if ($fromDate) {
      [$date] = CRM_Utils_Date::setDateDefaults($fromDate);
      $this->_formValues['member_start_date_low'] = $this->_defaults['member_start_date_low'] = $date;
    }

    $toDate = CRM_Utils_Request::retrieve('end', 'Date');
    if ($toDate) {
      [$date] = CRM_Utils_Date::setDateDefaults($toDate);
      $this->_formValues['member_start_date_high'] = $this->_defaults['member_start_date_high'] = $date;
    }
    $joinDate = CRM_Utils_Request::retrieve('join', 'Date');
    if ($joinDate) {
      [$date] = CRM_Utils_Date::setDateDefaults($joinDate);
      $this->_formValues['member_join_date_low'] = $this->_defaults['member_join_date_low'] = $date;
    }

    $joinEndDate = CRM_Utils_Request::retrieve('joinEnd', 'Date');
    if ($joinEndDate) {
      [$date] = CRM_Utils_Date::setDateDefaults($joinEndDate);
      $this->_formValues['member_join_date_high'] = $this->_defaults['member_join_date_high'] = $date;
    }

    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive',
      $this
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Memberships');
  }

  /**
   * Set the metadata for the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Membership' => CRM_Member_BAO_Query::getSearchFieldMetadata()]);
  }

}
