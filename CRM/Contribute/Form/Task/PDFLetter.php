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
    parent::preProcess();
    $this->assign('single', $this->isSingle());
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
    $defaults = [];
    if (isset($this->_activityId)) {
      $params = ['id' => $this->_activityId];
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = $defaults['details'] ?? NULL;
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

    // Build common form elements
    // use contact form as a base
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);

    // Contribute PDF tasks allow you to email as well, so we need to add email address to those forms
    $this->add('select', 'from_email_address', ts('From Email Address'), $this->_fromEmails, TRUE);
    CRM_Core_Form_Task_PDFLetterCommon::buildQuickForm($this);

    // specific need for contributions
    $this->add('static', 'more_options_header', NULL, ts('Thank-you Letter Options'));
    $this->add('checkbox', 'receipt_update', ts('Update receipt dates for these contributions'), FALSE);
    $this->add('checkbox', 'thankyou_update', ts('Update thank-you dates for these contributions'), FALSE);

    // Group options for tokens are not yet implemented. dgg
    $options = [
      '' => ts('- no grouping -'),
      'contact_id' => ts('Contact'),
      'contribution_recur_id' => ts('Contact and Recurring'),
      'financial_type_id' => ts('Contact and Financial Type'),
      'campaign_id' => ts('Contact and Campaign'),
      'payment_instrument_id' => ts('Contact and Payment Method'),
    ];
    $this->addElement('select', 'group_by', ts('Group contributions by'), $options, [], "<br/>", FALSE);
    // this was going to be free-text but I opted for radio options in case there was a script injection risk
    $separatorOptions = ['comma' => 'Comma', 'td' => 'Horizontal Table Cell', 'tr' => 'Vertical Table Cell', 'br' => 'Line Break'];

    $this->addElement('select', 'group_by_separator', ts('Separator (grouped contributions)'), $separatorOptions);
    $emailOptions = [
      '' => ts('Generate PDFs for printing (only)'),
      'email' => ts('Send emails where possible. Generate printable PDFs for contacts who cannot receive email.'),
      'both' => ts('Send emails where possible. Generate printable PDFs for all contacts.'),
    ];
    if (CRM_Core_Config::singleton()->doNotAttachPDFReceipt) {
      $emailOptions['pdfemail'] = ts('Send emails with an attached PDF where possible. Generate printable PDFs for contacts who cannot receive email.');
      $emailOptions['pdfemail_both'] = ts('Send emails with an attached PDF where possible. Generate printable PDFs for all contacts.');
    }
    $this->addElement('select', 'email_options', ts('Print and email options'), $emailOptions, [], "<br/>", FALSE);

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Make Thank-you Letters'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Done'),
      ],
    ]);

  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $formValues = $this->controller->exportValues($this->getName());
    [$formValues, $categories, $html_message, $messageToken, $returnProperties] = CRM_Contact_Form_Task_PDFLetterCommon::processMessageTemplate($formValues);
    $isPDF = FALSE;
    $emailParams = [];
    if (!empty($formValues['email_options'])) {
      $returnProperties['email'] = $returnProperties['on_hold'] = $returnProperties['is_deceased'] = $returnProperties['do_not_email'] = 1;
      $emailParams = [
        'subject' => $formValues['subject'] ?? NULL,
        'from' => $formValues['from_email_address'] ?? NULL,
      ];

      $emailParams['from'] = CRM_Utils_Mail::formatFromAddress($emailParams['from']);

      // We need display_name for emailLetter() so add to returnProperties here
      $returnProperties['display_name'] = 1;
      if (stristr($formValues['email_options'], 'pdfemail')) {
        $isPDF = TRUE;
      }
    }
    // update dates ?
    $receipt_update = $formValues['receipt_update'] ?? FALSE;
    $thankyou_update = $formValues['thankyou_update'] ?? FALSE;
    $nowDate = date('YmdHis');
    $receipts = $thanks = $emailed = 0;
    $updateStatus = '';
    $task = 'CRM_Contribution_Form_Task_PDFLetterCommon';
    $realSeparator = ', ';
    $tableSeparators = [
      'td' => '</td><td>',
      'tr' => '</td></tr><tr><td>',
    ];
    //the original thinking was mutliple options - but we are going with only 2 (comma & td) for now in case
    // there are security (& UI) issues we need to think through
    if (isset($formValues['group_by_separator'])) {
      if (in_array($formValues['group_by_separator'], ['td', 'tr'])) {
        $realSeparator = $tableSeparators[$formValues['group_by_separator']];
      }
      elseif ($formValues['group_by_separator'] == 'br') {
        $realSeparator = "<br />";
      }
    }
    // a placeholder in case the separator is common in the string - e.g ', '
    $separator = '****~~~~';
    $groupBy = $this->getSubmittedValue('group_by');

    // skip some contacts ?
    $skipOnHold = $this->skipOnHold ?? FALSE;
    $skipDeceased = $this->skipDeceased ?? TRUE;
    $contributionIDs = $this->getIDs();
    if ($this->isQueryIncludesSoftCredits()) {
      $contributionIDs = [];
      $result = $this->getSearchQueryResults();
      while ($result->fetch()) {
        $this->_contactIds[$result->contact_id] = $result->contact_id;
        $contributionIDs["{$result->contact_id}-{$result->contribution_id}"] = $result->contribution_id;
      }
    }
    [$contributions, $contacts] = $this->buildContributionArray($groupBy, $contributionIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator, $this->isQueryIncludesSoftCredits());
    $html = [];
    $contactHtml = $emailedHtml = [];
    foreach ($contributions as $contributionId => $contribution) {
      $contact = &$contacts[$contribution['contact_id']];
      $grouped = FALSE;
      $groupByID = 0;
      if ($groupBy) {
        $groupByID = empty($contribution[$groupBy]) ? 0 : $contribution[$groupBy];
        $contribution = $contact['combined'][$groupBy][$groupByID];
        $grouped = TRUE;
      }

      if (empty($groupBy) || empty($contact['is_sent'][$groupBy][$groupByID])) {
        $html[$contributionId] = $this->generateHtml($contact, $contribution, $groupBy, $contributions, $realSeparator, $tableSeparators, $messageToken, $html_message, $separator, $grouped, $groupByID);
        $contactHtml[$contact['contact_id']][] = $html[$contributionId];
        if (!empty($formValues['email_options'])) {
          if ($this->emailLetter($contact, $html[$contributionId], $isPDF, $formValues, $emailParams)) {
            $emailed++;
            if (!stristr($formValues['email_options'], 'both')) {
              $emailedHtml[$contributionId] = TRUE;
            }
          }
        }
        $contact['is_sent'][$groupBy][$groupByID] = TRUE;
      }
      // Update receipt/thankyou dates
      $contributionParams = ['id' => $contributionId];
      if ($receipt_update) {
        $contributionParams['receipt_date'] = $nowDate;
      }
      if ($thankyou_update) {
        $contributionParams['thankyou_date'] = $nowDate;
      }
      if ($receipt_update || $thankyou_update) {
        civicrm_api3('Contribution', 'create', $contributionParams);
        $receipts = ($receipt_update ? $receipts + 1 : $receipts);
        $thanks = ($thankyou_update ? $thanks + 1 : $thanks);
      }
    }

    $contactIds = array_keys($contacts);
    CRM_Contact_Form_Task_PDFLetterCommon::createActivities($this, $html_message, $contactIds, CRM_Utils_Array::value('subject', $formValues, ts('Thank you letter')), CRM_Utils_Array::value('campaign_id', $formValues), $contactHtml);
    $html = array_diff_key($html, $emailedHtml);

    if (!empty($formValues['is_unit_test'])) {
      return $html;
    }

    //CRM-19761
    if (!empty($html)) {
      $type = $this->getSubmittedValue('document_type');

      if ($type === 'pdf') {
        CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);
      }
      else {
        CRM_Utils_PDF_Document::html2doc($html, "CiviLetter.$type", $formValues);
      }
    }

    $this->postProcessHook();

    if ($emailed) {
      $updateStatus = ts('Receipts have been emailed to %1 contributions.', [1 => $emailed]);
    }
    if ($receipts) {
      $updateStatus = ts('Receipt date has been updated for %1 contributions.', [1 => $receipts]);
    }
    if ($thanks) {
      $updateStatus .= ' ' . ts('Thank-you date has been updated for %1 contributions.', [1 => $thanks]);
    }

    if ($updateStatus) {
      CRM_Core_Session::setStatus($updateStatus);
    }
    if (!empty($html)) {
      // ie. we have only sent emails - lets no show a white screen
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    $tokens = array_merge(CRM_Core_SelectValues::domainTokens(), $tokens);
    return $tokens;
  }

  /**
   * Generate the contribution array from the form, we fill in the contact details and determine any aggregation
   * around contact_id of contribution_recur_id
   *
   * @param string $groupBy
   * @param array $contributionIDs
   * @param array $returnProperties
   * @param bool $skipOnHold
   * @param bool $skipDeceased
   * @param array $messageToken
   * @param string $task
   * @param string $separator
   * @param bool $isIncludeSoftCredits
   *
   * @return array
   */
  public function buildContributionArray($groupBy, $contributionIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator, $isIncludeSoftCredits) {
    $contributions = $contacts = [];
    foreach ($contributionIDs as $item => $contributionId) {
      $contribution = CRM_Contribute_BAO_Contribution::getContributionTokenValues($contributionId, $messageToken)['values'][$contributionId];
      $contribution['campaign'] = $contribution['contribution_campaign_title'] ?? NULL;
      $contributions[$contributionId] = $contribution;

      if ($isIncludeSoftCredits) {
        //@todo find out why this happens & add comments
        [$contactID] = explode('-', $item);
        $contactID = (int) $contactID;
      }
      else {
        $contactID = $contribution['contact_id'];
      }
      if (!isset($contacts[$contactID])) {
        $contacts[$contactID] = [];
        $contacts[$contactID]['contact_aggregate'] = 0;
        $contacts[$contactID]['combined'] = $contacts[$contactID]['contribution_ids'] = [];
      }

      $contacts[$contactID]['contact_aggregate'] += $contribution['total_amount'];
      $groupByID = empty($contribution[$groupBy]) ? 0 : $contribution[$groupBy];

      $contacts[$contactID]['contribution_ids'][$groupBy][$groupByID][$contributionId] = TRUE;
      if (!isset($contacts[$contactID]['combined'][$groupBy]) || !isset($contacts[$contactID]['combined'][$groupBy][$groupByID])) {
        $contacts[$contactID]['combined'][$groupBy][$groupByID] = $contribution;
        $contacts[$contactID]['aggregates'][$groupBy][$groupByID] = $contribution['total_amount'];
      }
      else {
        $contacts[$contactID]['combined'][$groupBy][$groupByID] = self::combineContributions($contacts[$contactID]['combined'][$groupBy][$groupByID], $contribution, $separator);
        $contacts[$contactID]['aggregates'][$groupBy][$groupByID] += $contribution['total_amount'];
      }
    }
    // Assign the available contributions before calling tokens so hooks parsing smarty can access it.
    // Note that in core code you can only use smarty here if enable if for the whole site, incl
    // CiviMail, with a big performance impact.
    // Hooks allow more nuanced smarty usage here.
    CRM_Core_Smarty::singleton()->assign('contributions', $contributions);
    foreach ($contacts as $contactID => $contact) {
      [$tokenResolvedContacts] = CRM_Utils_Token::getTokenDetails(['contact_id' => $contactID],
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        $task
      );
      $contacts[$contactID] = array_merge($tokenResolvedContacts[$contactID], $contact);
    }
    return [$contributions, $contacts];
  }

  /**
   * We combine the contributions by adding the contribution to each field with the separator in
   * between the existing value and the new one. We put the separator there even if empty so it is clear what the
   * value for previous contributions was
   *
   * @param array $existing
   * @param array $contribution
   * @param string $separator
   *
   * @return array
   */
  public static function combineContributions($existing, $contribution, $separator) {
    foreach ($contribution as $field => $value) {
      $existing[$field] = isset($existing[$field]) ? $existing[$field] . $separator : '';
      $existing[$field] .= $value;
    }
    return $existing;
  }

  /**
   * We are going to retrieve the combined contribution and if smarty mail is enabled we
   * will also assign an array of contributions for this contact to the smarty template
   *
   * @param array $contact
   * @param array $contributions
   * @param $groupBy
   * @param int $groupByID
   */
  public static function assignCombinedContributionValues($contact, $contributions, $groupBy, $groupByID) {
    CRM_Core_Smarty::singleton()->assign('contact_aggregate', $contact['contact_aggregate']);
    CRM_Core_Smarty::singleton()
      ->assign('contributions', $contributions);
    CRM_Core_Smarty::singleton()->assign('contribution_aggregate', $contact['aggregates'][$groupBy][$groupByID]);
  }

  /**
   * @param $contact
   * @param $formValues
   * @param $contribution
   * @param $groupBy
   * @param $contributions
   * @param $realSeparator
   * @param $tableSeparators
   * @param $messageToken
   * @param $html_message
   * @param $separator
   * @param $categories
   * @param bool $grouped
   * @param int $groupByID
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function generateHtml(&$contact, $contribution, $groupBy, $contributions, $realSeparator, $tableSeparators, $messageToken, $html_message, $separator, $grouped, $groupByID) {
    static $validated = FALSE;
    $html = NULL;

    $groupedContributions = array_intersect_key($contributions, $contact['contribution_ids'][$groupBy][$groupByID]);
    CRM_Contribute_Form_Task_PDFLetter::assignCombinedContributionValues($contact, $groupedContributions, $groupBy, $groupByID);

    if (empty($groupBy) || empty($contact['is_sent'][$groupBy][$groupByID])) {
      if (!$validated && in_array($realSeparator, $tableSeparators) && !CRM_Contribute_Form_Task_PDFLetter::isValidHTMLWithTableSeparator($messageToken, $html_message)) {
        $realSeparator = ', ';
        CRM_Core_Session::setStatus(ts('You have selected the table cell separator, but one or more token fields are not placed inside a table cell. This would result in invalid HTML, so comma separators have been used instead.'));
      }
      $validated = TRUE;
      $html = str_replace($separator, $realSeparator, $this->resolveTokens($html_message, $contact, $contribution, $messageToken, $grouped, $separator, $groupedContributions));
    }

    return $html;
  }

  /**
   * Send pdf by email.
   *
   * @param array $contact
   * @param string $html
   *
   * @param $is_pdf
   * @param array $format
   * @param array $params
   *
   * @return bool
   */
  public function emailLetter($contact, $html, $is_pdf, $format = [], $params = []) {
    try {
      if (empty($contact['email'])) {
        return FALSE;
      }
      $mustBeEmpty = ['do_not_email', 'is_deceased', 'on_hold'];
      foreach ($mustBeEmpty as $emptyField) {
        if (!empty($contact[$emptyField])) {
          return FALSE;
        }
      }

      $defaults = [
        'toName' => $contact['display_name'],
        'toEmail' => $contact['email'],
        'text' => '',
        'html' => $html,
      ];
      if (empty($params['from'])) {
        $emails = CRM_Core_BAO_Email::getFromEmail();
        $emails = array_keys($emails);
        $defaults['from'] = array_pop($emails);
      }
      else {
        $defaults['from'] = $params['from'];
      }
      if (!empty($params['subject'])) {
        $defaults['subject'] = $params['subject'];
      }
      else {
        $defaults['subject'] = ts('Thank you for your contribution/s');
      }
      if ($is_pdf) {
        $defaults['html'] = ts('Please see attached');
        $defaults['attachments'] = [CRM_Utils_Mail::appendPDF('ThankYou.pdf', $html, $format)];
      }
      $params = array_merge($defaults);
      return CRM_Utils_Mail::send($params);
    }
    catch (CRM_Core_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Check that the token only appears in a table cell. The '</td><td>' separator cannot otherwise work
   * Calculate the number of times it appears IN the cell & the number of times it appears - should be the same!
   *
   * @param string $token
   * @param string $entity
   * @param string $textToSearch
   *
   * @return bool
   */
  public static function isHtmlTokenInTableCell($token, $entity, $textToSearch) {
    $tokenToMatch = $entity . '\.' . $token;
    $pattern = '|<td(?![\w-])((?!</td>).)*\{' . $tokenToMatch . '\}.*?</td>|si';
    $within = preg_match_all($pattern, $textToSearch);
    $total = preg_match_all("|{" . $tokenToMatch . "}|", $textToSearch);
    return ($within == $total);
  }

  /**
   * Check whether any of the tokens exist in the html outside a table cell.
   * If they do the table cell separator is not supported (return false)
   * At this stage we are only anticipating contributions passed in this way but
   * it would be easy to add others
   * @param $tokens
   * @param $html
   *
   * @return bool
   */
  public static function isValidHTMLWithTableSeparator($tokens, $html) {
    $relevantEntities = ['contribution'];
    foreach ($relevantEntities as $entity) {
      if (isset($tokens[$entity]) && is_array($tokens[$entity])) {
        foreach ($tokens[$entity] as $token) {
          if (!CRM_Contribute_Form_Task_PDFLetter::isHtmlTokenInTableCell($token, $entity, $html)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   *
   * @param string $html_message
   * @param array $contact
   * @param array $contribution
   * @param array $messageToken
   * @param bool $grouped
   *   Does this letter represent more than one contribution.
   * @param string $separator
   *   What is the preferred letter separator.
   * @param array $contributions
   *
   * @return string
   */
  protected function resolveTokens(string $html_message, $contact, $contribution, $messageToken, $grouped, $separator, $contributions): string {
    if ($grouped) {
      $tokenHtml = CRM_Utils_Token::replaceMultipleContributionTokens($separator, $html_message, $contributions, $messageToken);
    }
    else {
      // no change to normal behaviour to avoid risk of breakage
      $tokenHtml = CRM_Utils_Token::replaceContributionTokens($html_message, $contribution, TRUE, $messageToken);
    }
    $useSmarty = (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY);
    return CRM_Core_BAO_MessageTemplate::renderMessageTemplate(['text' => '', 'html' => $tokenHtml, 'subject' => ''], !$useSmarty, $contact['contact_id'], ['contact' => $contact])['html'];
  }

}
