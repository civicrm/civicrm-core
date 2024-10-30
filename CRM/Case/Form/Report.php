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
 * This class generates form components for case report.
 */
class CRM_Case_Form_Report extends CRM_Core_Form {

  /**
   * Case Id
   * @var int
   */
  public $_caseID = NULL;

  /**
   * Client Id
   * @var int
   */
  public $_clientID = NULL;

  /**
   * Activity set name
   * @var string
   */
  public $_activitySetName = NULL;

  public $_report = NULL;

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->_caseID = CRM_Utils_Request::retrieve('caseid', 'Integer', $this, TRUE);
    $this->_clientID = CRM_Utils_Request::retrieve('cid', 'Integer', $this, TRUE);
    $this->_activitySetName = CRM_Utils_Request::retrieve('asn', 'String', $this, TRUE);

    $this->_report = $this->get('report');
    if ($this->_report) {
      $this->assign('report', $this->_report);
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

    $includeActivites = [
      1 => ts('All Activities'),
      2 => ts('Exclude Completed Activities'),
    ];
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

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Generate Report'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
    // We want this form to redirect to a full page
    $this->preventAjaxSubmit();
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    // this is either a 1 or a 2, but the url expects a 1 or 0
    $all = ($params['include_activities'] == 1) ? 1 : 0;

    // similar but comes from a checkbox that's either 1 or not present
    $is_redact = empty($params['is_redact']) ? 0 : 1;

    $asn = rawurlencode($this->_activitySetName);

    CRM_Utils_System::redirect(
      CRM_Utils_System::url(
        'civicrm/case/report/print',
        "caseID={$this->_caseID}&cid={$this->_clientID}&asn={$asn}&redact={$is_redact}&all={$all}"
      )
    );
  }

}
