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
 * This file is for CiviGrant search
 */
class CRM_Grant_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Prefix for the controller.
   *
   * @var string
   */
  protected $_prefix = 'grant_';

  /**
   * Metadata of all fields to include on the form.
   *
   * @var array
   */
  protected $searchFieldMetadata = [];

  /**
   * @return string
   */
  public function getDefaultEntity() {
    return 'Grant';
  }

  /**
   * Processing needed for buildForm and later.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    /**
     * set the button names
     */
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->sortNameOnly = TRUE;

    parent::preProcess();

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Grant_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $prefix = NULL;
    if ($this->_context === 'user') {
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
    $this->addSortNameField();

    (new CRM_Grant_BAO_Query())
      ->buildAdvancedSearchPaneForm($this, 'CiviGrant');

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $this->addTaskMenu(CRM_Grant_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()));
    }

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
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    $this->setFormValues();
    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName === $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }

    $selector = new CRM_Grant_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context === 'basic' || $this->_context === 'user') {
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

    $query = &$selector->getQuery();
    if ($this->_context === 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  /**
   * Hack form values :-(.
   *
   * @throws \CRM_Core_Exception
   */
  public function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)

    if (!$this->_force) {
      return;
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;

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
    return ts('Find Grants');
  }

  /**
   * Get metadata for fields being assigned by metadata.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getEntityMetadata() {
    return CRM_Grant_BAO_Query::getSearchFieldMetadata();
  }

  /**
   * Set the metadata for the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Grant' => $this->getEntityMetadata()]);
  }

}
