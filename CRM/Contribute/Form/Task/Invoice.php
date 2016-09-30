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

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
   * Gives all the statues for conribution.
   */
  public $_contributionStatusId;

  /**
   * Gives the HTML template of PDF Invoice.
   */
  public $_messageInvoice;

  /**
   * This variable is used to assign parameters for HTML template of PDF Invoice.
   */
  public $_invoiceTemplate;

  /**
   * Selected output.
   */
  public $_selectedOutput;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    if ($id) {
      $this->_contributionIds = array($id);
      $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContributions', 1);

      // set the redirection after actions
      $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);
      $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
        "action=view&reset=1&id={$id}&cid={$contactId}&context=contribution&selectedChild=contribute"
      );

      CRM_Core_Session::singleton()->pushUserContext($url);
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
      array(
        'url' => $url,
        'title' => ts('Search Results'),
      ),
    );

    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->_selectedOutput = CRM_Utils_Request::retrieve('select', 'String', $this);
    $this->assign('selectedOutput', $this->_selectedOutput);

    if ($this->_selectedOutput == 'email') {
      CRM_Utils_System::setTitle(ts('Email Invoice'));
    }
    else {
      CRM_Utils_System::setTitle(ts('Print Contribution Invoice'));
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();
    $this->preventAjaxSubmit();
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('isAdmin', 1);
    }
    $contactID = $session->get('userID');
    $contactEmails = CRM_Core_BAO_Email::allEmails($contactID);
    $emails = array();
    $fromDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $contactID, 'display_name'
    );
    foreach ($contactEmails as $emailId => $item) {
      $email = $item['email'];
      if ($email) {
        $emails[$emailId] = '"' . $fromDisplayName . '" <' . $email . '> ';
      }
      if (isset($emails[$emailId])) {
        $emails[$emailId] .= $item['locationType'];
        if ($item['is_primary']) {
          $emails[$emailId] .= ' ' . ts('(preferred)');
        }
        $emails[$emailId] = htmlspecialchars($emails[$emailId]);
      }
    }
    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address');
    foreach ($fromEmailAddress as $key => $email) {
      $fromEmailAddress[$key] = htmlspecialchars($fromEmailAddress[$key]);
    }
    $fromEmail = CRM_Utils_Array::crmArrayMerge($emails, $fromEmailAddress);
    $this->add('select', 'from_email_address', ts('From Email Address'), array('' => '- select -') + $fromEmail);
    if ($this->_selectedOutput != 'email') {
      $this->addElement('radio', 'output', NULL, ts('Email Invoice'), 'email_invoice');
      $this->addElement('radio', 'output', NULL, ts('PDF Invoice'), 'pdf_invoice');
      $this->addRule('output', ts('Selection required'), 'required');
      $this->addFormRule(array('CRM_Contribute_Form_Task_Invoice', 'formRule'));
    }
    else {
      $this->addRule('from_email_address', ts('From Email Address is required'), 'required');
    }

    $this->add('wysiwyg', 'email_comment', ts('If you would like to add personal message to email please add it here. (If sending to more then one receipient the same message will be sent to each contact.)'), array(
      'rows' => 2,
      'cols' => 40,
    ));

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => $this->_selectedOutput == 'email' ? ts('Send Email') : ts('Process Invoice(s)'),
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
   * Global validation rules for the form.
   *
   * @param array $values
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = array();

    if ($values['output'] == 'email_invoice' && empty($values['from_email_address'])) {
      $errors['from_email_address'] = ts("From Email Address is required");
    }

    return $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $this->printPDF($this->_contributionIds, $params, $this->_contactIds, $this);
  }

  /**
   * Process the PDf and email with activity and attachment on click of Print Invoices.
   *
   * @param array $contribIDs
   *   Contribution Id.
   * @param array $params
   *   Associated array of submitted values.
   * @param array $contactIds
   *   Contact Id.
   * @param CRM_Core_Form $form
   *   Form object.
   */
  public static function printPDF($contribIDs, &$params, $contactIds, &$form) {
    // get all the details needed to generate a invoice
    $messageInvoice = array();
    $invoiceTemplate = CRM_Core_Smarty::singleton();
    $invoiceElements = CRM_Contribute_Form_Task_PDF::getElements($contribIDs, $params, $contactIds);

    // gives the status id when contribution status is 'Refunded'
    $contributionStatusID = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $refundedStatusId = CRM_Utils_Array::key('Refunded', $contributionStatusID);
    $cancelledStatusId = CRM_Utils_Array::key('Cancelled', $contributionStatusID);
    $pendingStatusId = CRM_Utils_Array::key('Pending', $contributionStatusID);

    // getting data from admin page
    $prefixValue = Civi::settings()->get('contribution_invoice_settings');

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

      if (!empty($billingAddress[$contribution->contact_id]['state_province_id'])) {
        $stateProvinceAbbreviation = CRM_Core_PseudoConstant::stateProvinceAbbreviation($billingAddress[$contribution->contact_id]['state_province_id']);
      }
      else {
        $stateProvinceAbbreviation = '';
      }

      if ($contribution->contribution_status_id == $refundedStatusId || $contribution->contribution_status_id == $cancelledStatusId) {
        if (is_null($contribution->creditnote_id)) {
          $creditNoteId = CRM_Contribute_BAO_Contribution::createCreditNoteId();
          CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'creditnote_id', $creditNoteId);
        }
        else {
          $creditNoteId = $contribution->creditnote_id;
        }
      }
      $invoiceId = CRM_Utils_Array::value('invoice_prefix', $prefixValue) . "" . $contribution->id;

      //to obtain due date for PDF invoice
      $contributionReceiveDate = date('F j,Y', strtotime(date($input['receive_date'])));
      $invoiceDate = date("F j, Y");
      $dueDate = date('F j ,Y', strtotime($contributionReceiveDate . "+" . $prefixValue['due_date'] . "" . $prefixValue['due_date_period']));

      if ($input['component'] == 'contribute') {
        $eid = $contribID;
        $etable = 'contribution';
        $lineItem = CRM_Price_BAO_LineItem::getLineItems($eid, $etable, NULL, TRUE, TRUE);
      }
      else {
        $eid = $contribution->_relatedObjects['participant']->id;
        $etable = 'participant';
        $lineItem = CRM_Price_BAO_LineItem::getLineItems($eid, $etable, NULL, TRUE, FALSE, '', TRUE);
      }

      //TO DO: Need to do changes for partially paid to display amount due on PDF invoice
      $amountDue = ($input['amount'] - $input['amount']);

      // retreiving the subtotal and sum of same tax_rate
      $dataArray = array();
      $subTotal = 0;
      foreach ($lineItem as $entity_id => $taxRate) {
        if (isset($dataArray[(string) $taxRate['tax_rate']])) {
          $dataArray[(string) $taxRate['tax_rate']] = $dataArray[(string) $taxRate['tax_rate']] + CRM_Utils_Array::value('tax_amount', $taxRate);
        }
        else {
          $dataArray[(string) $taxRate['tax_rate']] = CRM_Utils_Array::value('tax_amount', $taxRate);
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

        $values['title'] = CRM_Utils_Array::value('title', CRM_Utils_Array::value($contribution->contribution_page_id, $mailDetails));
        $values['receipt_from_name'] = CRM_Utils_Array::value('receipt_from_name', CRM_Utils_Array::value($contribution->contribution_page_id, $mailDetails));
        $values['receipt_from_email'] = CRM_Utils_Array::value('receipt_from_email', CRM_Utils_Array::value($contribution->contribution_page_id, $mailDetails));
        $values['cc_receipt'] = CRM_Utils_Array::value('cc_receipt', CRM_Utils_Array::value($contribution->contribution_page_id, $mailDetails));
        $values['bcc_receipt'] = CRM_Utils_Array::value('bcc_receipt', CRM_Utils_Array::value($contribution->contribution_page_id, $mailDetails));

        $title = CRM_Utils_Array::value('title', CRM_Utils_Array::value($contribution->contribution_page_id, $mailDetails));
      }
      $source = $contribution->source;

      $config = CRM_Core_Config::singleton();
      if (!isset($params['forPage'])) {
        $config->doNotAttachPDFReceipt = 1;
      }

      // get organization address
      $domain = CRM_Core_BAO_Domain::getDomain();
      $locParams = array('contact_id' => $domain->contact_id);
      $locationDefaults = CRM_Core_BAO_Location::getValues($locParams);
      if (isset($locationDefaults['address'][1]['state_province_id'])) {
        $stateProvinceAbbreviationDomain = CRM_Core_PseudoConstant::stateProvinceAbbreviation($locationDefaults['address'][1]['state_province_id']);
      }
      else {
        $stateProvinceAbbreviationDomain = '';
      }
      if (isset($locationDefaults['address'][1]['country_id'])) {
        $countryDomain = CRM_Core_PseudoConstant::country($locationDefaults['address'][1]['country_id']);
      }
      else {
        $countryDomain = '';
      }

      // parameters to be assign for template
      $tplParams = array(
        'title' => $title,
        'component' => $input['component'],
        'id' => $contribution->id,
        'source' => $source,
        'invoice_id' => $invoiceId,
        'resourceBase' => $config->userFrameworkResourceURL,
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
        'pendingStatusId' => $pendingStatusId,
        'cancelledStatusId' => $cancelledStatusId,
        'contribution_status_id' => $contribution->contribution_status_id,
        'subTotal' => $subTotal,
        'street_address' => CRM_Utils_Array::value('street_address', CRM_Utils_Array::value($contribution->contact_id, $billingAddress)),
        'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', CRM_Utils_Array::value($contribution->contact_id, $billingAddress)),
        'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', CRM_Utils_Array::value($contribution->contact_id, $billingAddress)),
        'city' => CRM_Utils_Array::value('city', CRM_Utils_Array::value($contribution->contact_id, $billingAddress)),
        'stateProvinceAbbreviation' => $stateProvinceAbbreviation,
        'postal_code' => CRM_Utils_Array::value('postal_code', CRM_Utils_Array::value($contribution->contact_id, $billingAddress)),
        'is_pay_later' => $contribution->is_pay_later,
        'organization_name' => $contribution->_relatedObjects['contact']->organization_name,
        'domain_organization' => $domain->name,
        'domain_street_address' => CRM_Utils_Array::value('street_address', CRM_Utils_Array::value('1', $locationDefaults['address'])),
        'domain_supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', CRM_Utils_Array::value('1', $locationDefaults['address'])),
        'domain_supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', CRM_Utils_Array::value('1', $locationDefaults['address'])),
        'domain_city' => CRM_Utils_Array::value('city', CRM_Utils_Array::value('1', $locationDefaults['address'])),
        'domain_postal_code' => CRM_Utils_Array::value('postal_code', CRM_Utils_Array::value('1', $locationDefaults['address'])),
        'domain_state' => $stateProvinceAbbreviationDomain,
        'domain_country' => $countryDomain,
        'domain_email' => CRM_Utils_Array::value('email', CRM_Utils_Array::value('1', $locationDefaults['email'])),
        'domain_phone' => CRM_Utils_Array::value('phone', CRM_Utils_Array::value('1', $locationDefaults['phone'])),
      );

      if (isset($creditNoteId)) {
        $tplParams['creditnote_id'] = $creditNoteId;
      }

      $pdfFileName = "{$invoiceId}.pdf";
      $sendTemplateParams = array(
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => 'contribution_invoice_receipt',
        'contactId' => $contribution->contact_id,
        'tplParams' => $tplParams,
        'PDFFilename' => $pdfFileName,
      );
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
      //CRM-16319 - we dont store in userID in case the user is doing multiple
      //transactions etc
      if (empty($contactID)) {
        $contactID = $session->get('transaction.userID');
      }
      // Fix Invoice email doesnot send out when completed payment using Paypal
      if (empty($contactID)) {
        $contactID = current($contactIds);
      }
      $contactEmails = CRM_Core_BAO_Email::allEmails($contactID);
      $emails = array();
      $fromDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactID, 'display_name'
      );

      foreach ($contactEmails as $emailId => $item) {
        $email = $item['email'];
        if ($email) {
          $emails[$emailId] = '"' . $fromDisplayName . '" <' . $email . '> ';
        }
      }
      $fromEmail = CRM_Utils_Array::crmArrayMerge($emails, CRM_Core_OptionGroup::values('from_email_address'));

      // from email address
      if (isset($params['from_email_address'])) {
        $fromEmailAddress = CRM_Utils_Array::value($params['from_email_address'], $fromEmail);
      }
      // condition to check for download PDF Invoice or email Invoice
      if ($invoiceElements['createPdf']) {
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        if (isset($params['forPage'])) {
          return $html;
        }
        else {
          $mail = array(
            'subject' => $subject,
            'body' => $message,
            'html' => $html,
          );
          if ($mail['html']) {
            $messageInvoice[] = $mail['html'];
          }
          else {
            $messageInvoice[] = nl2br($mail['body']);
          }
        }
      }
      elseif ($contribution->_component == 'contribute') {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contribution->contact_id);

        $sendTemplateParams['tplParams'] = array_merge($tplParams, array('email_comment' => $invoiceElements['params']['email_comment']));
        $sendTemplateParams['from'] = $fromEmailAddress;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_receipt', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $values);

        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        // functions call for adding activity with attachment
        $pdfFileName = "{$invoiceId}.pdf";
        $fileName = self::putFile($html, $pdfFileName);
        self::addActivities($subject, $contribution->contact_id, $fileName, $params);
      }
      elseif ($contribution->_component == 'event') {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contribution->contact_id);

        $sendTemplateParams['tplParams'] = array_merge($tplParams, array('email_comment' => $invoiceElements['params']['email_comment']));
        $sendTemplateParams['from'] = $fromEmailAddress;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_confirm', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_confirm', $values);

        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        // functions call for adding activity with attachment
        $pdfFileName = "{$invoiceId}.pdf";
        $fileName = self::putFile($html, $pdfFileName);
        self::addActivities($subject, $contribution->contact_id, $fileName, $params);
      }

      CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'invoice_id', $invoiceId);
      $invoiceTemplate->clearTemplateVars();
    }

    if ($invoiceElements['createPdf']) {
      if (isset($params['forPage'])) {
        return $html;
      }
      else {
        $pdfFileName = "{$invoiceId}.pdf";
        CRM_Utils_PDF_Utils::html2pdf($messageInvoice, $pdfFileName, FALSE, array(
          'margin_top' => 10,
          'margin_left' => 65,
          'metric' => 'px',
        ));
        // functions call for adding activity with attachment
        $fileName = self::putFile($html, $pdfFileName);
        self::addActivities($subject, $contactIds, $fileName, $params);

        CRM_Utils_System::civiExit();
      }
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

  /**
   * Add activity for Email Invoice and the PDF Invoice.
   *
   * @param string $subject
   *   Activity subject.
   * @param array $contactIds
   *   Contact Id.
   * @param string $fileName
   *   Gives the location with name of the file.
   * @param array $params
   *   For invoices.
   *
   */
  static public function addActivities($subject, $contactIds, $fileName, $params) {
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    $config = CRM_Core_Config::singleton();
    $config->doNotAttachPDFReceipt = 1;

    if (!empty($params['output']) && $params['output'] == 'pdf_invoice') {
      $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
        'Downloaded Invoice',
        'name'
      );
    }
    else {
      $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
        'Emailed Invoice',
        'name'
      );
    }

    $activityParams = array(
      'subject' => $subject,
      'source_contact_id' => $userID,
      'target_contact_id' => $contactIds,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'attachFile_1' => array(
        'uri' => $fileName,
        'type' => 'application/pdf',
        'location' => $fileName,
        'upload_date' => date('YmdHis'),
      ),
    );
    CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * Create the Invoice file in upload folder for attachment.
   *
   * @param string $html
   *   Content for pdf in html format.
   *
   * @param string $name
   *
   * @return string
   *   Name of file which is in pdf format
   */
  static public function putFile($html, $name = 'Invoice.pdf') {
    $options = new Options();
    $options->set('isRemoteEnabled', TRUE);

    $doc = new DOMPDF($options);
    $doc->load_html($html);
    $doc->render();
    $html = $doc->output();
    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . $name;
    file_put_contents($fileName, $html);
    return $fileName;
  }

  /**
   * Callback to perform action on Print Invoice button.
   */
  public static function getPrintPDF() {
    $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, FALSE);
    $contributionIDs = array($contributionId);
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE);
    $params = array('output' => 'pdf_invoice');
    CRM_Contribute_Form_Task_Invoice::printPDF($contributionIDs, $params, $contactId, CRM_Core_DAO::$_nullObject);
  }

}
