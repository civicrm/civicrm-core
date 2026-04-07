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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Custom;

use api\v4\Api4TestBase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\EntityFile;
use Civi\Api4\File;

/**
 * @group headless
 */
class CustomFileTest extends Api4TestBase {

  /**
   */
  public function testCustomFileContent(): void {
    // Baseline count for civicrm_entity_file which should not change during this test
    $entityFileCount = EntityFile::get(FALSE)->selectRowCount()->execute()->count();

    $fieldName = 'ContactFileFields.TestMyFile';
    [$customGroup, $customField] = explode('.', $fieldName);

    $this->createTestRecord('CustomGroup', [
      'title' => $customGroup,
      'extends' => 'Individual',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => $customField,
      'custom_group_id.name' => $customGroup,
      'html_type' => 'File',
      'data_type' => 'File',
      'file_is_public' => TRUE,
    ]);

    $getFields = Contact::getFields(FALSE)
      ->execute()->indexBy('name');
    $this->assertEquals('File', $getFields[$fieldName]['fk_entity']);
    $this->assertTrue($getFields[$fieldName]['input_attrs']['file_is_public']);

    $contact = $this->createTestRecord('Individual');

    // File is saved in private dir (is_public defaults to FALSE)
    $file1 = $this->createTestRecord('File', [
      'mime_type' => 'text/plain',
      'file_name' => 'test123.txt',
      'content' => 'Hello World 123',
    ]);
    $file1Path = \CRM_Core_Config::singleton()->customFileUploadDir . $file1['uri'];
    $this->assertFileExists($file1Path);

    Contact::update(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addValue($fieldName, $file1['id'])
      ->execute();

    // No EntityFile records should have been created
    $this->assertSame($entityFileCount, EntityFile::get(FALSE)->selectRowCount()->execute()->count());

    $result = $this->getTestRecord('File', $file1['id'], ['uri', 'file_name', 'url', 'content', 'is_public']);

    $this->assertEquals($file1['uri'], $result['uri']);
    $this->assertEquals('test123.txt', $result['file_name']);
    $this->assertEquals('Hello World 123', $result['content']);
    $this->assertStringContainsString($result['uri'], $result['url']);

    // File has been moved to public dir
    $this->assertTrue($result['is_public']);
    $this->assertFileDoesNotExist($file1Path);
    // Path has been changed to public dir
    $file1Path = \CRM_Core_Config::singleton()->imageUploadDir . $result['uri'];
    $this->assertFileExists($file1Path);

    // Update file contents
    File::update(FALSE)
      ->addWhere('id', '=', $file1['id'])
      ->addValue('content', 'Hello World 456')
      ->execute();

    // Update contact with no change to the file. Ensure it still exists
    Contact::update(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addValue($fieldName, $file1['id'])
      ->addValue('first_name', 'Test')
      ->execute();
    $this->assertFileExists($file1Path);

    // This time use a join to fetch the file
    $result = Contact::get(FALSE)
      ->addSelect('id', "$fieldName.uri", "$fieldName.file_name", "$fieldName.url", "$fieldName.content")
      ->addWhere('id', '=', $contact['id'])
      ->execute()->single();

    $this->assertEquals($file1['uri'], $result["$fieldName.uri"]);
    $this->assertEquals('test123.txt', $result["$fieldName.file_name"]);
    $this->assertEquals('Hello World 456', $result["$fieldName.content"]);
    $this->assertStringContainsString($file1['uri'], $result["$fieldName.url"]);

    $file2 = $this->createTestRecord('File', [
      'mime_type' => 'text/plain',
      'file_name' => 'test123.txt',
      'content' => 'Hello World 1234',
      'is_public' => TRUE,
    ]);
    $file2Path = \CRM_Core_Config::singleton()->imageUploadDir . $file2['uri'];

    // Update contact with a different file
    Contact::update(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addValue($fieldName, $file2['id'])
      ->execute();

    // Original file should have been deleted
    $result = File::get(FALSE)
      ->selectRowCount()
      ->addWhere('id', '=', $file1['id'])
      ->execute();
    $this->assertCount(0, $result);
    $this->assertFileDoesNotExist($file1Path);

    $this->assertFileExists($file2Path);

    // Remove the file from the contact
    Contact::update(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addValue($fieldName, NULL)
      ->execute();

    $result = Contact::get(FALSE)
      ->addSelect('id', "$fieldName.uri", "$fieldName.file_name", "$fieldName.url", "$fieldName.content")
      ->addWhere('id', '=', $contact['id'])
      ->execute()->single();
    $this->assertNull($result["$fieldName.uri"]);
    $this->assertNull($result["$fieldName.file_name"]);
    $this->assertNull($result["$fieldName.content"]);

    $result = File::get(FALSE)
      ->selectRowCount()
      ->addWhere('id', '=', $file2['id'])
      ->execute();
    $this->assertCount(0, $result);
    $this->assertFileDoesNotExist($file2Path);
  }

  public function testMoveFile(): void {
    $fieldName = 'ActFileFields.TestMyFile';
    [$customGroup, $customField] = explode('.', $fieldName);

    $this->createTestRecord('CustomGroup', [
      'title' => $customGroup,
      'extends' => 'Activity',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => $customField,
      'custom_group_id.name' => $customGroup,
      'html_type' => 'File',
      'data_type' => 'File',
    ]);

    $tmpFile = $this->createTmpFile('Hello World 12345');

    $file = $this->createTestRecord('File', [
      'mime_type' => 'text/plain',
      'file_name' => 'test456.txt',
      'move_file' => $tmpFile,
    ]);

    $this->assertFileDoesNotExist($tmpFile);
    $newFile = \CRM_Core_Config::singleton()->customFileUploadDir . $file['uri'];
    $this->assertFileExists($newFile);

    $activity = $this->createTestRecord('Activity', [
      $fieldName => $file['id'],
    ]);

    $result = Activity::get(FALSE)
      ->addSelect('id', "$fieldName.uri", "$fieldName.file_name", "$fieldName.url", "$fieldName.content")
      ->addWhere('id', '=', $activity['id'])
      ->execute()->single();

    $this->assertEquals($file['uri'], $result["$fieldName.uri"]);
    $this->assertEquals('test456.txt', $result["$fieldName.file_name"]);
    $this->assertEquals('Hello World 12345', $result["$fieldName.content"]);
    $this->assertStringContainsString("id={$file['id']}&fcs=", $result["$fieldName.url"]);

    File::delete(FALSE)
      ->addWhere('id', '=', $file['id'])
      ->execute();
    $this->assertFileDoesNotExist($newFile);
  }

  protected function createTmpFile(string $content): string {
    $tmpDir = sys_get_temp_dir();
    $this->assertTrue($tmpDir && is_dir($tmpDir), 'Tmp dir must exist: ' . $tmpDir);
    $path = tempnam(sys_get_temp_dir(), 'Test');
    \Civi::fs()->dumpFile($path, $content);
    return $path;
  }

}
