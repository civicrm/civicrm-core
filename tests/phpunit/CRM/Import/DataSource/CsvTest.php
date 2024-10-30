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
 * Tests for the CRM_Import_Datasource_Csv class.
 *
 * @group headless
 * @group import
 */
class CRM_Import_DataSource_CsvTest extends CiviUnitTestCase {

  /**
   * Prepare for tests.
   */
  public function setUp(): void {
    $this->createLoggedInUser();
    parent::setUp();
  }

  /**
   * Test the to csv function.
   *
   * @param array $fileData
   *
   * @dataProvider getCsvFiles
   *
   * @throws \CRM_Core_Exception
   */
  public function testToCsv(array $fileData): void {
    $form = $this->submitDatasourceForm($fileData['filename']);
    $csvObject = new CRM_Import_DataSource_CSV($form->getUserJobID());
    $rows = $csvObject->getRows(0, 0, [], FALSE);
    foreach (['first_name', 'last_name', 'email'] as $field) {
      $json = json_encode($rows[0][$field]);
      $this->assertEquals($fileData["{$field}_json"], $json, "{$fileData['filename']} failed on $field");
    }
    $csvObject->purge();
  }

  /**
   * Get csv files to test.
   *
   * @return array
   */
  public function getCsvFiles(): array {
    return [
      // import.csv is utf8-encoded, with no BOM
      [
        [
          'filename' => 'import.csv',
          'first_name_json' => '"Yogi"',
          'last_name_json' => '"Bear"',
          'email_json' => '"yogi@yellowstone.park"',
        ],
      ],
      // yogi.csv is latin1-encoded
      [
        [
          'filename' => 'yogi.csv',
          'first_name_json' => '"Yogi"',
          'last_name_json' => '"Bear"',
          'email_json' => '"yogi@yellowstone.park"',
        ],
      ],
      // specialchar.csv is utf8-encoded, with no BOM
      [
        [
          'filename' => 'specialchar.csv',
          // note that json uses unicode representation not utf8 byte sequences
          'first_name_json' => '"Yog\u00e0"',
          'last_name_json' => '"Ber\u00e0"',
          'email_json' => '"yogi@yellowstone.park"',
        ],
      ],
      // specialchar_with_BOM.csv is utf8-encoded with BOM
      [
        [
          'filename' => 'specialchar_with_BOM.csv',
          'first_name_json' => '"Yog\u00e0"',
          'last_name_json' => '"Ber\u00e0"',
          'email_json' => '"yogi@yellowstone.park"',
        ],
      ],
    ];
  }

  /**
   * Test the trim function.
   *
   * @dataProvider trimDataProvider
   *
   * @param string $input
   * @param string $expected
   */
  public function testTrim(string $input, string $expected): void {
    $this->assertSame($expected, CRM_Import_DataSource_CSV::trimNonBreakingSpaces($input));
  }

  /**
   * DataProvider for testTrim.
   *
   * @return array
   */
  public function trimDataProvider(): array {
    return [
      'plain' => ['plain', 'plain'],
      'non-breaking-space-at-end-latin1' => ['foo' . chr(0xA0), 'foo'],
      'non-breaking-space-at-end-utf8' => ["foo\u{a0}", 'foo'],
      'non-breaking-space-at-start-latin1' => [chr(0xA0) . 'foo', 'foo'],
      'non-breaking-space-at-start-utf8' => ["\u{a0}foo", 'foo'],
      'non-breaking-space-at-both-latin1' => [chr(0xA0) . 'foo' . chr(0xA0), 'foo'],
      'non-breaking-space-at-both-utf8' => ["\u{a0}foo\u{a0}", 'foo'],
      'sharing-same-byte' => ['fooà', 'fooà'],
      'sharing-same-byte-plus-space-end' => ["fooà\u{a0}", 'fooà'],
      'sharing-same-byte-plus-space-start' => ["\u{a0}àfoo", 'àfoo'],
      'sharing-same-byte-plus-space-both' => ["\u{a0}àfooà\u{a0}", 'àfooà'],
      'multiple-spaces' => ["\u{a0}\u{a0}foo\u{a0}\u{a0}", 'foo'],
    ];
  }

  /**
   * Test only one column and a blank line at the end, because
   * fgetcsv will return the blank lines as array(0 => NULL) which is an
   * edge case. Note if it has more than one column then the blank line gets
   * skipped because of some checking for column-count matches in the import,
   * and so you don't hit the current fail.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBlankLineAtEnd(): void {
    $form = $this->submitDatasourceForm('blankLineAtEnd.csv');
    $csvObject = new CRM_Import_DataSource_CSV($form->getUserJobID());

    $json = json_encode($csvObject->getRow()['email']);
    $this->assertEquals('"yogi@yellowstone.park"', $json);
    $csvObject->purge();
  }

  /**
   * Test submitting the datasource form.
   *
   * @param string $csvFileName
   *
   * @return \CRM_Contact_Import_Form_DataSource
   *
   * @throws \CRM_Core_Exception
   */
  protected function submitDatasourceForm(string $csvFileName): CRM_Contact_Import_Form_DataSource {
    $_GET['dataSource'] = 'CRM_Import_DataSource_CSV';
    /** @var CRM_Contact_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_DataSource', [
      'uploadFile' => [
        'name' => __DIR__ . '/' . $csvFileName,
      ],
      'skipColumnHeader' => TRUE,
      'contactType' => 'Individual',
      'dataSource' => 'CRM_Import_DataSource_CSV',
    ]);
    $form->buildForm();
    $form->postProcess();
    return $form;
  }

}
