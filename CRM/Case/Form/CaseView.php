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
 * This class generates view mode for CiviCase.
 */
class CRM_Case_Form_CaseView extends CRM_Core_Form {
  /**
   * Check for merge cases.
   * @var bool
   */
  private $_mergeCases = FALSE;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_showRelatedCases = CRM_Utils_Array::value('relatedCases', $_GET);

    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    //pull the related cases.
    $this->assign('showRelatedCases', FALSE);
    if ($this->_showRelatedCases) {
      $relatedCases = $this->get('relatedCases');
      if (!isset($relatedCases)) {
        $cId = CRM_Utils_Request::retrieve('cid', 'Integer', CRM_Core_DAO::$_nullObject);
        $caseId = CRM_Utils_Request::retrieve('id', 'Integer', CRM_Core_DAO::$_nullObject);
        $relatedCases = CRM_Case_BAO_Case::getRelatedCases($caseId, $cId);
      }
      $this->assign('relatedCases', $relatedCases);
      $this->assign('showRelatedCases', TRUE);
      CRM_Utils_System::setTitle(ts('Related Cases'));
      return;
    }

    $this->_hasAccessToAllCases = CRM_Core_Permission::check('access all cases and activities');
    $this->assign('hasAccessToAllCases', $this->_hasAccessToAllCases);

    $this->assign('contactID', $this->_contactID = (int) $this->get('cid'));
    $this->assign('caseID', $this->_caseID = (int) $this->get('id'));

