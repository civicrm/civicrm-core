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
 * This class provides the functionality to email a group of contacts
 */
class CRM_Activity_Form_Task_FileOnCase extends CRM_Activity_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Variable to store redirect path.
   * @var string
   */
  protected $_userContext;

  /**
   * Variable to store contact Ids.
   * @var array
   */
  public $_contacts;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    CRM_Utils_System::setTitle(ts('File on Case'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addEntityRef('unclosed_case_id', ts('Select Case'), ['entity' => 'Case'], TRUE);
    $this->addDefaultButtons(ts('Save'));
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $formparams = $this->exportValues();
    $caseId = $formparams['unclosed_case_id'];
    $filedActivities = 0;
    foreach ($this->_activityHolderIds as $key => $id) {
      $targetContactValues = $defaults = [];
      $params = ['id' => $id];
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      if (CRM_Case_BAO_Case::checkPermission($id, 'File On Case', $defaults['activity_type_id'])) {

        if (!CRM_Utils_Array::crmIsEmptyArray($defaults['target_contact'])) {
          $targetContactValues = array_combine(array_unique($defaults['target_contact']),
            explode(';', trim($defaults['target_contact_value']))
          );
          $targetContactValues = implode(',', array_keys($targetContactValues));
        }

        $params = [
          'caseID' => $caseId,
          'activityID' => $id,
          'newSubject' => empty($defaults['subject']) ? '' : $defaults['subject'],
          'targetContactIds' => $targetContactValues,
          'mode' => 'file',
        ];

        $error_msg = CRM_Activity_Page_AJAX::_convertToCaseActivity($params);
        if (empty($error_msg['error_msg'])) {
          $filedActivities++;
        }
        else {
          CRM_Core_Session::setStatus($error_msg['error_msg'], ts("Error"), "error");
        }
      }
      else {
        CRM_Core_Session::setStatus(
          ts('Not permitted to file activity %1 %2.', [
            1 => empty($defaults['subject']) ? '' : $defaults['subject'],
            2 => $defaults['activity_date_time'],
          ]),
          ts("Error"), "error");
      }
    }

    CRM_Core_Session::setStatus($filedActivities, ts("Filed Activities"), "success");
    CRM_Core_Session::setStatus("", ts('Total Selected Activities: %1', [1 => count($this->_activityHolderIds)]), "info");
  }

}
