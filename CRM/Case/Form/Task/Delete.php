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
 * This class provides the functionality to delete a group of case records.
 */
class CRM_Case_Form_Task_Delete extends CRM_Case_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific case?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Are we moving case to Trash.
   *
   * @var bool
   */
  public $_moveToTrash = TRUE;

  /**
   * Build all the data structures needed to build the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    if (!CRM_Core_Permission::checkActionPermission('CiviCase', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Delete cases'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $deleted = $failed = 0;
    foreach ($this->_entityIds as $caseId) {
      if (CRM_Case_BAO_Case::deleteCase($caseId, $this->_moveToTrash)) {
        $deleted++;
      }
      else {
        $failed++;
      }
    }

    if ($deleted) {
      if ($this->_moveToTrash) {
        $msg = ts('%count case moved to trash.', ['plural' => '%count cases moved to trash.', 'count' => $deleted]);
      }
      else {
        $msg = ts('%count case permanently deleted.', ['plural' => '%count cases permanently deleted.', 'count' => $deleted]);
      }
      CRM_Core_Session::setStatus($msg, ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', ['plural' => '%count could not be deleted.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

}
