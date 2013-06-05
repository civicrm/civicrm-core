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
 * This class generates view mode for CiviCase
 *
 */
class CRM_Case_Form_CaseView extends CRM_Core_Form {
  /*
     * check for merge cases.
     */

  private $_mergeCases = FALSE;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // js for changing activity status
    CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'templates/CRM/Case/Form/ActivityChangeStatus.js');

    $this->_showRelatedCases = CRM_Utils_Array::value('relatedCases', $_GET);

    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    //pull the related cases.
    $this->assign('showRelatedCases', FALSE);
    if ($this->_showRelatedCases) {
      $relatedCases = $this->get('relatedCases');
      if (!isset($relatedCases)) {
        $cId          = CRM_Utils_Request::retrieve('cid', 'Integer', CRM_Core_DAO::$_nullObject);
        $caseId       = CRM_Utils_Request::retrieve('id', 'Integer', CRM_Core_DAO::$_nullObject);
        $relatedCases = CRM_Case_BAO_Case::getRelatedCases($caseId, $cId);
      }
      $this->assign('relatedCases', $relatedCases);
      $this->assign('showRelatedCases', TRUE);
      return;
    }

    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }
    $this->_hasAccessToAllCases = CRM_Core_Permission::check('access all cases and activities');
    $this->assign('hasAccessToAllCases', $this->_hasAccessToAllCases);

    $this->_contactID = $this->get('cid');
    $this->_caseID = $this->get('id');

    $fulltext = CRM_Utils_Request::retrieve('context', 'String', CRM_Core_DAO::$_nullObject);
    if ($fulltext == 'fulltext') {
      $this->assign('fulltext', $fulltext);
    }

    $this->assign('caseID', $this->_caseID);
    $this->assign('contactID', $this->_contactID);

    //validate case id.
    $this->_userCases = array();
    $session          = CRM_Core_Session::singleton();
    $userID           = $session->get('userID');
    if (!$this->_hasAccessToAllCases) {
      $this->_userCases = CRM_Case_BAO_Case::getCases(FALSE, $userID);
      if (!array_key_exists($this->_caseID, $this->_userCases)) {
        CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
      }
    }
    $this->assign('userID', $userID);

    if (CRM_Case_BAO_Case::caseCount($this->_contactID) >= 2) {
      $this->_mergeCases = TRUE;
    }
    $this->assign('mergeCases', $this->_mergeCases);

    //retrieve details about case
    $params = array('id' => $this->_caseID);

    $returnProperties = array('case_type_id', 'subject', 'status_id', 'start_date');
    CRM_Core_DAO::commonRetrieve('CRM_Case_BAO_Case', $params, $values, $returnProperties);

    $values['case_type_id'] = trim(CRM_Utils_Array::value('case_type_id', $values),
      CRM_Core_DAO::VALUE_SEPARATOR
    );
    $values['case_type_id'] = explode(CRM_Core_DAO::VALUE_SEPARATOR,
      CRM_Utils_Array::value('case_type_id', $values)
    );

    $statuses     = CRM_Case_PseudoConstant::caseStatus('label', FALSE);
    $caseTypeName = CRM_Case_BAO_Case::getCaseType($this->_caseID, 'name');
    $caseType     = CRM_Case_BAO_Case::getCaseType($this->_caseID);

    $this->_caseDetails = array(
      'case_type' => $caseType,
      'case_status' => $statuses[$values['case_status_id']],
      'case_subject' => CRM_Utils_Array::value('subject', $values),
      'case_start_date' => $values['case_start_date'],
    );
    $this->_caseType = $caseTypeName;
    $this->assign('caseDetails', $this->_caseDetails);

    $newActivityUrl = CRM_Utils_System::url('civicrm/case/activity',
      "action=add&reset=1&cid={$this->_contactID}&caseid={$this->_caseID}&atype=",
      FALSE, NULL, FALSE
    );
    $this->assign('newActivityUrl', $newActivityUrl);

    // Send Email activity requires a different URL format from all other activities
    $newActivityEmailUrl = CRM_Utils_System::url('civicrm/activity/email/add',
      "action=add&context=standalone&reset=1&caseid={$this->_caseID}&atype=",
      FALSE, NULL, FALSE
    );
    $this->assign('newActivityEmailUrl', $newActivityEmailUrl);

    $reportUrl = CRM_Utils_System::url('civicrm/case/report',
      "reset=1&cid={$this->_contactID}&caseid={$this->_caseID}&asn=",
      FALSE, NULL, FALSE
    );
    $this->assign('reportUrl', $reportUrl);

    // add to recently viewed

    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "action=view&reset=1&id={$this->_caseID}&cid={$this->_contactID}&context=home"
    );

    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactID);
    $this->assign('displayName', $displayName);

    CRM_Utils_System::setTitle($displayName . ' - ' . $caseType);

    $recentOther = array();
    if (CRM_Core_Permission::checkActionPermission('CiviCase', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/case',
        "action=delete&reset=1&id={$this->_caseID}&cid={$this->_contactID}&context=home"
      );
    }

    // Add the recently viewed case
    CRM_Utils_Recent::add($displayName . ' - ' . $caseType,
      $url,
      $this->_caseID,
      'Case',
      $this->_contactID,
      NULL,
      $recentOther
    );


    //get the related cases for given case.
    $relatedCases = $this->get('relatedCases');
    if (!isset($relatedCases)) {
      $relatedCases = CRM_Case_BAO_Case::getRelatedCases($this->_caseID, $this->_contactID);
      $relatedCases = empty($relatedCases) ? FALSE : $relatedCases;
      $this->set('relatedCases', $relatedCases);
    }
    $this->assign('hasRelatedCases', $relatedCases);

    $entitySubType = !empty($values['case_type_id']) ? $values['case_type_id'][0] : NULL;
    $this->assign('caseTypeID', $entitySubType);
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Case',
      $this,
      $this->_caseID,
      NULL,
      $entitySubType
    );
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this,
      $groupTree
    );
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    //this call is for show related cases.
    if ($this->_showRelatedCases) {
      return;
    }

    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $caseRoles    = $xmlProcessor->get($this->_caseType, 'CaseRoles');
    $reports      = $xmlProcessor->get($this->_caseType, 'ActivitySets');

    //adding case manager.CRM-4510.
    $managerRoleId = $xmlProcessor->getCaseManagerRoleId($this->_caseType);
    if (!empty($managerRoleId)) {
      $caseRoles[$managerRoleId] = $caseRoles[$managerRoleId] . '<br />' . '(' . ts('Case Manager') . ')';
    }

    $aTypes = $xmlProcessor->get($this->_caseType, 'ActivityTypes', TRUE);

    $allActTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name');

    // remove Open Case activity type since we're inside an existing case
    if (($openActTypeId = array_search('Open Case', $allActTypes)) &&
      array_key_exists($openActTypeId, $aTypes)
    ) {
      unset($aTypes[$openActTypeId]);
    }

    //check for link cases.
    $unclosedCases = CRM_Case_BAO_Case::getUnclosedCases(NULL, array($this->_caseID));
    if (empty($unclosedCases) &&
      ($linkActTypeId = array_search('Link Cases', $allActTypes)) &&
      array_key_exists($linkActTypeId, $aTypes)
    ) {
      unset($aTypes[$linkActTypeId]);
    }

    if (!$xmlProcessor->getNaturalActivityTypeSort()) {
      asort($aTypes);
    }

    $this->add('select', 'activity_type_id', ts('New Activity'), array('' => ts('- select activity type -')) + $aTypes);
    if ($this->_hasAccessToAllCases) {
      $this->add('select', 'report_id', ts('Run QA Audit / Redact'),
        array(
          '' => ts('- select activity set -')) + $reports
      );
      $this->add('select', 'timeline_id', ts('Add Timeline'),
        array(
          '' => ts('- select activity set -')) + $reports
      );
    }
    $this->addElement('submit', $this->getButtonName('next'), ts('Go'),
      array(
        'class' => 'form-submit-inline',
        'onclick' => "return checkSelection( this );",
      )
    );

    if ($this->_mergeCases) {
      $allCases = CRM_Case_BAO_Case::getContactCases($this->_contactID);
      $otherCases = array();
      foreach ($allCases as $caseId => $details) {
        //filter current and own cases.
        if (($caseId == $this->_caseID) ||
          (!$this->_hasAccessToAllCases &&
            !array_key_exists($caseId, $this->_userCases)
          )
        ) {
          continue;
        }

        $otherCases[$caseId] = 'Case ID: ' . $caseId . ' Type: ' . $details['case_type'] . ' Start: ' . $details['case_start_date'];
      }
      if (empty($otherCases)) {
        $this->_mergeCases = FALSE;
        $this->assign('mergeCases', $this->_mergeCases);
      }
      else {
        $this->add('select', 'merge_case_id',
          ts('Select Case for Merge'),
          array(
            '' => ts('- select case -')) + $otherCases
        );
        $this->addElement('submit',
          $this->getButtonName('next', 'merge_case'),
          ts('Merge'),
          array(
            'class' => 'form-submit-inline',
            'onclick' => "return checkSelection( this );",
          )
        );
      }
    }

    $this->add('text', 'change_client_id', ts('Assign to another Client'));
    $this->add('hidden', 'contact_id', '', array('id' => 'contact_id'));
    $this->addElement('submit',
      $this->getButtonName('next', 'edit_client'),
      ts('Reassign Case'),
      array(
        'class' => 'form-submit-inline',
        'onclick' => "return checkSelection( this );",
      )
    );

    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $this->add('select', 'status_id', ts('Status'), array("" => ts(' - any status - ')) + $activityStatus);

    // activity dates
    $this->addDate('activity_date_low', ts('Activity Dates - From'), FALSE, array('formatType' => 'searchDate'));
    $this->addDate('activity_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->add('checkbox', 'activity_deleted', ts('Deleted Activities'));
    }

    //get case related relationships (Case Role)
    $caseRelationships = CRM_Case_BAO_Case::getCaseRoles($this->_contactID, $this->_caseID);

    //save special label because we unset it in the loop
    $managerLabel = empty($managerRoleId) ? '' : $caseRoles[$managerRoleId];

    //build reporter select
    $reporters = array("" => ts(' - any reporter - '));
    foreach ($caseRelationships as $key => & $value) {
      $reporters[$value['cid']] = $value['name'] . " ( {$value['relation']} )";

      if (!empty($managerRoleId)) {
        if ($managerRoleId == $value['relation_type']) {
          $value['relation'] = $managerLabel;
        }
      }

      //calculate roles that don't have relationships
      if (CRM_Utils_Array::value($value['relation_type'], $caseRoles)) {
        unset($caseRoles[$value['relation_type']]);
      }
    }

    // take all case activity types for search filter, CRM-7187
    $aTypesFilter = array();
    $allCaseActTypes = CRM_Case_PseudoConstant::caseActivityType();
    foreach ($allCaseActTypes as $typeDetails) {
      if (!in_array($typeDetails['name'], array(
        'Open Case'))) {
        $aTypesFilter[$typeDetails['id']] = CRM_Utils_Array::value('label', $typeDetails);
      }
    }
    asort($aTypesFilter);
    $this->add('select', 'activity_type_filter_id', ts('Activity Type'), array('' => ts('- select activity type -')) + $aTypesFilter);

    $this->assign('caseRelationships', $caseRelationships);

    //also add client as role. CRM-4438
    $caseRoles['client'] = CRM_Case_BAO_Case::getContactNames($this->_caseID);

    $this->assign('caseRoles', $caseRoles);

    $this->add('select', 'reporter_id', ts('Reporter/Role'), $reporters);

    // Retrieve ALL client relationships
    $relClient = CRM_Contact_BAO_Relationship::getRelationship($this->_contactID,
      CRM_Contact_BAO_Relationship::CURRENT,
      0, 0, 0, NULL, NULL, FALSE
    );

    // Now build 'Other Relationships' array by removing relationships that are already listed under Case Roles
    // so they don't show up twice.
    $clientRelationships = array();
    foreach ($relClient as $r) {
      if (!array_key_exists($r['id'], $caseRelationships)) {
        $clientRelationships[] = $r;
      }
    }
    $this->assign('clientRelationships', $clientRelationships);

    // Now global contact list that appears on all cases.
    $globalGroupInfo = array();
    $relGlobal = CRM_Case_BAO_Case::getGlobalContacts($globalGroupInfo);
    $this->assign('globalRelationships', $relGlobal);
    $this->assign('globalGroupInfo', $globalGroupInfo);

    // List of relationship types
    $baoRel    = new CRM_Contact_BAO_Relationship();
    $relType   = $baoRel->getRelationType('Individual');
    $roleTypes = array();
    foreach ($relType as $k => $v) {
      $roleTypes[substr($k, 0, strpos($k, '_'))] = $v;
    }
    $this->add('select', 'role_type', ts('Relationship Type'), array('' => ts('- select type -')) + $roleTypes);

    $hookCaseSummary = CRM_Utils_Hook::caseSummary($this->_caseID);
    if (is_array($hookCaseSummary)) {
      $this->assign('hookCaseSummary', $hookCaseSummary);
    }


    $allTags = CRM_Core_BAO_Tag::getTags('civicrm_case');

    if (!empty($allTags)) {
      $this->add('select', 'case_tag', ts('Tags'), $allTags, FALSE,
        array('id' => 'tags', 'multiple' => 'multiple', 'title' => ts('- select -'))
      );

      $tags = CRM_Core_BAO_EntityTag::getTag($this->_caseID, 'civicrm_case');

      $this->setDefaults(array('case_tag' => $tags));

      foreach ($tags as $tid) {
        $tags[$tid] = $allTags[$tid];
      }

      $this->assign('tags', implode(', ', array_filter($tags)));
      $this->assign('showTags', TRUE);
    }
    else {
      $this->assign('showTags', FALSE);
    }

    // build tagset widget

    // see if we have any tagsets which can be assigned to cases
    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_case');
    if ($parentNames) {
      $this->assign('showTagsets', TRUE);
    }
    else {
      $this->assign('showTagsets', FALSE);
    }
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_case', $this->_caseID, FALSE, TRUE);

    $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $buttonName = $this->controller->getButtonName();

    // user context
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "reset=1&action=view&cid={$this->_contactID}&id={$this->_caseID}&show=1"
    );
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);

    if (CRM_Utils_Array::value('timeline_id', $params) &&
      CRM_Utils_Array::value('_qf_CaseView_next', $_POST)
    ) {
      $session            = CRM_Core_Session::singleton();
      $this->_uid         = $session->get('userID');
      $xmlProcessor       = new CRM_Case_XMLProcessor_Process();
      $xmlProcessorParams = array(
        'clientID' => $this->_contactID,
        'creatorID' => $this->_uid,
        'standardTimeline' => 0,
        'activity_date_time' => date('YmdHis'),
        'caseID' => $this->_caseID,
        'caseType' => $this->_caseType,
        'activitySetName' => $params['timeline_id'],
      );
      $xmlProcessor->run($this->_caseType, $xmlProcessorParams);
      $reports = $xmlProcessor->get($this->_caseType, 'ActivitySets');

      CRM_Core_Session::setStatus(ts('Activities from the %1 activity set have been added to this case.',
          array(1 => $reports[$params['timeline_id']])
      ), ts('Done'), 'success');
    }
    elseif ($this->_mergeCases &&
      $buttonName == '_qf_CaseView_next_merge_case'
    ) {

      $mainCaseId = $params['merge_case_id'];
      $otherCaseId = $this->_caseID;

      //merge two cases.
      CRM_Case_BAO_Case::mergeCases($this->_contactID, $mainCaseId, NULL, $otherCaseId);

      //redirect user to main case view.
      $url = CRM_Utils_System::url('civicrm/contact/view/case',
        "reset=1&action=view&cid={$this->_contactID}&id={$mainCaseId}&show=1"
      );
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
    }
    elseif ($buttonName == '_qf_CaseView_next_edit_client') {
      $mainCaseId = CRM_Case_BAO_Case::mergeCases($params['contact_id'], $this->_caseID, $this->_contactID, NULL, TRUE);

      // user context
      $url = CRM_Utils_System::url('civicrm/contact/view/case',
        "reset=1&action=view&cid={$params['contact_id']}&id={$mainCaseId[0]}&show=1"
      );
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
    }
  }
}

