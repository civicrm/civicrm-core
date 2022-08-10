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
   * @var callable
   */
  private $hookCallback;

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
      'administer CiviCRM',
      'view all contacts',
    ];

    // Admin can apply the api_key filter
    $result = Contact::autocomplete()
      ->setInput($lastName)
      ->setClientFilters(['api_key' => 'secret789'])
      ->execute();
    $this->assertCount(1, $result);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];

    // Non-admin cannot apply filter
    $result = Contact::autocomplete()
      ->setInput($lastName)
      ->setClientFilters(['api_key' => 'secret789'])
      ->execute();
    $this->assertCount(3, $result);

    // Cannot apply filter even with permissions disabled
    $result = Contact::autocomplete(FALSE)
      ->setInput($lastName)
      ->setClientFilters(['api_key' => 'secret789'])
      ->execute();
    $this->assertCount(3, $result);

    // Assert that the end-user is not allowed to inject arbitrary savedSearch params
    $msg = '';
    try {
      $result = Contact::autocomplete()
        ->setInput($lastName)
        ->setSavedSearch([
          'api_entity' => 'Contact',
          'api_params' => [],
        ])
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
    }
    $this->assertEquals('Parameter "savedSearch" is not of the correct type. Expecting string.', $msg);

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

}
