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
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use Civi\Api4\MockBasicEntity;
use Civi\Api4\SavedSearch;
use Civi\Core\Event\GenericHookEvent;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AutocompleteTest extends Api4TestBase implements HookInterface, TransactionalInterface {

  /**
   * @var callable
   */
  private $hookCallback;

  public function setUpHeadless(): void {
    \Civi\Test::headless()->install('org.civicrm.search_kit')->apply();
  }

  /**
   * Listens for civi.api4.entityTypes event to manually add this nonstandard entity
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function on_civi_api4_entityTypes(GenericHookEvent $e): void {
    $e->entities['MockBasicEntity'] = MockBasicEntity::getInfo();
  }

  public function on_civi_api_prepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($this->hookCallback && is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      ($this->hookCallback)($apiRequest);
    }
  }

  public function setUp(): void {
    $this->hookCallback = NULL;
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
    $this->assertEquals($entities[1]['identifier'], $result[1]['id']);
    $this->assertEquals($entities[2]['identifier'], $result[0]['id']);

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

  public function testAutocompleteValidation() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      [
        'first_name' => 'One',
        'api_key' => 'secret123',
      ],
      [
        'first_name' => 'Two',
        'api_key' => 'secret456',
      ],
      [
        'first_name' => 'Three',
        'api_key' => 'secret789',
      ],
    ];
    $records = $this->saveTestRecords('Contact', [
      'records' => $sampleData,
      'defaults' => ['last_name' => $lastName],
    ]);

    $this->createLoggedInUser();

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'view all contacts',
    ];

    // Assert that the end-user is not allowed to inject arbitrary savedSearch params
    $msg = '';
    try {
      Contact::autocomplete()
        ->setInput($lastName)
        ->setSavedSearch([
          'api_entity' => 'Contact',
          'api_params' => [],
        ])
        ->execute();
      $this->fail();
    }
    catch (UnauthorizedException $e) {
      $msg = $e->getMessage();
    }
    $this->assertEquals('Access denied', $msg);

    // With hook callback, permissions can be overridden by injecting a trusted filter
    $this->hookCallback = function(\Civi\Api4\Generic\AutocompleteAction $action) {
      $action->addFilter('api_key', 'secret456');
      $action->setCheckPermissions(FALSE);
    };

    $result = Contact::autocomplete()
      ->setInput($lastName)
      ->execute();
    $this->assertCount(1, $result);
  }

  public function testAutocompletePager() {
    MockBasicEntity::delete()->addWhere('identifier', '>', 0)->execute();
    $sampleData = [];
    foreach (range(1, 21) as $num) {
      $sampleData[] = ['foo' => 'Test ' . $num];
    }
    MockBasicEntity::save()
      ->setRecords($sampleData)
      ->execute();

    $result1 = MockBasicEntity::autocomplete()
      ->setInput('est')
      ->execute();
    $this->assertEquals('Test 1', $result1[0]['label']);
    $this->assertEquals(10, $result1->countFetched());
    $this->assertEquals(11, $result1->countMatched());

    $result2 = MockBasicEntity::autocomplete()
      ->setInput('est')
      ->setPage(2)
      ->execute();
    $this->assertEquals('Test 11', $result2[0]['label']);
    $this->assertEquals(10, $result2->countFetched());
    $this->assertEquals(11, $result2->countMatched());

    $result3 = MockBasicEntity::autocomplete()
      ->setInput('est')
      ->setPage(3)
      ->execute();
    $this->assertEquals('Test 21', $result3[0]['label']);
    $this->assertEquals(1, $result3->countFetched());
    $this->assertEquals(1, $result3->countMatched());
  }

  public function testAutocompleteIdField() {
    $label = uniqid();
    $sample = $this->saveTestRecords('SavedSearch', [
      'records' => [
        ['name' => 'c', 'label' => "C $label"],
        ['name' => 'a', 'label' => "A $label"],
        ['name' => 'b', 'label' => "B $label"],
      ],
      'defaults' => ['api_entity' => 'Contact'],
    ]);

    $result1 = SavedSearch::autocomplete()
      ->setInput($label)
      ->setKey('name')
      ->execute();

    $this->assertEquals('a', $result1[0]['id']);
    $this->assertEquals('b', $result1[1]['id']);
    $this->assertEquals('c', $result1[2]['id']);

    // This key won't be used since api_entity is not a unique index
    $result2 = SavedSearch::autocomplete()
      ->setInput($label)
      ->setKey('api_entity')
      ->execute();
    // Expect id to be returned as key instead of api_entity
    $this->assertEquals($sample[1]['id'], $result2[0]['id']);
    $this->assertEquals($sample[2]['id'], $result2[1]['id']);
    $this->assertEquals($sample[0]['id'], $result2[2]['id']);
  }

}
