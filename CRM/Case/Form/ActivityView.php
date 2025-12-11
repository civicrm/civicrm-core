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
 * This class does pre processing for viewing an activity or their revisions.
 */
class CRM_Case_Form_ActivityView extends CRM_Core_Form {

  /**
   * Process the view.
   */
  public function preProcess() {
    $contactID = CRM_Utils_Request::retrieve('cid', 'Integer', $this, TRUE);
    $activityID = CRM_Utils_Request::retrieve('aid', 'Integer', $this, TRUE);
    $revs = CRM_Utils_Request::retrieve('revs', 'Boolean');
    $caseID = CRM_Utils_Request::retrieve('caseID', 'Boolean');
    $activitySubject = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
      $activityID,
      'subject'
    );

    //check for required permissions, CRM-6264
    if ($activityID &&
      !CRM_Activity_BAO_Activity::checkPermission($activityID, CRM_Core_Action::VIEW)
    ) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->assign('contactID', $contactID);
    $this->assign('caseID', $caseID);
    // CRM-9145
    $this->assign('activityID', $activityID);

    $xmlProcessor = new CRM_Case_XMLProcessor_Report();
    $report = $xmlProcessor->getActivityInfo($contactID, $activityID, TRUE);

    $attachmentUrl = CRM_Core_BAO_File::attachmentInfo('civicrm_activity', $activityID);
    if ($attachmentUrl) {
      $report['fields'][] = [
        'label' => 'Attachment(s)',
        'value' => $attachmentUrl,
        'type' => 'Link',
      ];
    }

    $tags = CRM_Core_BAO_EntityTag::getTag($activityID, 'civicrm_activity');
    if (!empty($tags)) {
      $allTag = CRM_Core_DAO_EntityTag::buildOptions('tag_id', 'get');
      foreach ($tags as $tid) {
        $tags[$tid] = $allTag[$tid];
      }
      $report['fields'][] = [
        'label' => 'Tags',
        'value' => implode('<br />', $tags),
        'type' => 'String',
      ];
    }

    $this->assign('report', $report);

    $latestRevisionID = CRM_Activity_BAO_Activity::getLatestActivityId($activityID);

    $viewPriorActivities = [];
    $priorActivities = CRM_Activity_BAO_Activity::getPriorAcitivities($activityID);
    foreach ($priorActivities as $activityId => $activityValues) {
      if (CRM_Case_BAO_Case::checkPermission($activityId, 'view', NULL, $contactID)) {
        $viewPriorActivities[$activityId] = $activityValues;
      }
    }

    if ($revs) {
      $this->setTitle(ts('Activity Revision History'));
      $this->assign('revs', $revs);
      $this->assign('result', $viewPriorActivities);
      $this->assign('subject', $activitySubject);
      $this->assign('latestRevisionID', $latestRevisionID);
    }
    else {
      $this->assign('revs', 0);
      if (count($viewPriorActivities) > 1) {
        $this->assign('activityID', $activityID);
      }

      if ($latestRevisionID != $activityID) {
        $this->assign('latestRevisionID', $latestRevisionID);
      }
    }

    $parentID = CRM_Activity_BAO_Activity::getParentActivity($activityID);
    $this->assign('parentID', $parentID ?? NULL);

    //viewing activity should get diplayed in recent list.CRM-4670
    $activityTypeID = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activityID, 'activity_type_id');

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $activityTargetContacts = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activityID, $targetID);
    if (!empty($activityTargetContacts)) {
      $recentContactId = $activityTargetContacts[0];
    }
    else {
      $recentContactId = $contactID;
    }

    if (!isset($caseID)) {
      $caseID = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseActivity', $activityID, 'case_id', 'activity_id');
    }

    $url = CRM_Utils_System::url('civicrm/case/activity/view',
      "reset=1&aid={$activityID}&cid={$recentContactId}&caseID={$caseID}&context=home"
    );

    $recentContactDisplay = CRM_Contact_BAO_Contact::displayName($recentContactId);
    // add the recently created Activity
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);

    $title = "";
    if (isset($activitySubject)) {
      $title = $activitySubject . ' - ';
    }

    $title .= $recentContactDisplay . ' (' . $activityTypes[$activityTypeID] . ')';

    $recentOther = [];
    if (CRM_Case_BAO_Case::checkPermission($activityID, 'edit')) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/case/activity',
        "reset=1&action=update&id={$activityID}&cid={$recentContactId}&caseid={$caseID}&context=home"
      );
    }
    if (CRM_Case_BAO_Case::checkPermission($activityID, 'delete')) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/case/activity',
        "reset=1&action=delete&id={$activityID}&cid={$recentContactId}&caseid={$caseID}&context=home"
      );
    }

    CRM_Utils_Recent::add($title,
      $url,
      $activityID,
      'Activity',
      $recentContactId,
      $recentContactDisplay,
      $recentOther
    );

    // Set breadcrumb to take the user back to the case being viewed
    $caseTypeId = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseID, 'case_type_id');
    $caseType = CRM_Core_PseudoConstant::getLabel('CRM_Case_BAO_Case', 'case_type_id', $caseTypeId);
    $caseContact = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $caseID, 'contact_id', 'case_id');

    CRM_Utils_System::resetBreadCrumb();
    $breadcrumb = [
      [
        'title' => ts('Home'),
        'url' => CRM_Utils_System::url(),
      ],
      [
        'title' => ts('CiviCRM'),
        'url' => CRM_Utils_System::url('civicrm', 'reset=1'),
      ],
      [
        'title' => ts('CiviCase Dashboard'),
        'url' => CRM_Utils_System::url('civicrm/case', 'reset=1'),
      ],
      [
        'title' => $caseType,
        'url' => CRM_Utils_System::url('civicrm/contact/view/case', [
          'reset' => 1,
          'id' => $caseID,
          'context' => 'case',
          'action' => 'view',
          'cid' => $caseContact,
        ]),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadcrumb);

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Done'),
      ],
    ]);
    // Add additional action links
    $activityDeleted = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activityID, 'is_deleted');
    $actionLinks = CRM_Case_Selector_Search::permissionedActionLinks($caseID, $contactID, CRM_Core_Session::getLoggedInContactID(), NULL, $activityTypeID, $activityDeleted, $activityID, FALSE);
    $this->assign('actionLinks', $actionLinks);
  }

}
