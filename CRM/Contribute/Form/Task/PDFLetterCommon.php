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
   * @param CRM_Contribute_Form_Task $form
   * @param array $formValues
   */
  public static function postProcess(&$form, $formValues = NULL) {
    if (empty($formValues)) {
      $formValues = $form->controller->exportValues($form->getName());
    }
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($formValues);
    $isPDF = FALSE;
    $emailParams = [];
    if (!empty($formValues['email_options'])) {
      $returnProperties['email'] = $returnProperties['on_hold'] = $returnProperties['is_deceased'] = $returnProperties['do_not_email'] = 1;
      $emailParams = [
        'subject' => CRM_Utils_Array::value('subject', $formValues),
        'from' => CRM_Utils_Array::value('from_email_address', $formValues),
      ];

      $emailParams['from'] = CRM_Utils_Mail::formatFromAddress($emailParams['from']);

      // We need display_name for emailLetter() so add to returnProperties here
      $returnProperties['display_name'] = 1;
      if (stristr($formValues['email_options'], 'pdfemail')) {
        $isPDF = TRUE;
      }
    }
    // update dates ?
    $receipt_update = isset($formValues['receipt_update']) ? $formValues['receipt_update'] : FALSE;
    $thankyou_update = isset($formValues['thankyou_update']) ? $formValues['thankyou_update'] : FALSE;
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
    $separator = '****~~~~';// a placeholder in case the separator is common in the string - e.g ', '
    $groupBy = $formValues['group_by'];

    // skip some contacts ?
    $skipOnHold = isset($form->skipOnHold) ? $form->skipOnHold : FALSE;
    $skipDeceased = isset($form->skipDeceased) ? $form->skipDeceased : TRUE;
    $contributionIDs = $form->getVar('_contributionIds');
    if ($form->_includesSoftCredits) {
      //@todo - comment on what is stored there
      $contributionIDs = $form->getVar('_contributionContactIds');
    }
    list($contributions, $contacts) = self::buildContributionArray($groupBy, $contributionIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator, $form->_includesSoftCredits);
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
        $html[$contributionId] = self::generateHtml($contact, $contribution, $groupBy, $contributions, $realSeparator, $tableSeparators, $messageToken, $html_message, $separator, $grouped, $groupByID);
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

      if ($type == 'pdf') {
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
   * @return string
   */
  private static function resolveTokens($html_message, $contact, $contribution, $messageToken, $grouped, $separator) {
    $categories = self::getTokenCategories();
    $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact, TRUE, $messageToken);
    if ($grouped) {
      $tokenHtml = CRM_Utils_Token::replaceMultipleContributionTokens($separator, $tokenHtml, $contribution, TRUE, $messageToken);
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
  public static function buildContributionArray($groupBy, $contributionIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator, $isIncludeSoftCredits) {
    $contributions = $contacts = [];
    foreach ($contributionIDs as $item => $contributionId) {
      // Basic return attributes available to the template.
      $returnValues = ['contact_id', 'total_amount', 'financial_type', 'receive_date', 'contribution_campaign_title'];
      if (!empty($messageToken['contribution'])) {
        $returnValues = array_merge($messageToken['contribution'], $returnValues);
      }
      // retrieve contribution tokens listed in $returnProperties using Contribution.Get API
      $contribution = civicrm_api3('Contribution', 'getsingle', [
        'id' => $contributionId,
        'return' => $returnValues,
      ]);
      $contribution['campaign'] = CRM_Utils_Array::value('contribution_campaign_title', $contribution);
      $contributions[$contributionId] = $contribution;

      if ($isIncludeSoftCredits) {
        //@todo find out why this happens & add comments
        list($contactID) = explode('-', $item);
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
      $tokenResolvedContacts = CRM_Utils_Token::getTokenDetails(['contact_id' => $contactID],
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        $task
      );
      $contacts[$contactID] = array_merge($tokenResolvedContacts[0][$contactID], $contact);
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
      ->assign('contributions', array_intersect_key($contributions, $contact['contribution_ids'][$groupBy][$groupByID]));
    CRM_Core_Smarty::singleton()->assign('contribution_aggregate', $contact['aggregates'][$groupBy][$groupByID]);

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
   */
  protected static function generateHtml(&$contact, $contribution, $groupBy, $contributions, $realSeparator, $tableSeparators, $messageToken, $html_message, $separator, $grouped, $groupByID) {
    static $validated = FALSE;
    $html = NULL;

    self::assignCombinedContributionValues($contact, $contributions, $groupBy, $groupByID);

    if (empty($groupBy) || empty($contact['is_sent'][$groupBy][$groupByID])) {
      if (!$validated && in_array($realSeparator, $tableSeparators) && !self::isValidHTMLWithTableSeparator($messageToken, $html_message)) {
        $realSeparator = ', ';
        CRM_Core_Session::setStatus(ts('You have selected the table cell separator, but one or more token fields are not placed inside a table cell. This would result in invalid HTML, so comma separators have been used instead.'));
      }
      $validated = TRUE;
      $html = str_replace($separator, $realSeparator, self::resolveTokens($html_message, $contact, $contribution, $messageToken, $grouped, $separator));
    }

    return $html;
  }

}
