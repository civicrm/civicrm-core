<?php

/**
 * This class provides the common functionality for creating PDF letter for
 * one or a group of contact ids.
 */
class CRM_Contribute_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

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
    $categories = CRM_Contact_Form_Task_PDFLetterCommon::getTokenCategories();
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

}
