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
use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\Individual;
use Civi\Api4\Navigation;
use Civi\Api4\Organization;
use Civi\Api4\Utils\CoreUtil;
use Civi\Schema\EntityRepository;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactTypeTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Set by tests which change contact types, so cached metadata is cleared afterwards.
   *
   * @var bool
   */
  private bool $clearCaches = FALSE;

  public function tearDown(): void {
    parent::tearDown();
    if ($this->clearCaches) {
      \Civi::cache('contactTypes')->clear();
      \Civi::cache('metadata')->clear();
    }
  }

  public function testMetadata(): void {
    $this->assertSame('parent_id', CoreUtil::getInfoItem('ContactType', 'parent_field'));
  }

  public function testEntityFieldsEventDispatchedForIndividual(): void {
    unset(\Civi::$statics['civi.entity.fields']);
    $contactListenerCalled = FALSE;
    $individualListenerCalled = FALSE;
    $genericListenerEntities = [];

    $dispatcher = \Civi::dispatcher();
    $contactListener = function($e) use (&$contactListenerCalled) {
      $contactListenerCalled = TRUE;
    };
    $individualListener = function($e) use (&$individualListenerCalled) {
      $individualListenerCalled = TRUE;
    };
    $genericListener = function($e) use (&$genericListenerEntities) {
      $genericListenerEntities[] = $e->entity;
    };

    $dispatcher->addListener('civi.entity.fields::Contact', $contactListener);
    $dispatcher->addListener('civi.entity.fields::Individual', $individualListener);
    $dispatcher->addListener('civi.entity.fields', $genericListener);

    try {
      \Civi::entity('Individual')->getFields();

      // Parent listeners are called because Individual loads its parent's fields first
      $this->assertTrue($contactListenerCalled);
      $this->assertContains('Contact', $genericListenerEntities);

      // Facade-specific listeners are called too
      $this->assertTrue($individualListenerCalled);
      $this->assertContains('Individual', $genericListenerEntities);
    }
    finally {
      $dispatcher->removeListener('civi.entity.fields::Contact', $contactListener);
      $dispatcher->removeListener('civi.entity.fields::Individual', $individualListener);
      $dispatcher->removeListener('civi.entity.fields', $genericListener);
      unset(\Civi::$statics['civi.entity.fields']);
    }
  }

  /**
   * The contact-type facades' title, description and icon follow the editable contact type.
   */
  public function testFacadeInfoFollowsContactType(): void {
    $this->clearCaches = TRUE;
    ContactType::update(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->addValue('label', 'Person')
      ->addValue('icon', 'fa-child')
      ->execute();
    \Civi::cache('metadata')->clear();

    $this->assertSame('Person', CoreUtil::getInfoItem('Individual', 'title'));
    // There's no plural label, so the plural is still the (translatable) core string
    $this->assertSame('Individuals', CoreUtil::getInfoItem('Individual', 'title_plural'));
    $this->assertSame('Contacts of type Person.', CoreUtil::getInfoItem('Individual', 'description'));
    $this->assertSame('fa-child', CoreUtil::getInfoItem('Individual', 'icon'));
    // The API name is unchanged
    $this->assertSame('Individual', CoreUtil::getInfoItem('Individual', 'name'));
  }

  /**
   * A Contact access rule which runs after core's own check also applies to writes through a contact type.
   */
  public function testContactAccessRuleAppliesToContactTypes(): void {
    $contactID = $this->createTestRecord('Individual')['id'];
    $update = Individual::update(FALSE);
    $create = Individual::create(FALSE);
    $this->assertTrue(CoreUtil::checkAccessRecord($update, ['id' => $contactID, 'first_name' => 'Renamed']));
    $this->assertTrue(CoreUtil::checkAccessRecord($create, ['first_name' => 'New']));

    $denyListener = fn(AuthorizeRecordEvent $e) => $e->setAuthorized(FALSE);
    \Civi::dispatcher()->addListener('civi.api4.authorizeRecord::Contact', $denyListener, -10);
    try {
      $this->assertFalse(CoreUtil::checkAccessRecord($update, ['id' => $contactID, 'first_name' => 'Renamed']));
      $this->assertFalse(CoreUtil::checkAccessRecord($create, ['first_name' => 'New']));
    }
    finally {
      \Civi::dispatcher()->removeListener('civi.api4.authorizeRecord::Contact', $denyListener);
    }
  }

  /**
   * Another facade of Contact, e.g. from an extension, doesn't change which contact type a field belongs to.
   */
  public function testTypeSpecificFieldsIgnoreOtherContactFacades(): void {
    $addFacade = function($e) {
      $e->entityTypes['TestDonor'] = ['name' => 'TestDonor', 'facade_of' => 'Contact', 'where' => ['contact_type' => 'Individual']];
    };
    \Civi::dispatcher()->addListener('hook_civicrm_entityTypes', $addFacade);
    EntityRepository::flush();
    unset(\Civi::$statics['CRM_Contact_BAO_ContactType']['getTypeSpecificFields']);
    try {
      $this->assertContains('TestDonor', EntityRepository::getFacadesOf('Contact'));
      $typeSpecificFields = \CRM_Contact_BAO_ContactType::getTypeSpecificFields();
      $this->assertSame('Individual', $typeSpecificFields['first_name']);
      $this->assertSame('Organization', $typeSpecificFields['organization_name']);
      $this->assertSame('Household', $typeSpecificFields['household_name']);
    }
    finally {
      \Civi::dispatcher()->removeListener('hook_civicrm_entityTypes', $addFacade);
      EntityRepository::flush();
      unset(\Civi::$statics['CRM_Contact_BAO_ContactType']['getTypeSpecificFields']);
    }
  }

  public function testRefCountForContactType(): void {
    $contactID = $this->createTestRecord('Individual')['id'];
    $this->createTestRecord('Email', ['contact_id' => $contactID]);
    // A dynamic reference (entity_table + entity_id)
    $this->createTestRecord('Note', ['entity_table' => 'civicrm_contact', 'entity_id' => $contactID]);
    $contactRefs = CoreUtil::getRefCount('Contact', $contactID);
    $refFields = array_map(fn($ref) => $ref['name'] . '.' . $ref['field'], $contactRefs);
    $this->assertContains('Email.contact_id', $refFields);
    $this->assertContains('Note.entity_id', $refFields);
    $this->assertSame($contactRefs, CoreUtil::getRefCount('Individual', $contactID));
  }

  public function testMenuItemWillBeCreatedAndDeleted(): void {
    ContactType::create(FALSE)
      ->addValue('name', 'Tester')
      ->addValue('label', 'Tèstër')
      ->addValue('parent_id.name', 'Individual')
      ->execute();
    // Menu item should have been auto-created
    $nav = $this->getTestRecord('Navigation', ['name' => 'New Tester']);
    $this->assertSame('New Tèstër', $nav['label']);

    ContactType::update(FALSE)
      ->addWhere('name', '=', 'Tester')
      ->addValue('label', 'Wëll Téstęd!')
      ->execute();

    // Menu item should have been updated
    $nav = $this->getTestRecord('Navigation', ['name' => 'New Tester']);
    $this->assertSame('New Wëll Téstęd!', $nav['label']);

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

    $this->assertNull($this->getTestRecord('Contact', $c1)['contact_sub_type']);
    $this->assertEquals(['TesterB'], $this->getTestRecord('Contact', $c2)['contact_sub_type']);
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

    $this->assertSame('Dr.', $result[$indiv]['prefix_id:label']);
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

    $this->assertSame('Dr.', $emails[$indiv]['contact_id.prefix_id:label']);
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
    $this->assertSame('Household', $result['contact_type']);

    // Contact type should be inferred by the type of name given
    $result = Contact::save(FALSE)
      ->addRecord(['organization_name' => 'Foo'])
      ->execute()->first();
    $this->assertSame('Organization', $result['contact_type']);
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

    $household = $this->getTestRecord('Contact', $hhId);

    $this->assertSame('Household', $household['contact_type']);
    $this->assertTrue(empty($household['organization_name']));
  }

  public function testDepth(): void {
    ContactType::delete(FALSE)
      ->addWhere('parent_id', 'IS NOT NULL')
      ->execute();

    $this->saveTestRecords('ContactType', [
      'records' => [
        ['parent_id.name' => 'Organization', 'name' => 'ZOrg'],
        ['parent_id.name' => 'Individual', 'name' => '1Ind'],
        ['parent_id.name' => 'Organization', 'name' => '1Org'],
        ['parent_id.name' => 'Individual', 'name' => '2Ind'],
      ],
    ]);

    $result = ContactType::get(FALSE)
      ->addSelect('name', '_depth')
      ->addOrderBy('name', 'ASC')
      ->setLimit(5)
      ->setOffset(1)
      ->setDebug(TRUE)
      ->execute();

    $expected = [
      'Individual' => 0,
      '1Ind' => 1,
      '2Ind' => 1,
      'Organization' => 0,
      '1Org' => 1,
    ];
    $this->assertEquals($expected, $result->column('_depth', 'name'));
    $this->assertTrue($result->debug['useCache']);
  }

  public function testCachedGet(): void {
    $this->saveTestRecords('ContactType', [
      'records' => [
        ['parent_id.name' => 'Individual', 'name' => 'CachedSubtype', 'label' => 'Cached Subtype'],
      ],
    ]);

    $result = ContactType::get(FALSE)
      ->addSelect('id', 'name', 'label', 'icon', 'parent_id', 'parent_id.name', 'parent_id.label')
      ->addWhere('parent_id.name', '=', 'Individual')
      ->addWhere('name', '=', 'CachedSubtype')
      ->setDebug(TRUE)
      ->execute();

    $this->assertTrue($result->debug['useCache']);
    $this->assertCount(1, $result);
    $row = $result->first();
    $this->assertSame('CachedSubtype', $row['name']);
    $this->assertSame('Cached Subtype', $row['label']);
    $this->assertNull($row['icon']);
    $this->assertSame('Individual', $row['parent_id.name']);
    $this->assertSame('Individual', $row['parent_id.label']);
  }

}
