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
 */

/**
 * This class provides the functionality to create PDF letter for a group of contacts or a single contact.
 */
class CRM_Contribute_Form_Task_PDFLetter extends CRM_Contribute_Form_Task {

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->skipOnHold = $this->skipDeceased = FALSE;
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'Positive', $this, FALSE);

    // retrieve contact ID if this is 'single' mode
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);

    $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($cid) {
      CRM_Contact_Form_Task_PDFLetterCommon::preProcessSingle($this, $cid);
      $this->_single = TRUE;
      $this->_cid = $cid;
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */
  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    if (isset($this->_activityId)) {
      $params = array('id' => $this->_activityId);
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = CRM_Utils_Array::value('details', $defaults);
    }
    else {
      $defaults['thankyou_update'] = 1;
    }
    $defaults = $defaults + CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);

    // use contact form as a base
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);

    // specific need for contributions
    $this->add('static', 'more_options_header', NULL, ts('Thank-you Letter Options'));
    $this->add('checkbox', 'receipt_update', ts('Update receipt dates for these contributions'), FALSE);
    $this->add('checkbox', 'thankyou_update', ts('Update thank-you dates for these contributions'), FALSE);

    // Group options for tokens are not yet implemented. dgg
    $options = array(
      '' => ts('- no grouping -'),
      'contact_id' => ts('Contact'),
      'contribution_recur_id' => ts('Contact and Recurring'),
      'financial_type_id' => ts('Contact and Financial Type'),
      'campaign_id' => ts('Contact and Campaign'),
      'payment_instrument_id' => ts('Contact and Payment Method'),
    );
    $this->addElement('select', 'group_by', ts('Group contributions by'), $options, array(), "<br/>", FALSE);
    // this was going to be free-text but I opted for radio options in case there was a script injection risk
    $separatorOptions = array('comma' => 'Comma', 'td' => 'Horizontal Table Cell', 'tr' => 'Vertical Table Cell', 'br' => 'Line Break');

    $this->addElement('select', 'group_by_separator', ts('Separator (grouped contributions)'), $separatorOptions);
    $emailOptions = array(
      '' => ts('Generate PDFs for printing (only)'),
      'email' => ts('Send emails where possible. Generate printable PDFs for contacts who cannot receive email.'),
      'both' => ts('Send emails where possible. Generate printable PDFs for all contacts.'),
    );
    if (CRM_Core_Config::singleton()->doNotAttachPDFReceipt) {
      $emailOptions['pdfemail'] = ts('Send emails with an attached PDF where possible. Generate printable PDFs for contacts who cannot receive email.');
      $emailOptions['pdfemail_both'] = ts('Send emails with an attached PDF where possible. Generate printable PDFs for all contacts.');
    }
    $this->addElement('select', 'email_options', ts('Print and email options'), $emailOptions, array(), "<br/>", FALSE);

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Make Thank-you Letters'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
        ),
      )
    );

  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contribute_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    return $tokens;
  }

}
