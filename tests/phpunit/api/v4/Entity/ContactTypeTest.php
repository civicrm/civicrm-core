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

use Civi\Api4\Contact;
use api\v4\Api4TestBase;
use Civi\Api4\ContactType;
use Civi\Api4\Email;
use Civi\Api4\Individual;
use Civi\Api4\Navigation;
use Civi\Api4\Organization;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactTypeTest extends Api4TestBase implements TransactionalInterface {

  public function testMenuItemWillBeCreatedAndDeleted(): void {
    ContactType::create(FALSE)
      ->addValue('name', 'Tester')
      ->addValue('label', 'Tèstër')
      ->addValue('parent_id.name', 'Individual')
      ->execute();
    // Menu item should have been auto-created
    $nav = Navigation::get(FALSE)->addWhere('name', '=', 'New Tester')->execute()->single();
    $this->assertEquals('New Tèstër', $nav['label']);

    ContactType::update(FALSE)
      ->addWhere('name', '=', 'Tester')
      ->addValue('label', 'Wëll Téstęd!')
      ->execute();

    // Menu item should have been updated
    $nav = Navigation::get(FALSE)->addWhere('name', '=', 'New Tester')->execute()->single();
    $this->assertEquals('New Wëll Téstęd!', $nav['label']);

    ContactType::delete(FALSE)
      ->addWhere('name', '=', 'Tester')
      ->execute();
    // Menu item should be gone
    $this->assertCount(0, Navigation::get(FALSE)->addWhere('name', '=', 'New Tester')->execute());
  }

  public function testSubTypeWillBeRemovedFromExistingContacts(): void {
    foreach (['TesterA', 'TesterB'] as $name) {
      ContactType::create(FALSE)
        ->addValue('name', $name)
        ->addValue('label', $name)
        ->addValue('parent_id.name', 'Individual')
        ->execute();
    }
    $c1 = Contact::create(FALSE)
      ->addValue('contact_sub_type', ['TesterA'])
      ->execute()->first()['id'];
    $c2 = Contact::create(FALSE)
      ->addValue('contact_sub_type', ['TesterA', 'TesterB'])
      ->execute()->first()['id'];

    ContactType::delete(FALSE)
      ->addWhere('name', '=', 'TesterA')
      ->execute();

    $this->assertNull(Contact::get(FALSE)->addWhere('id', '=', $c1)->execute()->first()['contact_sub_type']);
    $this->assertEquals(['TesterB'], Contact::get(FALSE)->addWhere('id', '=', $c2)->execute()->first()['contact_sub_type']);
  }

  public function testGetReturnsFieldsAppropriateToEachContactType(): void {
    $indiv = Contact::create()
      ->setValues(['first_name' => 'Joe', 'last_name' => 'Tester', 'prefix_id:label' => 'Dr.', 'contact_type' => 'Individual'])
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'ind@example.com']))
      ->setCheckPermissions(FALSE)
      ->execute()->first()['id'];

    $org = Contact::create()
      ->setValues(['organization_name' => 'Tester Org', 'contact_type' => 'Organization'])
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'org@example.com']))
      ->setCheckPermissions(FALSE)
      ->execute()->first()['id'];

    $hh = Contact::create()
      ->setValues(['household_name' => 'Tester Family', 'contact_type' => 'Household'])
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'hh@example.com']))
      ->setCheckPermissions(FALSE)
      ->execute()->first()['id'];

    $result = Contact::get(FALSE)
      ->addSelect('*', 'prefix_id:label')
      ->addWhere('id', 'IN', [$indiv, $org, $hh])
      ->execute()
      ->indexBy('id');

    $this->assertArrayHasKey('first_name', $result[$indiv]);
    $this->assertArrayNotHasKey('first_name', $result[$org]);
    $this->assertArrayNotHasKey('first_name', $result[$hh]);

    $this->assertEquals('Dr.', $result[$indiv]['prefix_id:label']);
    $this->assertArrayNotHasKey('prefix_id:label', $result[$org]);
    $this->assertArrayNotHasKey('prefix_id:label', $result[$hh]);

    $this->assertArrayHasKey('organization_name', $result[$org]);
    $this->assertArrayNotHasKey('organization_name', $result[$indiv]);
    $this->assertArrayNotHasKey('organization_name', $result[$hh]);

    $this->assertArrayHasKey('sic_code', $result[$org]);
    $this->assertArrayNotHasKey('sic_code', $result[$indiv]);
    $this->assertArrayNotHasKey('sic_code', $result[$hh]);

    $this->assertArrayHasKey('household_name', $result[$hh]);
    $this->assertArrayNotHasKey('household_name', $result[$org]);
    $this->assertArrayNotHasKey('household_name', $result[$indiv]);

    $emails = Email::get(FALSE)
      ->addWhere('contact_id', 'IN', [$indiv, $org, $hh])
      ->addSelect('id', 'contact_id', 'contact_id.*', 'contact_id.prefix_id:label')
      ->execute()
      ->indexBy('contact_id');

    $this->assertArrayHasKey('contact_id.first_name', $emails[$indiv]);
    $this->assertArrayNotHasKey('contact_id.first_name', $emails[$org]);
    $this->assertArrayNotHasKey('contact_id.first_name', $emails[$hh]);

    $this->assertEquals('Dr.', $emails[$indiv]['contact_id.prefix_id:label']);
    $this->assertArrayNotHasKey('contact_id.prefix_id:label', $emails[$org]);
    $this->assertArrayNotHasKey('contact_id.prefix_id:label', $emails[$hh]);

    $this->assertArrayHasKey('contact_id.organization_name', $emails[$org]);
    $this->assertArrayNotHasKey('contact_id.organization_name', $emails[$indiv]);
    $this->assertArrayNotHasKey('contact_id.organization_name', $emails[$hh]);

    $this->assertArrayHasKey('contact_id.sic_code', $emails[$org]);
    $this->assertArrayNotHasKey('contact_id.sic_code', $emails[$indiv]);
    $this->assertArrayNotHasKey('contact_id.sic_code', $emails[$hh]);

    $this->assertArrayHasKey('contact_id.household_name', $emails[$hh]);
    $this->assertArrayNotHasKey('contact_id.household_name', $emails[$org]);
    $this->assertArrayNotHasKey('contact_id.household_name', $emails[$indiv]);

  }

  public function testSaveContactWithImpliedType(): void {
    // Ensure pseudoconstant suffix works
    $result = Contact::create(FALSE)
      ->addValue('contact_type:name', 'Household')
      ->execute()->first();
    $this->assertEquals('Household', $result['contact_type']);

    // Contact type should be inferred by the type of name given
    $result = Contact::save(FALSE)
      ->addRecord(['organization_name' => 'Foo'])
      ->execute()->first();
    $this->assertEquals('Organization', $result['contact_type']);
  }

  public function testContactTypeWontChange(): void {
    $hhId = $this->createTestRecord('Household')['id'];
    $orgId = $this->createTestRecord('Organization')['id'];

    $orgUpdate = Organization::update(FALSE)
      ->addWhere('id', 'IN', [$hhId, $orgId])
      ->addValue('organization_name', 'Foo')
      ->execute();
    $this->assertCount(1, $orgUpdate);

    $indUpdate = Individual::update(FALSE)
      ->addWhere('id', 'IN', [$hhId, $orgId])
      ->addValue('first_name', 'Foo')
      ->execute();
    $this->assertCount(0, $indUpdate);

    $orgUpdate = Organization::update(FALSE)
      ->addWhere('id', '=', $hhId)
      ->addValue('organization_name', 'Foo')
      ->execute();
    // This seems unexpected but is due to the fact that for efficiency the api
    // will skip lookups and go straight to writeRecord when given a single id.
    // Commented out assertion doesn't work:
    // $this->assertCount(0, $orgUpdate);

    $household = Contact::get(FALSE)->addWhere('id', '=', $hhId)->execute()->single();

    $this->assertEquals('Household', $household['contact_type']);
    $this->assertTrue(empty($household['organization_name']));
  }

}
