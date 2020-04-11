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
 * $Id$
 *
 */


namespace api\v4\Entity;

use Civi\Api4\Contact;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ContactTypeTest extends UnitTestCase {

  public function testContactGetReturnsFieldsAppropriateToEachContactType() {
    $indiv = Contact::create()
      ->setValues(['first_name' => 'Joe', 'last_name' => 'Tester', 'contact_type' => 'Individual'])
      ->setCheckPermissions(FALSE)
      ->execute()->first()['id'];

    $org = Contact::create()
      ->setValues(['organization_name' => 'Tester Org', 'contact_type' => 'Organization'])
      ->setCheckPermissions(FALSE)
      ->execute()->first()['id'];

    $hh = Contact::create()
      ->setValues(['household_name' => 'Tester Family', 'contact_type' => 'Household'])
      ->setCheckPermissions(FALSE)
      ->execute()->first()['id'];

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', 'IN', [$indiv, $org, $hh])
      ->execute()
      ->indexBy('id');

    $this->assertArrayHasKey('first_name', $result[$indiv]);
    $this->assertArrayNotHasKey('first_name', $result[$org]);
    $this->assertArrayNotHasKey('first_name', $result[$hh]);

    $this->assertArrayHasKey('organization_name', $result[$org]);
    $this->assertArrayNotHasKey('organization_name', $result[$indiv]);
    $this->assertArrayNotHasKey('organization_name', $result[$hh]);

    $this->assertArrayHasKey('sic_code', $result[$org]);
    $this->assertArrayNotHasKey('sic_code', $result[$indiv]);
    $this->assertArrayNotHasKey('sic_code', $result[$hh]);

    $this->assertArrayHasKey('household_name', $result[$hh]);
    $this->assertArrayNotHasKey('household_name', $result[$org]);
    $this->assertArrayNotHasKey('household_name', $result[$indiv]);
  }

}
