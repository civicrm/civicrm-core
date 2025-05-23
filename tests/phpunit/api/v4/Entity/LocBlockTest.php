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
use Civi\Api4\Email;
use Civi\Api4\LocBlock;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class LocBlockTest extends Api4TestBase implements TransactionalInterface {

  /**
   * @throws \CRM_Core_Exception
   */
  public function testSaveWithJoins(): void {
    $locBlock1 = LocBlock::create(FALSE)
      ->addValue('email_id.email', 'first@e.mail')
      ->addValue('email_2_id.email', 'second@e.mail')
      ->execute()->first();

    $locBlock = LocBlock::get(FALSE)
      ->addWhere('id', '=', $locBlock1['id'])
      ->addSelect('email_id', 'email_2_id', 'email_id.email', 'email_2_id.email')
      ->execute()->first();
    $this->assertEquals('first@e.mail', $locBlock['email_id.email']);
    $this->assertEquals('second@e.mail', $locBlock['email_2_id.email']);
    $this->assertEquals($locBlock1['email_id'], $locBlock['email_id']);
    $this->assertEquals($locBlock1['email_2_id'], $locBlock['email_2_id']);

    // Share an email with the 1st block
    $locBlock2 = LocBlock::create(FALSE)
      ->addValue('email_id', $locBlock1['email_id'])
      ->addValue('email_2_id.email', 'third@e.mail')
      ->execute()->first();

    // Void both emails in block 1
    LocBlock::update(FALSE)
      ->addWhere('id', '=', $locBlock1['id'])
      ->addValue('email_id.email', '')
      ->addValue('email_2_id.email', '')
      ->execute();

    // 1 email has been deleted, the other preserved (because it's shared with block 2)
    $email1 = Email::get(FALSE)
      ->addSelect('id')
      ->addWhere('id', 'IN', [$locBlock1['email_id'], $locBlock1['email_2_id']])
      ->execute()->column('id');

    $this->assertEquals([$locBlock1['email_id']], $email1);
  }

  public function testLocBlockWithBlankValues(): void {
    $locBlockId = LocBlock::create(FALSE)
      ->addValue('address_id.street_address', '')
      ->addValue('phone_id.phone', '')
      ->addValue('email_id.email', '')
      ->execute()->first()['id'];

    // Get locBlock
    $locBlock = LocBlock::get(FALSE)
      ->addWhere('id', '=', $locBlockId)
      ->execute()->single();

    $this->assertNotEmpty($locBlock['address_id']);
    $this->assertEmpty($locBlock['phone_id']);
    $this->assertEmpty($locBlock['email_id']);

    // Update with a 2nd blank email & an address
    LocBlock::update(FALSE)
      ->addWhere('id', '=', $locBlockId)
      ->addValue('email2_id.email', '')
      ->addValue('address_id.street_address', '123')
      ->execute();

    $updatedLocBlock = LocBlock::get(FALSE)
      ->addWhere('id', '=', $locBlockId)
      ->addSelect('*', 'address_id.street_address', 'phone_id.phone', 'email_id.email')
      ->execute()->single();

    $this->assertEquals($locBlock['address_id'], $updatedLocBlock['address_id']);
    $this->assertEquals('123', $updatedLocBlock['address_id.street_address']);
    $this->assertNull($updatedLocBlock['phone_id.phone']);
    $this->assertNull($updatedLocBlock['email_id.email']);
  }

}
