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
 * This class provides the functionality to update a group of
 * grants. This class provides functionality for the actual
 * update.
 */
class CRM_Grant_Form_Task_Update extends CRM_Grant_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();

    //check permission for update.
    if (!CRM_Core_Permission::check('edit grants')) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $grantStatus = CRM_Grant_DAO_Grant::buildOptions('status_id');
    $this->addElement('select', 'status_id', ts('Grant Status'), ['' => ''] + $grantStatus);

    $this->addElement('text', 'amount_granted', ts('Amount Granted'));
    $this->addRule('amount_granted', ts('Please enter a valid amount.'), 'money');

    $this->add('datepicker', 'decision_date', ts('Grant Decision'), [], FALSE, ['time' => FALSE]);

    $this->assign('elements', ['status_id', 'amount_granted', 'decision_date']);
    $this->assign('totalSelectedGrants', count($this->_grantIds));

    $this->addDefaultButtons(ts('Update Grants'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $updatedGrants = 0;

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $qfKey = $params['qfKey'];
    foreach ($params as $key => $value) {
      if ($value == '' || $key == 'qfKey') {
        unset($params[$key]);
      }
    }
    $values = [
      'skipRecentView' => TRUE,
    ];

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $values[$key] = $value;
      }
      foreach ($this->_grantIds as $grantId) {
        $values['id'] = $grantId;

        CRM_Grant_BAO_Grant::writeRecord($values);
        $updatedGrants++;
      }
    }

    $status = ts('Updated Grant(s): %1 (Total Selected: %2)', [1 => $updatedGrants, 2 => count($this->_grantIds)]);
    CRM_Core_Session::setStatus($status, '', 'info');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 'force=1&qfKey=' . $qfKey));
  }

}
