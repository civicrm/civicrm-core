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
 * This class provides the functionality to create PDF letter for a group of
 * contacts or a single contact.
 */
class CRM_Member_Form_Task_PDFLetter extends CRM_Member_Form_Task {

  use CRM_Contact_Form_Task_PDFTrait;

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
   *
   * @return void
   */
  public function preProcess() {
    $this->skipOnHold = $this->skipDeceased = FALSE;
    parent::preProcess();
    $this->setContactIDs();
    $this->preProcessPDF();
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    $this->addPDFElementsToForm();
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    // TODO: rewrite using contribution token and one letter by contribution
    $this->setContactIDs();
    $skipOnHold = $this->skipOnHold ?? FALSE;
    $skipDeceased = $this->skipDeceased ?? TRUE;
    $this->postProcessMembers($this->_memberIds, $skipOnHold, $skipDeceased, $this->_contactIds);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param $membershipIDs
   * @param $skipOnHold
   * @param $skipDeceased
   * @param $contactIDs
   *
   * @throws \CRM_Core_Exception
   * @todo this is horrible copy & paste code because there is so much risk of breakage
   * in fixing the existing pdfLetter classes to be suitably generic
   *
   */
  public function postProcessMembers($membershipIDs, $skipOnHold, $skipDeceased, $contactIDs) {
    $form = $this;
    $formValues = $form->controller->exportValues($form->getName());
    [$formValues, $categories, $html_message, $messageToken, $returnProperties] = CRM_Contact_Form_Task_PDFLetterCommon::processMessageTemplate($formValues);

    $html
      = $this->generateHTML(
      $membershipIDs,
      $returnProperties,
      $skipOnHold,
      $skipDeceased,
      $messageToken,
      $html_message,
      $categories
    );
    CRM_Contact_Form_Task_PDFLetterCommon::createActivities($form, $html_message, $contactIDs, $formValues['subject'], CRM_Utils_Array::value('campaign_id', $formValues));
    CRM_Utils_PDF_Utils::html2pdf($html, $this->getFileName() . '.pdf', FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit();
  }

  /**
   * Generate html for pdf letters.
   *
   * @internal
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
  public function generateHTML($membershipIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $html_message, $categories) {
    $memberships = CRM_Utils_Token::getMembershipTokenDetails($membershipIDs);
    $html = [];

    foreach ($membershipIDs as $membershipID) {
      $membership = $memberships[$membershipID];
      // get contact information
      $contactId = $membership['contact_id'];
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('membership', $membership, $html_message, $messageToken);
      $html[] = CRM_Core_BAO_MessageTemplate::renderTemplate(['messageTemplate' => ['msg_html' => $tokenHtml], 'contactId' => $contactId])['html'];

    }
    return $html;
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::membershipTokens(), $tokens);
    return $tokens;
  }

}
