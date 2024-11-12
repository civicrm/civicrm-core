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

namespace Civi\tests\phpunit\api\v4\Custom;

use api\v4\Custom\CustomTestBase;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use Civi\Api4\File;

/**
 * @group headless
 */
class CustomFileTest extends CustomTestBase {

  /**
   */
  public function testCustomFileField(): void {
    $group = CustomGroup::create()->setValues([
      'title' => 'FileFields',
      'extends' => 'Individual',
    ])->execute()->single();
    $field = CustomField::create()->setValues([
      'label' => 'TestMyFile',
      'custom_group_id.name' => 'FileFields',
      'html_type' => 'File',
      'data_type' => 'File',
    ])->execute()->single();

    $fieldName = 'FileFields.TestMyFile';

    $contact = $this->createTestRecord('Individual');

    // FIXME: Use Api4 when available
    $file = civicrm_api3('Attachment', 'create', [
      // The mismatch between entity id and entity table feels very wrong but that's how core does it for now
      'entity_id' => $contact['id'],
      'entity_table' => $group['table_name'],
      'mime_type' => 'text/plain',
      'name' => 'test123.txt',
      'content' => 'Hello World 123',
    ]);

    civicrm_api4('Individual', 'update', [
      'values' => [
        'id' => $contact['id'],
        $fieldName => $file['id'],
      ],
      'checkPermissions' => FALSE,
    ]);

    $file = File::get(FALSE)
      ->addSelect('id', 'file_name', 'url')
      ->addWhere('id', '=', $file['id'])
      ->execute()->single();

    $this->assertEquals('test123.txt', $file['file_name']);
    $this->assertStringContainsString("id={$file['id']}&eid={$contact['id']}&fcs=", $file['url']);
  }

}
