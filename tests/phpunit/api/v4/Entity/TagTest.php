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

  public function testMergeTags(): void {
    $this->createLoggedInUser();

    $tagA = Tag::create(FALSE)
      ->addValue('name', uniqid('tagA'))
      ->addValue('used_for', 'civicrm_contact')
      ->execute()->first();
    $tagB = Tag::create(FALSE)
      ->addValue('name', uniqid('tagB'))
      ->addValue('used_for', 'civicrm_activity')
      ->execute()->first();
    $tagBChild = Tag::create(FALSE)
      ->addValue('name', uniqid('tagBChild'))
      ->addValue('parent_id', $tagB['id'])
      ->execute()->first();

    $contact = Contact::create(FALSE)->execute()->first();
    EntityTag::create(FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('tag_id', $tagA['id'])
      ->execute();
    EntityTag::create(FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('tag_id', $tagB['id'])
      ->execute();

    $result = Tag::merge(FALSE)
      ->setTargetId($tagA['id'])
      ->setTagIds([$tagB['id']])
      ->setLabel('Merged Label')
      ->execute()->first();
    $this->assertEquals($tagA['id'], $result['id']);
    $this->assertEquals([$tagB['id']], $result['merged']);

    $mergedTag = Tag::get(FALSE)
      ->addWhere('id', '=', $tagA['id'])
      ->addSelect('label', 'used_for')
      ->execute()->first();
    $this->assertEquals('Merged Label', $mergedTag['label']);
    $this->assertEqualsCanonicalizing(['civicrm_contact', 'civicrm_activity'], $mergedTag['used_for']);

    // Tag B no longer exists.
    $this->assertCount(0, Tag::get(FALSE)->addWhere('id', '=', $tagB['id'])->execute());

    // Tag B's child is reparented onto the target tag.
    $child = Tag::get(FALSE)
      ->addWhere('id', '=', $tagBChild['id'])
      ->addSelect('parent_id')
      ->execute()->first();
    $this->assertEquals($tagA['id'], $child['parent_id']);

    // The contact's two entity-tags are deduped down to one, pointing at the target.
    $entityTags = EntityTag::get(FALSE)
      ->addWhere('entity_id', '=', $contact['id'])
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->execute();
    $this->assertCount(1, $entityTags);
    $this->assertEquals($tagA['id'], $entityTags->first()['tag_id']);
  }

  public function testMergeReservedTagRequiresPermission(): void {
    $this->createLoggedInUser();

    $tagA = Tag::create(FALSE)->addValue('name', uniqid('tagA'))->execute()->first();
    $reserved = Tag::create(FALSE)
      ->addValue('name', uniqid('reserved'))
      ->addValue('is_reserved', TRUE)
      ->execute()->first();

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM'];

    try {
      Tag::merge()
        ->setTargetId($tagA['id'])
        ->setTagIds([$reserved['id']])
        ->execute();
      $this->fail('Expected UnauthorizedException merging a reserved tag without the "administer reserved tags" permission.');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      // Expected.
    }

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM', 'administer reserved tags'];

    $result = Tag::merge()
      ->setTargetId($tagA['id'])
      ->setTagIds([$reserved['id']])
      ->execute()->first();
    $this->assertEquals($tagA['id'], $result['id']);
  }

}
