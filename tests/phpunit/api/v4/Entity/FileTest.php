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


namespace api\v4\Entity;

use api\v4\Api4TestBase;

/**
 * @group headless
 */
class FileTest extends Api4TestBase {

  private $tempDir;

  public function setUp(): void {
    parent::setUp();
    $this->tempDir = \CRM_Utils_File::tempdir('api4-file-test-');
  }

  public function tearDown(): void {
    \CRM_Utils_File::cleanDir($this->tempDir, TRUE, FALSE);
    parent::tearDown();
  }

  /**
   * Use the File::create() option 'move_file' for a trusted request (checkPermissions=FALSE).
   */
  public function testMoveTrusted(): void {
    $originalContent = 'Hello World ' . rand();
    $originalFile = $this->tempDir . '/original.txt';
    file_put_contents($originalFile, $originalContent);
    $this->assertFileExists($originalFile);

    $create = \Civi\Api4\File::create(FALSE)
      ->setValues([
        'mime_type' => 'text/plain',
        'file_name' => 'test456.txt',
        'move_file' => $originalFile,
      ])->execute()->single();

    $this->assertFileDoesNotExist($originalFile);

    // Hmm, the API has no way to read the content of the saved file! We'll fudge it...
    $newFile = \CRM_Core_Config::singleton()->customFileUploadDir . $create['uri'];
    $this->assertFileExists($newFile);
    $this->assertEquals($originalContent, file_get_contents($newFile));
  }

  /**
   * Use the File::create() option 'move_file' for an untrusted request (checkPermissions=TRUE).
   */
  public function testMoveUntrusted(): void {
    $originalContent = 'Hello World ' . rand();
    $originalFile = $this->tempDir . '/original.txt';
    file_put_contents($originalFile, $originalContent);
    $this->assertFileExists($originalFile);

    try {
      \Civi\Api4\File::create(TRUE)
        ->setValues([
          'mime_type' => 'text/plain',
          'file_name' => 'test456.txt',
          'move_file' => $originalFile,
        ])->execute()->single();
      $this->fail('File::create should fail');
    }
    catch (\Throwable $e) {
      $this->assertTrue(str_contains($e->getMessage(), 'only allowed in trusted operation'), 'Exception should relate to permission check');
      $this->assertFileExists($originalFile, 'If creation is rejected, then file should still exist.');
    }
  }

}
