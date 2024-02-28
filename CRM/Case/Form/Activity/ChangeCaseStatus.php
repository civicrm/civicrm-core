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
 * This class generates form components for OpenCase Activity.
 */
class CRM_Case_Form_Activity_ChangeCaseStatus {

  /**
   * @param CRM_Core_Form $form
   *
   * @throws Exception
   */
  public static function preProcess(&$form) {
    if (!isset($form->_caseId)) {
      CRM_Core_Error::statusBounce(ts('Case Id not found.'));
    }

    $form->addElement('checkbox', 'updateLinkedCases', NULL, NULL, ['class' => 'select-row']);

    $caseID = CRM_Utils_Array::first($form->_caseId);
    $cases = CRM_Case_BAO_Case::getRelatedCases($caseID);
    $form->assign('linkedCases', $cases);
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode the default values are retrieved from the database.
   *
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function setDefaultValues(&$form) {
    $defaults = [];
    // Retrieve current case status
    // @todo this var is created as an array below, but the form field is single-valued. See also comment about _oldCaseStatus in endPostProcess.
    $defaults['case_status_id'] = $form->_defaultCaseStatus;

    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm($form) {
    $form->removeElement('status_id');
    $form->removeElement('priority_id');

    $caseTypes = [];

    $statusLabels = CRM_Case_PseudoConstant::caseStatus();
    $statusNames = CRM_Case_PseudoConstant::caseStatus('name');

    // Limit case statuses to allowed types for these case(s)
    $allCases = civicrm_api3('Case', 'get', ['return' => 'case_type_id', 'id' => ['IN' => (array) $form->_caseId]]);
    foreach ($allCases['values'] as $case) {
      $caseTypes[$case['case_type_id']] = $case['case_type_id'];
    }
    $caseTypes = civicrm_api3('CaseType', 'get', ['id' => ['IN' => $caseTypes]]);
    foreach ($caseTypes['values'] as $ct) {
      if (!empty($ct['definition']['statuses'])) {
        foreach ($statusLabels as $id => $label) {
          if (!in_array($statusNames[$id], $ct['definition']['statuses'])) {
            unset($statusLabels[$id]);
          }
        }
      }
    }

    foreach ($form->_caseId as $key => $val) {
      $form->_oldCaseStatus[] = $form->_defaultCaseStatus[] = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $val, 'status_id');
    }

    foreach ($form->_defaultCaseStatus as $keydefault => $valdefault) {
      if (!array_key_exists($valdefault, $statusLabels)) {
        $statusLabels[$valdefault] = CRM_Core_PseudoConstant::getLabel('CRM_Case_BAO_Case', 'status_id', $valdefault);
      }
    }
    $element = $form->add('select', 'case_status_id', ts('Case Status'),
      $statusLabels, TRUE
    );
    // check if the case status id passed in url is a valid one, set as default and freeze
    if (CRM_Utils_Request::retrieve('case_status_id', 'Positive', $form)) {
      $caseStatusId = CRM_Utils_Request::retrieve('case_status_id', 'Positive', $form);
      $caseStatus = CRM_Case_PseudoConstant::caseStatus();
      $form->_defaultCaseStatus = array_key_exists($caseStatusId, $caseStatus) ? $caseStatusId : NULL;
      $element->freeze();
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $form) {
    return TRUE;
  }

  /**
   * Process the form submission.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   */
  public static function beginPostProcess(&$form, &$params) {
    $params['id'] = $params['case_id'] ?? NULL;

    if (($params['updateLinkedCases'] ?? NULL) === '1') {
      $caseID = CRM_Utils_Array::first($form->_caseId);
      $cases = CRM_Case_BAO_Case::getRelatedCases($caseID);

      foreach ($cases as $currentCase) {
        if ($currentCase['status_id'] != $params['case_status_id']) {
          $form->_caseId[] = $currentCase['case_id'];
        }
      }
    }
  }

  /**
   * Process the form submission.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param CRM_Activity_BAO_Activity $activity
   */
  public static function endPostProcess(&$form, &$params, $activity) {
    $groupingValues = CRM_Core_OptionGroup::values('case_status', FALSE, TRUE, FALSE, NULL, 'value');

    // Set case end_date if we're closing the case. Clear end_date if we're (re)opening it.
    if (($groupingValues[$params['case_status_id']] ?? NULL) == 'Closed' && !empty($params['activity_date_time'])) {
      $params['end_date'] = CRM_Utils_Date::isoToMysql($params['activity_date_time']);

      // End case-specific relationships (roles)
      foreach ($params['target_contact_id'] as $cid) {
        $rels = CRM_Case_BAO_Case::getCaseRoles($cid, $params['case_id']);
        foreach ($rels as $relId => $relData) {
          $relationshipParams = [
            'id' => $relId,
            'end_date' => $params['end_date'],
          ];
          // @todo we can't switch directly to api because there is too much business logic and it breaks closing cases with organisations as client relationships
          //civicrm_api3('Relationship', 'create', $relationshipParams);
          CRM_Contact_BAO_Relationship::add($relationshipParams);
        }
      }
    }
    elseif (($groupingValues[$params['case_status_id']] ?? NULL) == 'Opened') {
      $params['end_date'] = 'null';

      // Reopen case-specific relationships (roles)
      foreach ($params['target_contact_id'] as $cid) {
        $rels = CRM_Case_BAO_Case::getCaseRoles($cid, $params['case_id'], NULL, FALSE);
        foreach ($rels as $relId => $relData) {
          $relationshipParams = [
            'id' => $relId,
            'end_date' => 'null',
          ];
          // @todo we can't switch directly to api because there is too much business logic and it breaks closing cases with organisations as client relationships
          //civicrm_api3('Relationship', 'create', $relationshipParams);
          CRM_Contact_BAO_Relationship::add($relationshipParams);
        }
      }
    }
    $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activity->status_id = $params['status_id'];
    $params['priority_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Normal');
    $activity->priority_id = $params['priority_id'];

    // Note here we don't need to do the filtering that happens in buildForm.
    // All we want is the label for a given id so we can put it in the subject.
    $statusLabels = CRM_Case_PseudoConstant::caseStatus();
    foreach ($form->_oldCaseStatus as $statusval) {
      // @todo we store all old statuses but then we only use the first one.
      if ($activity->subject == 'null') {
        $activity->subject = ts('Case status changed from %1 to %2', [
          1 => $statusLabels[$statusval] ?? NULL,
          2 => $statusLabels[$params['case_status_id']] ?? NULL,
        ]);
        $activity->save();
      }
    }
  }

}
