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
 * This file is for activity search.
 */
class CRM_Activity_Form_Search extends CRM_Core_Form_Search {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

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
  protected $_prefix = 'activity_';

  /**
   * @return string
   */
  public function getDefaultEntity(): string {
    return 'Activity';
  }

  /**
   * Processing needed for buildForm and later.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $this->set('searchFormName', 'Search');

    // set the button names
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->sortNameOnly = TRUE;

    parent::preProcess();

    if (empty($this->_formValues) && isset($this->_ssID)) {
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Activity_Selector_Search($this->_queryParams,
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
  public function buildQuickForm(): void {
    parent::buildQuickForm();
    $this->addSortNameField();

    CRM_Activity_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $this->addTaskMenu(CRM_Activity_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()));
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
   *
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;
    $this->setFormValues();
    if (!empty($_POST)) {
      $specialParams = [
        'activity_type_id',
        'priority_id',
      ];
      $changeNames = [
        'priority_id' => 'activity_priority_id',
      ];

      CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams, $changeNames);
    }
    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

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

    $selector = new CRM_Activity_Selector_Search($this->_queryParams,
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
   * Probably more hackery than anything else.
   *
   * @throws \CRM_Core_Exception
   */
  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $status = CRM_Utils_Request::retrieve('status', 'String', $this);
    if ($status) {
      $this->_formValues['activity_status_id'] = $status;
      $this->_defaults['activity_status_id'] = $status;
    }

    $survey = CRM_Utils_Request::retrieve('survey', 'Positive');

    if ($survey) {
      $this->_formValues['activity_survey_id'] = $this->_defaults['activity_survey_id'] = $survey;
      $sid = $this->_formValues['activity_survey_id'] ?? NULL;
      $activity_type_id = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $sid, 'activity_type_id');

      // since checkbox are replaced by multiple select option
      $this->_formValues['activity_type_id'] = $activity_type_id;
      $this->_defaults['activity_type_id'] = $activity_type_id;
    }
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;

        $activity_role = CRM_Utils_Request::retrieve('activity_role', 'Positive', $this);

        if ($activity_role) {
          $this->_formValues['activity_role'] = $activity_role;
        }
        else {
          $this->_defaults['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'sort_name');
        }
        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }

    // Enable search activity by custom value
    // @todo this is not good security practice. Instead define entity fields in metadata &
    // use getEntity Defaults
    $requestParams = CRM_Utils_Request::exportValues();
    foreach (array_keys($requestParams) as $key) {
      if (substr($key, 0, 7) !== 'custom_') {
        continue;
      }
      elseif (empty($requestParams[$key])) {
        continue;
      }
      $customValue = CRM_Utils_Request::retrieve($key, 'String', $this);
      if ($customValue) {
        $this->_formValues[$key] = $customValue;
        $this->_defaults[$key] = $customValue;
      }
    }

    if (!empty($this->_defaults)) {
      $this->setDefaults($this->_defaults);
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Activities');
  }

  /**
   * Get metadata for the entity  fields.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getEntityMetadata() {
    return CRM_Activity_BAO_Query::getSearchFieldMetadata();
  }

  /**
   * Set the metadata for the form.
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Activity' => $this->getEntityMetadata()]);
  }

}
