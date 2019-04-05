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
      CRM_Core_Error::fatal(ts('Case Id not found.'));
    }
    if (count($form->_caseId) != 1) {
      CRM_Core_Resources::fatal(ts('Expected one case-type'));
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

    $linkCaseId = CRM_Utils_Array::value('link_to_case_id', $values);
    assert('is_numeric($linkCaseId)');
    if ($linkCaseId == CRM_Utils_Array::first($form->_caseId)) {
      $errors['link_to_case'] = ts('Please select some other case to link.');
    }

    // do check for existing related cases.
    $relatedCases = $form->get('relatedCases');
    if (is_array($relatedCases) && array_key_exists($linkCaseId, $relatedCases)) {
      $errors['link_to_case'] = ts('Selected case is already linked.');
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
  public static function endPostProcess(&$form, &$params, &$activity) {
    $activityId = $activity->id;
    $linkCaseID = CRM_Utils_Array::value('link_to_case_id', $params);

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