    // Access check.
    if (!CRM_Case_BAO_Case::accessCase($this->_caseID, FALSE)) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }

    $fulltext = CRM_Utils_Request::retrieve('context', 'String', CRM_Core_DAO::$_nullObject);
    if ($fulltext == 'fulltext') {
      $this->assign('fulltext', $fulltext);
    }

    $this->assign('contactType', CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactID, 'contact_type'));
    $this->assign('userID', CRM_Core_Session::getLoggedInContactID());

    //retrieve details about case
    $params = array('id' => $this->_caseID);

    $returnProperties = array('case_type_id', 'subject', 'status_id', 'start_date');
    CRM_Core_DAO::commonRetrieve('CRM_Case_BAO_Case', $params, $values, $returnProperties);

    $statuses = CRM_Case_PseudoConstant::caseStatus('label', FALSE);
    $caseTypeName = CRM_Case_BAO_Case::getCaseType($this->_caseID, 'name');
    $caseType = CRM_Case_BAO_Case::getCaseType($this->_caseID);

    $this->_caseDetails = array(
      'case_type' => $caseType,
      'case_status' => CRM_Utils_Array::value($values['case_status_id'], $statuses),
      'case_subject' => CRM_Utils_Array::value('subject', $values),
      'case_start_date' => $values['case_start_date'],
    );
    $this->_caseType = $caseTypeName;
    $this->assign('caseDetails', $this->_caseDetails);

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
    $this->assign('hasRelatedCases', (bool) $relatedCases);
    if ($relatedCases) {
      $this->assign('relatedCaseLabel', ts('%1 Related Case', array(
            'count' => count($relatedCases),
            'plural' => '%1 Related Cases',
          )));
      $this->assign('relatedCaseUrl', CRM_Utils_System::url('civicrm/contact/view/case', array(
        'id' => $this->_caseID,
        'cid' => $this->_contactID,
        'relatedCases' => 1,
        'action' => 'view',
      )));
    }

    $entitySubType = !empty($values['case_type_id']) ? $values['case_type_id'] : NULL;
    $this->assign('caseTypeID', $entitySubType);
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Case',
      $this,
      $this->_caseID,
      NULL,
      $entitySubType
    );
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->_caseID);
  }

  /**
   * Set default values for the form.
   *
   * @return array;
   */
  public function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //this call is for show related cases.
    if ($this->_showRelatedCases) {
      return;
    }

    $allowedRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType($this->_contactID);

    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'js/crm.livePage.js', 1, 'html-header')
      ->addScriptFile('civicrm', 'templates/CRM/Case/Form/CaseView.js', 2, 'html-header')
      ->addVars('relationshipTypes', CRM_Contact_Form_Relationship::getRelationshipTypeMetadata($allowedRelationshipTypes));

    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $caseRoles = $xmlProcessor->get($this->_caseType, 'CaseRoles');
    $reports = $xmlProcessor->get($this->_caseType, 'ActivitySets');

    //adding case manager.CRM-4510.
    $managerRoleId = $xmlProcessor->getCaseManagerRoleId($this->_caseType);
    if (!empty($managerRoleId)) {
      $caseRoles[$managerRoleId] = $caseRoles[$managerRoleId] . '<br />' . '(' . ts('Case Manager') . ')';
    }

    $aTypes = $xmlProcessor->get($this->_caseType, 'ActivityTypes', TRUE);

    $allActTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name');

    $emailActivityType = array_search('Email', $allActTypes);
    $pdfActivityType = array_search('Print PDF Letter', $allActTypes);

    if ($pdfActivityType) {
      $this->assign('exportDoc', CRM_Utils_System::url('civicrm/activity/pdf/add',
        "action=add&context=standalone&reset=1&cid={$this->_contactID}&caseid={$this->_caseID}&atype=$pdfActivityType"));
    }

    // remove Open Case activity type since we're inside an existing case
    if ($openActTypeId = array_search('Open Case', $allActTypes)) {
      unset($aTypes[$openActTypeId]);
    }

    // Only show "link cases" activity if other cases exist.
    $linkActTypeId = array_search('Link Cases', $allActTypes);
    if ($linkActTypeId) {
      $count = civicrm_api3('Case', 'getcount', array(
        'check_permissions' => TRUE,
        'id' => array('!=' => $this->_caseID),
        'is_deleted' => 0,
      ));
      if (!$count) {
        unset($aTypes[$linkActTypeId]);
      }
    }

    if (!$xmlProcessor->getNaturalActivityTypeSort()) {
      asort($aTypes);
    }

    $activityLinks = array('' => ts('Add Activity'));
    foreach ($aTypes as $type => $label) {
      if ($type == $emailActivityType) {
        $url = CRM_Utils_System::url('civicrm/activity/email/add',
          "action=add&context=standalone&reset=1&caseid={$this->_caseID}&atype=$type",
          FALSE, NULL, FALSE
        );
      }
      elseif ($type == $pdfActivityType) {
        $url = CRM_Utils_System::url('civicrm/activity/pdf/add',
          "action=add&context=standalone&reset=1&cid={$this->_contactID}&caseid={$this->_caseID}&atype=$type",
          FALSE, NULL, FALSE);
      }
      else {
        $url = CRM_Utils_System::url('civicrm/case/activity',
          "action=add&reset=1&cid={$this->_contactID}&caseid={$this->_caseID}&atype=$type",
          FALSE, NULL, FALSE
        );
      }
      $activityLinks[$url] = $label;
    }

    $this->add('select', 'add_activity_type_id', '', $activityLinks, FALSE, array('class' => 'crm-select2 crm-action-menu fa-calendar-check-o twenty'));
    if ($this->_hasAccessToAllCases) {
      $this->add('select', 'report_id', '',
        array('' => ts('Activity Audit')) + $reports,
        FALSE,
        array('class' => 'crm-select2 crm-action-menu fa-list-alt')
      );
      $this->add('select', 'timeline_id', '',
        array('' => ts('Add Timeline')) + $reports,
        FALSE,
        array('class' => 'crm-select2 crm-action-menu fa-list-ol')
      );
    }
    $this->addElement('submit', $this->getButtonName('next'), ' ', array('class' => 'hiddenElement'));

    $this->buildMergeCaseForm();

    //call activity form
    self::activityForm($this, $aTypes);

    //get case related relationships (Case Role)
    $caseRelationships = CRM_Case_BAO_Case::getCaseRoles($this->_contactID, $this->_caseID);

    //save special label because we unset it in the loop
    $managerLabel = empty($managerRoleId) ? '' : $caseRoles[$managerRoleId];

    foreach ($caseRelationships as $key => & $value) {
      if (!empty($managerRoleId)) {
        if ($managerRoleId == $value['relation_type']) {
          $value['relation'] = $managerLabel;
        }
      }

      //calculate roles that don't have relationships
      if (!empty($caseRoles[$value['relation_type']])) {
        unset($caseRoles[$value['relation_type']]);
      }
    }

    $this->assign('caseRelationships', $caseRelationships);

    //also add client as role. CRM-4438
    $caseRoles['client'] = CRM_Case_BAO_Case::getContactNames($this->_caseID);

    $this->assign('caseRoles', $caseRoles);

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
    CRM_Case_BAO_Case::getGlobalContacts($globalGroupInfo);
    $this->assign('globalGroupInfo', $globalGroupInfo);

    // List relationship types for adding an arbitrary new role to the case
    $this->add('select',
      'role_type',
      ts('Relationship Type'),
      array('' => ts('- select type -')) + $allowedRelationshipTypes,
      FALSE,
      array('class' => 'crm-select2 twenty', 'data-select-params' => '{"allowClear": false}')
    );

    $hookCaseSummary = CRM_Utils_Hook::caseSummary($this->_caseID);
    if (is_array($hookCaseSummary)) {
      $this->assign('hookCaseSummary', $hookCaseSummary);
    }

    CRM_Core_BAO_Tag::getTags('civicrm_case', $allTags, NULL,
      '&nbsp;&nbsp;', TRUE);

    if (!empty($allTags)) {
      $this->add('select', 'case_tag', ts('Tags'), $allTags, FALSE,
        array('id' => 'tags', 'multiple' => 'multiple', 'class' => 'crm-select2')
      );

      $tags = CRM_Core_BAO_EntityTag::getTag($this->_caseID, 'civicrm_case');

      $this->setDefaults(array('case_tag' => $tags));

      foreach ($tags as $tid) {
        if (isset($allTags[$tid])) {
          $tags[$tid] = $allTags[$tid];
        }
        else {
          unset($tags[$tid]);
        }
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
   * Process the form.
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

    if (!empty($params['timeline_id']) && !empty($_POST['_qf_CaseView_next'])) {
      $session = CRM_Core_Session::singleton();
      $this->_uid = $session->get('userID');
      $xmlProcessor = new CRM_Case_XMLProcessor_Process();
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
  }

  /**
   * Build the activity selector/datatable
   * @param CRM_Core_Form $form
   * @param array $aTypes
   *   To include acivities related to current case id $form->_caseID.
   */
  public static function activityForm($form, $aTypes = array()) {
    $caseRelationships = CRM_Case_BAO_Case::getCaseRoles($form->_contactID, $form->_caseID);
    //build reporter select
    $reporters = array("" => ts(' - any reporter - '));
    foreach ($caseRelationships as $key => & $value) {
      $reporters[$value['cid']] = $value['name'] . " ( {$value['relation']} )";
    }
    $form->add('select', 'reporter_id', ts('Reporter/Role'), $reporters, FALSE, array('id' => 'reporter_id_' . $form->_caseID));

    // take all case activity types for search filter, CRM-7187
    $aTypesFilter = array();
    $allCaseActTypes = CRM_Case_PseudoConstant::caseActivityType();
    foreach ($allCaseActTypes as $typeDetails) {
      if (!in_array($typeDetails['name'], array('Open Case'))) {
        $aTypesFilter[$typeDetails['id']] = CRM_Utils_Array::value('label', $typeDetails);
      }
    }
    $aTypesFilter = $aTypesFilter + $aTypes;
    asort($aTypesFilter);
    $form->add('select', 'activity_type_filter_id', ts('Activity Type'), array('' => ts('- select activity type -')) + $aTypesFilter, FALSE, array('id' => 'activity_type_filter_id_' . $form->_caseID));

    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $form->add('select', 'status_id', ts('Status'), array("" => ts(' - any status - ')) + $activityStatus, FALSE, array('id' => 'status_id_' . $form->_caseID));

    // activity dates
    $form->addDate('activity_date_low_' . $form->_caseID, ts('Activity Dates - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('activity_date_high_' . $form->_caseID, ts('To'), FALSE, array('formatType' => 'searchDate'));

    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $form->add('checkbox', 'activity_deleted', ts('Deleted Activities'), '', FALSE, array('id' => 'activity_deleted_' . $form->_caseID));
    }
  }

  /**
   * Form elements for merging cases
   */
  public function buildMergeCaseForm() {
    $otherCases = array();
    $result = civicrm_api3('Case', 'get', array(
      'check_permissions' => TRUE,
      'contact_id' => $this->_contactID,
      'is_deleted' => 0,
      'id' => array('!=' => $this->_caseID),
      'return' => array('id', 'start_date', 'case_type_id.title'),
    ));
    foreach ($result['values'] as $id => $case) {
      $otherCases[$id] = "#$id: {$case['case_type_id.title']} " . ts('(opened %1)', array(1 => $case['start_date']));
    }

    $this->assign('mergeCases', $this->_mergeCases = (bool) $otherCases);

    if ($otherCases) {
      $this->add('select', 'merge_case_id',
        ts('Select Case for Merge'),
        array(
          '' => ts('- select case -'),
        ) + $otherCases,
        FALSE,
        array('class' => 'crm-select2 huge')
      );
      $this->addElement('submit',
        $this->getButtonName('next', 'merge_case'),
        ts('Merge'),
        array(
          'class' => 'hiddenElement',
        )
      );
    }
  }

}
