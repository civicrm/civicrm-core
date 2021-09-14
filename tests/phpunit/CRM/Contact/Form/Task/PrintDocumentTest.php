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
  * Test class for CRM_Contact_Form_Task_PDF.
  * @group headless
  */
class CRM_Contact_Form_Task_PrintDocumentTest extends CiviUnitTestCase {

  protected $_docTypes = NULL;

  protected $_contactIds = NULL;

  protected function setUp(): void {
    parent::setUp();
    $this->_contactIds = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
    $this->_docTypes = CRM_Core_SelectValues::documentApplicationType();
  }

  /**
   * Test the documents got token replaced rightfully.
   */
  public function testPrintDocument(): void {
    foreach (['docx', 'odt'] as $docType) {
      $formValues = [
        'document_file' => [
          'name' => __DIR__ . "/sample_documents/Template.$docType",
          'type' => $this->_docTypes[$docType],
        ],
      ];
      $this->_testDocumentContent($formValues, $docType);
    }
  }

  /**
   *  Assert the content of document
   *
   * @param array $formValues
   * @param array $type
   */
  public function _testDocumentContent($formValues, $type) {
    $html = [];
    /* @var CRM_Contact_Form_Task_PDF $form */
    $form = $this->getFormObject('CRM_Contact_Form_Task_PDF', [], NULL, [
      'radio_ts' => 'ts_sel',
      'task' => CRM_Member_Task::PDF_LETTER,
    ]);
    list($formValues, $html_message, $messageToken, $returnProperties) = $form->processMessageTemplate($formValues);
    list($html_message, $zip) = CRM_Utils_PDF_Document::unzipDoc($formValues['document_file_path'], $formValues['document_type']);

    foreach ($this->_contactIds as $item => $contactId) {
      $html[] = CRM_Core_BAO_MessageTemplate::renderTemplate(['messageTemplate' => ['msg_html' => $html_message], 'contactId' => $contactId, 'disableSmarty' => TRUE])['html'];
    }

    $fileName = pathinfo($formValues['document_file_path'], PATHINFO_FILENAME) . '.' . $type;
    $returnContent = CRM_Utils_PDF_Document::printDocuments($html, $fileName, $type, $zip, TRUE);
    $returnContent = strip_tags($returnContent);

    $this->assertTrue(strpos($returnContent, 'Hello Antonia D`souza') !== 0);
    $this->assertTrue(strpos($returnContent, 'Hello Anthony Collins') !== 0);
  }

}
