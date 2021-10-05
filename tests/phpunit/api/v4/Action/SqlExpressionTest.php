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


namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\Contact;
use Civi\Api4\Email;

/**
 * @group headless
 */
class SqlExpressionTest extends UnitTestCase {

  public function testSelectNull() {
    Contact::create()->addValue('first_name', 'bob')->setCheckPermissions(FALSE)->execute();
    $result = Contact::get()
      ->addSelect('NULL AS nothing', 'NULL', 'NULL AS b*d char', 'first_name')
      ->addWhere('first_name', '=', 'bob')
      ->setLimit(1)
      ->execute()
      ->first();
    $this->assertNull($result['nothing']);
    $this->assertNull($result['NULL']);
    $this->assertNull($result['b_d_char']);
    $this->assertEquals('bob', $result['first_name']);
    $this->assertArrayNotHasKey('b*d char', $result);
  }

  public function testSelectNumbers() {
    Contact::create()->addValue('first_name', 'bob')->setCheckPermissions(FALSE)->execute();
    $result = Contact::get()
      ->addSelect('first_name', 123, 45.678, '-55 AS neg')
      ->addWhere('first_name', '=', 'bob')
      ->setLimit(1)
      ->execute()
      ->first();
    $this->assertEquals('bob', $result['first_name']);
    $this->assertEquals('123', $result['123']);
    $this->assertEquals('-55', $result['neg']);
    $this->assertEquals('45.678', $result['45_678']);
  }

  public function testSelectStrings() {
    Contact::create()->addValue('first_name', 'bob')->setCheckPermissions(FALSE)->execute();
    $result = Contact::get()
      ->addSelect('first_name')
      ->addSelect('"hello world" AS hi')
      ->addSelect('"can\'t \"quote\"" AS quot')
      ->addWhere('first_name', '=', 'bob')
      ->setLimit(1)
      ->execute()
      ->first();
    $this->assertEquals('bob', $result['first_name']);
    $this->assertEquals('hello world', $result['hi']);
    $this->assertEquals('can\'t "quote"', $result['quot']);
  }

  public function testSelectAlias() {
    try {
      Contact::get()
        ->addSelect('first_name AS bob')
        ->execute();
    }
    catch (\API_Exception $e) {
      $msg = $e->getMessage();
    }
    $this->assertStringContainsString('alias', $msg);
    try {
      Contact::get()
        ->addSelect('55 AS sort_name')
        ->execute();
    }
    catch (\API_Exception $e) {
      $msg = $e->getMessage();
    }
    $this->assertStringContainsString('existing field name', $msg);
    Contact::get()
      ->addSelect('55 AS ok_alias')
      ->execute();
  }

  public function testSelectEquations() {
    $contact = Contact::create(FALSE)->addValue('first_name', 'bob')
      ->addChain('email', Email::create()->setValues(['email' => 'hello@example.com', 'contact_id' => '$id']))
      ->execute()->first();
    $result = Email::get(FALSE)
      ->setSelect([
        'IF((contact_id.first_name = "bob"), "Yes", "No") AS is_bob',
        'IF((contact_id.first_name != "fred"), "No", "Yes") AS is_fred',
        '(5 * 11)',
        '(5 > 11) AS five_greater_eleven',
        '(5 <= 11) AS five_less_eleven',
        '(1 BETWEEN 0 AND contact_id) AS is_between',
        // These fields don't exist
        '(illegal * stuff) AS illegal_stuff',
        // This field will be null
        '(hold_date + 5) AS null_plus_five',
      ])
      ->addWhere('contact_id', '=', $contact['id'])
      ->setLimit(1)
      ->execute()
      ->first();
    $this->assertEquals('Yes', $result['is_bob']);
    $this->assertEquals('No', $result['is_fred']);
    $this->assertEquals('55', $result['5_11']);
    $this->assertFalse($result['five_greater_eleven']);
    $this->assertTrue($result['five_less_eleven']);
    $this->assertTrue($result['is_between']);
    $this->assertArrayNotHasKey('illegal_stuff', $result);
    $this->assertEquals('5', $result['null_plus_five']);
  }

}
