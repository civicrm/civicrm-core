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
 * This class generates form components for LinkCase Activity.
 */
class CRM_Case_Form_Activity_LinkCases {

  /**
   * @param CRM_Core_Form $form
   *
   * @throws Exception
   */
  public static function preProcess(&$form) {
    if (empty($form->_caseId)) {
      CRM_Core_Error::statusBounce(ts('Case Id not found.'));
    }
    if (count($form->_caseId) != 1) {
      CRM_Core_Error::statusBounce(ts('Expected one case-type'));
    }

    $caseId = CRM_Utils_Array::first($form->_caseId);

    $form->assign('clientID', $form->_currentlyViewedContactId);
    $form->assign('sortName', CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $form->_currentlyViewedContactId, 'sort_name'));
    $form->assign('caseTypeLabel', CRM_Case_BAO_Case::getCaseType($caseId));

    // get the related cases for given case.
    $relatedCases = $form->get('relatedCases');
    if (!isset($relatedCases)) {
      $relatedCases = CRM_Case_BAO_Case::getRelatedCases($caseId);
      $form->set('relatedCases', empty($relatedCases) ? FALSE : $relatedCases);
    }
  }

  /**
   * Set default values for the form.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function setDefaultValues(&$form) {
    $defaults = [];
    if (!empty($_GET['link_to_case_id']) && CRM_Utils_Rule::positiveInteger($_GET['link_to_case_id'])) {
      $defaults['link_to_case_id'] = $_GET['link_to_case_id'];
    }
    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $excludeCaseIds = (array) $form->_caseId;
    $relatedCases = $form->get('relatedCases');
    if (is_array($relatedCases) && !empty($relatedCases)) {
      $excludeCaseIds = array_merge($excludeCaseIds, array_keys($relatedCases));
    }
    $form->addEntityRef('link_to_case_id', ts('Link To Case'), [
      'entity' => 'Case',
      'api' => [
        'extra' => ['case_id.case_type_id.title', 'contact_id.sort_name'],
        'params' => [
          'case_id' => ['NOT IN' => $excludeCaseIds],
          'case_id.is_deleted' => 0,
        ],
      ],
    ], TRUE);
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
    $errors = [];

    $linkCaseId = $values['link_to_case_id'] ?? NULL;

    if (!CRM_Utils_Rule::positiveInteger($linkCaseId) || $linkCaseId == 0) {
      // We can't just return $errors because when the page reloads the
      // entityref widget throws an error before the page can display the error.
      // It seems ok with other invalid values, just not 0, but both are equally invalid.
      CRM_Core_Error::statusBounce(ts('The linked case ID is invalid.'));
    }

    if ($linkCaseId == CRM_Utils_Array::first($form->_caseId)) {
      $errors['link_to_case_id'] = ts('Please select some other case to link.');
    }

    // do check for existing related cases.
    $relatedCases = $form->get('relatedCases');
    if (is_array($relatedCases) && array_key_exists($linkCaseId, $relatedCases)) {
      $errors['link_to_case_id'] = ts('Selected case is already linked.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   *
   *
   * @param CRM_Core_Form $form
   * @param array $params
   */
  public static function beginPostProcess(&$form, &$params) {
  }

  /**
   * Process the form submission.
   *
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param CRM_Activity_BAO_Activity $activity
   */
  public static function endPostProcess($form, $params, $activity) {
    $activityId = $activity->id;
    $linkCaseID = $params['link_to_case_id'] ?? NULL;

    //create a link between two cases.
    if ($activityId && $linkCaseID) {
      $caseParams = [
        'case_id' => $linkCaseID,
        'activity_id' => $activityId,
      ];
      CRM_Case_BAO_Case::processCaseActivity($caseParams);
    }
  }

}
