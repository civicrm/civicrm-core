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
 * $Id$
 *
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
    if (!CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::UPDATE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $grantStatus = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id');
    $this->addElement('select', 'status_id', ts('Grant Status'), array('' => '') + $grantStatus);

    $this->addElement('text', 'amount_granted', ts('Amount Granted'));
    $this->addRule('amount_granted', ts('Please enter a valid amount.'), 'money');

    $this->addDate('decision_date', ts('Grant Decision'), FALSE, array('formatType' => 'custom'));

    $this->assign('elements', array('status_id', 'amount_granted', 'decision_date'));
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

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $values[$key] = $value;
      }
      foreach ($this->_grantIds as $grantId) {
        $ids['grant_id'] = $grantId;

        CRM_Grant_BAO_Grant::add($values, $ids);
        $updatedGrants++;
      }
    }

    $status = ts('Updated Grant(s): %1 (Total Selected: %2)', array(1 => $updatedGrants, 2 => count($this->_grantIds)));
    CRM_Core_Session::setStatus($status, '', 'info');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 'force=1&qfKey=' . $qfKey));
  }

}
