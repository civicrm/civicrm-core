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
use Civi\Api4\EntitySet;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AutocompleteTest extends Api4TestBase implements HookInterface, TransactionalInterface {

  /**
   * @var callable
   */
  private $hookCallback;

  private $autocompleteRunCount = 0;

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
    $this->autocompleteRunCount = 0;
    // Ensure MockBasicEntity gets added via above listener
    \Civi::cache('metadata')->clear();
    MockBasicEntity::delete(FALSE)->addWhere('identifier', '>', 0)->execute();
    \Civi::settings()->set('includeWildCardInName', 1);
    \Civi::settings()->revert('autocomplete_displays');
    parent::setUp();
  }

  public function tearDown(): void {
    \Civi::settings()->revert('search_autocomplete_count');
    \Civi::settings()->revert('autocomplete_displays');
    parent::tearDown();
  }

  public function testSetDefaultDisplay(): void {
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'api_entity' => 'Contact',
    ]);
    $searchDisplay = $this->createTestRecord('SearchDisplay', [
      'saved_search_id' => $savedSearch['id'],
      'name' => 'the_test_contact_default',
      'type' => 'autocomplete',
      'is_autocomplete_default' => TRUE,
    ]);
    $setting = \Civi::settings()->get('autocomplete_displays');
    $this->assertEquals(['Contact:the_test_contact_default'], $setting);
    $searchDisplay = SearchDisplay::get(FALSE)
      ->addWhere('id', '=', $searchDisplay['id'])
      ->addSelect('*', 'is_autocomplete_default')
      ->execute()->single();
    $this->assertTrue($searchDisplay['is_autocomplete_default']);

    \Civi::settings()->revert('autocomplete_displays');

    $searchDisplay = SearchDisplay::get(FALSE)
      ->addWhere('id', '=', $searchDisplay['id'])
      ->addSelect('*', 'is_autocomplete_default')
      ->execute()->single();
    $this->assertFalse($searchDisplay['is_autocomplete_default']);
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

    $result = $this->runAutocomplete('Contact', ['input' => $lastName]);

    // Contacts will be returned in order by sort_name
    $this->assertStringEndsWith('Both', $result[0]['label']);
    $this->assertEquals('fa-star', $result[0]['icon']);
    $this->assertStringEndsWith('No icon', $result[1]['label']);
    $this->assertEquals('fa-user', $result[1]['icon']);
    $this->assertStringEndsWith('Starry', $result[2]['label']);
    $this->assertEquals('fa-star', $result[2]['icon']);
  }

  public function testAutocompleteValidation(): void {
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

  public function testAutocompletePager(): void {
    \Civi::settings()->set('search_autocomplete_count', 10);
    MockBasicEntity::delete()->addWhere('identifier', '>', 0)->execute();
    $sampleData = [];
    foreach (range(1, 21) as $num) {
      $sampleData[] = ['foo' => 'Test ' . $num];
    }
    MockBasicEntity::save()
      ->setRecords(array_reverse($sampleData))
      ->execute();

    $result = $this->runAutocomplete('MockBasicEntity', ['input' => 'est']);
    $this->assertEquals(3, $this->autocompleteRunCount);
    $this->assertCount(count($sampleData), $result);
    $this->assertEquals('Test 1', $result[0]['label']);
    $this->assertEquals('Test 11', $result[10]['label']);
    $this->assertEquals('Test 21', $result[20]['label']);
  }

  public function testAutocompleteWithDifferentKey(): void {
    $label = \CRM_Utils_String::createRandom(10, implode('', range('a', 'z')));
    $sample = $this->saveTestRecords('SavedSearch', [
      'records' => [
        ['name' => 'c', 'label' => "C $label"],
        ['name' => 'a', 'label' => "A $label"],
        ['name' => 'b', 'label' => "B $label"],
      ],
      'defaults' => ['api_entity' => 'Contact'],
    ])->indexBy('name');

    $result1 = SavedSearch::autocomplete()
      ->setInput($label)
      ->setKey('name')
      ->execute();

    $this->assertEquals('a', $result1[0]['id']);
    $this->assertEquals('b', $result1[1]['id']);
    $this->assertEquals('c', $result1[2]['id']);

    // Try searching by ID - should only get one result
    $result1 = SavedSearch::autocomplete()
      ->setInput((string) $sample['b']['id'])
      ->setKey('name')
      ->execute();
    $this->assertEquals(1, $result1->countFetched());
    $this->assertEquals('b', $result1[0]['id']);

    // This key won't be used since api_entity is not a unique index
    $result2 = SavedSearch::autocomplete()
      ->setInput($label)
      ->setKey('api_entity')
      ->execute();
    // Expect id to be returned as key instead of api_entity
    $this->assertEquals($sample['a']['id'], $result2[0]['id']);
    $this->assertEquals($sample['b']['id'], $result2[1]['id']);
    $this->assertEquals($sample['c']['id'], $result2[2]['id']);
  }

  public function testContactAutocompleteById(): void {
    \Civi::settings()->set('search_autocomplete_count', 3);
    $firstName = \CRM_Utils_String::createRandom(10, implode('', range('a', 'z')));

    $contacts = $this->saveTestRecords('Contact', [
      'records' => array_fill(0, 15, ['first_name' => $firstName]),
    ]);

    $cid = $contacts[11]['id'];

    Contact::save(FALSE)
      ->addRecord(['id' => $contacts[11]['id'], 'last_name' => "Aaaac$cid"])
      ->addRecord(['id' => $contacts[0]['id'], 'last_name' => "Aaaac$cid"])
      ->addRecord(['id' => $contacts[14]['id'], 'last_name' => "Aaaab$cid"])
      ->addRecord(['id' => $contacts[6]['id'], 'last_name' => "Aaaaa$cid"])
      ->addRecord(['id' => $contacts[1]['id'], 'last_name' => "Aaaad$cid"])
      ->execute();

    $allResults = $this->runAutocomplete('Contact', ['input' => (string) $cid]);

    // Exact match should be at beginning of the list
    $this->assertEquals($cid, \CRM_Utils_Array::first($allResults)['id']);
    $this->assertEquals($cid, $allResults[0]['id']);
    // If by chance there are other matching contacts in the db, skip over them
    foreach ($allResults as $i => $row) {
      if ($row['id'] == $contacts[6]['id']) {
        break;
      }
    }
    // The other 4 matches should be in order
    $this->assertEquals($contacts[6]['id'], $allResults[$i]['id']);
    $this->assertEquals($contacts[14]['id'], $allResults[$i + 1]['id']);
    $this->assertEquals($contacts[0]['id'], $allResults[$i + 2]['id']);
    $this->assertEquals($contacts[1]['id'], $allResults[$i + 3]['id']);

    // Ensure partial match doesn't work (end of id)
    $result = Contact::autocomplete()
      ->setInput(substr((string) $cid, -1))
      ->execute();
    $this->assertNotContains($cid, $result->column('id'));

    // Ensure partial match doesn't work (beginning of id)
    $result = Contact::autocomplete()
      ->setInput(substr((string) $cid, 0, 1))
      ->execute();
    $this->assertNotContains($cid, $result->column('id'));
  }

  public function testMailingAutocompleteNoDisabledGroups(): void {
    $this->createTestRecord('Group', [
      'title' => 'Second Star',
      'frontend_title' => 'Second Star',
      'name' => 'Second_Star',
      'group_type:name' => 'Mailing List',
    ]);
    $this->createTestRecord('Group', [
      'title' => 'Second',
      'frontend_title' => 'Second',
      'name' => 'Second',
      'group_type:name' => 'Mailing List',
      'is_active' => 0,
    ]);

    $result = EntitySet::autocomplete()
      ->setInput('')
      ->setFieldName('Mailing.recipients_include')
      ->setFormName('crmMailing.1')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals('Second Star', $result[0]['label']);
  }

  /**
   * Emulates the behavior of `$.fn.crmAutocomplete` in Common.js
   *
   * @return array
   */
  public function runAutocomplete(string $entityName, array $params): array {
    $searchField = NULL;
    $allResults = [];
    do {
      $result = civicrm_api4($entityName, 'autocomplete', $params + [
        'checkPermissions' => FALSE,
        'searchField' => $searchField,
        'exclude' => array_column($allResults, 'id'),
      ]);
      $this->autocompleteRunCount++;
      $allResults = array_merge($allResults, (array) $result);
      $searchField = $result->searchField;
      $more = $result->countFetched() < $result->countMatched();
      // If no more results for this searchField, advance to the next
      $fieldIndex = array_search($searchField, $result->searchFields);
      if (!$more && strlen($params['input'] ?? '') && $fieldIndex < (count($result->searchFields) - 1)) {
        $searchField = $result->searchFields[$fieldIndex + 1];
        $more = TRUE;
      }
    } while ($more);
    return $allResults;
  }

}
