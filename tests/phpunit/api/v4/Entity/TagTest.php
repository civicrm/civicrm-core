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
use api\v4\UnitTestCase;
use Civi\Api4\EntityTag;
use Civi\Api4\Tag;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class TagTest extends UnitTestCase implements TransactionalInterface {

  public function testTagFilter() {
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

    $shouldReturnContact2 = Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('tags', 'IN', [$setChild['id']])
      ->execute();
    $this->assertCount(1, $shouldReturnContact2);
    $this->assertEquals($contact2['id'], $shouldReturnContact2->first()['id']);
  }

}
