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

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\Individual;
use Civi\Api4\RelationshipCache;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class GetLinksTest extends Api4TestBase implements TransactionalInterface {

  public function testContactLinks(): void {
    $links = Contact::getLinks(FALSE)
      ->addWhere('api_action', '=', 'create')
      ->execute()->indexBy('path');
    $this->assertEquals('Add Individual', $links['civicrm/contact/add?reset=1&ct=Individual']['text']);
    $this->assertEquals('fa-user', $links['civicrm/contact/add?reset=1&ct=Individual']['icon']);
    $this->assertEquals('Add Organization', $links['civicrm/contact/add?reset=1&ct=Organization']['text']);
    $this->assertEquals('Add Household', $links['civicrm/contact/add?reset=1&ct=Household']['text']);
    $this->assertEquals(['contact_type' => 'Household'], $links['civicrm/contact/add?reset=1&ct=Household']['api_values']);

    $links = Contact::getLinks(FALSE)
      ->addWhere('ui_action', 'IN', ['view', 'update', 'delete'])
      ->execute()->indexBy('ui_action');
    $this->assertEquals('', $links['view']['target']);
    $this->assertEquals('', $links['update']['target']);
    $this->assertEquals('crm-popup', $links['delete']['target']);
  }

  public function testIndividualLinks(): void {
    // Add some individual contact types
    foreach (['Squirrel', 'Chipmunk', 'Rabbit'] as $type) {
      ContactType::create(FALSE)
        ->addValue('label', $type)
        ->addValue('name', substr($type, 0, 3))
        ->addValue('icon', strtolower("fa-$type"))
        ->addValue('parent_id:name', 'Individual')
        ->execute();
    }
    // Red herring belongs to Organization not Individual
    ContactType::create(FALSE)
      ->addValue('label', 'Herring')
      ->addValue('name', 'Her')
      ->addValue('icon', "fa-herring")
      ->addValue('parent_id:name', 'Organization')
      ->execute();
    $links = Individual::getLinks(FALSE)
      ->addWhere('api_action', '=', 'create')
      ->execute();
    $this->assertStringContainsString('ct=Individual', $links[0]['path']);
    $this->assertEquals('Add Individual', $links[0]['text']);
    $this->assertEquals(0, $links[0]['weight']);
    $this->assertNull($links[0]['api_values']);
    $links = $links->indexBy('text');
    $this->assertEquals('fa-squirrel', $links['Add Squirrel']['icon']);
    $this->assertGreaterThan($links['Add Rabbit']['weight'], $links['Add Squirrel']['weight']);
    $this->assertGreaterThan($links['Add Chipmunk']['weight'], $links['Add Rabbit']['weight']);
    $this->assertStringContainsString('ct=Individual', $links['Add Squirrel']['path']);
    $this->assertStringContainsString('cst=Squ', $links['Add Squirrel']['path']);
    $this->assertArrayNotHasKey('Add Organization', (array) $links);
    $this->assertArrayNotHasKey('Add Household', (array) $links);
    $this->assertArrayNotHasKey('Add Herring', (array) $links);
  }

  public function testRelationshipCacheLinks(): void {
    $links = RelationshipCache::getLinks(FALSE)
      ->addValue('relationship_id', 1)
      ->addValue('near_contact_id', 2)
      ->addValue('orientation:name', 'a_b')
      ->execute()->indexBy('ui_action');
    $this->assertGreaterThan(1, $links->count());
    foreach ($links as $link) {
      $this->assertEquals('Relationship', $link['entity']);
    }
    $this->assertStringContainsString('id=1', $links['view']['path']);
    $this->assertStringContainsString('id=1', $links['update']['path']);
    $this->assertStringContainsString('id=1', $links['delete']['path']);
    $this->assertStringContainsString('cid=2', $links['add']['path']);
    $this->assertStringContainsString('cid=2', $links['view']['path']);
    $this->assertStringContainsString('cid=2', $links['update']['path']);
    $this->assertStringContainsString('rtype=a_b', $links['update']['path']);
    $this->assertStringContainsString('cid=2', $links['delete']['path']);
  }

}
