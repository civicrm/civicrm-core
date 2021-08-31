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

use Civi\Token\TokenProcessor;

/**
 * This class provides the functionality to create PDF/Word letters for activities.
 */
class CRM_Activity_Form_Task_PDF extends CRM_Activity_Form_Task {

  use CRM_Contact_Form_Task_PDFTrait;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->setTitle('Print/Merge Document');
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->addPDFElementsToForm();
    // Remove types other than pdf as they are not working (have never worked) and don't want fix
    // for them to block pdf.
    // @todo debug & fix....
    $this->add('select', 'document_type', ts('Document Type'), ['pdf' => ts('Portable Document Format (.pdf)')]);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $form = $this;
    $activityIds = $form->_activityHolderIds;
    $formValues = $form->controller->exportValues($form->getName());
    $html_message = CRM_Core_Form_Task_PDFLetterCommon::processTemplate($formValues);

    // Do the rest in another function to make testing easier
    $form->createDocument($activityIds, $html_message, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    return $this->createTokenProcessor()->listTokens();
  }

  /**
   * Create a token processor
   *
   * @return \Civi\Token\TokenProcessor
   */
  public function createTokenProcessor() {
    return new TokenProcessor(\Civi::dispatcher(), [
      'controller' => get_class(),
      'smarty' => FALSE,
      'schema' => ['activityId'],
    ]);
  }

  /**
   * Produce the document from the activities
   * This uses the new token processor
   *
   * @param  array $activityIds  array of activity ids
   * @param  string $html_message message text with tokens
   * @param  array $formValues   formValues from the form
   *
   * @return array
   */
  public function createDocument($activityIds, $html_message, $formValues) {
    $tp = $this->createTokenProcessor();
    $tp->addMessage('body_html', $html_message, 'text/html');

    foreach ($activityIds as $activityId) {
      $tp->addRow()->context('activityId', $activityId);
    }
    $tp->evaluate();

    return $this->renderFromRows($tp->getRows(), 'body_html', $formValues);
  }

  /**
   * Render html from rows
   *
   * @param $rows
   * @param string $msgPart
   *   The name registered with the TokenProcessor
   * @param array $formValues
   *   The values submitted through the form
   *
   * @return string
   *   If formValues['is_unit_test'] is true, otherwise outputs document to browser
   */
  public function renderFromRows($rows, $msgPart, $formValues) {
    $html = [];
    foreach ($rows as $row) {
      $html[] = $row->render($msgPart);
    }

    if (!empty($formValues['is_unit_test'])) {
      return $html;
    }

    if (!empty($html)) {
      $this->outputFromHtml($formValues, $html);
    }
  }

  /**
   * Output the pdf or word document from the generated html.
   *
   * @param array $formValues
   * @param array $html
   */
  protected function outputFromHtml($formValues, array $html) {
    if (!empty($formValues['subject'])) {
      $fileName = CRM_Utils_File::makeFilenameWithUnicode($formValues['subject'], '_', 200);
    }
    else {
      $fileName = 'CiviLetter';
    }
    if ($formValues['document_type'] === 'pdf') {
      CRM_Utils_PDF_Utils::html2pdf($html, $fileName . '.pdf', FALSE, $formValues);
    }
    else {
      CRM_Utils_PDF_Document::html2doc($html, $fileName . '.' . $formValues['document_type'], $formValues);
    }
  }

}
