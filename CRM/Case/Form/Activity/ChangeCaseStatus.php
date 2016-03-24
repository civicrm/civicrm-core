<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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
      CRM_Core_Error::fatal(ts('Case Id not found.'));
    }
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
    $defaults = array();
    // Retrieve current case status
    $defaults['case_status_id'] = $form->_defaultCaseStatus;

    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $form->removeElement('status_id');
    $form->removeElement('priority_id');

    $form->_caseStatus = CRM_Case_PseudoConstant::caseStatus();

    foreach ($form->_caseId as $key => $val) {
      $form->_oldCaseStatus[] = $form->_defaultCaseStatus[] = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $val, 'status_id');
    }

    foreach ($form->_defaultCaseStatus as $keydefault => $valdefault) {
      if (!array_key_exists($valdefault, $form->_caseStatus)) {
        $form->_caseStatus[$valdefault] = CRM_Core_OptionGroup::getLabel('case_status',
          $valdefault,
          FALSE
        );
      }
    }
    $element = $form->add('select', 'case_status_id', ts('Case Status'),
      $form->_caseStatus, TRUE
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
   *
   * @param CRM_Core_Form $form
   * @param array $params
   */
  public static function beginPostProcess(&$form, &$params) {
    $params['id'] = CRM_Utils_Array::value('case_id', $params);
  }

  /**
   * Process the form submission.
   *
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param CRM_Activity_BAO_Activity $activity
   */
  public static function endPostProcess(&$form, &$params, $activity) {
    $groupingValues = CRM_Core_OptionGroup::values('case_status', FALSE, TRUE, FALSE, NULL, 'value');

    // Set case end_date if we're closing the case. Clear end_date if we're (re)opening it.
    if (CRM_Utils_Array::value($params['case_status_id'], $groupingValues) == 'Closed' && !empty($params['activity_date_time'])) {
      $params['end_date'] = $params['activity_date_time'];

      // End case-specific relationships (roles)
      foreach ($params['target_contact_id'] as $cid) {
        $rels = CRM_Case_BAO_Case::getCaseRoles($cid, $params['case_id']);
        // FIXME: Is there an existing function to close a relationship?
        $query = 'UPDATE civicrm_relationship SET end_date=%2 WHERE id=%1';
        foreach ($rels as $relId => $relData) {
          $relParams = array(
            1 => array($relId, 'Integer'),
            2 => array($params['end_date'], 'Timestamp'),
          );
          CRM_Core_DAO::executeQuery($query, $relParams);
        }
      }
    }
    elseif (CRM_Utils_Array::value($params['case_status_id'], $groupingValues) == 'Opened') {
      $params['end_date'] = "null";

      // Reopen case-specific relationships (roles)
      foreach ($params['target_contact_id'] as $cid) {
        $rels = CRM_Case_BAO_Case::getCaseRoles($cid, $params['case_id']);
        // FIXME: Is there an existing function?
        $query = 'UPDATE civicrm_relationship SET end_date=NULL WHERE id=%1';
        foreach ($rels as $relId => $relData) {
          $relParams = array(1 => array($relId, 'Integer'));
          CRM_Core_DAO::executeQuery($query, $relParams);
        }
      }
    }
    $params['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
    $activity->status_id = $params['status_id'];
    $params['priority_id'] = CRM_Core_OptionGroup::getValue('priority', 'Normal', 'name');
    $activity->priority_id = $params['priority_id'];

    foreach ($form->_oldCaseStatus as $statuskey => $statusval) {
      if ($activity->subject == 'null') {
        $activity->subject = ts('Case status changed from %1 to %2', array(
            1 => CRM_Utils_Array::value($statusval, $form->_caseStatus),
            2 => CRM_Utils_Array::value($params['case_status_id'], $form->_caseStatus),
          )
        );
        $activity->save();
      }
    }

    // FIXME: does this do anything ?
    $params['statusMsg'] = ts('Case Status changed successfully.');
  }

}
