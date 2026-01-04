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
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    $this->setTitle(ts('File on Case'));
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
          $targetContactValues = implode(',', array_unique($defaults['target_contact']));
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
