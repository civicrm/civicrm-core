<?php

use Civi\Api4\Membership;

/**
 * This class provides the common functionality for creating PDF letter for
 * members
 *
 * @deprecated
 */
class CRM_Member_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * Process the form after the input has been submitted and validated.
   * @todo this is horrible copy & paste code because there is so much risk of breakage
   * in fixing the existing pdfLetter classes to be suitably generic
   *
   * @deprecated
   *
   * @param CRM_Core_Form $form
   * @param $membershipIDs
   * @param $skipOnHold
   * @param $skipDeceased
   * @param $contactIDs
   */
  public static function postProcessMembers(&$form, $membershipIDs, $skipOnHold, $skipDeceased, $contactIDs) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $formValues = $form->controller->exportValues($form->getName());
    [$formValues, $categories, $html_message, $messageToken, $returnProperties] = CRM_Contact_Form_Task_PDFLetterCommon::processMessageTemplate($formValues);

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
    CRM_Contact_Form_Task_PDFLetterCommon::createActivities($form, $html_message, $contactIDs, $formValues['subject'], CRM_Utils_Array::value('campaign_id', $formValues));

    // Set the filename for the PDF using the Activity Subject, if defined. Remove unwanted characters and limit the length to 200 characters.
    if (!empty($form->getSubmittedValue('subject'))) {
      $fileName = CRM_Utils_File::makeFilenameWithUnicode($form->getSubmittedValue('subject'), '_', 200) . '.pdf';
    }
    else {
      $fileName = 'CiviLetter.pdf';
    }

    CRM_Utils_PDF_Utils::html2pdf($html, $fileName, FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit();
  }

  /**
   * Generate html for pdf letters.
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @deprecated
   */
  public static function generateHTML($membershipIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $html_message, $categories) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $memberships = Membership::get(FALSE)
      ->addWhere('id', 'IN', $membershipIDs)
      ->addSelect('contact_id')->execute();
    $html = [];

    foreach ($memberships as $membership) {
      $html[] = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => ['msg_html' => $html_message],
        'contactId' => $membership['contact_id'],
        'schema' => ['contactId', 'membershipId'],
        'tokenContext' => ['membershipId' => $membership['id']],
        'disableSmarty' => !defined('CIVICRM_MAIL_SMARTY') || !CIVICRM_MAIL_SMARTY,
      ])['html'];
    }
    return $html;
  }

}
