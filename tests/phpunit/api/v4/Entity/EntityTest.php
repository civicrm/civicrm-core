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

use Civi\Api4\Entity;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class EntityTest extends Api4TestBase {

  public function testEntityGet(): void {
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    $result = Entity::get(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertArrayHasKey('Entity', $result, "Entity::get missing itself");

    // Label fields
    $this->assertEquals('title', $result['Event']['label_field']);
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
      $this->assertEquals('CRM_Contact_DAO_Contact', $result[$contactType]['dao']);
      $this->assertContains('DAOEntity', $result[$contactType]['type']);
      $this->assertEquals('display_name', $result[$contactType]['label_field']);
      $this->assertEquals(['id'], $result[$contactType]['primary_key']);
      // Contact icon fields
      $this->assertEquals(['contact_sub_type:icon', 'contact_type:icon'], $result[$contactType]['icon_field']);
    }

    foreach (['Individual', 'Organization', 'Household'] as $contactType) {
      $this->assertContains('ContactType', $result[$contactType]['type']);
      $this->assertEquals($contactType, $result[$contactType]['where']['contact_type']);
    }

    $this->assertEquals('Individual', $result['Individual']['title']);
    $this->assertEquals('Individuals', $result['Individual']['title_plural']);
    $this->assertEquals('Household', $result['Household']['title']);
    $this->assertEquals('Households', $result['Household']['title_plural']);
    $this->assertEquals('Organization', $result['Organization']['title']);
    $this->assertEquals('Organizations', $result['Organization']['title_plural']);
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

}
