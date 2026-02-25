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
use Civi\Api4\EntityTag;
use Civi\Api4\Individual;
use Civi\Api4\Tag;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class TagTest extends Api4TestBase implements TransactionalInterface {

  public function testTagFilter(): void {
    // Ensure bypassing permissions works correctly by giving none to the logged-in user
    $cid = $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    $conTag = Tag::create(FALSE)
      ->addValue('name', uniqid('con'))
      ->addValue('used_for', 'civicrm_contact')
      ->addValue('color', '#cccccc')
      ->execute()->first();
    $tagChild = Tag::create(FALSE)
      ->addValue('name', uniqid('child'))
      ->addValue('parent_id', $conTag['id'])
      ->execute()->first();
    $tagSubChild = Tag::create(FALSE)
      ->addValue('name', uniqid('child'))
      ->addValue('parent_id', $tagChild['id'])
      ->execute()->first();
    $tagSet = Tag::create(FALSE)
      ->addValue('name', uniqid('set'))
      ->addValue('used_for', 'civicrm_contact')
      ->addValue('is_tagset', TRUE)
      ->execute()->first();
    $setChild = Tag::create(FALSE)
      ->addValue('name', uniqid('child'))
      ->addValue('parent_id', $tagSet['id'])
      ->execute()->first();
    $this->assertEquals($cid, $conTag['created_id']);
    $this->assertEquals($cid, $setChild['created_id']);

    $contact1 = Contact::create(FALSE)
      ->execute()->first();
    $contact2 = Contact::create(FALSE)
      ->execute()->first();
    EntityTag::create(FALSE)
      ->addValue('entity_id', $contact1['id'])
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('tag_id', $tagSubChild['id'])
      ->execute();
    EntityTag::create(FALSE)
      ->addValue('entity_id', $contact2['id'])
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('tag_id', $setChild['id'])
      ->execute();

    $shouldReturnContact1 = Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('tags:name', 'IN', [$conTag['name']])
      ->execute();
    $this->assertCount(1, $shouldReturnContact1);
    $this->assertEquals($contact1['id'], $shouldReturnContact1->first()['id']);

    $shouldReturnContact2 = Individual::get(FALSE)
      ->addSelect('id')
      ->addWhere('tags', 'IN', [$setChild['id']])
      ->execute();
    $this->assertCount(1, $shouldReturnContact2);
    $this->assertEquals($contact2['id'], $shouldReturnContact2->first()['id']);
  }

  public function testEntityTagGetFields(): void {
    $this->saveTestRecords('Tag', [
      'records' => [
        ['name' => 'c-1', 'used_for' => 'civicrm_contact'],
        ['name' => 'c-2', 'used_for:name' => 'Contact'],
        ['name' => 'a-1', 'used_for:name' => 'Activity'],
        ['name' => 'tagset', 'used_for' => 'civicrm_activity', 'is_tagset' => TRUE],
      ],
    ]);

    $getFields = EntityTag::getFields(FALSE)
      ->addWhere('name', '=', 'tag_id')
      ->setLoadOptions(TRUE);

    // No filter
    $options = $getFields
      ->execute()[0]['options'];
    $this->assertContains('c-1', $options);
    $this->assertContains('c-2', $options);
    $this->assertContains('a-1', $options);
    $this->assertNotContains('tagset', $options);

    // Filter: Contact
    $options = $getFields
      ->setValues(['entity_table:name' => 'Contact'])
      ->execute()[0]['options'];
    $this->assertContains('c-1', $options);
    $this->assertContains('c-2', $options);
    $this->assertNotContains('a-1', $options);
    $this->assertNotContains('tagset', $options);

    // Filter: Activity
    $options = $getFields
      ->setValues(['entity_table:name' => 'Activity'])
      ->execute()[0]['options'];
    $this->assertNotContains('c-1', $options);
    $this->assertNotContains('c-2', $options);
    $this->assertContains('a-1', $options);
    $this->assertNotContains('tagset', $options);
  }

  public function testTagSaving(): void {
    // Ensure bypassing permissions works correctly by giving none to the logged-in user
    $cid = $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    $tagA = Tag::create(FALSE)
      ->addValue('name', uniqid('A'))
      ->addValue('used_for', 'civicrm_contact')
      ->addValue('color', '#cccccc')
      ->execute()->first();
    $tagB = Tag::create(FALSE)
      ->addValue('name', uniqid('B'))
      ->addValue('used_for', 'civicrm_contact')
      ->execute()->first();
    $tagC = Tag::create(FALSE)
      ->addValue('name', uniqid('C'))
      ->addValue('used_for', 'civicrm_contact')
      ->execute()->first();
    $tagD = Tag::create(FALSE)
      ->addValue('name', uniqid('D'))
      ->addValue('used_for', 'civicrm_contact')
      ->execute()->first();

    // create contact1 with two tags
    $contact = Contact::create(FALSE)
      ->addValue('tags', [$tagA['id'], $tagB['id']])
      ->execute()->first();

    $contactTags = (array) EntityTag::get(FALSE)
      ->addWhere('entity_id', '=', $contact['id'])
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->execute();

    $this->assertCount(2, $contactTags);

    // change to 3 different tags
    Contact::update(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addValue('tags', [$tagB['id'], $tagC['id'], $tagD['id']])
      ->execute();

    $contactTags = (array) EntityTag::get(FALSE)
      ->addWhere('entity_id', '=', $contact['id'])
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->execute();

    $this->assertCount(3, $contactTags);
    // ensure tag A has been removed
    $this->assertCount(0, array_filter($contactTags, fn ($et) => $et['tag_id'] === $tagA['id']));

    // set tags by name
    Contact::update(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addValue('tags:name', [$tagA['name'], $tagB['name']])
      ->execute();

    $contactTags = (array) EntityTag::get(FALSE)
      ->addWhere('entity_id', '=', $contact['id'])
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->execute();

    $this->assertCount(2, $contactTags);
    $this->assertSame([$tagA['id'], $tagB['id']], array_map(fn ($et) => $et['tag_id'], $contactTags));
  }

}
