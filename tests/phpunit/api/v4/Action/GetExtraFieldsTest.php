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
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Household;
use Civi\Api4\Individual;
use Civi\Api4\Tag;

/**
 * @group headless
 */
class GetExtraFieldsTest extends Api4TestBase {

  public function testGetFieldsByContactType(): void {
    $getFields = Contact::getFields(FALSE)->addWhere('type', '=', 'Field');

    $baseFields = array_column(\CRM_Contact_BAO_Contact::fields(), 'name');
    $returnedFields = $getFields->execute()->column('name');
    $notReturned = array_diff($baseFields, $returnedFields);

    // With no contact_type specified, all fields should be returned
    $this->assertEmpty($notReturned);

    $individualFields = (array) $getFields->setValues(['contact_type' => 'Individual'])->execute()->indexBy('name');
    $this->assertArrayNotHasKey('sic_code', $individualFields);
    $this->assertTrue($individualFields['contact_type']['readonly']);
    $this->assertArrayHasKey('first_name', $individualFields);

    $orgId = Contact::create(FALSE)->addValue('contact_type', 'Organization')->execute()->first()['id'];
    $organizationFields = (array) $getFields->setValues(['id' => $orgId])->execute()->indexBy('name');
    $this->assertArrayHasKey('organization_name', $organizationFields);
    $this->assertArrayHasKey('sic_code', $organizationFields);
    $this->assertTrue($organizationFields['contact_type']['readonly']);
    $this->assertArrayNotHasKey('first_name', $organizationFields);
    $this->assertArrayNotHasKey('household_name', $organizationFields);

    $hhId = Household::create(FALSE)->execute()->first()['id'];
    $householdFields = (array) $getFields->setValues(['id' => $hhId])->execute()->indexBy('name');
    $this->assertArrayNotHasKey('sic_code', $householdFields);
    $this->assertTrue($householdFields['contact_type']['readonly']);
    $this->assertArrayNotHasKey('first_name', $householdFields);
    $this->assertArrayHasKey('household_name', $householdFields);
  }

  public function testContactPseudoEntityGetFields(): void {
    $individualFields = (array) Individual::getFields(FALSE)
      ->execute()->indexBy('name');
    $this->assertArrayNotHasKey('sic_code', $individualFields);
    $this->assertArrayNotHasKey('contact_type', $individualFields);
    $this->assertArrayHasKey('last_name', $individualFields);
    $this->assertEquals('Individual', $individualFields['birth_date']['entity']);
    $this->assertEquals('Individual', $individualFields['age_years']['entity']);
  }

  public function testGetOptionsAddress(): void {
    $getFields = Address::getFields(FALSE)->addWhere('name', '=', 'state_province_id')->setLoadOptions(TRUE);

    $usOptions = $getFields->setValues(['country_id' => 1228])->execute()->first();

    $this->assertContains('Alabama', $usOptions['options']);
    $this->assertNotContains('Alberta', $usOptions['options']);

    $caOptions = $getFields->setValues(['country_id' => 1039])->execute()->first();

    $this->assertNotContains('Alabama', $caOptions['options']);
    $this->assertContains('Alberta', $caOptions['options']);
  }

  public function testGetFkFields(): void {
    $fields = \Civi\Api4\Participant::getFields()
      ->setLoadOptions(TRUE)
      ->addWhere('name', 'IN', ['event_id', 'event_id.created_id', 'contact_id.gender_id', 'event_id.created_id.sort_name'])
      ->execute()
      ->indexBy('name');

    $this->assertCount(4, $fields);
    $this->assertEquals('Participant', $fields['event_id']['entity']);
    $this->assertEquals('Event', $fields['event_id.created_id']['entity']);
    $this->assertEquals('Contact', $fields['event_id.created_id.sort_name']['entity']);
    $this->assertGreaterThan(1, count($fields['contact_id.gender_id']['options']));
  }

  public function testGetTagsFromFilterField(): void {
    $actTag = Tag::create(FALSE)
      ->addValue('name', uniqid('act'))
      ->addValue('used_for', 'civicrm_activity')
      ->addValue('color', '#aaaaaa')
      ->execute()->first();
    $conTag = Tag::create(FALSE)
      ->addValue('name', uniqid('con'))
      ->addValue('used_for', 'civicrm_contact')
      ->addValue('color', '#cccccc')
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

    $actField = Activity::getFields(FALSE)
      ->addWhere('name', '=', 'tags')
      ->setLoadOptions(['name', 'color'])
      ->execute()->first();
    $actTags = array_column($actField['options'], 'color', 'name');
    $this->assertEquals('#aaaaaa', $actTags[$actTag['name']]);
    $this->assertArrayNotHasKey($conTag['name'], $actTags);
    $this->assertArrayNotHasKey($tagSet['name'], $actTags);
    $this->assertArrayNotHasKey($setChild['name'], $actTags);

    $conField = Contact::getFields(FALSE)
      ->addWhere('name', '=', 'tags')
      ->setLoadOptions(['name', 'color'])
      ->execute()->first();
    $conTags = array_column($conField['options'], 'color', 'name');
    $this->assertEquals('#cccccc', $conTags[$conTag['name']]);
    $this->assertArrayNotHasKey($actTag['name'], $conTags);
    $this->assertArrayNotHasKey($tagSet['name'], $conTags);
    $this->assertArrayHasKey($setChild['name'], $conTags);
  }

}
