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
 * This class create activities for a case.
 */
class CRM_Case_Form_Activity extends CRM_Activity_Form_Activity {

  /**
   * Cases this activity belongs to.
   *
   * @var int[]
   */
  public $_caseId;

  /**
   * The default case type variable defined.
   *
   * @var int[]
   */
  public $_caseType;

  /**
   * The array of releted contact info.
   *
   * @var array
   */
  public $_relatedContacts;

  /**
   * The case type definition column info
   * for the caseId;
   *
   * @var array
   */
  public $_caseTypeDefinition;

  /**
   * This is here to avoid php 8 warnings but it should be converted to
   * some mechanism more local to ChangeCaseStatus. It also doesn't make sense
   * that it's an array.
   *
   * @var array
   * @internal
   */
  public $_oldCaseStatus;

  /**
   * This is here to avoid php 8 warnings but it should be converted to
   * some mechanism more local to ChangeCaseStatus. It also doesn't make sense
   * that it's an array.
   *
   * @var array
   * @internal
   */
  public $_defaultCaseStatus;

  /**
   * @var int
   * Used by ChangeCaseStartDate. See getter/setter below.
   */
  private $openCaseActivityId;

  /**
   * Build the form object.
   */
  public function preProcess() {
    $caseIds = CRM_Utils_Request::retrieve('caseid', 'CommaSeparatedIntegers', $this);
    $this->_caseId = $caseIds ? explode(',', $caseIds) : [];
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    if (!$this->_context) {
      $this->_context = 'caseActivity';
    }
    $this->_crmDir = 'Case';
    $this->assign('context', $this->_context);

    parent::preProcess();

    $scheduleStatusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Scheduled');
    $this->assign('scheduleStatusId', $scheduleStatusId);

    if (!$this->_caseId && $this->_activityId) {
      $this->_caseId = (array) CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseActivity', $this->_activityId,
        'case_id', 'activity_id'
      );
    }
    if ($this->_caseId) {
      $this->assign('caseId', $this->_caseId);
      $this->assign('countId', count($this->_caseId));
      $this->assign('caseID', CRM_Utils_Array::first($this->_caseId));
    }

    if (!$this->_caseId ||
      (!$this->_activityId && !$this->_activityTypeId)
    ) {
      CRM_Core_Error::statusBounce(ts('required params missing.'));
    }

    //check for case activity access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }
    //validate case id.
    if ($this->_caseId &&
      !CRM_Core_Permission::check('access all cases and activities')
    ) {
      $params = ['type' => 'any'];
      $allCases = CRM_Case_BAO_Case::getCases(TRUE, $params);
      if (count(array_intersect($this->_caseId, array_keys($allCases))) == 0) {
        CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
      }
    }

    //validate case activity id.
    if ($this->_activityId &&
      ($this->_action & CRM_Core_Action::UPDATE)
    ) {
      $valid = CRM_Case_BAO_Case::checkPermission($this->_activityId, 'edit',
        $this->_activityTypeId
      );
      if (!$valid) {
        CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
      }
    }

    foreach ($this->_caseId as $casePos => $caseId) {
      $this->_caseType[$casePos] = CRM_Case_BAO_Case::getCaseType($caseId, 'name');
    }
    $this->assign('caseType', $this->_caseType);

    $this->_caseTypeDefinition = $this->getCaseTypeDefinition();

    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    foreach ($this->_caseId as $casePos => $caseId) {
      $clients[] = CRM_Case_BAO_Case::getContactNames($caseId);
    }
    $this->assign('client_names', $clients);

