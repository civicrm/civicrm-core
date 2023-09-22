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
 * Files required
 */
class CRM_Campaign_Form_Gotv extends CRM_Core_Form {

  /**
   * Are we forced to run a search
   *
   * @var int
   */
  protected $_force;

  protected $_votingTab = FALSE;

  protected $_searchVoterFor;

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->_search = $_GET['search'] ?? NULL;
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_surveyId = CRM_Utils_Request::retrieve('sid', 'Positive', $this);
    $this->_interviewerId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //does control come from voting tab interface.
    $this->_votingTab = $this->get('votingTab');
    $this->_subVotingTab = $this->get('subVotingTab');
    $this->_searchVoterFor = 'gotv';
    if ($this->_votingTab) {
      if ($this->_subVotingTab == 'searchANDReserve') {
        $this->_searchVoterFor = 'reserve';
      }
      elseif ($this->_subVotingTab == 'searchANDInterview') {
        $this->_searchVoterFor = 'interview';
      }
    }
    $this->assign('force', $this->_force);
    $this->assign('votingTab', $this->_votingTab);
    $this->assign('searchParams', json_encode($this->get('searchParams')));
    $this->assign('buildSelector', $this->_search);
    $this->assign('searchVoterFor', $this->_searchVoterFor);
    $this->set('searchVoterFor', $this->_searchVoterFor);

    $surveyTitle = NULL;
    if ($this->_surveyId) {
      $surveyTitle = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $this->_surveyId, 'title');
    }
    $this->assign('surveyTitle', $surveyTitle);

    //append breadcrumb to survey dashboard.
    if (CRM_Campaign_BAO_Campaign::accessCampaign()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey(s)'), 'url' => $url]]);
    }

    //set the form title.
    $this->setTitle(ts('GOTV (Voter Tracking)'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_search) {
      return;
    }

    //build common search form.
    CRM_Campaign_BAO_Query::buildSearchForm($this);

    //build the array of all search params.
    $this->_searchParams = [];
    foreach ($this->_elements as $element) {
      $name = $element->_attributes['name'];
      if ($name == 'qfKey') {
        continue;
      }
      $this->_searchParams[$name] = $name;
    }
    $this->set('searchParams', $this->_searchParams);
    $this->assign('searchParams', json_encode($this->_searchParams));

    $defaults = [];

    if (!$this->_surveyId) {
      $this->_surveyId = key(CRM_Campaign_BAO_Survey::getSurveys(TRUE, TRUE));
    }

    if ($this->_force || $this->_votingTab) {
      $session = CRM_Core_Session::singleton();
      $userId = $session->get('userID');
      // get interviewer id
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive',
        CRM_Core_DAO::$_nullObject, FALSE, $userId
      );

      $defaults['survey_interviewer_id'] = $cid;
    }
    if ($this->_surveyId) {
      $defaults['campaign_survey_id'] = $this->_surveyId;
    }
    if (!empty($defaults)) {
      $this->setDefaults($defaults);
    }

    //validate the required ids.
    $this->validateIds();
  }

  public function validateIds() {
    $errorMessages = [];
    //check for required permissions.
    if (!CRM_Core_Permission::check('manage campaign') &&
      !CRM_Core_Permission::check('administer CiviCampaign') &&
      !CRM_Core_Permission::check("{$this->_searchVoterFor} campaign contacts")
    ) {
      $errorMessages[] = ts('You are not authorized to access this page.');
    }

    $surveys = CRM_Campaign_BAO_Survey::getSurveys();
    if (empty($surveys)) {
      $errorMessages[] = ts("It looks like no surveys have been created yet. <a %1>Click here to create a new survey.</a>", [1 => 'href="' . CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add') . '"']);
    }

    if ($this->_force && !$this->_surveyId) {

      $errorMessages[] = ts('Could not find Survey.');

    }

    $this->assign('errorMessages', empty($errorMessages) ? FALSE : $errorMessages);
  }

}
