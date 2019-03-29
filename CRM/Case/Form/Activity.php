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
 * This class create activities for a case.
 */
class CRM_Case_Form_Activity extends CRM_Activity_Form_Activity {

  /**
   * Cases this activity belongs to.
   *
   * @var []int
   */
  public $_caseId;

  /**
   * The default case type variable defined.
   *
   * @var []int
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
      CRM_Core_Error::fatal('required params missing.');
    }

    //check for case activity access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }
    //validate case id.
    if ($this->_caseId &&
      !CRM_Core_Permission::check('access all cases and activities')
    ) {
      $params = array('type' => 'any');
      $allCases = CRM_Case_BAO_Case::getCases(TRUE, $params);
      if (count(array_intersect($this->_caseId, array_keys($allCases))) == 0) {
        CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
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
        CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
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
      $caseTypes = CRM_Case_PseudoConstant::caseType();

      if (empty($caseTypes) && ($this->_activityTypeName == 'Change Case Type') && !$this->_caseId) {
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$caseIds}&show=1"
        );
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext($url);
        CRM_Core_Error::statusBounce(ts("You do not have any active Case Types"));
      }

      // check if activity count is within the limit
      $xmlProcessor = new CRM_Case_XMLProcessor_Process();
      foreach ($this->_caseId as $casePos => $caseId) {
        $caseType = $this->_caseType[$casePos];
        $activityInst = $xmlProcessor->getMaxInstance($caseType);

        // If not bounce back and also provide activity edit link
        if (isset($activityInst[$this->_activityTypeName])) {
          $activityCount = CRM_Case_BAO_Case::getCaseActivityCount($caseId, $this->_activityTypeId);
          if ($activityCount >= $activityInst[$this->_activityTypeName]) {
            if ($activityInst[$this->_activityTypeName] == 1) {
              $atArray = array('activity_type_id' => $this->_activityTypeId);
              $activities = CRM_Case_BAO_Case::getCaseActivity($caseId,
                $atArray,
                $this->_currentUserId
              );
              $activityId = CRM_Utils_Array::first(array_keys($activities['data']));
              $editUrl = CRM_Utils_System::url('civicrm/case/activity',
                "reset=1&cid={$this->_currentlyViewedContactId}&caseid={$caseId}&action=update&id={$activityId}"
              );
            }
            CRM_Core_Error::statusBounce(ts("You can not add another '%1' activity to this case. %2",
                array(
                  1 => $this->_activityTypeName,
                  2 => ts("Do you want to <a %1>edit the existing activity</a>?", array(1 => "href='$editUrl'")),
                )
              ),
              $url
            );
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
    $targetContactValues = array();
    foreach ($this->_caseId as $key => $val) {
      //get all clients.
      $clients = CRM_Case_BAO_Case::getContactNames($val);
      if (isset($this->_activityId) && empty($_POST)) {
        if (!CRM_Utils_Array::crmIsEmptyArray($this->_defaults['target_contact'])) {
          $targetContactValues = array_combine(array_unique($this->_defaults['target_contact']),
            explode(';', trim($this->_defaults['target_contact_value']))
          );
          //exclude all clients.
          foreach ($clients as $clientId => $vals) {
            if (array_key_exists($clientId, $targetContactValues)) {
              unset($targetContactValues[$clientId]);
            }
          }
        }
      }
      $this->assign('targetContactValues', empty($targetContactValues) ? FALSE : $targetContactValues);

      if (isset($this->_encounterMedium)) {
        $this->_defaults['medium_id'] = $this->_encounterMedium;
      }
      elseif (empty($this->_defaults['medium_id'])) {
        // set default encounter medium CRM-4816
        $medium = CRM_Core_OptionGroup::values('encounter_medium', FALSE, FALSE, FALSE, 'AND is_default = 1');
        if (count($medium) == 1) {
          $this->_defaults['medium_id'] = key($medium);
        }
      }

      return $this->_defaults;
    }
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
      $aTypes = array();
      foreach (array_unique($this->_caseType) as $val) {
        $activityTypes = $xmlProcessor->get($val, 'ActivityTypes', TRUE);
        $aTypes = $aTypes + $activityTypes;
      }

      // remove Open Case activity type since we're inside an existing case
      $openCaseID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
      unset($aTypes[$openCaseID]);
      asort($aTypes);
      $this->_fields['followup_activity_type_id']['attributes'] = array('' => '- select activity type -') + $aTypes;
    }

    parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::DETACH | CRM_Core_Action::RENEW)) {
      return;
    }

    $this->assign('urlPath', 'civicrm/case/activity');

    $encounterMediums = CRM_Case_PseudoConstant::encounterMedium();

    if ($this->_activityTypeFile == 'OpenCase' && $this->_action == CRM_Core_Action::UPDATE) {
      $this->getElement('activity_date_time')->freeze();

      if ($this->_activityId) {
        // Fixme: what's the justification for this? It seems like it is just re-adding an option in case it is the default and disabled.
        // Is that really a big problem?
        $this->_encounterMedium = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $this->_activityId, 'medium_id');
        if (!array_key_exists($this->_encounterMedium, $encounterMediums)) {
          $encounterMediums[$this->_encounterMedium] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'medium_id', $this->_encounterMedium);
        }
      }
    }

    $this->add('select', 'medium_id', ts('Medium'), $encounterMediums, TRUE);
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
      $checkBoxes = array();
      foreach ($this->_relatedContacts as $id => $row) {
        foreach ($row as $key => $value) {
          $checkBoxes[$key] = $this->addElement('checkbox', $key, NULL, NULL, array('class' => 'select-row'));
        }
      }

      $this->addGroup($checkBoxes, 'contact_check');
      $this->addElement('checkbox', 'toggleSelect', NULL, NULL,
        array('class' => 'select-rows')
      );
      $this->assign('searchRows', $this->_relatedContacts);
    }
    $this->_relatedContacts = $rgc + $relCon;

    $this->addFormRule(array('CRM_Case_Form_Activity', 'formRule'), $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    // skip form rule if deleting
    if (CRM_Utils_Array::value('_qf_Activity_next_', $fields) == 'Delete' || CRM_Utils_Array::value('_qf_Activity_next_', $fields) == 'Restore') {
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
        $params = array('id' => $this->_activityId);
        $activityDelete = CRM_Activity_BAO_Activity::deleteActivity($params, TRUE);
        if ($activityDelete) {
          $statusMsg = ts('The selected activity has been moved to the Trash. You can view and / or restore deleted activities by checking "Deleted Activities" from the Case Activities search filter (under Manage Case).<br />');
        }
      }
      else {
        $statusMsg = ts("Selected Activity cannot be deleted.");
      }

      $tagParams = array(
        'entity_table' => 'civicrm_activity',
        'entity_id' => $this->_activityId,
      );
      CRM_Core_BAO_EntityTag::del($tagParams);

      CRM_Core_Session::setStatus('', $statusMsg, 'info');
      return;
    }

    if ($this->_action & CRM_Core_Action::RENEW) {
      $statusMsg = NULL;
      $params = array('id' => $this->_activityId);
      $activityRestore = CRM_Activity_BAO_Activity::restoreActivity($params);
      if ($activityRestore) {
        $statusMsg = ts('The selected activity has been restored.<br />');
      }
      CRM_Core_Session::setStatus('', $statusMsg, 'info');
      return;
    }

    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    //set parent id if its edit mode
    if ($parentId = CRM_Utils_Array::value('parent_id', $this->_defaults)) {
      $params['parent_id'] = $parentId;
    }

    $params['activity_type_id'] = $this->_activityTypeId;

    // format with contact (target contact) values
    if (isset($params['target_contact_id'])) {
      $params['target_contact_id'] = explode(',', $params['target_contact_id']);
    }
    else {
      $params['target_contact_id'] = array();
    }

    // format activity custom data
    if (!empty($params['hidden_custom'])) {
      if ($this->_activityId) {
        // retrieve and include the custom data of old Activity
        $oldActivity = civicrm_api3('Activity', 'getsingle', array('id' => $this->_activityId));
        $params = array_merge($oldActivity, $params);

        // unset custom fields-id from params since we want custom
        // fields to be saved for new activity.
        foreach ($params as $key => $value) {
          $match = array();
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

      // build custom data getFields array
      $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE, $this->_activityTypeId);
      $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
        CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
          NULL, NULL, TRUE
        )
      );
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $this->_activityId,
        'Activity'
      );
    }

    // assigning formatted value
    if (!empty($params['assignee_contact_id'])) {
      $params['assignee_contact_id'] = explode(',', $params['assignee_contact_id']);
    }
    else {
      $params['assignee_contact_id'] = array();
    }

    if (isset($this->_activityId)) {
      // activity which hasn't been modified by a user yet
      if ($this->_defaults['is_auto'] == 1) {
        $params['is_auto'] = 0;
      }

      // always create a revision of an case activity. CRM-4533
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
        $vvalue[] = array('case_id' => $val, 'actId' => $activity->id);
        // call end post process, after the activity has been created/updated.
        $this->endPostProcess($params, $activity);
      }
    }
    else {
      // since the params we need to set are very few, and we don't want rest of the
      // work done by bao create method , lets use dao object to make the changes
      $params = array('id' => $this->_activityId);
      $params['is_current_revision'] = 0;
      $activity = new CRM_Activity_DAO_Activity();
      $activity->copyValues($params);
      $activity->save();
    }

    // create a new version of activity if activity was found to
    // have been modified/created by user
    if (isset($newActParams)) {
      // set proper original_id
      if (!empty($this->_defaults['original_id'])) {
        $newActParams['original_id'] = $this->_defaults['original_id'];
      }
      else {
        $newActParams['original_id'] = $activity->id;
      }

      //is_current_revision will be set to 1 by default.
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
        $vvalue[] = array('case_id' => $val, 'actId' => $activity->id);
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
        $tagParams = array();
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
      $caseParams = array(
        'activity_id' => $vval['actId'],
        'case_id' => $vval['case_id'],
      );
      CRM_Case_BAO_Case::processCaseActivity($caseParams);
    }

    // send copy to selected contacts.
    $mailStatus = '';
    $mailToContacts = array();

    //CRM-5695
    //check for notification settings for assignee contacts
    $selectedContacts = array('contact_check');
    if (Civi::settings()->get('activity_assignee_notification')) {
      $selectedContacts[] = 'assignee_contact_id';
    }

    foreach ($vvalue as $vkey => $vval) {
      foreach ($selectedContacts as $dnt => $val) {
        if (array_key_exists($val, $params) && !CRM_Utils_Array::crmIsEmptyArray($params[$val])) {
          if ($val == 'contact_check') {
            $mailStatus = ts("A copy of the activity has also been sent to selected contacts(s).");
          }
          else {
            $this->_relatedContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(array($vval['actId']), TRUE, FALSE);
            $mailStatus .= ' ' . ts("A copy of the activity has also been sent to assignee contacts(s).");
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
              if ($contactDetails = CRM_Utils_Array::value($this->_relatedContacts[$id]['email'], $mailToContacts)) {
                $caseRole = CRM_Utils_Array::value('role', $this->_relatedContacts[$id]);
                $mailToContacts[$this->_relatedContacts[$id]['email']]['role'] = $contactDetails['role'] . ', ' . $caseRole;
              }
              else {
                $mailToContacts[$this->_relatedContacts[$id]['email']] = $this->_relatedContacts[$id];
              }
            }
          }
        }
      }

      $extraParams = array('case_id' => $vval['case_id'], 'client_id' => $this->_currentlyViewedContactId);
      $result = CRM_Activity_BAO_Activity::sendToAssignee($activity, $mailToContacts, $extraParams);
      if (empty($result)) {
        $mailStatus = '';
      }

      // create follow up activity if needed
      $followupStatus = '';
      if (!empty($params['followup_activity_type_id'])) {
        $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($vval['actId'], $params);

        if ($followupActivity) {
          $caseParams = array(
            'activity_id' => $followupActivity->id,
            'case_id' => $vval['case_id'],
          );
          CRM_Case_BAO_Case::processCaseActivity($caseParams);
          $followupStatus = ts("A followup activity has been scheduled.") . '<br /><br />';
        }
      }
      $title = ts("%1 Saved", array(1 => $this->_activityTypeName));
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

}
