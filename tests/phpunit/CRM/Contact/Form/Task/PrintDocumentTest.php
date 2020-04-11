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
  * Test class for CRM_Contact_Form_Task_PDFLetterCommon.
  * @group headless
  */
class CRM_Contact_Form_Task_PrintDocumentTest extends CiviUnitTestCase {

  protected $_docTypes = NULL;

  protected $_contactIds = NULL;

  protected function setUp() {
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
  public function testPrintDocument() {
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
    $form = new CRM_Contact_Form_Task_PDFLetterCommon();
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = $form->processMessageTemplate($formValues);
    list($html_message, $zip) = CRM_Utils_PDF_Document::unzipDoc($formValues['document_file_path'], $formValues['document_type']);

    foreach ($this->_contactIds as $item => $contactId) {
      $params = ['contact_id' => $contactId];
      list($contact) = CRM_Utils_Token::getTokenDetails($params,
        $returnProperties,
        FALSE,
        FALSE,
        NULL,
        $messageToken,
        'CRM_Contact_Form_Task_PDFLetterCommon'
      );
      $html[] = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
    }

    $fileName = pathinfo($formValues['document_file_path'], PATHINFO_FILENAME) . '.' . $type;
    $returnContent = CRM_Utils_PDF_Document::printDocuments($html, $fileName, $type, $zip, TRUE);
    $returnContent = strip_tags($returnContent);

    $this->assertTrue(strpos($returnContent, 'Hello Antonia D`souza') !== 0);
    $this->assertTrue(strpos($returnContent, 'Hello Anthony Collins') !== 0);
  }

}
