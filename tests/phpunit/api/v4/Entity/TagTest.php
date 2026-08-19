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

use Civi\Api4\Afform;
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

  private $afformName = 'apiv4TagTestForm';

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', '=', $this->afformName)->execute();
    parent::tearDown();
  }

  public function testGetTaggedEntities(): void {
    // Scoped to both a real DB-table entity (Contact, via EntityTag) and Afform,
    // which has no EntityTag rows and is only reachable via the alterNonDbTaggableEntities
    // hook - this exercises both code paths through a single tag.
    $tag = Tag::create(FALSE)
      ->addValue('name', uniqid('mixed'))
      ->addValue('used_for', ['civicrm_contact', 'Afform'])
      ->execute()->single();

    $contact = Contact::create(FALSE)->execute()->single();
    EntityTag::create(FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('tag_id', $tag['id'])
      ->execute();

    Afform::create(FALSE)
      ->addValue('name', $this->afformName)
      ->addValue('title', 'Tag Test Form')
      ->addValue('tags', [$tag['name']])
      ->execute();

    $tagged = Tag::getTaggedEntities(FALSE)->addWhere('tag_id', '=', $tag['id'])->execute();
    $this->assertCount(2, $tagged);
    $byTable = $tagged->indexBy('entity_table');
    $this->assertEquals($contact['id'], $byTable['civicrm_contact']['entity_id']);
    $this->assertEquals($this->afformName, $byTable['Afform']['entity_id']);

    // A tag scoped to Afform but with nothing actually tagged should come back empty,
    // not error, and shouldn't pick up the other tag's Afform above.
    $unusedTag = Tag::create(FALSE)
      ->addValue('name', uniqid('unused'))
      ->addValue('used_for', 'Afform')
      ->execute()->single();
    $this->assertCount(0, Tag::getTaggedEntities(FALSE)->addWhere('tag_id', '=', $unusedTag['id'])->execute());

    // A nonexistent tag ID is an empty result, not an error.
    $this->assertCount(0, Tag::getTaggedEntities(FALSE)->addWhere('tag_id', '=', $unusedTag['id'] + 999999)->execute());

    // WHERE supports fetching more than one tag at once via IN, and applies additional
    // filters (here entity_type) on top of the hook/EntityTag-sourced rows.
    $multiTag = Tag::getTaggedEntities(FALSE)
      ->addWhere('tag_id', 'IN', [$tag['id'], $unusedTag['id']])
      ->addWhere('entity_type', '=', 'Afform')
      ->execute();
    $this->assertCount(1, $multiTag);
    $this->assertEquals($this->afformName, $multiTag->first()['entity_id']);
  }

  public function testGetTaggedEntitiesResolvesLabelAndUrl(): void {
    // Same mixed scenario as testGetTaggedEntities, but this checks the display-ready
    // fields (resolved label/url per row) that only get computed when asked for via
    // `select` -- the plain `entity_table`/`entity_id` shape doesn't pay for them.
    $tag = Tag::create(FALSE)
      ->addValue('name', uniqid('summary'))
      ->addValue('used_for', ['civicrm_contact', 'Afform'])
      ->execute()->single();

    $contact = Individual::create(FALSE)
      ->addValue('first_name', 'Rev')
      ->addValue('last_name', 'Lookup')
      ->execute()->single();
    EntityTag::create(FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('tag_id', $tag['id'])
      ->execute();

    Afform::create(FALSE)
      ->addValue('name', $this->afformName)
      ->addValue('title', 'Tag Test Form')
      ->addValue('tags', [$tag['name']])
      ->execute();

    $tagged = Tag::getTaggedEntities(FALSE)->addWhere('tag_id', '=', $tag['id'])->setSelect(['entity_type', 'label', 'url'])->execute();
    $this->assertCount(2, $tagged);
    $byType = $tagged->indexBy('entity_type');
    $this->assertStringContainsString('Lookup', $byType['Contact']['label']);
    $this->assertStringContainsString('cid=' . $contact['id'], $byType['Contact']['url']);
    $this->assertEquals('Tag Test Form', $byType['Afform']['label']);
    $this->assertStringContainsString('afform#/edit/' . $this->afformName, $byType['Afform']['url']);

    // A nonexistent tag ID is an empty result, not an error.
    $this->assertCount(0, Tag::getTaggedEntities(FALSE)->addWhere('tag_id', '=', $tag['id'] + 999999)->setSelect(['label', 'url'])->execute());
  }

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

}
