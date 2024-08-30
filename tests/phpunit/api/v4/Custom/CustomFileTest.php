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


namespace api\v4\Custom;

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\File;
use Civi\Api4\EntityFile;

/**
 * @group headless
 */
class CustomFileTest extends CustomTestBase {

  public function testCustomFileField(): void {
    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'FilingCabinet')
      ->addValue('extends', 'Individual')
      ->execute()
      ->single();

    CustomField::create(FALSE)
      ->addValue('label', 'Passport')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('data_type', 'File')
      ->addValue('html_type', 'File')
      ->execute();

    CustomField::create(FALSE)
      ->addValue('label', 'Permit')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('data_type', 'File')
      ->addValue('html_type', 'File')
      ->execute();

    // file path for custom file uploads
    $filepath = \Civi::paths()->getPath(\CRM_Core_Config::singleton()->customFileUploadDir);

    \CRM_Utils_File::createFakeFile($filepath, 'Name: Franz. Birthplace: Prague', 'passport.txt');
    $passport = File::create(FALSE)
      ->addValue('uri', "$filepath/passport.txt")
      ->execute()
      ->single();

    $franz = Contact::save(FALSE)
      ->addRecord([
        'first_name' => 'Franz',
        'last_name' => 'Kafka',
        'contact_type' => 'Individual',
        'FilingCabinet.Passport' => $passport['id'],
      ])
      ->execute()
      ->single();

    \CRM_Utils_File::createFakeFile($filepath, 'Name: Franz. Work permit', 'permit.txt');
    $permit = File::create(FALSE)
      ->addValue('uri', "$filepath/permit.txt")
      ->execute()
      ->single();

    Contact::update(FALSE)
      ->addWhere('id', '=', $franz['id'])
      ->addValue('FilingCabinet.Permit', $permit['id'])
      ->execute();

    // check the fields on the contact work
    $contactGet = Contact::get(FALSE)
      ->addWhere('id', '=', $franz['id'])
      ->addSelect('FilingCabinet.Passport')
      ->addSelect('FilingCabinet.Permit')
      ->execute()
      ->single();

    $this->assertEquals($passport['id'], $contactGet['FilingCabinet.Passport']);
    $this->assertEquals($permit['id'], $contactGet['FilingCabinet.Permit']);

    $entityFileRecords = EntityFile::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $franz['id'])
      ->execute();

    $this->assertEquals($entityFileRecords->count(), 2);

    // get the file info
    // note: entity join is required to get hashed url
    $fileInfo = File::get(FALSE)
      ->addSelect('file_name', 'url')
      ->addWhere('id', '=', $passport['id'])
      ->addJoin('Contact', 'LEFT', 'EntityFile')
      ->execute()
      ->single();

    // url contains a checksum
    $this->assertStringContainsString('&fcs=', $fileInfo['url']);
    $this->assertEquals($fileInfo['file_name'], 'passport.txt');

  }

}
