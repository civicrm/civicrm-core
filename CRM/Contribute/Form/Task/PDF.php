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

/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contribute_Form_Task_PDF extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */
  public $_single = FALSE;

  protected $_rows;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE
    );

    if ($id) {
      $this->_contributionIds = [$id];
      $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContributions', 1);
    }
    else {
      parent::preProcess();
    }

    // check that all the contribution ids have pending status
    $query = "
SELECT count(*)
FROM   civicrm_contribution
WHERE  contribution_status_id != 1
AND    {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query);
    if ($count != 0) {
      CRM_Core_Error::statusBounce("Please select only online contributions with Completed status.");
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
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->addElement('radio', 'output', NULL, ts('Email Receipts'), 'email_receipt',
      [
        'onClick' => "document.getElementById('selectPdfFormat').style.display = 'none';
        document.getElementById('selectEmailFrom').style.display = 'block';"]
    );
    $this->addElement('radio', 'output', NULL, ts('PDF Receipts'), 'pdf_receipt',
      [
        'onClick' => "document.getElementById('selectPdfFormat').style.display = 'block';
        document.getElementById('selectEmailFrom').style.display = 'none';"]
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
      ]
    );
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
      $input = $ids = $objects = [];

      if (in_array($detail['contact'], $elements['excludeContactIds'])) {
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

      if (!$elements['baseIPN']->validateData($input, $ids, $objects, FALSE)) {
        CRM_Core_Error::fatal();
      }

      $contribution = &$objects['contribution'];

      // set some fake input values so we can reuse IPN code
      $input['amount'] = $contribution->total_amount;
      $input['is_test'] = $contribution->is_test;
      $input['fee_amount'] = $contribution->fee_amount;
      $input['net_amount'] = $contribution->net_amount;
      $input['trxn_id'] = $contribution->trxn_id;
      $input['trxn_date'] = isset($contribution->trxn_date) ? $contribution->trxn_date : NULL;
      $input['receipt_update'] = $params['receipt_update'];
      $input['contribution_status_id'] = $contribution->contribution_status_id;
      $input['paymentProcessor'] = empty($contribution->trxn_id) ? NULL :
        CRM_Core_DAO::singleValueQuery("SELECT payment_processor_id
          FROM civicrm_financial_trxn
          WHERE trxn_id = %1
          LIMIT 1", [
            1 => [$contribution->trxn_id, 'String']]);

      // CRM_Contribute_BAO_Contribution::composeMessageArray expects mysql formatted date
      $objects['contribution']->receive_date = CRM_Utils_Date::isoToMysql($objects['contribution']->receive_date);

      $values = [];
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

      $mail = CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $objects['contribution']->id, $values,
        $elements['createPdf']);

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
  static public function getElements($contribIds, $params, $contactIds) {
    $pdfElements = [];

    $pdfElements['contribIDs'] = implode(',', $contribIds);

    $pdfElements['details'] = CRM_Contribute_Form_Task_Status::getDetails($pdfElements['contribIDs']);

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
          || CRM_Utils_Array::value('is_deceased', $values)
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

}
