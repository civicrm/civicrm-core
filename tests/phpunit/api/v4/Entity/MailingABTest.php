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
use Civi\Api4\Mailing;
use Civi\Api4\MailingAB;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MailingABTest extends Api4TestBase implements TransactionalInterface {

  protected ?array $enableComponents = ['CiviMail'];

  public function testMailingABCRUD(): void {
    // Create
    $mailingAB = MailingAB::create(FALSE)
      ->addValue('name', 'Test Mailing AB')
      ->addValue('status', 'Draft')
      ->execute()
      ->first();

    $this->assertNotNull($mailingAB['id']);
    $this->assertSame('Test Mailing AB', $mailingAB['name']);
    $this->assertSame('Draft', $mailingAB['status']);

    // Read
    $read = MailingAB::get(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute()
      ->first();

    $this->assertSame('Test Mailing AB', $read['name']);

    // Update
    MailingAB::update(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->addValue('name', 'Updated Mailing AB')
      ->execute();

    $updated = MailingAB::get(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute()
      ->first();

    $this->assertSame('Updated Mailing AB', $updated['name']);

    // Delete
    MailingAB::delete(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute();

    $deleted = MailingAB::get(FALSE)
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute();

    $this->assertCount(0, $deleted);
  }

  public function testDeletingMailingClearsReference(): void {
    $mailingIDs = [];
    foreach (['a', 'b', 'c'] as $mkey) {
      $mailingIDs[$mkey] = $this->createTestRecord('Mailing', ['name' => "Test Mailing AB $mkey"])['id'];
    }
    $mailingAB = $this->createTestRecord('MailingAB', [
      'name' => 'Test Mailing AB',
      'mailing_id_a' => $mailingIDs['a'],
      'mailing_id_b' => $mailingIDs['b'],
      'mailing_id_c' => $mailingIDs['c'],
    ]);

    Mailing::delete(FALSE)
      ->addWhere('id', '=', $mailingIDs['c'])
      ->execute();

    $mailingAB = MailingAB::get(FALSE)
      ->addSelect('mailing_id_a', 'mailing_id_b', 'mailing_id_c')
      ->addWhere('id', '=', $mailingAB['id'])
      ->execute()
      ->single();
    $this->assertSame($mailingIDs['a'], $mailingAB['mailing_id_a']);
    $this->assertSame($mailingIDs['b'], $mailingAB['mailing_id_b']);
    $this->assertNull($mailingAB['mailing_id_c']);
  }

}
