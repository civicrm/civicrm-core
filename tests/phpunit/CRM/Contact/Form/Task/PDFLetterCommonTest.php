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
 *
 * @group headless
 */
class CRM_Contact_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  /**
   * Contact ID.
   *
   * @var int
   */
  protected $contactId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->contactId = $this->createLoggedInUser();
  }

  /**
   * Test the pdf filename is assigned as expected.
   *
   * @param string|null $pdfFileName
   *   Value for pdf_file_name param.
   * @param string|null $activitySubject
   *   Value of the subject of the activity.
   * @param bool|null $isLiveMode
   *   TRUE when the form is in live mode, NULL when it is a preview.
   * @param string $expectedFilename
   *   Expected filename assigned to the pdf.
   *
   * @dataProvider getFilenameCases
   */
  public function testFilenameIsAssigned(?string $pdfFileName, ?string $activitySubject, ?bool $isLiveMode, string $expectedFilename): void {
    $form = $this->getPDFForm([
      'pdf_file_name' => $pdfFileName,
      'subject' => $activitySubject,
    ], [$this->contactId], $isLiveMode);
    $fileNameAssigned = $this->submitForm($form)['fileName'];
    $this->assertEquals($expectedFilename, $fileNameAssigned);
  }

  /**
   * DataProvider for testFilenameIsAssigned.
   *
   * @return array
   *   Array with the test information.
   */
  public function getFilenameCases(): array {
    return [
      [
        'FilenameInParam',
        'FilenameInActivitySubject',
        NULL,
        'FilenameInParam_preview',
      ],
      [
        'FilenameInParam',
        'FilenameInActivitySubject',
        TRUE,
        'FilenameInParam',
      ],
      [
        NULL,
        'FilenameInActivitySubject',
        NULL,
        'FilenameInActivitySubject_preview',
      ],
      [
        NULL,
        'FilenameInActivitySubject',
        TRUE,
        'FilenameInActivitySubject',
      ],
      [
        NULL,
        NULL,
        NULL,
        'CiviLetter_preview',
      ],
      [
        NULL,
        NULL,
        TRUE,
        'CiviLetter',
      ],
    ];
  }

  /**
   * @param \CRM_Core_Form $form
   *
   * @return int|mixed
   */
  protected function submitForm(CRM_Core_Form $form) {
    $form->preProcess();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      return $e->errorData;

    }
    $this->fail('line should be unreachable');
  }

  /**
   * @param array $formValues
   * @param array $contactIDs
   * @param bool|null $isLiveMode
   *
   * @return \CRM_Contact_Form_Task_PDF
   */
  protected function getPDFForm(array $formValues, array $contactIDs, ?bool $isLiveMode = TRUE): CRM_Contact_Form_Task_PDF {
    // pretty cludgey.
    $_REQUEST['cid'] = $contactIDs[0];
    /* @var CRM_Contact_Form_Task_PDF $form */
    $form = $this->getFormObject('CRM_Contact_Form_Task_PDF', array_merge([
      'pdf_file_name' => 'pdf_file_name',
      'subject' => 'subject',
      'document_type' => 'pdf',
      'buttons' => [
        '_qf_PDF_upload' => $isLiveMode,
      ],
    ], $formValues));
    $form->_contactIds = $contactIDs;
    return $form;
  }

  /**
   * Test contact tokens are resolved.
   */
  public function testContactTokensAreResolved(): void {
    $form = $this->getPDFForm([
      'html_message' => '{contact.first_name}, {contact.email_greeting}',
    ], [$this->contactId]);
    $processedMessage = $this->submitForm($form)['html'];
    $this->assertStringContainsString('Logged In, Dear Logged In', $processedMessage);
  }

}
