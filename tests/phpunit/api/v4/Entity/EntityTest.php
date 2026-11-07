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

use Civi\Api4\ContactType;
use Civi\Api4\Entity;
use Civi\Api4\Provider\ActionObjectProvider;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class EntityTest extends Api4TestBase implements HookInterface {

  protected ?array $enableComponents = ['*'];

  private ?string $eventDescription = NULL;

  private ?string $individualLabel = NULL;

  public function on_civi_api4_entityTypes(GenericHookEvent $e): void {
    if ($this->eventDescription !== NULL) {
      $e->entities['Event']['description'] = $this->eventDescription;
    }
  }

  public function tearDown(): void {
    \CRM_Core_Session::singleton()->set('isUpgradePending', NULL);
    ActionObjectProvider::flushClassEntities();
    if ($this->individualLabel !== NULL) {
      ContactType::update(FALSE)
        ->addWhere('name', '=', 'Individual')
        ->addValue('label', $this->individualLabel)
        ->execute();
    }
    parent::tearDown();
  }

  public function testEntityGet(): void {
    $result = Entity::get(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertArrayHasKey('Entity', $result, "Entity::get missing itself");

    // Label fields
    $this->assertSame('title', $result['Event']['label_field']);
    // Search fields
    $this->assertEquals(['sort_name'], $result['Contact']['search_fields']);
    $this->assertEquals(['title'], $result['Event']['search_fields']);
    $this->assertEquals(['contact_id.sort_name', 'event_id.title'], $result['Participant']['search_fields']);
  }

  public function testContactPseudoEntityGet(): void {
    $result = Entity::get(FALSE)
      ->execute()
      ->indexBy('name');

    foreach (['Contact', 'Individual', 'Organization', 'Household'] as $contactType) {
      $this->assertSame('CRM_Contact_DAO_Contact', $result[$contactType]['dao']);
      $this->assertContains('DAOEntity', $result[$contactType]['type']);
      $this->assertSame('display_name', $result[$contactType]['label_field']);
      $this->assertEquals(['id'], $result[$contactType]['primary_key']);
      // Contact icon fields
      $this->assertEquals(['contact_sub_type:icon', 'contact_type:icon'], $result[$contactType]['icon_field']);
    }

    foreach (['Individual', 'Organization', 'Household'] as $contactType) {
      $this->assertContains('ContactType', $result[$contactType]['type']);
      $this->assertEquals($contactType, $result[$contactType]['where']['contact_type']);
    }

    $this->assertSame('Individual', $result['Individual']['title']);
    $this->assertSame('Individuals', $result['Individual']['title_plural']);
    $this->assertSame('Household', $result['Household']['title']);
    $this->assertSame('Households', $result['Household']['title_plural']);
    $this->assertSame('Organization', $result['Organization']['title']);
    $this->assertSame('Organizations', $result['Organization']['title_plural']);
  }

  public function testEntity(): void {
    $result = Entity::getActions(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertNotContains(
      'create',
      array_keys((array) $result),
      "Entity entity has more than basic actions");
  }

  public function testEntityComponent(): void {
    \CRM_Core_BAO_ConfigSetting::disableComponent('CiviEvent');
    $result = Entity::get(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertArrayNotHasKey('Participant', $result,
      "Entity::get should not have Participant when CiviEvent disabled");

    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');
    $result = Entity::get(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertArrayHasKey('Participant', $result,
      "Entity::get should have Participant when CiviEvent enabled");
  }

  public function testEntityTypesEventRunsAfterMetadataClear(): void {
    $this->eventDescription = 'First description';
    $this->assertSame('First description', $this->getEntityInfo('Event')['description']);

    $this->eventDescription = 'Second description';
    \Civi::cache('metadata')->clear();
    $this->assertSame('Second description', $this->getEntityInfo('Event')['description']);
  }

  public function testClassEntitiesAreReusedAfterMetadataClear(): void {
    $this->setCachedEventTitle('Cached title');
    \Civi::cache('metadata')->clear();
    $this->assertSame('Cached title', $this->getEntityInfo('Event')['title']);
  }

  public function testUpgradeModeBypassesClassEntityCache(): void {
    $this->setCachedEventTitle('Cached title');
    \CRM_Core_Session::singleton()->set('isUpgradePending', TRUE);
    \Civi::cache('metadata')->clear();
    $this->assertSame('Event', $this->getEntityInfo('Event')['title']);
  }

  public function testContactTypeChangeRefreshesEntityInfo(): void {
    $this->assertSame('Contacts of type Individual.', $this->getEntityInfo('Individual')['description']);

    $this->individualLabel = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->execute()->single()['label'];
    ContactType::update(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->addValue('label', 'Person')
      ->execute();
    $this->assertSame('Contacts of type Person.', $this->getEntityInfo('Individual')['description']);
  }

  public function testWordReplacementRefreshesEntityInfo(): void {
    $this->assertSame('Contacts of type Individual.', $this->getEntityInfo('Individual')['description']);

    $this->createTestRecord('WordReplacement', [
      'find_word' => 'Contacts of type',
      'replace_word' => 'People of type',
      'match_type' => 'wildcardMatch',
    ]);
    $this->assertSame('People of type Individual.', $this->getEntityInfo('Individual')['description']);
  }

  public function testTranslationRefreshesEntityInfo(): void {
    $this->assertSame('Contacts of type Individual.', $this->getEntityInfo('Individual')['description']);

    $source = 'Contacts of type %1.';
    $sourceKey = \CRM_Core_BAO_TranslationSource::createGuid($source);
    $this->createTestRecord('TranslationSource', [
      'source' => $source,
      'source_key' => $sourceKey,
      'entity' => 'api4',
    ]);
    $this->createTestRecord('Translation', [
      'source_key' => $sourceKey,
      'language' => 'en_US',
      'string' => 'People of type %1.',
      'status_id:name' => 'active',
    ]);
    \Civi::cache('metadata')->clear();
    $this->assertSame('People of type Individual.', $this->getEntityInfo('Individual')['description']);
  }

  private function setCachedEventTitle(string $title): void {
    $this->getEntityInfo('Event');
    $classEntities = \Civi::cache('long')->get('api4.entities.classInfo');
    $classEntities[\CRM_Core_I18n::getLocale()]['Event']['title'] = $title;
    \Civi::cache('long')->set('api4.entities.classInfo', $classEntities);
  }

  private function getEntityInfo(string $entityName): array {
    return Entity::get(FALSE)
      ->addWhere('name', '=', $entityName)
      ->execute()->single();
  }

}