    $caseIds = implode(',', $this->_caseId);
    // set context for pushUserContext and for statusBounce
    if ($this->_context == 'fulltext') {
      if ($this->_action == CRM_Core_Action::UPDATE || $this->_action == CRM_Core_Action::DELETE) {
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$caseIds}&show=1&context={$this->_context}"
        );
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/search/custom', 'force=1');
      }
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view/case',
        "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$caseIds}&show=1"
      );
    }
    if (!$this->_activityId) {
      // check if activity count is within the limit
      $xmlProcessor = new CRM_Case_XMLProcessor_Process();
      foreach ($this->_caseId as $casePos => $caseId) {
        $caseType = $this->_caseType[$casePos];
        $activityInst = $xmlProcessor->getMaxInstance($caseType);

        // If not bounce back and also provide activity edit link if only one existing activity
        if (isset($activityInst[$this->_activityTypeName])) {
          $activityCount = CRM_Case_BAO_Case::getCaseActivityCount($caseId, $this->_activityTypeId);
          $editUrl = self::checkMaxInstances(
            $caseId,
            $this->_activityTypeId,
            $activityInst[$this->_activityTypeName],
            $this->_currentUserId,
            $this->_currentlyViewedContactId,
            $activityCount
          );
          $bounceMessage = self::getMaxInstancesBounceMessage($editUrl, $this->_activityTypeName, $activityInst[$this->_activityTypeName], $activityCount);
          if ($bounceMessage) {
            CRM_Core_Error::statusBounce($bounceMessage, $url);
          }
        }
      }
    }

    // Turn off the prompt which asks the user if they want to create separate
    // activities when specifying multiple contacts "with" a new activity.
    // Instead, always create one activity with all contacts together.
    $this->supportsActivitySeparation = FALSE;

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $this->_defaults = parent::setDefaultValues();
    if (empty($this->_defaults['medium_id'])) {
      // set default encounter medium CRM-4816
      $medium = CRM_Core_OptionGroup::values('encounter_medium', FALSE, FALSE, FALSE, 'AND is_default = 1');
      if (count($medium) == 1) {
        $this->_defaults['medium_id'] = key($medium);
      }
    }

    return $this->_defaults;
  }

  public function buildQuickForm() {
    $this->_fields['source_contact_id']['label'] = ts('Reported By');
    unset($this->_fields['status_id']['attributes']['required']);

    if ($this->restrictAssignmentByUserAccount()) {
      $assigneeParameters['uf_user'] = 1;
    }

    $activityAssignmentGroups = $this->getActivityAssignmentGroups();
    if (!empty($activityAssignmentGroups)) {
      $assigneeParameters['group'] = ['IN' => $activityAssignmentGroups];
    }

    if (!empty($assigneeParameters)) {
      $this->_fields['assignee_contact_id']['attributes']['api']['params']
        = array_merge($this->_fields['assignee_contact_id']['attributes']['api']['params'], $assigneeParameters);

      $this->_fields['followup_assignee_contact_id']['attributes']['api']['params']
        = array_merge($this->_fields['followup_assignee_contact_id']['attributes']['api']['params'], $assigneeParameters);

      //Disallow creating a contact from the assignee field UI.
      $this->_fields['assignee_contact_id']['attributes']['create'] = FALSE;
      $this->_fields['followup_assignee_contact_id']['attributes']['create'] = FALSE;
    }

    if ($this->_caseType) {
      $xmlProcessor = new CRM_Case_XMLProcessor_Process();
      $aTypes = [];
      foreach (array_unique($this->_caseType) as $val) {
        $activityTypes = $xmlProcessor->get($val, 'ActivityTypes', TRUE);
        $aTypes = $aTypes + $activityTypes;
      }

      // remove Open Case activity type since we're inside an existing case
      $openCaseID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
      unset($aTypes[$openCaseID]);
      asort($aTypes);
      $this->_fields['followup_activity_type_id']['attributes'] = ['' => ts('- select activity type -')] + $aTypes;
    }

    parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::DETACH | CRM_Core_Action::RENEW)) {
      return;
    }

    $this->assign('urlPath', 'civicrm/case/activity');

    if ($this->_activityTypeFile == 'OpenCase' && $this->_action == CRM_Core_Action::UPDATE) {
      $this->getElement('activity_date_time')->freeze();
    }

    $this->addSelect('medium_id');

    // Related contacts
    $i = 0;
    foreach ($this->_caseId as $key => $val) {
      $this->_relatedContacts[] = $rgc = CRM_Case_BAO_Case::getRelatedAndGlobalContacts($val);
      $contName = CRM_Case_BAO_Case::getContactNames($val);
      foreach ($contName as $nkey => $nval) {
        array_push($this->_relatedContacts[$i][0], $this->_relatedContacts[$i][0]['managerOf'] = $nval['display_name']);
      }
      $i++;
    }

    //add case client in send a copy selector.CRM-4438.
    foreach ($this->_caseId as $key => $val) {
      $relatedContacts[] = $relCon = CRM_Case_BAO_Case::getContactNames($val);
    }

    if (!empty($relatedContacts)) {
      foreach ($relatedContacts as $relatedContact) {
        $this->_relatedContacts[] = $relatedContact;
      }
    }

    if (!empty($this->_relatedContacts)) {
      $checkBoxes = [];
      foreach ($this->_relatedContacts as $id => $row) {
        foreach ($row as $key => $value) {
          $checkBoxes[$key] = $this->addElement('checkbox', $key, NULL, NULL, ['class' => 'select-row']);
        }
      }

      $this->addGroup($checkBoxes, 'contact_check');
      $this->addElement('checkbox', 'toggleSelect', NULL, NULL,
        ['class' => 'select-rows']
      );
      $this->assign('searchRows', $this->_relatedContacts);
    }
    $this->_relatedContacts = $rgc + $relCon;

    $this->addFormRule(['CRM_Case_Form_Activity', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    // skip form rule if deleting
    if (($fields['_qf_Activity_next_'] ?? NULL) == 'Delete' || ($fields['_qf_Activity_next_'] ?? NULL) == 'Restore') {
      return TRUE;
    }

    return parent::formRule($fields, $files, $self);
  }

  /**
   * Process the form submission.
   *
   * @param array $params
   */
  public function postProcess($params = NULL) {
    $transaction = new CRM_Core_Transaction();

    if ($this->_action & CRM_Core_Action::DELETE) {
      $statusMsg = NULL;

      //block deleting activities which affects
      //case attributes.CRM-4543
      $activityCondition = " AND v.name IN ('Open Case', 'Change Case Type', 'Change Case Status', 'Change Case Start Date')";
      $caseAttributeActivities = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $activityCondition);

      if (!array_key_exists($this->_activityTypeId, $caseAttributeActivities)) {
        $params = ['id' => $this->_activityId];
        $activityDelete = CRM_Activity_BAO_Activity::deleteActivity($params, TRUE);
        if ($activityDelete) {
          $statusMsg = ts('The selected activity has been moved to the Trash. You can view and / or restore deleted activities by checking "Deleted Activities" from the Case Activities search filter (under Manage Case).<br />');
        }
      }
      else {
        $statusMsg = ts("Selected Activity cannot be deleted.");
      }

      CRM_Core_Session::setStatus('', $statusMsg, 'info');
      return;
    }

    if ($this->_action & CRM_Core_Action::RENEW) {
      $statusMsg = NULL;
      $params = ['id' => $this->_activityId];
      $activityRestore = CRM_Activity_BAO_Activity::restoreActivity($params);
      if ($activityRestore) {
        $statusMsg = ts('The selected activity has been restored.<br />');
      }
      CRM_Core_Session::setStatus('', $statusMsg, 'info');
      return;
    }

    // store the submitted values in an array
    // Explanation for why we only check the is_unittest element: Prior to adding that check, there was no check and so any $params passed in would have been overwritten. Just in case somebody is passing in some non-null params and that broken code would have inadvertently been working, we can maintain backwards compatibility by only checking for the is_unittest parameter, and so that broken code will still work. At the same time this allows unit tests to pass in a $params without it getting overwritten. See also PR #2077 for some discussion of when the $params parameter was added as a passed in variable.
    if (empty($params['is_unittest'])) {
      $params = $this->getSubmittedValues();
    }

    //set parent id if its edit mode
    if ($parentId = $this->_defaults['parent_id'] ?? NULL) {
      $params['parent_id'] = $parentId;
    }

    $params['activity_type_id'] = $this->_activityTypeId;

    // format with contact (target contact) values
    if (isset($params['target_contact_id'])) {
      $params['target_contact_id'] = explode(',', $params['target_contact_id']);
    }
    else {
      $params['target_contact_id'] = [];
    }

    // format activity custom data
    if ($this->_activityId) {
      // retrieve and include the custom data of old Activity
      $oldActivity = civicrm_api3('Activity', 'getsingle', ['id' => $this->_activityId]);
      $params = array_merge($oldActivity, $params);

      // unset custom fields-id from params since we want custom
      // fields to be saved for new activity.
      foreach ($params as $key => $value) {
        $match = [];
        if (preg_match('/^(custom_\d+_)(\d+)$/', $key, $match)) {
          $params[$match[1] . '-1'] = $params[$key];

          // for autocomplete transfer hidden value instead of label
          if ($params[$key] && isset($params[$key . '_id'])) {
            $params[$match[1] . '-1_id'] = $params[$key . '_id'];
            unset($params[$key . '_id']);
          }
          unset($params[$key]);
        }
      }
    }

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $this->_activityId,
      'Activity'
    );

    // assigning formatted value
    if (!empty($params['assignee_contact_id'])) {
      $params['assignee_contact_id'] = explode(',', $params['assignee_contact_id']);
    }
    else {
      $params['assignee_contact_id'] = [];
    }

    if (isset($this->_activityId)) {
      // activity which hasn't been modified by a user yet
      if ($this->_defaults['is_auto'] == 1) {
        $params['is_auto'] = 0;
      }

      // @todo This is called newActParams because it USED TO create new activity revisions. But at the moment just changing the part that is broken.
      // $params gets merged with the existing activity data every time, including the activity id.
      $newActParams = $params;

      // add target contact values in update mode
      if (empty($params['target_contact_id']) && !empty($this->_defaults['target_contact'])) {
        $newActParams['target_contact_id'] = $this->_defaults['target_contact'];
      }
    }

    if (!isset($newActParams)) {
      // add more attachments if needed for old activity
      CRM_Core_BAO_File::formatAttachment($params,
        $params,
        'civicrm_activity'
      );

      // call begin post process, before the activity is created/updated.
      $this->beginPostProcess($params);
      foreach ($this->_caseId as $key => $val) {
        $params['case_id'] = $val;
        // activity create/update
        $activity = CRM_Activity_BAO_Activity::create($params);
        $vvalue[] = ['case_id' => $val, 'actId' => $activity->id];
        // call end post process, after the activity has been created/updated.
        $this->endPostProcess($params, $activity);
      }
    }
    else {
      // @todo This can go eventually. Just focusing on not creating new
      // revisions for now. This is still needed to keep it matched up to any
      // existing older revisions while they are still in the db.
      // set proper original_id
      if (!empty($this->_defaults['original_id'])) {
        $newActParams['original_id'] = $this->_defaults['original_id'];
      }
      else {
        $newActParams['original_id'] = NULL;
      }

      // add attachments if any
      CRM_Core_BAO_File::formatAttachment($newActParams,
        $newActParams,
        'civicrm_activity'
      );

      // call begin post process, before the activity is created/updated.
      $this->beginPostProcess($newActParams);
      foreach ($this->_caseId as $key => $val) {
        $newActParams['case_id'] = $val;
        $activity = CRM_Activity_BAO_Activity::create($newActParams);
        $vvalue[] = ['case_id' => $val, 'actId' => $activity->id];
        // call end post process, after the activity has been created/updated.
        $this->endPostProcess($newActParams, $activity);
      }
      // copy files attached to old activity if any, to new one,
      // as long as users have not selected the 'delete attachment' option.
      if (empty($newActParams['is_delete_attachment']) && ($this->_activityId != $activity->id)) {
        CRM_Core_BAO_File::copyEntityFile('civicrm_activity', $this->_activityId,
          'civicrm_activity', $activity->id
        );
      }

      // copy back params to original var
      $params = $newActParams;
    }

    foreach ($vvalue as $vkey => $vval) {
      if ($vval['actId']) {
        // add tags if exists
        $tagParams = [];
        if (!empty($params['tag'])) {
          if (!is_array($params['tag'])) {
            $params['tag'] = explode(',', $params['tag']);
          }

          $tagParams = array_fill_keys($params['tag'], 1);
        }

        //save static tags
        CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_activity', $vval['actId']);

        //save free tags
        if (isset($params['taglist']) && !empty($params['taglist'])) {
          CRM_Core_Form_Tag::postProcess($params['taglist'], $vval['actId'], 'civicrm_activity', $this);
        }
      }

      // update existing case record if needed
      $caseParams = $params;
      $caseParams['id'] = $vval['case_id'];
      if (!empty($caseParams['case_status_id'])) {
        $caseParams['status_id'] = $caseParams['case_status_id'];
      }

      // unset params intended for activities only
      unset($caseParams['subject'], $caseParams['details'],
        $caseParams['status_id'], $caseParams['custom']
      );
      CRM_Case_BAO_Case::create($caseParams);
      // create case activity record
      $caseParams = [
        'activity_id' => $vval['actId'],
        'case_id' => $vval['case_id'],
      ];
      CRM_Case_BAO_Case::processCaseActivity($caseParams);
    }

    // send copy to selected contacts.
    $mailStatus = '';
    $mailToContacts = [];

    //CRM-5695
    //check for notification settings for assignee contacts
    $selectedContacts = ['contact_check'];
    if (Civi::settings()->get('activity_assignee_notification')) {
      $selectedContacts[] = 'assignee_contact_id';
    }

    $dndActivityTypes = Civi::settings()->get('do_not_notify_assignees_for') ?? [];
    foreach ($vvalue as $vkey => $vval) {
      foreach ($selectedContacts as $dnt => $val) {
        if (array_key_exists($val, $params) && !CRM_Utils_Array::crmIsEmptyArray($params[$val])) {
          if ($val == 'contact_check') {
            $mailStatus = ts("A copy of the activity has also been sent to selected contact(s).");
          }
          else {
            if (!in_array($this->_activityTypeId, $dndActivityTypes)) {
              $this->_relatedContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(
                [$vval['actId']], TRUE, FALSE
              );
              $mailStatus .= ' ' . ts("A copy of the activity has also been sent to assignee contact(s).");
            }
            else {
              continue;
            }
          }
          //build an associative array with unique email addresses.
          foreach ($params[$val] as $key => $value) {
            if ($val == 'contact_check') {
              $id = $key;
            }
            else {
              $id = $value;
            }

            if (isset($id) && array_key_exists($id, $this->_relatedContacts) && isset($this->_relatedContacts[$id]['email'])) {
              //if email already exists in array then append with ', ' another role only otherwise add it to array.
              $contactDetails = $mailToContacts[$this->_relatedContacts[$id]['email']] ?? NULL;
              if ($contactDetails) {
                $caseRole = $this->_relatedContacts[$id]['role'] ?? NULL;
                $mailToContacts[$this->_relatedContacts[$id]['email']]['role'] = $contactDetails['role'] . ', ' . $caseRole;
              }
              else {
                $mailToContacts[$this->_relatedContacts[$id]['email']] = $this->_relatedContacts[$id];
              }
            }
          }
        }
      }

      $extraParams = ['case_id' => $vval['case_id'], 'client_id' => $this->_currentlyViewedContactId];
      $result = CRM_Activity_BAO_Activity::sendToAssignee($activity, $mailToContacts, $extraParams);
      if (empty($result)) {
        $mailStatus = '';
      }

      // create follow up activity if needed
      $followupStatus = '';
      if (!empty($params['followup_activity_type_id'])) {
        $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($vval['actId'], $params);

        if ($followupActivity) {
          $caseParams = [
            'activity_id' => $followupActivity->id,
            'case_id' => $vval['case_id'],
          ];
          CRM_Case_BAO_Case::processCaseActivity($caseParams);
          $followupStatus = ts("A followup activity has been scheduled.") . '<br /><br />';

          //dev/core#1721
          if (Civi::settings()->get('activity_assignee_notification') &&
            !in_array($followupActivity->activity_type_id,
              Civi::settings()->get('do_not_notify_assignees_for'))
          ) {
            $followupActivityIDs = [$followupActivity->id];
            $followupAssigneeContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($followupActivityIDs, TRUE, FALSE);

            if (!empty($followupAssigneeContacts)) {
              $mailToFollowupContacts = [];
              foreach ($followupAssigneeContacts as $facValues) {
                $mailToFollowupContacts[$facValues['email']] = $facValues;
              }

              $facParams['case_id'] = $vval['case_id'];
              $sentFollowup = CRM_Activity_BAO_Activity::sendToAssignee($followupActivity, $mailToFollowupContacts, $facParams);
              if ($sentFollowup) {
                $mailStatus .= '<br />' . ts("A copy of the follow-up activity has also been sent to follow-up assignee contacts(s).");
              }
            }
          }
        }
      }
      $title = ts("%1 Saved", [1 => $this->_activityTypeName]);
      CRM_Core_Session::setStatus($followupStatus . $mailStatus, $title, 'success');
    }
  }

  /**
   * Returns the groups that contacts must belong to in order to be assigned
   * an activity for this case. It returns an empty array if no groups are found for
   * the case type linked to the caseId.
   *
   * @return array
   */
  private function getActivityAssignmentGroups() {
    if (!$this->_caseTypeDefinition) {
      return [];
    }

    $assignmentGroups = [];
    foreach ($this->_caseTypeDefinition as $caseId => $definition) {
      if (!empty($definition['activityAsgmtGrps'])) {
        $assignmentGroups = array_merge($assignmentGroups, $definition['activityAsgmtGrps']);
      }
    }

    return $assignmentGroups;
  }

  /**
   * Returns whether contacts must have a user account in order to be
   * assigned an activity for this case.
   *
   * @return bool
   */
  private function restrictAssignmentByUserAccount() {
    if (!$this->_caseTypeDefinition) {
      return FALSE;
    }

    foreach ($this->_caseTypeDefinition as $caseId => $definition) {
      if (!empty($definition['restrictActivityAsgmtToCmsUser'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the case type definition column value for the case type linked to the caseId.
   *
   * @return array
   */
  private function getCaseTypeDefinition() {
    if (!$this->_caseId) {
      return [];
    }

    $definitions = civicrm_api3('CaseType', 'get', [
      'return' => ['name', 'definition'],
      'name' => ['IN' => array_unique($this->_caseType)],
    ]);

    return array_column($definitions['values'], 'definition', 'name');
  }

  /**
   * Get the edit link for a case activity
   *
   * This isn't here for reusability - it was a pull out
   * from preProcess to make it easier to test.
   * There is CRM_Case_Selector_Search::addCaseActivityLinks but it would
   * need some rejigging, and there's also a FIXME note there already.
   *
   * @param int $caseId
   * @param int $activityTypeId
   * @param int $currentUserId
   * @param int $currentlyViewedContactId
   *
   * @return string
   */
  public static function getCaseActivityEditLink($caseId, $activityTypeId, $currentUserId, $currentlyViewedContactId) {
    $atArray = ['activity_type_id' => $activityTypeId];
    $activities = CRM_Case_BAO_Case::getCaseActivity($caseId,
      $atArray,
      $currentUserId
    );
    $firstActivity = CRM_Utils_Array::first($activities['data']);
    $activityId = empty($firstActivity['DT_RowId']) ? 0 : $firstActivity['DT_RowId'];
    return CRM_Utils_System::url('civicrm/case/activity',
      "reset=1&cid={$currentlyViewedContactId}&caseid={$caseId}&action=update&id={$activityId}"
    );
  }

  /**
   * Check the current activity count against max instances for a given case id and activity type.
   *
   * This isn't here for reusability - it was a pull out
   * from preProcess to make it easier to test.
   *
   * @param int $caseId
   * @param int $activityTypeId
   * @param int $maxInstances
   * @param int $currentUserId
   * @param int $currentlyViewedContactId
   * @param int $activityCount
   *
   * @return string
   *   If there is more than one existing activity of the given type then it's not clear which url to return so return null for the url.
   */
  public static function checkMaxInstances($caseId, $activityTypeId, $maxInstances, $currentUserId, $currentlyViewedContactId, $activityCount) {
    $editUrl = NULL;
    if ($activityCount >= $maxInstances) {
      if ($maxInstances == 1) {
        $editUrl = self::getCaseActivityEditLink($caseId, $activityTypeId, $currentUserId, $currentlyViewedContactId);
      }
    }
    return $editUrl;
  }

  /**
   * Compute the message text for the bounce message when max_instances is reached, depending on whether it's one or more than one.
   *
   * @param string $editUrl
   * @param string $activityTypeName
   *   This is actually label!! But we do want label though in this function.
   * @param int $maxInstances
   * @param int $activityCount
   *   Count of existing activities of the given type on the case
   *
   * @return string
   */
  public static function getMaxInstancesBounceMessage($editUrl, $activityTypeName, $maxInstances, $activityCount) {
    $bounceMessage = NULL;
    if ($activityCount >= $maxInstances) {
      if ($maxInstances == 1) {
        $bounceMessage = ts("You can not add another '%1' activity to this case. %2",
          [
            1 => $activityTypeName,
            2 => ts("Do you want to <a %1>edit the existing activity</a>?", [1 => "href='$editUrl'"]),
          ]
        );
      }
      else {
        // More than one instance, so don't provide a link. What would it be a link to anyway?
        $bounceMessage = ts("You can not add another '%1' activity to this case.",
          [
            1 => $activityTypeName,
          ]
        );
      }
    }
    return $bounceMessage;
  }

  /**
   * Getter used by ChangeCaseStartDate
   * @return int|null
   * @internal
   */
  public function getOpenCaseActivityId(): ?int {
    return $this->openCaseActivityId;
  }

  /**
   * Setter used by ChangeCaseStartDate
   * @param int $id
   * @internal
   */
  public function setOpenCaseActivityId(int $id): void {
    $this->openCaseActivityId = $id;
  }

}
