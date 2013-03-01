<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Files required
 */
class CRM_Campaign_Form_Gotv extends CRM_Core_Form {

  /**
   * Are we forced to run a search
   *
   * @var int
   * @access protected
   */
  protected $_force;

  protected $_votingTab = FALSE;

  protected $_searchVoterFor;

  /**
   * processing needed for buildForm and later
   *
   * @return void
   * @access public
   */ function preProcess() {
    $this->_search = CRM_Utils_Array::value('search', $_GET);
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
      CRM_Utils_System::appendBreadCrumb(array(array('title' => ts('Survey(s)'), 'url' => $url)));
    }

    //set the form title.
    CRM_Utils_System::setTitle(ts('GOTV (Voter Tracking)'));
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    if ($this->_search) {
      return;
    }

    //build common search form.
    CRM_Campaign_BAO_Query::buildSearchForm($this);

    //build the array of all search params.
    $this->_searchParams = array();
    foreach ($this->_elements as $element) {
      $name = $element->_attributes['name'];
      if ($name == 'qfKey') {
        continue;
      }
      $this->_searchParams[$name] = $name;
    }
    $this->set('searchParams', $this->_searchParams);
    $this->assign('searchParams', json_encode($this->_searchParams));

    $defaults = array();

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
      $defaults['survey_interviewer_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $cid,
        'sort_name',
        'id'
      );
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

  function validateIds() {
    $errorMessages = array();
    //check for required permissions.
    if (!CRM_Core_Permission::check('manage campaign') &&
      !CRM_Core_Permission::check('administer CiviCampaign') &&
      !CRM_Core_Permission::check("{$this->_searchVoterFor} campaign contacts")
    ) {
      $errorMessages[] = ts('You are not authorized to access this page.');
    }

    $surveys = CRM_Campaign_BAO_Survey::getSurveys();
    if (empty($surveys)) {
      $errorMessages[] = ts("Oops. It looks like no surveys have been created. <a href='%1'>Click here to create a new survey.</a>", array(1 => CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add')));
    }

    if ($this->_force && !$this->_surveyId) {

      $errorMessages[] = ts('Could not find Survey.');

    }

    $this->assign('errorMessages', empty($errorMessages) ? FALSE : $errorMessages);
  }
}

