<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class provides the functionality to add contacts for voter reservation.
 */
class CRM_Campaign_Form_Task_Release extends CRM_Campaign_Form_Task {

  /**
   * Survet id
   *
   * @var int
   */
  protected $_surveyId;

  /**
   * Number of voters
   *
   * @var int
   */
  protected $_interviewerId;

  /**
   * Survey details
   *
   * @var object
   */
  protected $_surveyDetails;

  protected $_surveyActivities;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->_interviewToRelease = $this->get('interviewToRelease');
    if ($this->_interviewToRelease) {
      //user came from interview form.
      foreach (array(
                 'surveyId',
                 'contactIds',
                 'interviewerId',
               ) as $fld) {
        $this->{"_$fld"} = $this->get($fld);
      }

      if (!empty($this->_contactIds)) {
        $this->assign('totalSelectedContacts', count($this->_contactIds));
      }
    }
    else {
      parent::preProcess();
      //get the survey id from user submitted values.
      $this->_surveyId = CRM_Utils_Array::value('campaign_survey_id', $this->get('formValues'));
      $this->_interviewerId = CRM_Utils_Array::value('survey_interviewer_id', $this->get('formValues'));
    }

    if (!$this->_surveyId) {
      CRM_Core_Error::statusBounce(ts("Please search with 'Survey', to apply this action."));
    }
    if (!$this->_interviewerId) {
      CRM_Core_Error::statusBounce(ts('Missing Interviewer contact.'));
    }
    if (!is_array($this->_contactIds) || empty($this->_contactIds)) {
      CRM_Core_Error::statusBounce(ts('Could not find respondents to release.'));
    }

    $surveyDetails = array();
    $params = array('id' => $this->_surveyId);
    $this->_surveyDetails = CRM_Campaign_BAO_Survey::retrieve($params, $surveyDetails);

    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $statusIds = array();
    foreach (array(
               'Scheduled',
             ) as $name) {
      if ($statusId = array_search($name, $activityStatus)) {
        $statusIds[] = $statusId;
      }
    }
    //fetch the target survey activities.
    $this->_surveyActivities = CRM_Campaign_BAO_Survey::voterActivityDetails($this->_surveyId,
      $this->_contactIds,
      $this->_interviewerId,
      $statusIds
    );
    if (count($this->_surveyActivities) < 1) {
      CRM_Core_Error::statusBounce(ts('We could not found respondent for this survey to release.'));
    }

    $this->assign('surveyTitle', $surveyDetails['title']);

    //append breadcrumb to survey dashboard.
    if (CRM_Campaign_BAO_Campaign::accessCampaign()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb(array(array('title' => ts('Survey(s)'), 'url' => $url)));
    }

    //set the title.
    CRM_Utils_System::setTitle(ts('Release Respondents'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->addDefaultButtons(ts('Release Respondents'), 'done');
  }

  public function postProcess() {
    $deleteActivityIds = array();
    foreach ($this->_contactIds as $cid) {
      if (array_key_exists($cid, $this->_surveyActivities)) {
        $deleteActivityIds[] = $this->_surveyActivities[$cid]['activity_id'];
      }
    }

    //set survey activities as deleted = true.
    if (!empty($deleteActivityIds)) {
      $query = 'UPDATE civicrm_activity SET is_deleted = 1 WHERE id IN ( ' . implode(', ', $deleteActivityIds) . ' )';
      CRM_Core_DAO::executeQuery($query);

      if ($deleteActivityIds) {
        $status = ts("Respondent has been released.", array(
          'count' => count($deleteActivityIds),
          'plural' => '%count respondents have been released.',
        ));
        CRM_Core_Session::setStatus($status, ts('Released'), 'success');
      }

      if (count($this->_contactIds) > count($deleteActivityIds)) {
        $status = ts('1 respondent did not release.',
          array(
            'count' => (count($this->_contactIds) - count($deleteActivityIds)),
            'plural' => '%count respondents did not release.',
          )
        );
        CRM_Core_Session::setStatus($status, ts('Notice'), 'alert');
      }
    }
  }

}
