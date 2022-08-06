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
use Civi\Api4\MockBasicEntity;
use Civi\Core\Event\GenericHookEvent;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AutocompleteTest extends Api4TestBase implements HookInterface, TransactionalInterface {

  /**
   * Listens for civi.api4.entityTypes event to manually add this nonstandard entity
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function on_civi_api4_entityTypes(GenericHookEvent $e): void {
    $e->entities['MockBasicEntity'] = MockBasicEntity::getInfo();
  }

  public function setUp(): void {
    // Ensure MockBasicEntity gets added via above listener
    \Civi::cache('metadata')->clear();
    MockBasicEntity::delete(FALSE)->addWhere('identifier', '>', 0)->execute();
    \Civi::settings()->set('includeWildCardInName', 1);
    parent::setUp();
  }

  public function testMockEntityAutocomplete(): void {
    $sampleData = [
      ['foo' => 'White', 'color' => 'ffffff'],
      ['foo' => 'Gray', 'color' => '777777'],
      ['foo' => 'Black', 'color' => '000000'],
    ];
    $entities = MockBasicEntity::save(FALSE)
      ->setRecords($sampleData)
      ->execute();

    $result = MockBasicEntity::autocomplete()
      ->setInput('a')
      ->execute();
    $this->assertCount(2, $result);
    $this->assertEquals('Black', $result[0]['label']);
    $this->assertEquals('777777', $result[1]['color']);

    $result = MockBasicEntity::autocomplete()
      ->setInput('ite')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($entities[0]['identifier'], $result[0]['id']);
    $this->assertEquals('ffffff', $result[0]['color']);
    $this->assertEquals('White', $result[0]['label']);
  }

  public function testContactIconAutocomplete(): void {
    $this->createTestRecord('ContactType', [
      'label' => 'Star',
      'name' => 'Star',
      'parent_id:name' => 'Individual',
      'icon' => 'fa-star',
    ]);
    $this->createTestRecord('ContactType', [
      'label' => 'None',
      'name' => 'None',
      'parent_id:name' => 'Individual',
      'icon' => NULL,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      [
        'first_name' => 'Starry',
        'contact_sub_type' => ['Star'],
      ],
      [
        'first_name' => 'No icon',
        'contact_sub_type' => ['None'],
      ],
      [
        'first_name' => 'Both',
        'contact_sub_type' => ['None', 'Star'],
      ],
    ];
    $records = $this->saveTestRecords('Contact', [
      'records' => $sampleData,
      'defaults' => ['last_name' => $lastName],
    ]);

    $result = Contact::autocomplete()
      ->setInput($lastName)
      ->execute();

    // Contacts will be returned in order by sort_name
    $this->assertStringStartsWith('Both', $result[0]['label']);
    $this->assertEquals('fa-star', $result[0]['icon']);
    $this->assertStringStartsWith('No icon', $result[1]['label']);
    $this->assertEquals('fa-user', $result[1]['icon']);
    $this->assertStringStartsWith('Starry', $result[2]['label']);
    $this->assertEquals('fa-star', $result[2]['icon']);
  }

}
