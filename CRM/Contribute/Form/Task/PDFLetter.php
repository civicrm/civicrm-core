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
 * This class provides the functionality to create PDF letter for a group of
 * contacts or a single contact.
 */
class CRM_Contribute_Form_Task_PDFLetter extends CRM_Contribute_Form_Task {

  /**
   * all the existing templates in the system
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
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

  function setDefaultValues() {
    $defaults = array();
    if (isset($this->_activityId)) {
      $params = array('id' => $this->_activityId);
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = CRM_Utils_Array::value('details', $defaults);
    }
    $defaults = $defaults + CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
    return $defaults;
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);

    // use contact form as a base
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);

    // specific need for contributions
    $this->add('static', 'more_options_header', NULL, ts('Record Update Options'));
    $this->add('checkbox', 'receipt_update', ts('Update receipt dates for these contributions'), FALSE);
    $this->add('checkbox', 'thankyou_update', ts('Update thank-you dates for these contributions'), FALSE);

    // Group options for tokens are not yet implemented. dgg
    $options = array(
      '' => ts('No Grouping'),
      'contact_id' => ts('Contact'),
      'contribution_recur_id' => ts('Recurring'),
      'financial_type_id' => ts('Financial Type'),
      'campaign_id' => ts('Campaign'),
      'payment_instrument_id' => 'Payment Instrument',
    );
    $this->addElement('select', 'group_by', ts('Grouping contributions in one letter based on'), $options, array(), "<br/>", FALSE);
    // this was going to be free-text but I opted for radio options in case there was a script injection risk
    $separatorOptions = array('comma' => 'Comma', 'td' => 'Table Cell' );
    $this->addElement('select', 'group_by_separator', ts('Separator for fields in Grouped Contributions'), $separatorOptions);
    $emailOptions = array(
      '' => ts('Generate PDFs for printing (only)'),
      'email' => ts('Send as email where possible. Only generate printable PDF for remainder'),
      'both' => ts('Send as email where possible And generate printable PDF for all'),
      'pdfemail' => ts('Send pdf in email where possible. Only generate printable PDF for remainder'),
      'pdfemail_both' => ts('Send pdf in email where possible And generate printable PDF for all'),
    );
    $this->addElement('select', 'email_options', ts('PDF by email options'), $emailOptions, array(), "<br/>", FALSE);

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
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // TODO: rewrite using contribution token and one letter by contribution
    $this->setContactIDs();

    CRM_Contribute_Form_Task_PDFLetterCommon::postProcess($this);
  }
}

