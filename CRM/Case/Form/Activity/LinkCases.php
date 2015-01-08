<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for OpenCase Activity
 *
 */
class CRM_Case_Form_Activity_LinkCases {
  /**
   * @param $form
   *
   * @throws Exception
   */
  static function preProcess(&$form) {
    if (!isset($form->_caseId)) {
      CRM_Core_Error::fatal(ts('Case Id not found.'));
    }
    if (count($form->_caseId) != 1) {
      CRM_Core_Resources::fatal(ts('Expected one case-type'));
    }

    $caseId = CRM_Utils_Array::first($form->_caseId);

    $form->assign('clientID', $form->_currentlyViewedContactId);
    $form->assign('caseTypeLabel', CRM_Case_BAO_Case::getCaseType($caseId));

    // get the related cases for given case.
    $relatedCases = $form->get('relatedCases');
    if (!isset($relatedCases)) {
      $relatedCases = CRM_Case_BAO_Case::getRelatedCases($caseId, $form->_currentlyViewedContactId);
      $form->set('relatedCases', empty($relatedCases) ? FALSE : $relatedCases);
    }
    $excludeCaseIds = array($caseId);
    if (is_array($relatedCases) && !empty($relatedCases)) {
      $excludeCaseIds = array_merge($excludeCaseIds, array_keys($relatedCases));
    }
    $form->assign('excludeCaseIds', implode(',', $excludeCaseIds));
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @param $form
   *
   * @return void
   */
  static function setDefaultValues(&$form) {
    return $defaults = array();
  }

  /**
   * @param $form
   */
  static function buildQuickForm(&$form) {
    $form->add('text', 'link_to_case_id', ts('Link To Case'), array('class' => 'huge'), TRUE);
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @param $files
   * @param $form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values, $files, $form) {
    $errors = array();

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
   * Function to process the form
   *
   * @access public
   *
   * @param $form
   * @param $params
   *
   * @return void
   */
  static function beginPostProcess(&$form, &$params) {}

  /**
   * Function to process the form
   *
   * @access public
   *
   * @param $form
   * @param $params
   * @param $activity
   *
   * @return void
   */
  static function endPostProcess(&$form, &$params, &$activity) {
    $activityId = $activity->id;
    $linkCaseID = CRM_Utils_Array::value('link_to_case_id', $params);

    //create a link between two cases.
    if ($activityId && $linkCaseID) {
      $caseParams = array(
        'case_id' => $linkCaseID,
        'activity_id' => $activityId,
      );
      CRM_Case_BAO_Case::processCaseActivity($caseParams);
    }
  }
}

