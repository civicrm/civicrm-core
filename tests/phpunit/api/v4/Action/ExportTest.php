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
use Civi\Api4\ContributionPage;
use Civi\Api4\Event;
use Civi\Api4\Generic\ExportAction;
use Civi\Api4\Navigation;
use Civi\Api4\PriceSet;
use Civi\Api4\PriceSetEntity;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ExportTest extends Api4TestBase implements TransactionalInterface {

  public function testExportNavigation(): void {
    $sampleNav = Navigation::get(FALSE)
      ->setLimit(1)
      ->execute()->single();

    $export = Navigation::export(FALSE)
      ->setId($sampleNav['id'])
      ->execute()->single();

    sort($export['params']['match']);
    $this->assertEquals(['domain_id', 'name'], $export['params']['match']);
    $this->assertArrayNotHasKey('id', $export['params']['values']);
    $this->assertArrayNotHasKey('domain_id', $export['params']['values']);
    $this->assertArrayHasKey('name', $export['params']['values']);
  }

  /**
   * PriceSetEntity.entity_id is a dynamic FK (paired with entity_table). Neither PriceSet
   * nor PriceSetEntity implement the ManagedEntity trait yet, so `export()` is not
   * registered as a callable action for them via civicrm_api4(). Since ExportAction is a
   * generic, entity-agnostic action, it can still be tested directly against these (and any
   * other) real entities by constructing it explicitly, bypassing the trait/action-registry
   * requirement that only gates the civicrm_api4() dispatch, not the action class itself.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDynamicForeignKeyExport(): void {
    // ContributionPage has a `name` field, so its dynamic FK should be portable.
    $page = ContributionPage::create(FALSE)
      ->addValue('title', 'Test Export Page')
      ->addValue('name', 'test_export_page')
      ->execute()->single();
    // Event has no `name` field, so there's nothing portable to join on: this must not
    // error, and must not regress to some other broken/incorrect representation.
    $event = Event::create(FALSE)
      ->addValue('title', 'Test Export Event')
      ->addValue('event_type_id', 1)
      ->addValue('start_date', 'now')
      ->execute()->single();

    $priceSet = PriceSet::create(FALSE)
      ->addValue('name', 'test_export_pset')
      ->addValue('title', 'Test Export PriceSet')
      ->addValue('extends:name', ['CiviEvent'])
      ->addValue('financial_type_id:name', 'Donation')
      ->execute()->single();

    [$pageLink, $eventLink] = PriceSetEntity::save(FALSE)
      ->setRecords([
        ['entity_table' => 'civicrm_contribution_page', 'entity_id' => $page['id']],
        ['entity_table' => 'civicrm_event', 'entity_id' => $event['id']],
      ])
      ->setDefaults(['price_set_id' => $priceSet['id']])
      ->execute();

    $pageExport = (new ExportAction('PriceSetEntity', 'export'))
      ->setCheckPermissions(FALSE)
      ->setId($pageLink['id'])
      ->execute()->single();

    $this->assertEquals('test_export_page', $pageExport['params']['values']['entity_id.name']);
    $this->assertArrayNotHasKey('entity_id', $pageExport['params']['values']);
    $this->assertArrayNotHasKey('entity_table', $pageExport['params']['values']);
    $this->assertEquals('ContributionPage', $pageExport['params']['values']['entity_table:name']);

    $eventExport = (new ExportAction('PriceSetEntity', 'export'))
      ->setCheckPermissions(FALSE)
      ->setId($eventLink['id'])
      ->execute()->single();

    $this->assertEquals($event['id'], $eventExport['params']['values']['entity_id']);
    $this->assertArrayNotHasKey('entity_id.name', $eventExport['params']['values']);
  }

  /**
   * The write-side counterpart: `entity_id.name` should resolve back to the correct
   * local id for whichever concrete entity `entity_table` points at, even though the
   * field has no single fixed fk_entity.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDynamicForeignKeyCreateResolvesNameToId(): void {
    $page = ContributionPage::create(FALSE)
      ->addValue('title', 'Test Import Page')
      ->addValue('name', 'test_import_page')
      ->execute()->single();

    $priceSet = PriceSet::create(FALSE)
      ->addValue('name', 'test_import_pset')
      ->addValue('title', 'Test Import PriceSet')
      ->addValue('extends:name', ['CiviEvent'])
      ->addValue('financial_type_id:name', 'Donation')
      ->execute()->single();

    $priceSetEntity = PriceSetEntity::create(FALSE)
      ->addValue('price_set_id', $priceSet['id'])
      ->addValue('entity_table', 'civicrm_contribution_page')
      ->addValue('entity_id.name', 'test_import_page')
      ->execute()->single();

    $this->assertEquals($page['id'], $priceSetEntity['entity_id']);
  }

}
