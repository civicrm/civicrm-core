<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class provides the functionality to restore a group of participations.
 */
class CRM_Case_Form_Task_Restore extends CRM_Case_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific case?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Restore Cases'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $restoredCases = $failed = 0;
    foreach ($this->_caseIds as $caseId) {
      if (CRM_Case_BAO_Case::restoreCase($caseId)) {
        $restoredCases++;
      }
      else {
        $failed++;
      }
    }

    if ($restoredCases) {
      $msg = ts('%count case restored from trash.', array(
        'plural' => '%count cases restored from trash.',
        'count' => $restoredCases,
      ));
      CRM_Core_Session::setStatus($msg, ts('Restored'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be restored.', array('plural' => '%count could not be restored.', 'count' => $failed)), ts('Error'), 'error');
    }
  }

}
