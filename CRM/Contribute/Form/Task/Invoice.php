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

use Civi\Api4\Contact;
use Civi\Api4\Email;

/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contribute_Form_Task_Invoice extends CRM_Contribute_Form_Task {
  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * Gives all the statues for conribution.
   * @var int
   */
  public $_contributionStatusId;

  /**
   * Gives the HTML template of PDF Invoice.
   * @var string
   */
  public $_messageInvoice;

  /**
   * This variable is used to assign parameters for HTML template of PDF Invoice.
   * @var string
   */
  public $_invoiceTemplate;

  /**
   * Selected output.
   * @var string
   */
  public $_selectedOutput;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    if ($id) {
      $this->_contributionIds = [$id];
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

    // check that all the contribution ids have status Completed, Pending, Refunded, or Partially Paid.
    $this->_contributionStatusId = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $status = ['Completed', 'Pending', 'Refunded', 'Partially paid'];
    $statusId = [];
    foreach ($this->_contributionStatusId as $key => $value) {
      if (in_array($value, $status)) {
        $statusId[] = $key;
      }
    }
    $Id = implode(",", $statusId);
    $query = "SELECT count(*) FROM civicrm_contribution WHERE contribution_status_id NOT IN ($Id) AND {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query);
    if ($count != 0) {
      CRM_Core_Error::statusBounce(ts('Please select only contributions with Completed, Pending, Refunded, or Partially Paid status.'));
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
    $breadCrumb = [
      [
        'url' => $url,
        'title' => ts('Search Results'),
      ],
    ];

    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->_selectedOutput = CRM_Utils_Request::retrieve('select', 'String', $this);
    $this->assign('selectedOutput', $this->_selectedOutput);

    $this->preProcessFromAddress();
    if ($this->_selectedOutput == 'email') {
      $this->setTitle(ts('Email Invoice'));
    }
    else {
      $this->setTitle(ts('Print Contribution Invoice'));
    }
  }

  /**
   * Pre Process Form Addresses to be used in Quickform
   *
   * This doesn't really do much - use part should be transferred back to caller
   * and noisy deprecation added.
   *
   * @deprecated
   *
   * @throws \CRM_Core_Exception
   */
  private function preProcessFromAddress() {
    $this->_emails = [];

    // @TODO remove these line and to it somewhere more appropriate. Currently some classes (e.g Case
    // are having to re-write contactIds afterwards due to this inappropriate variable setting
    // If we don't have any contact IDs, use the logged in contact ID
    $this->_contactIds = $this->_contactIds ?: [CRM_Core_Session::getLoggedInContactID()];

    $fromEmailValues = CRM_Core_BAO_Email::getFromEmail();

    if (empty($fromEmailValues)) {
      CRM_Core_Error::statusBounce(ts('Your user record does not have a valid email address and no from addresses have been configured.'));
    }

    $this->_emails = $fromEmailValues;
    $defaults = [];
    $this->_fromEmails = $fromEmailValues;
    if (is_numeric(key($this->_fromEmails))) {
      $emailID = (int) key($this->_fromEmails);
      $defaults = CRM_Core_BAO_Email::getEmailSignatureDefaults($emailID);
    }
    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      $defaults['from_email_address'] = CRM_Core_BAO_Domain::getFromEmail();
    }
    $this->setDefaults($defaults);
  }

  protected function getFieldsToExcludeFromPurification(): array {
    return [
      // Because value contains <angle brackets>
      'from_email_address',
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $this->preventAjaxSubmit();
    $this->assign('isAdmin', CRM_Core_Permission::check('administer CiviCRM'));

    $this->add('select', 'from_email_address', ts('From'), $this->_fromEmails, TRUE, ['class' => 'crm-select2 huge']);
    if ($this->_selectedOutput !== 'email') {
      $this->addElement('radio', 'output', NULL, ts('Email Invoice'), 'email_invoice');
      $this->addElement('radio', 'output', NULL, ts('PDF Invoice'), 'pdf_invoice');
      $this->addRule('output', ts('Selection required'), 'required');
      $this->addFormRule(['CRM_Contribute_Form_Task_Invoice', 'formRule']);
    }
    else {
      $this->addRule('from_email_address', ts('From Email Address is required'), 'required');
    }

    $this->addEntityRef('cc_id', ts('CC'), [
      'entity' => 'Email',
      'multiple' => TRUE,
    ]);
    $this->add('text', 'subject', ts('Replace Subject'), ['class' => 'huge', 'placeholder' => ts('Optional')]);
    $this->add('wysiwyg', 'email_comment', ts('Additional Message'), [
      'rows' => 2,
      'cols' => 40,
    ]);

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => $this->_selectedOutput === 'email' ? ts('Send Email') : ts('Process Invoice(s)'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
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
    $errors = [];

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
    self::printPDF($this->_contributionIds, $params, $this->_contactIds);
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function printPDF($contribIDs, &$params, $contactIds) {
    // get all the details needed to generate a invoice
    $messageInvoice = [];
    $isCreatePDF = FALSE;
    if (!empty($params['output']) &&
      ($params['output'] === 'pdf_invoice' || $params['output'] === 'pdf_receipt')
    ) {
      $isCreatePDF = TRUE;
    }

    $invoiceTemplate = CRM_Core_Smarty::singleton();
    $invoiceElements = self::getElements($contribIDs, $params, $contactIds, $isCreatePDF);
    $elementDetails = $invoiceElements['details'];
    $excludedContactIDs = $invoiceElements['excludeContactIds'];
    $suppressedEmails = $isCreatePDF ? NULL : $invoiceElements['suppressedEmails'];
    unset($invoiceElements);

    // @todo - almost all the code from here down should be removed. The expectation
    // is that the template should not rely on the form layer to assign variables
    // but rather the templates should be migrated over time to use tokens
    // and the variables assigned from the WorkFlow message class
    // ie $lineItems and $taxBreakdown and a small number of other complex variables
    // that are not suited to tokens.
    // The expectation is that the invoice should be renderable from other places,
    // including our form builder/ search kit layer based on being provided the
    // contribution ID & contact ID.
    // Note that current gaps are in 2 groups
    // 1) tpl file could be updated as of now - this is almost all the tokens
    // 2) no suitable token available. This is probably only the case with
    // the title token and the due_date token. We will incrementally update the template
    // but we don't do 'push notifications' (put out a message to tell users to
    // update their customised tokens) every update.
    // gives the status id when contribution status is 'Refunded'
    $contributionStatusID = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $refundedStatusId = CRM_Utils_Array::key('Refunded', $contributionStatusID);
    $cancelledStatusId = CRM_Utils_Array::key('Cancelled', $contributionStatusID);
    $pdfFormat = CRM_Core_BAO_MessageTemplate::getPDFFormatForTemplate('contribution_invoice_receipt');
    foreach ($elementDetails as $contributionID => $detail) {
      if (in_array($detail['contact'], $excludedContactIDs)) {
        continue;
      }

      $component = $detail['component'];
      $eventID = $detail['event'] ?? NULL;

      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = $contributionID;
      $contribution->find(TRUE);

      // Fetch the billing address. getValues should prioritize the billing
      // address, otherwise will return the primary address.
      $billingAddress = [];

      $addressDetails = CRM_Core_BAO_Address::getValues([
        'contact_id' => $contribution->contact_id,
        'is_billing' => 1,
      ]);

      if (!empty($addressDetails)) {
        // @todo - use {contribution.address_id.display} token in the template}
        // stop calculating this.
        $billingAddress = array_shift($addressDetails);
      }

      if ($contribution->contribution_status_id == $refundedStatusId || $contribution->contribution_status_id == $cancelledStatusId) {
        $creditNoteId = $contribution->creditnote_id;
      }
      if (!$contribution->invoice_number) {
        // @todo - use {contribution.invoice_number token in the template}. remove this code.
        $contribution->invoice_number = CRM_Contribute_BAO_Contribution::getInvoiceNumber($contribution->id);
      }

      //to obtain due date for PDF invoice
      $contributionReceiveDate = date('F j,Y', strtotime(date($contribution->receive_date)));
      // @todo - stop assigning invoiceDate to the template - use
      // {contribution.total_amount} or {domain.now} instead - both of which support
      // formatting via |crmDate
      $dueDateSetting = Civi::settings()->get('invoice_due_date');
      $dueDatePeriodSetting = Civi::settings()->get('invoice_due_date_period');
      $dueDate = date('F j, Y', strtotime($contributionReceiveDate . "+" . $dueDateSetting . "" . $dueDatePeriodSetting));

      // to email the invoice
      $mailDetails = [];
      $values = [];
      if ($component === 'event') {
        $daoName = 'CRM_Event_DAO_Event';
        $pageId = $eventID;
        $mailElements = [
          'title',
          'confirm_from_name',
          'confirm_from_email',
        ];
        CRM_Core_DAO::commonRetrieveAll($daoName, 'id', $pageId, $mailDetails, $mailElements);
        $values['title'] = $mailDetails[$eventID]['title'] ?? NULL;
        $values['confirm_from_name'] = $mailDetails[$eventID]['confirm_from_name'] ?? NULL;
        $values['confirm_from_email'] = $mailDetails[$eventID]['confirm_from_email'] ?? NULL;

        $title = $mailDetails[$eventID]['title'] ?? NULL;
      }
      elseif ($component === 'contribute') {
        $daoName = 'CRM_Contribute_DAO_ContributionPage';
        $pageId = $contribution->contribution_page_id;
        $mailElements = [
          'title',
          'receipt_from_name',
          'receipt_from_email',
          'cc_receipt',
          'bcc_receipt',
        ];
        CRM_Core_DAO::commonRetrieveAll($daoName, 'id', $pageId, $mailDetails, $mailElements);

        $values['title'] = $mailDetails[$contribution->contribution_page_id]['title'] ?? NULL;
        $values['receipt_from_name'] = $mailDetails[$contribution->contribution_page_id]['receipt_from_name'] ?? NULL;
        $values['receipt_from_email'] = $mailDetails[$contribution->contribution_page_id]['receipt_from_email'] ?? NULL;
        $values['cc_receipt'] = $mailDetails[$contribution->contribution_page_id]['cc_receipt'] ?? NULL;
        $values['bcc_receipt'] = $mailDetails[$contribution->contribution_page_id]['bcc_receipt'] ?? NULL;

        $title = $mailDetails[$contribution->contribution_page_id]['title'] ?? NULL;
      }

      $config = CRM_Core_Config::singleton();
      if (!isset($params['forPage'])) {
        $config->doNotAttachPDFReceipt = 1;
      }

      // get organization address
      // @todo - this should all be replaced by using tokens in the template.
      $domain = CRM_Core_BAO_Domain::getDomain();

      $invoiceNotes = Civi::settings()->get('invoice_notes');

      // parameters to be assign for template
      $tplParams = [
        // @todo is a 'title' a real thing - is so, it should be token.
        'title' => $title,
        // @todo used in the subject but analysis of ^^ would remove
        'component' => $component,
        // @todo not used in shipped template for a very long time, if ever, remove
        // token is available.
        'id' => $contribution->id,
        'resourceBase' => $config->userFrameworkResourceURL,
        // @todo add this to apiv4 & add a token so we can replace it here.
        'dueDate' => $dueDate,
        'notes' => $invoiceNotes,
        // @todo - stop assigning this to the template - ensure the
        // template uses {contribution.address_id.street_address} (etc)
        'street_address' => $billingAddress['street_address'] ?? NULL,
        'supplemental_address_1' => $billingAddress['supplemental_address_1'] ?? NULL,
        'supplemental_address_2' => $billingAddress['supplemental_address_2'] ?? NULL,
        'supplemental_address_3' => $billingAddress['supplemental_address_3'] ?? NULL,
        'city' => $billingAddress['city'] ?? NULL,
        'postal_code' => $billingAddress['postal_code'] ?? NULL,
        'state_province' => $billingAddress['state_province'] ?? NULL,
        'state_province_abbreviation' => $billingAddress['state_province_abbreviation'] ?? NULL,
        // Kept for backwards compatibility
        'stateProvinceAbbreviation' => $billingAddress['state_province_abbreviation'] ?? NULL,
        'country' => $billingAddress['country'] ?? NULL,
        // @todo not used in shipped template from 5.52 - from here down
        'organization_name' => Contact::get(FALSE)->addSelect('organization_name')->addWhere('id', '=', (int) $contribution->contact_id)->execute()->first()['organization_name'],
        'domain_organization' => $domain->name,
      ];

      if (isset($creditNoteId)) {
        $tplParams['creditnote_id'] = $creditNoteId;
      }

      $pdfFileName = $contribution->invoice_number . ".pdf";
      $sendTemplateParams = [
        'workflow' => 'contribution_invoice_receipt',
        'tplParams' => $tplParams,
        'PDFFilename' => $pdfFileName,
        'tokenContext' => ['contributionId' => $contribution->id, 'contactId' => $contribution->contact_id],
        'modelProps' => [
          'userEnteredText' => $params['email_comment'] ?? NULL,
        ],
      ];

      // from email address
      $fromEmailAddress = $params['from_email_address'] ?? NULL;
      if (!empty($params['cc_id'])) {
        // get contacts and their emails from email id
        $emailIDs = $params['cc_id'] ? explode(',', $params['cc_id']) : [];
        $emails = Email::get()
          ->addWhere('id', 'IN', $emailIDs)
          ->setCheckPermissions(FALSE)
          ->setSelect(['contact_id', 'email', 'contact_id.sort_name', 'contact_id.display_name'])->execute();
        $emailStrings = $contactUrlStrings = [];
        foreach ($emails as $email) {
          $emailStrings[] = '"' . $email['contact_id.sort_name'] . '" <' . $email['email'] . '>';
          // generate the contact url to put in Activity
          $contactURL = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'force' => 1, 'cid' => $email['contact_id']], TRUE);
          $contactUrlStrings[] = "<a href='{$contactURL}'>" . $email['contact_id.display_name'] . '</a>';
        }
        $cc_emails = implode(',', $emailStrings);
        $values['cc_receipt'] = $cc_emails;
        $ccContactsDetails = implode(',', $contactUrlStrings);
        // add CC emails as activity details
        $params['activity_details'] = "\ncc : " . $ccContactsDetails;

        // unset bcc to avoid unknown email come from online page configuration.
        unset($values['bcc_receipt']);
      }

      // get subject from UI
      if (!empty($params['subject'])) {
        $sendTemplateParams['subject'] = $values['subject'] = $params['subject'];
      }

      // condition to check for download PDF Invoice or email Invoice
      if ($isCreatePDF) {
        [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        if (isset($params['forPage'])) {
          return $html;
        }
        else {
          $mail = [
            'subject' => $subject,
            'body' => $message,
            'html' => $html,
          ];
          if ($mail['html']) {
            $messageInvoice[] = $mail['html'];
          }
          else {
            $messageInvoice[] = nl2br($mail['body']);
          }
        }
      }
      elseif ($component === 'contribute') {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contribution->contact_id);

        $sendTemplateParams['tplParams'] = array_merge($tplParams, ['email_comment' => $params['email_comment']]);
        $sendTemplateParams['from'] = $fromEmailAddress;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = $values['cc_receipt'] ?? NULL;
        $sendTemplateParams['bcc'] = $values['bcc_receipt'] ?? NULL;

        [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        // functions call for adding activity with attachment
        // make sure page layout is same for email and download invoices.
        $fileName = self::putFile($html, $pdfFileName, $pdfFormat);
        self::addActivities($subject, $contribution->contact_id, $fileName, $params, $contribution->id);
      }
      elseif ($component === 'event') {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contribution->contact_id);

        $sendTemplateParams['tplParams'] = $tplParams;
        $sendTemplateParams['from'] = $fromEmailAddress;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = $values['cc_receipt'] ?? NULL;
        $sendTemplateParams['bcc'] = $values['bcc_receipt'] ?? NULL;

        [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        // functions call for adding activity with attachment
        $fileName = self::putFile($html, $pdfFileName);
        self::addActivities($subject, $contribution->contact_id, $fileName, $params, $contribution->id);
      }
      $invoiceTemplate->clearTemplateVars();
    }

    if ($isCreatePDF) {
      if (isset($params['forPage'])) {
        return $html;
      }
      else {
        CRM_Utils_PDF_Utils::html2pdf($messageInvoice, $pdfFileName, FALSE, $pdfFormat);
        // functions call for adding activity with attachment
        $fileName = self::putFile($html, $pdfFileName, $pdfFormat);
        self::addActivities($subject, $contactIds, $fileName, $params);

        CRM_Utils_System::civiExit();
      }
    }
    else {
      if ($suppressedEmails) {
        $status = ts('Email was NOT sent to %1 contacts (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).', [1 => $suppressedEmails]);
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
   * Declaration of common variables for Invoice and PDF.
   *
   * @param array $contributionIDs
   *   Contribution Id.
   * @param array $params
   *   Parameter for pdf or email invoices.
   * @param array|int $contactIds
   *   Contact Id.
   * @param bool $isCreatePDF
   *
   * @return array
   *   array of common elements
   *
   * @throws \CRM_Core_Exception
   */
  private static function getElements(array $contributionIDs, array $params, $contactIds, bool $isCreatePDF): array {
    if (empty($contributionIDs)) {
      CRM_Core_Error::deprecatedWarning('calling this function with no IDs is deprecated');
      return [];
    }

    $rows = [];
    $lines = \Civi\Api4\LineItem::get(FALSE)
      ->addWhere('contribution_id', 'IN', $contributionIDs)
      ->addSelect('*', 'contribution_id.contact_id')
      ->execute();

    foreach ($lines as $line) {
      $rows[$line['contribution_id']] = $rows[$line['contribution_id']] ?? [] + [
        'component' => 'contribute',
        'contact' => $line['contribution_id.contact_id'],
        'membership' => NULL,
        'participant' => NULL,
        'event' => NULL,
      ];
      if ($line['entity_table'] == 'civicrm_participant') {
        $rows[$line['contribution_id']]['participant'] = $line['entity_id'];
        $rows[$line['contribution_id']]['event'] = \Civi\Api4\Participant::get(FALSE)
          ->addWhere('id', '=', $line['entity_id'])
          ->addSelect('event_id')
          ->execute()->single()['event_id'];
      }
      if ($line['entity_table'] == 'civicrm_membership') {
        $rows[$line['contribution_id']]['membership'] = $line['entity_id'];
      }
    }
    $pdfElements  = ['details' => $rows];
    $excludeContactIds = [];
    $suppressedEmails = 0;
    if (!$isCreatePDF) {
      $contactDetails = civicrm_api3('Contact', 'get', [
        'return' => ['email', 'do_not_email', 'is_deceased', 'on_hold'],
        'id' => ['IN' => $contactIds],
        'options' => ['limit' => 0],
      ])['values'];
      foreach ($contactDetails as $id => $values) {
        if (empty($values['email']) ||
          (empty($params['override_privacy']) && !empty($values['do_not_email']))
          || !empty($values['is_deceased'])
          || !empty($values['on_hold'])
        ) {
          $suppressedEmails++;
          $excludeContactIds[] = $values['contact_id'];
        }
      }
    }
    $pdfElements['suppressedEmails'] = $suppressedEmails;
    $pdfElements['excludeContactIds'] = $excludeContactIds;

    return $pdfElements;
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
   * @param int $contributionId
   *   Contribution Id.
   *
   */
  public static function addActivities($subject, $contactIds, $fileName, $params, $contributionId = NULL) {
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    $config = CRM_Core_Config::singleton();
    $config->doNotAttachPDFReceipt = 1;

    if (!empty($params['output']) && $params['output'] == 'pdf_invoice') {
      $activityType = 'Downloaded Invoice';
    }
    else {
      $activityType = 'Emailed Invoice';
    }

    $activityParams = [
      'subject' => $subject,
      'source_contact_id' => $userID,
      'target_contact_id' => $contactIds,
      'activity_type_id' => $activityType,
      'activity_date_time' => date('YmdHis'),
      'details' => $params['activity_details'] ?? NULL,
      'attachFile_1' => [
        'uri' => $fileName,
        'type' => 'application/pdf',
        'location' => $fileName,
        'upload_date' => date('YmdHis'),
      ],
    ];
    if ($contributionId) {
      $activityParams['source_record_id'] = $contributionId;
    }
    civicrm_api3('Activity', 'create', $activityParams);
  }

  /**
   * Create the Invoice file in upload folder for attachment.
   *
   * @param string $html
   *   Content for pdf in html format.
   *
   * @param string $name
   * @param array $format
   *
   * @return string
   *   Name of file which is in pdf format
   */
  public static function putFile($html, $name = 'Invoice.pdf', $format = NULL) {
    return CRM_Utils_Mail::appendPDF($name, $html, $format)['fullPath'] ?? '';
  }

  /**
   * Callback to perform action on Print Invoice button.
   */
  public static function getPrintPDF() {
    $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, FALSE);
    $contributionIDs = [$contributionId];
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE);
    $params = ['output' => 'pdf_invoice'];
    CRM_Contribute_Form_Task_Invoice::printPDF($contributionIDs, $params, $contactId);
  }

}
