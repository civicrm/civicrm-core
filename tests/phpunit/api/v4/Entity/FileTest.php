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

    // Get content of new file with api
    $getResult = \Civi\Api4\File::get(FALSE)
      ->addSelect('uri', 'url', 'content')
      ->addWhere('id', '=', $create['id'])
      ->execute()->single();
    $this->assertEquals($originalContent, $getResult['content']);
    // Assert the url does not contain the file name
    $this->assertStringNotContainsString('test456', $getResult['url']);
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

  public function testPublicPrivateFiles(): void {
    $fileContent = 'File Content ' . rand();

    // Create file with is_public = TRUE
    $create = \Civi\Api4\File::create(FALSE)
      ->setValues([
        'mime_type' => 'text/plain',
        'file_name' => 'public_test.txt',
        'is_public' => TRUE,
        'content' => $fileContent,
      ])->execute()->single();

    // Assert file is in the public directory
    $publicFile = \CRM_Core_Config::singleton()->imageUploadDir . '/' . $create['uri'];
    $this->assertFileExists($publicFile);
    $this->assertEquals($fileContent, file_get_contents($publicFile));

    // Assert we can get contents via API
    $getResult = \Civi\Api4\File::get(FALSE)
      ->addSelect('uri', 'url', 'content')
      ->addWhere('id', '=', $create['id'])
      ->execute()->single();
    $this->assertEquals($create['uri'], $getResult['uri']);
    $this->assertEquals($fileContent, $getResult['content']);
    // Assert the url contains the file name (it's public)
    $this->assertStringContainsString($getResult['uri'], $getResult['url']);

    // Update file to is_public = FALSE
    \Civi\Api4\File::update(FALSE)
      ->addValue('is_public', FALSE)
      ->addWhere('id', '=', $create['id'])
      ->execute();

    // Assert file has been moved to private directory
    $this->assertFileDoesNotExist($publicFile);
    $privateFile = \CRM_Core_Config::singleton()->customFileUploadDir . '/' . $create['uri'];
    $this->assertFileExists($privateFile);
    $this->assertEquals($fileContent, file_get_contents($privateFile));

    // Get content of moved file with api
    $getResult = \Civi\Api4\File::get(FALSE)
      ->addSelect('uri', 'url', 'content')
      ->addWhere('id', '=', $create['id'])
      ->execute()->single();
    $this->assertEquals($fileContent, $getResult['content']);
    // Assert the url does not contain the file name (it's private)
    $this->assertStringNotContainsString($getResult['uri'], $getResult['url']);
  }

}
