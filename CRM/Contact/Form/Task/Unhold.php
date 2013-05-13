<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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

class CRM_Contact_Form_Task_Unhold extends CRM_Contact_Form_Task {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
  }

  function buildQuickForm() {
    $this->addDefaultButtons(ts('Unhold Email'), 'done');
  }

  public function postProcess() {
    // Query to unhold emails of selected contacts
    $num = count($this->_contactIds);
    if ($num >= 1) {
      $queryString = "
UPDATE civicrm_email SET on_hold = 0, hold_date = null
WHERE on_hold = 1 AND hold_date is not null AND contact_id in (" . implode(",", $this->_contactIds) . ")";
      CRM_Core_DAO::executeQuery($queryString);
      $sql = "SELECT ROW_COUNT( )";
      $result = CRM_Core_DAO::singleValueQuery($sql);
      if ($result) {
        CRM_Core_Session::setStatus(ts('%count email was found on hold and updated.', array('count' => $result, 'plural' => '%count emails were found on hold and updated.')), ts('Emails Restored'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts('The selected contact does not have an email on hold.', array('count' => $result, 'plural' => 'None of the selected contacts have an email on hold.')), ts('No Emails to Restore'), 'info');
      }
    }
    else {
      CRM_Core_Session::setStatus(ts('Please select one or more contact for this action'), ts('No Contacts Selected'), 'error');
    }
  }
}

