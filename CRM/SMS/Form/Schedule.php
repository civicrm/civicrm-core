<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_SMS_Form_Schedule extends CRM_Core_Form {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {

    $this->_mailingID = $this->get('mailing_id');

    if (!$this->_mailingID) {
      $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, TRUE);
    }
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = array();

    $count = $this->get('count');

    $this->assign('count', $count);
    $defaults['send_option'] = 'send_immediate';
    return $defaults;
  }

  /**
   * Build the form object for the last step of the sms wizard.
   */
  public function buildQuickform() {

    // Fix Firefox issue where the non-default field is displayed as checked
    // on page refresh.
    $this->setAttribute('autocomplete', 'off');

    $sendOptions = [
      $this->createElement('radio', NULL, NULL, 'Send immediately', 'send_immediate', ['id' => 'send_immediate', 'style' => 'margin-bottom: 10px;']),
      $this->createElement('radio', NULL, NULL, 'Send at:', 'send_later', ['id' => 'send_later']),
    ];
    $this->addGroup($sendOptions, 'send_option', '', '<br>');

    $this->add('datepicker', 'start_date', '', NULL, FALSE, ['minDate' => time()]);

    $this->addFormRule(array('CRM_SMS_Form_Schedule', 'formRule'), $this);

    $buttons = array(
      array(
        'type' => 'back',
        'name' => ts('Previous'),
      ),
      array(
        'type' => 'next',
        'name' => ts('Submit Mass SMS'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Continue Later'),
      ),
    );

    $this->addButtons($buttons);

    $preview = array();
    $preview['type'] = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $this->_mailingID, 'body_html') ? 'html' : 'text';
    $preview['viewURL'] = CRM_Utils_System::url('civicrm/mailing/view', "reset=1&id={$this->_mailingID}");
    $this->assign_by_ref('preview', $preview);
  }

  /**
   * Form rule to validate the date selector and/or if we should deliver
   * immediately.
   *
   * Warning: if you make changes here, be sure to also make them in
   * Retry.php
   *
   * @param array $params
   *   The form values.
   *
   * @param $files
   * @param $self
   *
   * @return bool
   *   True if either we deliver immediately, or the date is properly
   *   set.
   */
  public static function formRule($params, $files, $self) {

    if (!empty($params['_qf_Schedule_submit'])) {
      CRM_Core_Session::setStatus(ts("Your Mass SMS has been saved. Click the 'Continue' action to resume working on it."), ts('Saved'), 'success');
      $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1&sms=1');
      CRM_Utils_System::redirect($url);
    }

    if ((isset($params['send_option']) && $params['send_option'] == 'send_immediate') || CRM_Utils_Array::value('_qf_Schedule_back', $params) == ts('Previous')) {
      return TRUE;
    }

    if (strtotime($params['start_date']) < time()) {
      return array(
        'start_date' => ts('Start date cannot be earlier than the current time.'),
      );
    }

    return TRUE;
  }

  /**
   * Process the posted form values. Create and schedule a Mass SMS.
   */
  public function postProcess() {
    $params = array();

    $params['mailing_id'] = $ids['mailing_id'] = $this->_mailingID;

    if (empty($params['mailing_id'])) {
      CRM_Core_Error::fatal(ts('Could not find a mailing id'));
    }

    $params['send_option'] = $this->controller->exportValue($this->_name, 'send_option');
    if (isset($params['send_option']) && $params['send_option'] == 'send_immediate') {
      $params['scheduled_date'] = date('YmdHis');
    }
    else {
      $params['scheduled_date'] = $this->controller->exportValue($this->_name, 'start_date');
    }

    $session = CRM_Core_Session::singleton();
    // set the scheduled_id
    $params['scheduled_id'] = $session->get('userID');

    // set approval details if workflow is not enabled
    if (!CRM_Mailing_Info::workflowEnabled()) {
      $params['approver_id'] = $session->get('userID');
      $params['approval_date'] = date('YmdHis');
      $params['approval_status_id'] = 1;
    }

    // Build the mailing object.
    CRM_Mailing_BAO_Mailing::create($params, $ids);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/mailing/browse/scheduled',
      'reset=1&scheduled=true&sms=1'
    ));
  }

  /**
   * Display Name of the form.
   *
   *
   * @return string
   */
  public function getTitle() {
    return ts('Schedule or Send');
  }

}
