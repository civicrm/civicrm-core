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
 * This class provides the functionality to delete a group of
 * members. This class provides functionality for the actual
 * deletion.
 */
class CRM_Member_Form_Task_Delete extends CRM_Member_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific membership?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Delete Memberships'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $deleted = $failed = 0;
    foreach ($this->_memberIds as $memberId) {
      if (CRM_Member_BAO_Membership::del($memberId)) {
        $deleted++;
      }
      else {
        $failed++;
      }
    }

    if ($deleted) {
      $msg = ts('%count membership deleted.', ['plural' => '%count memberships deleted.', 'count' => $deleted]);
      CRM_Core_Session::setStatus($msg, ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', ['plural' => '%count could not be deleted.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

}
