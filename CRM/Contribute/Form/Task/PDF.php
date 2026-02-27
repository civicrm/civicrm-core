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
use Civi\Api4\LineItem;
use Civi\Api4\Participant;

/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contribute_Form_Task_PDF extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var bool
   */
  public $_single = FALSE;

  protected $_rows;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    // check that all the contribution ids have pending status
    $query = "
SELECT count(*)
FROM   civicrm_contribution
WHERE  contribution_status_id != 1
AND    {$this->_componentClause}";
    if (CRM_Core_DAO::singleValueQuery($query)) {
      CRM_Core_Error::statusBounce("Please select only contributions with Completed status.");
    }

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
    $this->_contactIds = $this->_contactIds ?: [CRM_Core_Session::getLoggedInContactID()];
    $this->preProcessFromAddress();
    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    $this->setTitle(ts('Print Contribution Receipts'));
    // Ajax submit would interfere with pdf file download
    $this->preventAjaxSubmit();
  }

  /**
   * Pre Process Form Addresses to be used in Quickform
   *
   * @throws \CRM_Core_Exception
   */
  private function preProcessFromAddress() {
    $fromEmailValues = CRM_Core_BAO_Email::getFromEmail();

    if (empty($fromEmailValues)) {
      CRM_Core_Error::statusBounce(ts('Your user record does not have a valid email address and no from addresses have been configured.'));
    }

    $defaults = [];
    if (is_numeric(key($fromEmailValues))) {
      $emailID = (int) key($fromEmailValues);
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
  public function buildQuickForm() {

    $this->addElement('radio', 'output', NULL, ts('Email Receipts'), 'email_receipt',
      [
        'onClick' => "document.getElementById('selectPdfFormat').style.display = 'none';
        document.getElementById('selectEmailFrom').style.display = 'block';",
      ]
    );
    $this->addElement('radio', 'output', NULL, ts('PDF Receipts'), 'pdf_receipt',
      [
        'onClick' => "document.getElementById('selectPdfFormat').style.display = 'block';
        document.getElementById('selectEmailFrom').style.display = 'none';",
      ]
    );
    $this->addRule('output', ts('Selection required'), 'required');

    $this->add('select', 'pdf_format_id', ts('Page Format'),
      [0 => ts('- default -')] + CRM_Core_BAO_PdfFormat::getList(TRUE)
    );
    $this->add('checkbox', 'receipt_update', ts('Update receipt dates for these contributions'), FALSE);
    $this->add('checkbox', 'override_privacy', ts('Override privacy setting? (Do not email / Do not mail)'), FALSE);

    $this->add('select', 'from_email_address', ts('From Email'), $this->getFromEmails(), FALSE);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Process Receipt(s)'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'back',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Get an array of email IDS from which the back-office user may select the from field.
   *
   * @return array
   */
  public function getFromEmails(): array {
    return CRM_Core_BAO_Email::getFromEmail();
  }

  /**
   * Set default values.
   */
  public function setDefaultValues() {
    $defaultFormat = CRM_Core_BAO_PdfFormat::getDefaultValues();
    return ['pdf_format_id' => $defaultFormat['id'], 'receipt_update' => 1, 'override_privacy' => 0];
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // get all the details needed to generate a receipt
    $message = [];
    $template = CRM_Core_Smarty::singleton();

    $params = $this->controller->exportValues($this->_name);
    $isCreatePDF = FALSE;
    if (!empty($params['output']) &&
      ($params['output'] === 'pdf_invoice' || $params['output'] === 'pdf_receipt')
    ) {
      $isCreatePDF = TRUE;
    }
    $elements = self::getElements($this->_contributionIds, $params, $this->_contactIds, $isCreatePDF);
    $elementDetails = $elements['details'];
    $excludedContactIDs = $elements['excludeContactIds'];
    $suppressedEmails = $elements['suppressedEmails'];

    unset($elements);
    foreach ($elementDetails as $contribID => $detail) {
      $input = ['receipt_update' => $this->getSubmittedValue('receipt_update')];

      if (in_array($detail['contact'], $excludedContactIDs)) {
        continue;
      }

      if (isset($params['from_email_address']) && !$isCreatePDF) {
        // If a logged in user from email is used rather than a domain wide from email address
        // the from_email_address params key will be numerical and we need to convert it to be
        // in normal from email format
        $from = CRM_Utils_Mail::formatFromAddress($params['from_email_address']);
        // CRM-19129 Allow useres the choice of From Email to send the receipt from.
        $fromDetails = explode(' <', $from);
        $input['receipt_from_email'] = substr(trim($fromDetails[1]), 0, -1);
        $input['receipt_from_name'] = str_replace('"', '', $fromDetails[0]);
      }

      $mail = CRM_Contribute_BAO_Contribution::sendMail($input, [], $contribID, $isCreatePDF);

      if (!empty($mail['html'])) {
        $message[] = $mail['html'];
      }
      elseif (!empty($mail['body'])) {
        $message[] = nl2br($mail['body']);
      }

      // reset template values before processing next transactions
      $template->clearTemplateVars();
    }

    if ($isCreatePDF) {
      CRM_Utils_PDF_Utils::html2pdf($message,
        'receipt.pdf',
        FALSE,
        $params['pdf_format_id']
      );
      CRM_Utils_System::civiExit();
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
    $lines = LineItem::get(FALSE)
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
        $rows[$line['contribution_id']]['event'] = Participant::get(FALSE)
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

}
