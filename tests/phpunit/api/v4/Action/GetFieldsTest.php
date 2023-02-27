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
use Civi\Api4\Activity;
use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\EntityTag;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class GetFieldsTest extends Api4TestBase implements TransactionalInterface {

  public function testOptionsAreReturned() {
    $fields = Contact::getFields(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertTrue($fields['gender_id']['options']);
    $this->assertFalse($fields['first_name']['options']);

    $fields = Contact::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->execute()
      ->indexBy('name');
    $this->assertTrue(is_array($fields['gender_id']['options']));
    $this->assertFalse($fields['first_name']['options']);
  }

  public function testContactGetFields() {
    $fields = Contact::getFields(FALSE)
      ->execute()
      ->indexBy('name');
    // Ensure table & column are returned
    $this->assertEquals('civicrm_contact', $fields['display_name']['table_name']);
    $this->assertEquals('display_name', $fields['display_name']['column_name']);

    // Check suffixes
    $this->assertEquals(['name', 'label', 'icon'], $fields['contact_type']['suffixes']);
    $this->assertEquals(['name', 'label', 'icon'], $fields['contact_sub_type']['suffixes']);
  }

  public function testComponentFields() {
    \CRM_Core_BAO_ConfigSetting::disableComponent('CiviCampaign');
    $fields = \Civi\Api4\Event::getFields()
      ->addWhere('name', 'CONTAINS', 'campaign')
      ->execute();
    $this->assertCount(0, $fields);
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $fields = \Civi\Api4\Event::getFields()
      ->addWhere('name', 'CONTAINS', 'campaign')
      ->execute();
    $this->assertCount(1, $fields);
  }

  public function testInternalPropsAreHidden() {
    // Public getFields should not contain @internal props
    $fields = Contact::getFields(FALSE)
      ->execute();
    foreach ($fields as $field) {
      $this->assertArrayNotHasKey('output_formatters', $field);
    }
    // Internal entityFields should contain @internal props
    $fields = Contact::get(FALSE)
      ->entityFields();
    foreach ($fields as $field) {
      $this->assertArrayHasKey('output_formatters', $field);
    }
  }

  public function testPreloadFalse() {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviContribute');
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    Campaign::create()->setValues(['name' => 'Big Campaign', 'title' => 'Biggie'])->execute();
    // The campaign_id field has preload = false in the schema,
    // Which means the options will NOT load but suffixes are still available
    $fields = Contribution::getFields(FALSE)
      ->setLoadOptions(['name', 'label'])
      ->execute()->indexBy('name');
    $this->assertFalse($fields['campaign_id']['options']);
    $this->assertEquals(['name', 'label'], $fields['campaign_id']['suffixes']);
  }

  public function testRequiredAndNullableAndDeprecated() {
    $actFields = Activity::getFields(FALSE)
      ->setAction('create')
      ->execute()->indexBy('name');

    $this->assertFalse($actFields['id']['required']);
    $this->assertTrue($actFields['activity_type_id']['required']);
    $this->assertFalse($actFields['activity_type_id']['nullable']);
    $this->assertFalse($actFields['subject']['required']);
    $this->assertTrue($actFields['subject']['nullable']);
    $this->assertFalse($actFields['subject']['deprecated']);
    $this->assertTrue($actFields['phone_id']['deprecated']);
  }

  public function testGetSuffixes() {
    $actFields = Activity::getFields(FALSE)
      ->execute()->indexBy('name');

    $this->assertEquals(['name', 'label', 'description'], $actFields['engagement_level']['suffixes']);
    $this->assertEquals(['name', 'label', 'description', 'icon'], $actFields['activity_type_id']['suffixes']);
    $this->assertEquals(['name', 'label', 'description', 'color'], $actFields['status_id']['suffixes']);
    $this->assertEquals(['name', 'label', 'description', 'color'], $actFields['tags']['suffixes']);
  }

  public function testDynamicFks() {
    $tagFields = EntityTag::getFields(FALSE)
      ->execute()->indexBy('name');
    $this->assertEmpty($tagFields['entity_id']['fk_entity']);

    $tagFields = EntityTag::getFields(FALSE)
      ->addValue('entity_table', 'civicrm_activity')
      ->execute()->indexBy('name');
    $this->assertEquals('Activity', $tagFields['entity_id']['fk_entity']);

    $tagFields = EntityTag::getFields(FALSE)
      ->addValue('entity_table:name', 'Contact')
      ->execute()->indexBy('name');
    $this->assertEquals('Contact', $tagFields['entity_id']['fk_entity']);
  }

  public function testFiltersAreReturned(): void {
    $field = Contact::getFields(FALSE)
      ->addWhere('name', '=', 'employer_id')
      ->execute()->single();
    $this->assertEquals(['contact_type' => 'Organization'], $field['input_attrs']['filter']);
  }

}
