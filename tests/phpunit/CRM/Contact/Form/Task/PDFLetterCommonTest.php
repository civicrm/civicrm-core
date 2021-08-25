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
    $_REQUEST['cid'] = $this->contactId;
    $form = $this->getFormObject('CRM_Contact_Form_Task_PDF', [
      'pdf_file_name' => $pdfFileName,
      'subject' => $activitySubject,
      '_contactIds' => [],
      'document_type' => 'pdf',
      'buttons' => [
        '_qf_PDF_upload' => $isLiveMode,
      ],
    ]);
    $fileNameAssigned = NULL;
    $form->preProcess();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $fileNameAssigned = $e->errorData['fileName'];
    }

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

}
