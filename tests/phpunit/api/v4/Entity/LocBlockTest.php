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

/**
 * @group headless
 */
class LocBlockTest extends Api4TestBase {

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

    LocBlock::update(FALSE)
      ->addWhere('id', '=', $locBlock1['id'])
      ->addValue('email_id.email', '')
      ->addValue('email_2_id.email', '')
      ->execute();

    $email1 = Email::get(FALSE)
      ->addSelect('id')
      ->addWhere('id', 'IN', [$locBlock1['email_id'], $locBlock1['email_2_id']])
      ->execute()->column('id');

    $this->assertEquals([$locBlock1['email_id']], (array) $email1);
  }

}
