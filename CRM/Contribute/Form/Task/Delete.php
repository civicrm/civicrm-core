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

use Civi\Api4\Contribution;

/**
 * This class provides the functionality to delete a group of contributions.
 *
 * This class provides functionality for the actual deletion.
 */
class CRM_Contribute_Form_Task_Delete extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific contribution?
   *
   * @var bool
   */
  protected bool $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess(): void {
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $count = 0;
    $this->assign('rows');
    foreach ($this->_contributionIds as $key => $id) {
      if (!Contribution::checkAccess()
        ->setAction('delete')
        ->addValue('id', $id)
        ->execute()->first()['access']) {
        unset($this->_contributionIds[$key]);
        $count++;
      }
    }
    if ($count && empty($this->_contributionIds)) {
      CRM_Core_Session::setStatus(ts('1 contribution could not be deleted.', ['plural' => '%count contributions could not be deleted.', 'count' => $count]), ts('Error'), 'error');
      $this->addButtons([
        [
          'type' => 'back',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    elseif ($count && !empty($this->_contributionIds)) {
      CRM_Core_Session::setStatus(ts('1 contribution will not be deleted.', ['plural' => '%count contributions will not be deleted.', 'count' => $count]), ts('Warning'), 'warning');
      $this->addDefaultButtons(ts('Delete Contributions'), 'done');
    }
    else {
      $this->addDefaultButtons(ts('Delete Contributions'), 'done');
    }
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess(): void {
    $deleted = $failed = 0;
    foreach ($this->_contributionIds as $contributionId) {
      if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionId)) {
        $deleted++;
      }
      else {
        $failed++;
      }
    }

    if ($deleted) {
      $msg = ts('%count contribution deleted.', ['plural' => '%count contributions deleted.', 'count' => $deleted]);
      CRM_Core_Session::setStatus($msg, ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', ['plural' => '%count could not be deleted.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

}
