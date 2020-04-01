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
 *
 */


namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class SqlExpressionTest extends UnitTestCase {

  public function testSelectNull() {
    Contact::create()->addValue('first_name', 'bob')->setCheckPermissions(FALSE)->execute();
    $result = Contact::get()
      ->addSelect('NULL AS nothing', 'NULL', 'NULL AS b*d char', 'first_name AS firsty')
      ->addWhere('first_name', '=', 'bob')
      ->setLimit(1)
      ->execute()
      ->first();
    $this->assertNull($result['nothing']);
    $this->assertNull($result['NULL']);
    $this->assertNull($result['b_d_char']);
    $this->assertEquals('bob', $result['firsty']);
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
      ->addSelect('first_name AS bob')
      ->addSelect('"hello world" AS hi')
      ->addSelect('"can\'t \"quote\"" AS quot')
      ->addWhere('first_name', '=', 'bob')
      ->setLimit(1)
      ->execute()
      ->first();
    $this->assertEquals('bob', $result['bob']);
    $this->assertEquals('hello world', $result['hi']);
    $this->assertEquals('can\'t "quote"', $result['quot']);
  }

}
