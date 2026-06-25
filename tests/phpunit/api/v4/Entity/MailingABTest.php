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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\MailingAB;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MailingABTest extends Api4TestBase implements TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviMail');
  }

  public function testMailingABCRUD(): void {
    // Create
    $mailingAB = MailingAB::create(FALSE)
      ->addValue('name', 'Test Mailing AB')
      ->addValue('status', 'Draft')
      ->execute()
      ->first();

    $this->assertNotNull($mailingAB['id']);
    $this->assertEquals('Test Mailing AB', $mailingAB['name']);
    $this->assertEquals('Draft', $mailingAB['status']);

    // Read
    $read = MailingAB::get(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute()
      ->first();

    $this->assertEquals('Test Mailing AB', $read['name']);

    // Update
    MailingAB::update(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->addValue('name', 'Updated Mailing AB')
      ->execute();

    $updated = MailingAB::get(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute()
      ->first();

    $this->assertEquals('Updated Mailing AB', $updated['name']);

    // Delete
    MailingAB::delete(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute();

    $deleted = MailingAB::get(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute();

    $this->assertCount(0, $deleted);
  }

}
