<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    $this->_contactIds = array(
      $this->individualCreate(array('first_name' => 'Antonia', 'last_name' => 'D`souza')),
      $this->individualCreate(array('first_name' => 'Anthony', 'last_name' => 'Collins')),
    );
    $this->_docTypes = CRM_Core_SelectValues::documentApplicationType();
  }

  /**
   * Test the documents got token replaced rightfully.
   */
  public function testPrintDocument() {
    foreach (array('docx', 'odt') as $docType) {
      $formValues = array(
        'document_file' => array(
          'name' => __DIR__ . "/sample_documents/Template.$docType",
          'type' => $this->_docTypes[$docType],
        ),
      );
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
    $html = array();
    $form = new CRM_Contact_Form_Task_PDFLetterCommon();
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = $form->processMessageTemplate($formValues);
    list($html_message, $zip) = CRM_Utils_PDF_Document::unzipDoc($formValues['document_file_path'], $formValues['document_type']);

    foreach ($this->_contactIds as $item => $contactId) {
      $params = array('contact_id' => $contactId);
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
