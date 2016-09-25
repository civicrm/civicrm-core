<?php

/**
 * This class provides the common functionality for creating PDF letter for
 * members
 */
class CRM_Member_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * Process the form after the input has been submitted and validated.
   * @todo this is horrible copy & paste code because there is so much risk of breakage
   * in fixing the existing pdfLetter classes to be suitably generic
   *
   * @param CRM_Core_Form $form
   * @param $membershipIDs
   * @param $skipOnHold
   * @param $skipDeceased
   * @param $contactIDs
   */
  public static function postProcessMembers(&$form, $membershipIDs, $skipOnHold, $skipDeceased, $contactIDs) {
    $formValues = $form->controller->exportValues($form->getName());
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($formValues);

    $html
      = self::generateHTML(
        $membershipIDs,
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        $messageToken,
        $html_message,
        $categories
      );
    self::createActivities($form, $html_message, $contactIDs);

    CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }

  /**
   * Generate htmlfor pdf letters.
   *
   * @param array $membershipIDs
   * @param array $returnProperties
   * @param bool $skipOnHold
   * @param bool $skipDeceased
   * @param array $messageToken
   * @param $html_message
   * @param $categories
   *
   * @return array
   */
  public static function generateHTML($membershipIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $html_message, $categories) {
    $memberships = CRM_Utils_Token::getMembershipTokenDetails($membershipIDs);
    $html = array();

    foreach ($membershipIDs as $membershipID) {
      $membership = $memberships[$membershipID];
      // get contact information
      $contactId = $membership['contact_id'];
      $params = array('contact_id' => $contactId);
      //getTokenDetails is much like calling the api contact.get function - but - with some minor
      // special handlings. It precedes the existence of the api
      list($contacts) = CRM_Utils_Token::getTokenDetails(
        $params,
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        'CRM_Contribution_Form_Task_PDFLetterCommon'
      );

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contacts[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('membership', $membership, $tokenHtml, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contacts[$contactId], $categories, TRUE);
      $tokenHtml = CRM_Utils_Token::parseThroughSmarty($tokenHtml, $contacts[$contactId]);

      $html[] = $tokenHtml;

    }
    return $html;
  }

}
