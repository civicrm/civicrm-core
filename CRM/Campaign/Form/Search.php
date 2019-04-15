<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Files required.
 */
class CRM_Campaign_Form_Search extends CRM_Core_Form_Search {

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
   * @var string
   */
  protected $_prefix = "survey_";


  private $_operation = 'reserve';

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->_done = FALSE;
    $this->_defaults = array();

    //set the button name.
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_printButtonName = $this->getButtonName('next', 'print');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->loadStandardSearchOptionsFromUrl();

    //operation for state machine.
    $this->_operation = CRM_Utils_Request::retrieve('op', 'String', $this, FALSE, 'reserve');
    //validate operation.
    if (!in_array($this->_operation, array(
      'reserve',
      'release',
      'interview',
    ))
    ) {
      $this->_operation = 'reserve';
      $this->set('op', $this->_operation);
    }
    $this->set('searchVoterFor', $this->_operation);
    $this->assign('searchVoterFor', $this->_operation);
    $this->assign('isFormSubmitted', $this->isSubmitted());

    //do check permissions.
    if (!CRM_Core_Permission::check('administer CiviCampaign') &&
      !CRM_Core_Permission::check('manage campaign') &&
      !CRM_Core_Permission::check("{$this->_operation} campaign contacts")
    ) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }

    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this

    if (empty($_POST)) {
      $this->_formValues = $this->get('formValues');
    }
    else {
      $this->_formValues = $this->controller->exportValues($this->_name);
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

    //get the voter clause.
    $voterClause = $this->voterClause();

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $selector = new CRM_Campaign_Selector_Search($this->_queryParams,
      $this->_action,
      $voterClause,
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

    //append breadcrumb to survey dashboard.
    if (CRM_Campaign_BAO_Campaign::accessCampaign()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb(array(array('title' => ts('Survey(s)'), 'url' => $url)));
    }

    //set the form title.
    CRM_Utils_System::setTitle(ts('Find Respondents To %1', array(1 => ucfirst($this->_operation))));
  }

  /**
   * Load the default survey for all actions.
   *
   * @return array
   */
  public function setDefaultValues() {
    if (empty($this->_defaults)) {
      $defaultSurveyId = key(CRM_Campaign_BAO_Survey::getSurveys(TRUE, TRUE));
      if ($defaultSurveyId) {
        $this->_defaults['campaign_survey_id'] = $defaultSurveyId;
      }
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    //build the search form.
    CRM_Campaign_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $allTasks = CRM_Campaign_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());

      //hack to serve right page to state machine.
      $taskMapping = array(
        'interview' => CRM_Campaign_Task::INTERVIEW,
        'reserve' => CRM_Campaign_Task::RESERVE,
        'release' => CRM_Campaign_Task::RELEASE,
      );

      $currentTaskValue = CRM_Utils_Array::value($this->_operation, $taskMapping);
      $taskValue = array($currentTaskValue => $allTasks[$currentTaskValue]);
      if ($this->_operation == 'interview' && !empty($this->_formValues['campaign_survey_id'])) {
        $activityTypes = CRM_Core_PseudoConstant::activityType(FALSE, TRUE, FALSE, 'label', TRUE);

        $surveyTypeId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey',
          $this->_formValues['campaign_survey_id'],
          'activity_type_id'
        );
        $taskValue = array(
          $currentTaskValue => ts('Record %1 Responses',
            array(1 => $activityTypes[$surveyTypeId])
          ),
        );
      }

      $this->addTaskMenu($taskValue);
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
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }

    $this->fixFormValues();

    //format params as per task.
    $this->formatParams();

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

    //get the voter clause.
    $voterClause = $this->voterClause();

    $selector = new CRM_Campaign_Selector_Search($this->_queryParams,
      $this->_action,
      $voterClause,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'basic' ||
      $this->_context == 'user'
    ) {
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
    $query = $selector->getQuery();
    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  public function formatParams() {
    $interviewerId = CRM_Utils_Array::value('survey_interviewer_id', $this->_formValues);
    if ($interviewerId) {
      $this->set('interviewerId', $interviewerId);
    }

    //format multi-select group and contact types.
    foreach (array('group', 'contact_type') as $param) {
      if ($this->_force) {
        continue;
      }
      $paramValue = CRM_Utils_Array::value($param, $this->_formValues);
      if ($paramValue && is_array($paramValue)) {
        unset($this->_formValues[$param]);
        foreach ($paramValue as $key => $value) {
          $this->_formValues[$param][$value] = 1;
        }
      }
    }

    //apply filter of survey contact type for search.
    $contactType = CRM_Campaign_BAO_Survey::getSurveyContactType(CRM_Utils_Array::value('campaign_survey_id', $this->_formValues));
    if ($contactType && in_array($this->_operation, ['reserve', 'interview'])) {
      $this->_formValues['contact_type'][$contactType] = 1;
    }

    if ($this->_operation == 'reserve') {
      if (!empty($this->_formValues['campaign_survey_id'])) {
        $campaignId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey',
          $this->_formValues['campaign_survey_id'],
          'campaign_id'
        );

        //allow voter search in sub-part of given constituents,
        //but make sure in case user does not select any group.
        //get all associated campaign groups in where filter, CRM-7406
        $groups = CRM_Utils_Array::value('group', $this->_formValues);
        if ($campaignId && CRM_Utils_System::isNull($groups)) {
          $campGroups = CRM_Campaign_BAO_Campaign::getCampaignGroups($campaignId);
          foreach ($campGroups as $id => $title) {
            $this->_formValues['group'][$id] = 1;
          }
        }

        //carry servey id w/ this.
        $this->set('surveyId', $this->_formValues['campaign_survey_id']);
        unset($this->_formValues['campaign_survey_id']);
      }
      unset($this->_formValues['survey_interviewer_id']);
    }
    elseif ($this->_operation == 'interview' ||
      $this->_operation == 'release'
    ) {
      //to conduct interview / release activity status should be scheduled.
      $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
      if ($scheduledStatusId = array_search('Scheduled', $activityStatus)) {
        $this->_formValues['survey_status_id'] = $scheduledStatusId;
      }
    }

    //pass voter search operation.
    $this->_formValues['campaign_search_voter_for'] = $this->_operation;
  }

  public function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)

    //since we have qfKey, no need to manipulate set defaults.
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String');

    if (!$this->_force || CRM_Utils_Rule::qfKey($qfKey)) {
      return;
    }

    // get survey id
    $surveyId = CRM_Utils_Request::retrieve('sid', 'Positive');

    if ($surveyId) {
      $surveyId = CRM_Utils_Type::escape($surveyId, 'Integer');
    }
    else {
      // use default survey id
      $surveyId = key(CRM_Campaign_BAO_Survey::getSurveys(TRUE, TRUE));
    }
    if (!$surveyId) {
      CRM_Core_Error::fatal('Could not find valid Survey Id.');
    }
    $this->_formValues['campaign_survey_id'] = $this->_formValues['campaign_survey_id'] = $surveyId;

    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');

    // get interviewer id
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive',
      CRM_Core_DAO::$_nullObject, FALSE, $userId
    );
    //to force other contact as interviewer, user should be admin.
    if ($cid != $userId &&
      !CRM_Core_Permission::check('administer CiviCampaign')
    ) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }
    $this->_formValues['survey_interviewer_id'] = $cid;
    //get all in defaults.
    $this->_defaults = $this->_formValues;
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
  }

  /**
   * @return array
   */
  public function voterClause() {
    $params = array('campaign_search_voter_for' => $this->_operation);

    $clauseFields = array(
      'surveyId' => 'campaign_survey_id',
      'interviewerId' => 'survey_interviewer_id',
    );

    foreach ($clauseFields as $param => $key) {
      $params[$key] = CRM_Utils_Array::value($key, $this->_formValues);
      if (!$params[$key]) {
        $params[$key] = $this->get($param);
      }
    }

    //build the clause.
    $voterClause = CRM_Campaign_BAO_Query::voterClause($params);

    return $voterClause;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Respondents');
  }

}
