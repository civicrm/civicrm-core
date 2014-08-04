<?php

/**
 * This class provides the common functionality for creating PDF letter for
 * one or a group of contact ids.
 */
class CRM_Contribute_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @param CRM_Contribute_Form_Task $form
   *
   * @return void
   */
  static function postProcess(&$form) {
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($form);
    $isPDF = FALSE;
    $emailParams = array();
    if(!empty($formValues['email_options'])) {
      $returnProperties['email'] = $returnProperties['on_hold'] = $returnProperties['is_deceased'] = $returnProperties['do_not_email'] = 1;
      $emailParams = array(
        'subject'   => $formValues['subject']
      );
      // We need display_name for emailLetter() so add to returnProperties here
      $returnProperties['display_name'] = 1;
      if(stristr($formValues['email_options'], 'pdfemail')) {
        $isPDF = TRUE;
      }
    }
    // update dates ?
    $receipt_update  = isset($formValues['receipt_update']) ? $formValues['receipt_update'] : FALSE;
    $thankyou_update = isset($formValues['thankyou_update']) ? $formValues['thankyou_update'] : FALSE;
    $nowDate         = date('YmdHis');
    $receipts = $thanks = $emailed = 0;
    $updateStatus    = '';
    $task = 'CRM_Contribution_Form_Task_PDFLetterCommon';
    $realSeparator = ', ';
    //the original thinking was mutliple options - but we are going with only 2 (comma & td) for now in case
    // there are security (& UI) issues we need to think through
    if(isset($formValues['group_by_separator'])) {
      if($formValues['group_by_separator'] == 'td') {
        $realSeparator = "</td><td>";
      }
    }
    $separator = '****~~~~';// a placeholder in case the separator is common in the string - e.g ', '
    $validated = FALSE;

    $groupBy = $formValues['group_by'];

    // skip some contacts ?
    $skipOnHold = isset($form->skipOnHold) ? $form->skipOnHold : FALSE;
    $skipDeceased = isset($form->skipDeceased) ? $form->skipDeceased : TRUE;

    list($contributions, $contacts) = self::buildContributionArray($groupBy, $form, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator);
    $html = array();
    foreach ($contributions as $contributionId => $contribution) {
      $contact = &$contacts[$contribution['contact_id']];
      $grouped = $groupByID = 0;
      if($groupBy) {
        $groupByID = empty($contribution[$groupBy]) ? 0 : $contribution[$groupBy];
        $contribution = $contact['combined'][$groupBy][$groupByID];
        $grouped = TRUE;
      }

      self::assignCombinedContributionValues($contact, $contributions, $groupBy, $groupByID);

      if(empty($groupBy) || empty($contact['is_sent'][$groupBy][$groupByID])) {
        if(!$validated && $realSeparator == '</td><td>' && !self::isValidHTMLWithTableSeparator($messageToken, $html_message)) {
          $realSeparator = ', ';
          CRM_Core_Session::setStatus(ts('You have selected the table cell separator, but one or more token fields are not placed inside a table cell. This would result in invalid HTML, so comma separators have been used instead.'));
        }
        $validated = TRUE;
        $html[$contributionId] = str_replace($separator, $realSeparator, self::resolveTokens($html_message, $contact, $contribution, $messageToken, $categories, $grouped, $separator));
        $contact['is_sent'][$groupBy][$groupByID] = TRUE;
        if(!empty($formValues['email_options'])) {
          if(self::emailLetter($contact, $html[$contributionId], $isPDF, $formValues, $emailParams)) {
            $emailed ++;
            if(!stristr($formValues['email_options'], 'both')) {
              unset($html[$contributionId]);
            }
          }
        }
      }

      // update dates (do it for each contribution including grouped recurring contribution)
      //@todo - the 2 calls below bypass all hooks. Using the api would possibly be slower than one call but not than 2
      if ($receipt_update) {
        $result = CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'receipt_date', $nowDate);
        if ($result) {
          $receipts++;
        }
      }
      if ($thankyou_update) {
        $result = CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'thankyou_date', $nowDate);
        if ($result) {
          $thanks++;
        }
      }
    }
    //createActivities requires both $form->_contactIds and $contacts -
    //@todo - figure out why
    $form->_contactIds = array_keys($contacts);
    self::createActivities($form, $html_message, $form->_contactIds);
    if(!empty($html)) {
      CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);
    }

    $form->postProcessHook();

    if ($emailed) {
      $updateStatus = ts('Receipts have been emailed to %1 contributions.', array(1 => $emailed));
    }
    if ($receipts) {
      $updateStatus = ts('Receipt date has been updated for %1 contributions.', array(1 => $receipts));
    }
    if ($thanks) {
      $updateStatus .= ' ' . ts('Thank-you date has been updated for %1 contributions.', array(1 => $thanks));
    }

    if ($updateStatus) {
      CRM_Core_Session::setStatus($updateStatus);
    }
    if(!empty($html)) {
      // ie. we have only sent emails - lets no show a white screen
      CRM_Utils_System::civiExit(1);
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
  static function isValidHTMLWithTableSeparator($tokens, $html) {
    $relevantEntities = array('contribution');
    foreach ($relevantEntities as $entity) {
      if (isset($tokens[$entity]) && is_array($tokens[$entity])) {
        foreach ($tokens[$entity] as $token) {
          if(!self::isHtmlTokenInTableCell($token, $entity, $html)) {;
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * check that the token only appears in a table cell. The '</td><td>' separator cannot otherwise work
   * Calculate the number of times it appears IN the cell & the number of times it appears - should be the same!
   *
   * @param $token
   * @param $entity
   * @param $textToSearch
   *
   * @internal param $html
   *
   * @return bool
   */
  static function isHtmlTokenInTableCell($token, $entity, $textToSearch) {
    $tokenToMatch = $entity . '.' . $token;
    $dontCare = array();
    $within = preg_match_all("|<td.+?{".$tokenToMatch."}.+?</td|si", $textToSearch, $dontCare);
    $total = preg_match_all("|{".$tokenToMatch."}|", $textToSearch, $dontCare);
    return ($within == $total);
  }

 /**
  *
  * @param string $html_message
  * @param array $contact
  * @param array $contribution
  * @param array $messageToken
  * @param array $categories
  * @param bool $grouped Does this letter represent more than one contribution
  * @param string $separator What is the preferred letter separator
  * @return string
  */
 private static function resolveTokens($html_message, $contact, $contribution, $messageToken, $categories, $grouped, $separator) {
   $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact, TRUE, $messageToken);
   if($grouped) {
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
   * @param CRM_Contribute_Form_Task $form
   * @param array $returnProperties
   * @param boolean $skipOnHold
   * @param boolean $skipDeceased
   * @param array $messageToken
   * @param string $task
   * @param string $separator
   *
   * @return array:
   */
  static function buildContributionArray($groupBy, $form, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $task, $separator) {
    $contributions = $contacts = $notSent = array();
    $contributionIDs = $form->getVar('_contributionIds');
    if ($form->_includesSoftCredits) {
      //@todo - comment on what is stored there
      $contributionIDs = $form->getVar('_contributionContactIds');
    }
    foreach ($contributionIDs as $item => $contributionId) {
      // get contribution information
      $contribution = CRM_Utils_Token::getContributionTokenDetails(array('contribution_id' => $contributionId),
        $returnProperties,
        NULL,
        $messageToken,
        $task
      );
      $contribution = $contributions[$contributionId] = $contribution[$contributionId];
      if ($form->_includesSoftCredits) {
        //@todo find out why this happens & add comments
        list($contactID) = explode('-', $item);
        $contactID = (int) $contactID;
       }
      else {
         $contactID = $contribution['contact_id'];
      }
      if(!isset($contacts[$contactID])) {
        list($contact) = CRM_Utils_Token::getTokenDetails(array('contact_id' => $contactID),
          $returnProperties,
          $skipOnHold,
          $skipDeceased,
          NULL,
          $messageToken,
          $task
        );
        $contacts[$contactID] = $contact[$contactID];
        $contacts[$contactID]['contact_aggregate'] = 0;
        $contacts[$contactID]['combined'] = $contacts[$contactID]['contribution_ids'] = array();
      }

      $contacts[$contactID]['contact_aggregate'] += $contribution['total_amount'];
      $groupByID = empty($contribution[$groupBy]) ? 0 : $contribution[$groupBy];

      $contacts[$contactID]['contribution_ids'][$groupBy][$groupByID][$contributionId] = TRUE;
      if(!isset($contacts[$contactID]['combined'][$groupBy]) || !isset($contacts[$contactID]['combined'][$groupBy][$groupByID])) {
        $contacts[$contactID]['combined'][$groupBy][$groupByID] = $contribution;
        $contacts[$contactID]['aggregates'][$groupBy][$groupByID] = $contribution['total_amount'];
      }
      else {
        $contacts[$contactID]['combined'][$groupBy][$groupByID] = self::combineContributions($contacts[$contactID]['combined'][$groupBy][$groupByID], $contribution, $separator);
        $contacts[$contactID]['aggregates'][$groupBy][$groupByID] += $contribution['total_amount'];
      }
    }
    return array($contributions, $contacts);
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
  static function combineContributions($existing, $contribution, $separator) {
    foreach ($contribution as $field => $value) {
      $existing[$field] = isset($existing[$field]) ? $existing[$field] . $separator : '';
      $existing[$field]  .= $value;
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
   * @param $groupByID
   */
  static function assignCombinedContributionValues($contact, $contributions, $groupBy, $groupByID) {
    if (!defined('CIVICRM_MAIL_SMARTY') || !CIVICRM_MAIL_SMARTY) {
      return;
    }
    CRM_Core_Smarty::singleton()->assign('contact_aggregate', $contact['contact_aggregate']);
    CRM_Core_Smarty::singleton()->assign('contributions', array_intersect_key($contributions, $contact['contribution_ids'][$groupBy][$groupByID]));
    CRM_Core_Smarty::singleton()->assign('contribution_aggregate', $contact['aggregates'][$groupBy][$groupByID]);

  }

  /**
   * Send pdf by email
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
  static function emailLetter($contact, $html, $is_pdf, $format = array(), $params = array()) {
    try {
      if(empty($contact['email'])) {
        return FALSE;
      }
      $mustBeEmpty = array('do_not_email', 'is_deceased', 'on_hold');
      foreach ($mustBeEmpty as $emptyField) {
        if(!empty($contact[$emptyField])) {
          return FALSE;
        }
      }

      $defaults = array(
        'toName' => $contact['display_name'],
        'toEmail' => $contact['email'],
        'subject' => ts('Thank you for your contribution/s'),
        'text' => '',
        'html' => $html,
      );
      if(empty($params['from'])) {
        $emails = CRM_Core_BAO_Email::getFromEmail();
        $emails = array_keys($emails);
        $defaults['from'] = array_pop($emails);
      }
      if($is_pdf) {
        $defaults['html'] = ts('Please see attached');
        $defaults['attachments'] = array(CRM_Utils_Mail::appendPDF('ThankYou.pdf', $html, $format));
      }
      $params = array_merge($defaults);
      return CRM_Utils_Mail::send($params);
    }
    catch (CRM_Core_Exception $e) {
      return FALSE;
    }
  }
}
