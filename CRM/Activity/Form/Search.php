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
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This file is for activity search.
 */
class CRM_Activity_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * Prefix for the controller.
   */
  protected $_prefix = "activity_";

  /**
   * The saved search ID retrieved from the GET vars.
   *
   * @var int
   */
  protected $_ssID;

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    // set the button names
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->defaults = array();

    // we allow the controller to set force/reset externally, useful when we are being
    // driven by the wizard framework
    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');

    $this->assign("context", $this->_context);

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
    $selector = new CRM_Activity_Selector_Search($this->_queryParams,
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

    CRM_Activity_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $permission = CRM_Core_Permission::getPermission();

      $this->addTaskMenu(CRM_Activity_Task::permissionedTaskTitles($permission));
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
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $specialParams = array(
        'activity_type_id',
        'status_id',
        'activity_subject',
      );
      $changeNames = array('status_id' => 'activity_status_id');
      CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams, $changeNames);
    }

    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    // We don't show test records in summaries or dashboards
    if (empty($this->_formValues['activity_test']) && $this->_force) {
      $this->_formValues["activity_test"] = 0;
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

    $selector = new CRM_Activity_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'basic' || $this->_context == 'user') {
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

    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $status = CRM_Utils_Request::retrieve('status', 'String', $this);
    if ($status) {
      $this->_formValues['activity_status_id'] = $status;
      $this->_defaults['activity_status_id'] = $status;
    }

    $survey = CRM_Utils_Request::retrieve('survey', 'Positive', CRM_Core_DAO::$_nullObject);

    if ($survey) {
      $this->_formValues['activity_survey_id'] = $this->_defaults['activity_survey_id'] = $survey;
      $sid = CRM_Utils_Array::value('activity_survey_id', $this->_formValues);
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

    // Added for membership search

    $signupType = CRM_Utils_Request::retrieve('signupType', 'Positive',
      CRM_Core_DAO::$_nullObject
    );

    if ($signupType) {
      $this->_formValues['activity_role'] = 1;
      $this->_defaults['activity_role'] = 1;
      $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');

      $renew = CRM_Utils_Array::key('Membership Renewal', $activityTypes);
      $signup = CRM_Utils_Array::key('Membership Signup', $activityTypes);

      switch ($signupType) {
        case 3: // signups and renewals
          $this->_formValues['activity_type_id'][$renew] = 1;
          $this->_defaults['activity_type_id'][$renew] = 1;
        case 1: // signups only
          $this->_formValues['activity_type_id'][$signup] = 1;
          $this->_defaults['activity_type_id'][$signup] = 1;
          break;

        case 2: // renewals only
          $this->_formValues['activity_type_id'][$renew] = 1;
          $this->_defaults['activity_type_id'][$renew] = 1;
          break;
      }
    }

    $dateLow = CRM_Utils_Request::retrieve('dateLow', 'String',
      CRM_Core_DAO::$_nullObject
    );

    if ($dateLow) {
      $dateLow = date('m/d/Y', strtotime($dateLow));
      $this->_formValues['activity_date_relative'] = 0;
      $this->_defaults['activity_date_relative'] = 0;
      $this->_formValues['activity_date_low'] = $dateLow;
      $this->_defaults['activity_date_low'] = $dateLow;
    }

    $dateHigh = CRM_Utils_Request::retrieve('dateHigh', 'String',
      CRM_Core_DAO::$_nullObject
    );

    if ($dateHigh) {
      // Activity date time assumes midnight at the beginning of the date
      // This sets it to almost midnight at the end of the date
      /*   if ($dateHigh <= 99999999) {
      $dateHigh = 1000000 * $dateHigh + 235959;
      } */
      $dateHigh = date('m/d/Y', strtotime($dateHigh));
      $this->_formValues['activity_date_relative'] = 0;
      $this->_defaults['activity_date_relative'] = 0;
      $this->_formValues['activity_date_high'] = $dateHigh;
      $this->_defaults['activity_date_high'] = $dateHigh;
    }

    // Enable search activity by custom value
    $requestParams = CRM_Utils_Request::exportValues();
    foreach (array_keys($requestParams) as $key) {
      if (substr($key, 0, 7) != 'custom_') {
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
    return ts('Find Activities');
  }

}
