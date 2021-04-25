<?php

/**
 * This class provides the common functionality for creating PDF letter for
 * one or a group of contact ids.
 */
class CRM_Contribute_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * Build the form object.
   *
   * @var CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    // use contact form as a base
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($form);

    // Contribute PDF tasks allow you to email as well, so we need to add email address to those forms
    $form->add('select', 'from_email_address', ts('From Email Address'), $form->_fromEmails, TRUE);
    parent::buildQuickForm($form);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param \CRM_Contribute_Form_Task_PDFLetter $form
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
   */
  public static function postProcess(&$form, $formValues = NULL) {
    if (empty($formValues)) {
      $formValues = $form->controller->exportValues($form->getName());
    }
    [$formValues, $categories, $html_message, $messageToken, $returnProperties] = self::processMessageTemplate($formValues);
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
    $groupBy = $formValues['group_by'];

    // skip some contacts ?
    $skipOnHold = $form->skipOnHold ?? FALSE;
    $skipDeceased = $form->skipDeceased ?? TRUE;
    $contributionIDs = $form->getVar('_contributionIds');
    if ($form->isQueryIncludesSoftCredits()) {
      $contributionIDs = [];
      $result = $form->getSearchQueryResults();
      while ($result->fetch()) {
        $form->_contactIds[$result->contact_id] = $result->contact_id;
        $contributionIDs["{$result->contact_id}-{$result->contribution_id}"] = $result->contribution_id;
      }
    }
    [$contributions, $contacts] = CRM_Contribute_Form_Task_PDFLetter::buildContributionArray($groupBy, $contributionIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator, $form->isQueryIncludesSoftCredits());
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
        $html[$contributionId] = CRM_Contribute_Form_Task_PDFLetter::generateHtml($contact, $contribution, $groupBy, $contributions, $realSeparator, $tableSeparators, $messageToken, $html_message, $separator, $grouped, $groupByID);
        $contactHtml[$contact['contact_id']][] = $html[$contributionId];
        if (!empty($formValues['email_options'])) {
          if (self::emailLetter($contact, $html[$contributionId], $isPDF, $formValues, $emailParams)) {
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
    self::createActivities($form, $html_message, $contactIds, CRM_Utils_Array::value('subject', $formValues, ts('Thank you letter')), CRM_Utils_Array::value('campaign_id', $formValues), $contactHtml);
    $html = array_diff_key($html, $emailedHtml);

    if (!empty($formValues['is_unit_test'])) {
      return $html;
    }

    //CRM-19761
    if (!empty($html)) {
      $type = $formValues['document_type'];

      if ($type === 'pdf') {
        CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);
      }
      else {
        CRM_Utils_PDF_Document::html2doc($html, "CiviLetter.$type", $formValues);
      }
    }

    $form->postProcessHook();

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
          if (!self::isHtmlTokenInTableCell($token, $entity, $html)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
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
   * @throws \CRM_Core_Exception
   */
  public static function resolveTokens(string $html_message, $contact, $contribution, $messageToken, $grouped, $separator, $contributions): string {
    $categories = self::getTokenCategories();
    $domain = CRM_Core_BAO_Domain::getDomain();
    $tokenHtml = CRM_Utils_Token::replaceDomainTokens($html_message, $domain, TRUE, $messageToken);
    $tokenHtml = CRM_Utils_Token::replaceContactTokens($tokenHtml, $contact, TRUE, $messageToken);
    if ($grouped) {
      $tokenHtml = CRM_Utils_Token::replaceMultipleContributionTokens($separator, $tokenHtml, $contributions, $messageToken);
    }
    else {
      // no change to normal behaviour to avoid risk of breakage
      $tokenHtml = CRM_Utils_Token::replaceContributionTokens($tokenHtml, $contribution, TRUE, $messageToken);
    }
    $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact, $categories, TRUE);
    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      $smarty = CRM_Core_Smarty::singleton();
      // also add the tokens to the template
      $smarty->assign_by_ref('contact', $contact);
      $tokenHtml = $smarty->fetch("string:$tokenHtml");
    }
    return $tokenHtml;
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
  public static function emailLetter($contact, $html, $is_pdf, $format = [], $params = []) {
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

}
