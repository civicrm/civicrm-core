<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 *
 */

/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contribute_Form_Task_Invoice extends CRM_Contribute_Form_Task {
   /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * gives all the statues for conribution
   *
   * @access public
   */
  public $_contributionStatusId;

  /**
   * gives the HTML template of PDF Invoice
   *
   * @access public
   */
  public $_messageInvoice;

  /**
   * This variable is used to assign parameters for HTML template of PDF Invoice
   *
   * @access public
   */
  public $_invoiceTemplate;
  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */ 
  function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    if ($id) {
      $this->_contributionIds = array($id);
      $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContributions', 1);
    }
    else {
      parent::preProcess();
    }
    
    // check that all the contribution ids have status Completed, Pending, Refunded.
    $this->_contributionStatusId = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $status = array('Completed', 'Pending', 'Refunded');
    $statusId = array();
    foreach ($this->_contributionStatusId as $key => $value) {
      if (in_array($value, $status)) {
        $statusId[] = $key;
      }
    }
    $Id = implode(",", $statusId);
    $query = "SELECT count(*) FROM civicrm_contribution WHERE contribution_status_id NOT IN ($Id) AND {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query);
    if ($count != 0) {
      CRM_Core_Error::statusBounce(ts('Please select only contributions with Completed, Pending, Refunded status.'));
    }
    
    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();
    $this->assign('single', $this->_single);
    
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }
    
    $url = CRM_Utils_System::url('civicrm/contribute/search', $urlParams);
    $breadCrumb = array(
         array('url' => $url,
           'title' => ts('Search Results'),
         ));
    
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Print Contribution Invoice'));
  }
  
  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addElement('radio', 'output', NULL, ts('Email Invoice'), 'email_invoice', 
      array('onClick' => "document.getElementById('selectPdfFormat').style.display = 'none';document.getElementById ('comment').style.display = 'block';")
    );
    $this->addElement('radio', 'output', NULL, ts('PDF Invoice'), 'pdf_invoice',
      array('onClick' => "document.getElementById('comment').style.display = 'none';document.getElementById('selectPdfFormat').style.display = 'block';")
    );
    $this->addRule('output', ts('Selection required'), 'required');
    $this->add('textarea', 'email_comment', ts('If you would like to add personal message to email please add it here. (The same messages will sent to all receipients.)'));
    $this->addButtons(array(
        array(
         'type' => 'next',
         'name' => ts('Process Invoice(s)'),
         'isDefault' => TRUE,
        ),
        array(
         'type' => 'back',
         'name' => ts('Cancel'),
        ),
      )
    );
  }
  
   /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // get all the details needed to generate a invoice
    $this->_messageInvoice = array();
    
    $this->_invoiceTemplate = CRM_Core_Smarty::singleton();
    
    $invoiceElements = CRM_Contribute_Form_Task_PDF::getElements();
    
    // gives the status id when contribution status is 'Refunded'
    $refundedStatusId = CRM_Utils_Array::key('Refunded', $this->_contributionStatusId);

    // getting data from admin page
    $prefixValue = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,'contribution_invoice_settings');
    
    foreach ($invoiceElements['details'] as $contribID => $detail) {
      $input = $ids = $objects = array();
      
      if (in_array($detail['contact'], $invoiceElements['excludeContactIds'])) {
        continue;
      }
      
      $input['component'] = $detail['component'];
      
      $ids['contact'] = $detail['contact'];
      $ids['contribution'] = $contribID;
      $ids['contributionRecur'] = NULL;
      $ids['contributionPage'] = NULL;
      $ids['membership'] = CRM_Utils_Array::value('membership', $detail);
      $ids['participant'] = CRM_Utils_Array::value('participant', $detail);
      $ids['event'] = CRM_Utils_Array::value('event', $detail);
      
      if (!$invoiceElements['baseIPN']->validateData($input, $ids, $objects, FALSE)) {
        CRM_Core_Error::fatal();
      }
      
      $contribution = &$objects['contribution'];
      
      $input['amount'] = $contribution->total_amount;
      $input['invoice_id'] = $contribution->invoice_id;
      $input['receive_date'] = $contribution->receive_date;
      $input['contribution_status_id'] = $contribution->contribution_status_id;
      $input['organization_name'] = $contribution->_relatedObjects['contact']->organization_name;
      
      $objects['contribution']->receive_date = CRM_Utils_Date::isoToMysql($objects['contribution']->receive_date);
      
      $addressParams = array('contact_id' => $contribution->contact_id);
      $addressDetails = CRM_Core_BAO_Address::getValues($addressParams);
      
      // to get billing address if present 
      $billingAddress = array();
      foreach ($addressDetails as $key => $address) {
        if ((isset($address['is_billing']) && $address['is_billing'] == 1) && (isset($address['is_primary']) && $address['is_primary'] == 1) && $address['contact_id'] == $contribution->contact_id) {
          $billingAddress[$address['contact_id']] = $address;
          break;
        }
        elseif (($address['is_billing'] == 0 && $address['is_primary'] == 1) || (isset($address['is_billing']) && $address['is_billing'] == 1) && $address['contact_id'] == $contribution->contact_id) {
          $billingAddress[$address['contact_id']] = $address;
        }
      }
      
      $stateProvinceAbbreviation = CRM_Core_PseudoConstant::stateProvinceAbbreviation($billingAddress[$contribution->contact_id]['state_province_id']);
      
      if ($contribution->contribution_status_id == $refundedStatusId) {
        $invoiceId = CRM_Utils_Array::value('credit_notes_prefix', $prefixValue). "" .$contribution->id;
      }
      else {
        $invoiceId = CRM_Utils_Array::value('invoice_prefix', $prefixValue). "" .$contribution->id;
      }
      
      //to obtain due date for PDF invoice
      $contributionReceiveDate = date('F j,Y', strtotime(date($input['receive_date'])));
      $invoiceDate = date("F j, Y");
      $dueDate = date('F j ,Y', strtotime($contributionReceiveDate. "+" .$prefixValue['due_date']. "" .$prefixValue['due_date_period']));
      
      if ($input['component'] == 'contribute') {
        $eid = $contribID;
        $etable = 'contribution';     
      } 
      else {
        $eid = $contribution->_relatedObjects['participant']->id;
        $etable = 'participant';
      }
      
      //TO DO: Need to do changes for partially paid to display amount due on PDF invoice 
      $amountDue = ($input['amount'] - $input['amount']);
      
      // retreiving the subtotal and sum of same tax_rate 
      $lineItem = CRM_Price_BAO_LineItem::getLineItems($eid, $etable);
      $dataArray = array();
      $subTotal = 0;
      foreach ($lineItem as $entity_id => $taxRate) {
        if (isset($dataArray[$taxRate['tax_rate']])) {
          $dataArray[$taxRate['tax_rate']] = $dataArray[$taxRate['tax_rate']] + CRM_Utils_Array::value('tax_amount', $taxRate);
        }
        else {
          $dataArray[$taxRate['tax_rate']] = CRM_Utils_Array::value('tax_amount', $taxRate);
        }
        $subTotal += CRM_Utils_Array::value('subTotal', $taxRate);
      }
      
      // to email the invoice
      $mailDetails = array();
      $values = array();
      if ($contribution->_component == 'event') {
        $daoName = 'CRM_Event_DAO_Event';
        $pageId = $contribution->_relatedObjects['event']->id;
        $mailElements = array(
                              'title',
                              'confirm_from_name',
                              'confirm_from_email',
                              'cc_confirm',
                              'bcc_confirm',
                              );
        CRM_Core_DAO::commonRetrieveAll($daoName, 'id', $pageId, $mailDetails, $mailElements);
        
        $values['title'] = CRM_Utils_Array::value('title', $mailDetails[$contribution->_relatedObjects['event']->id]);
        $values['confirm_from_name'] = CRM_Utils_Array::value('confirm_from_name', $mailDetails[$contribution->_relatedObjects['event']->id]);
        $values['confirm_from_email'] = CRM_Utils_Array::value('confirm_from_email', $mailDetails[$contribution->_relatedObjects['event']->id]);
        $values['cc_confirm'] = CRM_Utils_Array::value('cc_confirm', $mailDetails[$contribution->_relatedObjects['event']->id]);
        $values['bcc_confirm'] = CRM_Utils_Array::value('bcc_confirm', $mailDetails[$contribution->_relatedObjects['event']->id]);
        
        $title = CRM_Utils_Array::value('title', $mailDetails[$contribution->_relatedObjects['event']->id]);
      }
      elseif ($contribution->_component == 'contribute') {
        $daoName = 'CRM_Contribute_DAO_ContributionPage';
        $pageId = $contribution->contribution_page_id;
        $mailElements = array(
                              'title',
                              'receipt_from_name',
                              'receipt_from_email',
                              'cc_receipt',
                              'bcc_receipt',
                              );
        CRM_Core_DAO::commonRetrieveAll($daoName, 'id', $pageId, $mailDetails, $mailElements);
        
        $values['title'] = CRM_Utils_Array::value('title',$mailDetails[$contribution->contribution_page_id]);
        $values['receipt_from_name'] = CRM_Utils_Array::value('receipt_from_name', $mailDetails[$contribution->contribution_page_id]);
        $values['receipt_from_email'] = CRM_Utils_Array::value('receipt_from_email', $mailDetails[$contribution->contribution_page_id]);
        $values['cc_receipt'] = CRM_Utils_Array::value('cc_receipt', $mailDetails[$contribution->contribution_page_id]);
        $values['bcc_receipt'] = CRM_Utils_Array::value('bcc_receipt', $mailDetails[$contribution->contribution_page_id]);
        
        $title = CRM_Utils_Array::value('title', $mailDetails[$contribution->contribution_page_id]);
      }
      
      $config = CRM_Core_Config::singleton();
      $config->doNotAttachPDFReceipt = 1;
      
      // parameters to be assign for template
      $tplParams = array(
                         'title' => $title,
                         'component' => $input['component'],
                         'id' => $contribution->id,
                         'invoice_id' => $invoiceId,
                         'imageUploadURL' => $config->imageUploadURL,
                         'defaultCurrency' => $config->defaultCurrency,
                         'amount' => $contribution->total_amount,
                         'amountDue' => $amountDue,
                         'invoice_date' => $invoiceDate,     
                         'dueDate' => $dueDate,
                         'notes' => CRM_Utils_Array::value('notes', $prefixValue),
                         'display_name' => $contribution->_relatedObjects['contact']->display_name,
                         'lineItem' => $lineItem,
                         'dataArray' => $dataArray,
                         'refundedStatusId' => $refundedStatusId,
                         'contribution_status_id' => $contribution->contribution_status_id,
                         'subTotal' => $subTotal,
                         'street_address' => CRM_Utils_Array::value('street_address', $billingAddress[$contribution->contact_id]),
                         'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', $billingAddress[$contribution->contact_id]),
                         'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', $billingAddress[$contribution->contact_id]),
                         'city' => CRM_Utils_Array::value('city', $billingAddress[$contribution->contact_id]),
                         'stateProvinceAbbreviation' => $stateProvinceAbbreviation,
                         'postal_code' => CRM_Utils_Array::value('postal_code', $billingAddress[$contribution->contact_id]),
                         'is_pay_later' => $contribution->is_pay_later,
                         'organization_name' => $contribution->_relatedObjects['contact']->organization_name,
                         );
      
      $sendTemplateParams = array(
                                  'groupName' => 'msg_tpl_workflow_contribution',
                                  'valueName' => 'contribution_invoice_receipt',
                                  'contactId' => $contribution->contact_id,
                                  'tplParams' => $tplParams,
                                  'PDFFilename' => 'Invoice.pdf',
                                  );
      
      
      // condition to check for download PDF Invoice or email Invoice
      if ($invoiceElements['createPdf']) {
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        $mail = array(
                      'subject' => $subject,
                      'body' => $message,                                   
                      'html' => $html,
                      );
        
        if ($mail['html']) {
          $this->_messageInvoice[] = $mail['html'];
        }     
        else {
          $this->_messageInvoice[] = nl2br($mail['body']);
        }
      }
      elseif ($contribution->_component == 'contribute') {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contribution->contact_id);
        
        $sendTemplateParams['tplParams'] = array_merge($tplParams,array('email_comment' => $invoiceElements['params']['email_comment']));
        $sendTemplateParams['from'] = CRM_Utils_Array::value('receipt_from_name', $values) . ' <' . $mailDetails[$contribution->contribution_page_id]['receipt_from_email']. '>';
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_receipt', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $values);
        
        list($sent, $subject, $message, $html) =  CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
      elseif ($contribution->_component == 'event') {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contribution->contact_id);
        
        $sendTemplateParams['tplParams'] = array_merge($tplParams,array('email_comment' => $invoiceElements['params']['email_comment']));
        $sendTemplateParams['from'] = CRM_Utils_Array::value('confirm_from_name', $values) . ' <' . $mailDetails[$contribution->_relatedObjects['event']->id]['confirm_from_email'].  '>';
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_confirm', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_confirm', $values);
        
        list($sent, $subject, $message, $html) =  CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
      
      $updateInvoiceId = CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'invoice_id', $invoiceId);
      $this->_invoiceTemplate->clearTemplateVars();
    }
    
    if ($invoiceElements['createPdf']) {
      CRM_Utils_PDF_Utils::html2pdf($this->_messageInvoice, 'Invoice.pdf', FALSE);
      CRM_Utils_System::civiExit();
    }
    else {
      if ($invoiceElements['suppressedEmails']) {
        $status = ts('Email was NOT sent to %1 contacts (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).', array(1 => $invoiceElements['suppressedEmails']));
        $msgTitle = ts('Email Error');
        $msgType = 'error';
      }
      else {
        $status = ts('Your mail has been sent.');
        $msgTitle = ts('Sent');
        $msgType = 'success';
      }
      CRM_Core_Session::setStatus($status, $msgTitle, $msgType);
    }
  }
}

