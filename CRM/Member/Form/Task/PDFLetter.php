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

use Civi\Api4\Membership;

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
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
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
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $this->setContactIDs();
    $this->postProcessMembers($this->_memberIds, $this->_contactIds);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param $membershipIDs
   * @param $contactIDs
   *
   * @throws \CRM_Core_Exception
   * @todo this is horrible copy & paste code because there is so much risk of breakage
   * in fixing the existing pdfLetter classes to be suitably generic
   *
   */
  public function postProcessMembers($membershipIDs, $contactIDs) {
    $form = $this;
    $formValues = $form->controller->exportValues($form->getName());
    [$formValues, $html_message] = $this->processMessageTemplate($formValues);
    $html = $this->generateHTML($membershipIDs, $html_message);
    $form->createActivities($html_message, $contactIDs, $formValues['subject'], $formValues['campaign_id'] ?? NULL);
    CRM_Utils_PDF_Utils::html2pdf($html, $this->getFileName() . '.pdf', FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit();
  }

  /**
   * Generate html for pdf letters.
   *
   * @param array $membershipIDs
   * @param $html_message
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal
   *
   */
  public function generateHTML($membershipIDs, $html_message): array {
    $memberships = Membership::get(FALSE)
      ->addWhere('id', 'IN', $membershipIDs)
      ->addSelect('contact_id')->execute();
    $html = [];

    foreach ($memberships as $membership) {
      $html[] = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => ['msg_html' => $html_message],
        'contactId' => $membership['contact_id'],
        'tokenContext' => ['membershipId' => $membership['id']],
        'disableSmarty' => !defined('CIVICRM_MAIL_SMARTY') || !CIVICRM_MAIL_SMARTY,
      ])['html'];
    }
    return $html;
  }

  /**
   * Get the token processor schema required to list any tokens for this task.
   *
   * @return array
   */
  public function getTokenSchema(): array {
    return ['membershipId', 'contactId'];
  }

}
