<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for case report
 *
 */
class CRM_Case_Form_Report extends CRM_Core_Form {

  /**
   * Case Id
   */
  public $_caseID = NULL;

  /**
   * Client Id
   */
  public $_clientID = NULL;

  /**
   * activity set name
   */
  public $_activitySetName = NULL;

  public $_report = NULL;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */ function preProcess() {
    $this->_caseID = CRM_Utils_Request::retrieve('caseid', 'Integer', $this, TRUE);
    $this->_clientID = CRM_Utils_Request::retrieve('cid', 'Integer', $this, TRUE);
    $this->_activitySetName = CRM_Utils_Request::retrieve('asn', 'String', $this, TRUE);

    $this->_report = $this->get('report');
    if ($this->_report) {
      $this->assign_by_ref('report', $this->_report);
    }

    // user context
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "reset=1&action=view&cid={$this->_clientID}&id={$this->_caseID}&show=1"
    );
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  public function buildQuickForm() {
    if ($this->_report) {
      return;
    }

    $includeActivites = array(1 => ts('Include All Activities'),
      2 => ts('Include Missing Activities Only'),
    );
    $includeActivitesGroup = $this->addRadio('include_activities',
      NULL,
      $includeActivites,
      NULL,
      '&nbsp;',
      TRUE
    );
    $includeActivitesGroup->setValue(1);

    $this->add('checkbox',
      'is_redact',
      ts('Redact (hide) Client and Service Provider Data')
    );

    $this->addButtons(array(
        array(
          'type' => 'refresh',
          'name' => ts('Generate Report'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $xmlProcessor = new CRM_Case_XMLProcessor_Report();
    $contents = $xmlProcessor->run($this->_clientID,
      $this->_caseID,
      $this->_activitySetName,
      $params
    );
    $this->set('report', $contents);
  }
}

