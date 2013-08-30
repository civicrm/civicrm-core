<?php

/**
 * This class provides the common functionality for creating PDF letter for
 * members
 */
class CRM_Member_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * process the form after the input has been submitted and validated
   * @todo this is horrible copy & paste code because there is so much risk of breakage
   * in fixing the existing pdfLetter classes to be suitably generic
   * @access public
   *
   * @return None
   */
  static function postProcess(&$form, $membershipIDs, $skipOnHold, $skipDeceased, $contactIDs) {

    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($form);

    $html = self::generateHTML($membershipIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $html_message, $categories);
    self::createActivities($form, $html_message, $contactIDs);

    CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }
  //end of function

  /**
   * generate htmlfor pdf letters
   * @param unknown_type $membershipIDs
   * @param unknown_type $returnProperties
   * @param unknown_type $skipOnHold
   * @param unknown_type $skipDeceased
   * @param unknown_type $messageToken
   * @return unknown
   */
  static function generateHTML($membershipIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $html_message, $categories) {
    $memberships = CRM_Utils_Token::getMembershipTokenDetails($membershipIDs);

    foreach ($memberships as $membershipID => $membership) {
      // get contact information
      $contactId = $membership['contact_id'];
      $params = array('contact_id' => $contactId);
      //getTokenDetails is much like calling the api contact.get function - but - with some minor
      // special handlings. It preceeds the existance of the api
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

