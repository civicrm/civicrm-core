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
    CRM_Contact_Form_Task_EmailCommon ::preProcessFromAddress($this, FALSE);
    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Print Contribution Receipts'));
    // Ajax submit would interfere with pdf file download
    $this->preventAjaxSubmit();
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

    $this->add('select', 'from_email_address', ts('From Email'), $this->_fromEmails, FALSE);

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
    $elements = self::getElements($this->_contributionIds, $params, $this->_contactIds);

    foreach ($elements['details'] as $contribID => $detail) {
      $input = $ids = [];

      if (in_array($detail['contact'], $elements['excludeContactIds'])) {
        continue;
      }
      // @todo - CRM_Contribute_BAO_Contribution::sendMail re-does pretty much everything between here & when we call it.
      $input['component'] = $detail['component'];

      $ids['contact'] = $detail['contact'];
      $ids['contribution'] = $contribID;
      $ids['contributionRecur'] = NULL;
      $ids['contributionPage'] = NULL;
      $ids['membership'] = $detail['membership'] ?? NULL;
      $ids['participant'] = $detail['participant'] ?? NULL;
      $ids['event'] = $detail['event'] ?? NULL;

      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = $contribID;
      $contribution->fetch();

      // set some fake input values so we can reuse IPN code
      $input['amount'] = $contribution->total_amount;
      $input['is_test'] = $contribution->is_test;
      $input['fee_amount'] = $contribution->fee_amount;
      $input['net_amount'] = $contribution->net_amount;
      $input['trxn_id'] = $contribution->trxn_id;
      $input['trxn_date'] = $contribution->trxn_date ?? NULL;
      $input['receipt_update'] = $params['receipt_update'];
      $input['contribution_status_id'] = $contribution->contribution_status_id;
      $input['paymentProcessor'] = empty($contribution->trxn_id) ? NULL :
        CRM_Core_DAO::singleValueQuery("SELECT payment_processor_id
          FROM civicrm_financial_trxn
          WHERE trxn_id = %1
          LIMIT 1", [
            1 => [$contribution->trxn_id, 'String'],
          ]);

      if (isset($params['from_email_address']) && !$elements['createPdf']) {
        // If a logged in user from email is used rather than a domain wide from email address
        // the from_email_address params key will be numerical and we need to convert it to be
        // in normal from email format
        $from = CRM_Utils_Mail::formatFromAddress($params['from_email_address']);
        // CRM-19129 Allow useres the choice of From Email to send the receipt from.
        $fromDetails = explode(' <', $from);
        $input['receipt_from_email'] = substr(trim($fromDetails[1]), 0, -1);
        $input['receipt_from_name'] = str_replace('"', '', $fromDetails[0]);
      }

      $mail = CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contribID, $elements['createPdf']);

      if ($mail['html']) {
        $message[] = $mail['html'];
      }
      else {
        $message[] = nl2br($mail['body']);
      }

      // reset template values before processing next transactions
      $template->clearTemplateVars();
    }

    if ($elements['createPdf']) {
      CRM_Utils_PDF_Utils::html2pdf($message,
        'civicrmContributionReceipt.pdf',
        FALSE,
        $elements['params']['pdf_format_id']
      );
      CRM_Utils_System::civiExit();
    }
    else {
      if ($elements['suppressedEmails']) {
        $status = ts('Email was NOT sent to %1 contacts (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).', [1 => $elements['suppressedEmails']]);
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
   *
   * @param array $contribIds
   *   Contribution Id.
   * @param array $params
   *   Parameter for pdf or email invoices.
   * @param array $contactIds
   *   Contact Id.
   *
   * @return array
   *   array of common elements
   *
   */
  public static function getElements($contribIds, $params, $contactIds) {
    $pdfElements = [];

    $pdfElements['contribIDs'] = implode(',', $contribIds);

    $pdfElements['details'] = self::getDetails($pdfElements['contribIDs']);

    $pdfElements['baseIPN'] = new CRM_Core_Payment_BaseIPN();

    $pdfElements['params'] = $params;

    $pdfElements['createPdf'] = FALSE;
    if (!empty($pdfElements['params']['output']) &&
      ($pdfElements['params']['output'] == "pdf_invoice" || $pdfElements['params']['output'] == "pdf_receipt")
    ) {
      $pdfElements['createPdf'] = TRUE;
    }

    $excludeContactIds = [];
    if (!$pdfElements['createPdf']) {
      $returnProperties = [
        'email' => 1,
        'do_not_email' => 1,
        'is_deceased' => 1,
        'on_hold' => 1,
      ];

      list($contactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, $returnProperties, FALSE, FALSE);
      $pdfElements['suppressedEmails'] = 0;
      $suppressedEmails = 0;
      foreach ($contactDetails as $id => $values) {
        if (empty($values['email']) ||
          (empty($params['override_privacy']) && !empty($values['do_not_email']))
          || !empty($values['is_deceased'])
          || !empty($values['on_hold'])
        ) {
          $suppressedEmails++;
          $pdfElements['suppressedEmails'] = $suppressedEmails;
          $excludeContactIds[] = $values['contact_id'];
        }
      }
    }
    $pdfElements['excludeContactIds'] = $excludeContactIds;

    return $pdfElements;
  }

  /**
   * @param string $contributionIDs
   *
   * @return array
   */
  private static function getDetails($contributionIDs) {
    if (empty($contributionIDs)) {
      return [];
    }
    $query = "
SELECT    c.id              as contribution_id,
          c.contact_id      as contact_id     ,
          mp.membership_id  as membership_id  ,
          pp.participant_id as participant_id ,
          p.event_id        as event_id
FROM      civicrm_contribution c
LEFT JOIN civicrm_membership_payment  mp ON mp.contribution_id = c.id
LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = c.id
LEFT JOIN civicrm_participant         p  ON pp.participant_id  = p.id
WHERE     c.id IN ( $contributionIDs )";

    $rows = [];
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $rows[$dao->contribution_id]['component'] = $dao->participant_id ? 'event' : 'contribute';
      $rows[$dao->contribution_id]['contact'] = $dao->contact_id;
      if ($dao->membership_id) {
        if (!array_key_exists('membership', $rows[$dao->contribution_id])) {
          $rows[$dao->contribution_id]['membership'] = [];
        }
        $rows[$dao->contribution_id]['membership'][] = $dao->membership_id;
      }
      if ($dao->participant_id) {
        $rows[$dao->contribution_id]['participant'] = $dao->participant_id;
      }
      if ($dao->event_id) {
        $rows[$dao->contribution_id]['event'] = $dao->event_id;
      }
    }
    return $rows;
  }

}
