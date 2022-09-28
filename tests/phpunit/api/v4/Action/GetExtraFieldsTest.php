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

use api\v4\UnitTestCase;
use Civi\Api4\Activity;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Tag;

/**
 * @group headless
 */
class GetExtraFieldsTest extends UnitTestCase {

  public function testGetFieldsByContactType() {
    $getFields = Contact::getFields(FALSE)->addSelect('name')->addWhere('type', '=', 'Field');

    $baseFields = array_column(\CRM_Contact_BAO_Contact::fields(), 'name');
    $returnedFields = $getFields->execute()->column('name');
    $notReturned = array_diff($baseFields, $returnedFields);

    // With no contact_type specified, all fields should be returned
    $this->assertEmpty($notReturned);

    $individualFields = $getFields->setValues(['contact_type' => 'Individual'])->execute()->column('name');
    $this->assertNotContains('sic_code', $individualFields);
    $this->assertNotContains('contact_type', $individualFields);
    $this->assertContains('first_name', $individualFields);

    $organizationFields = $getFields->setValues(['contact_type' => 'Organization'])->execute()->column('name');
    $this->assertContains('sic_code', $organizationFields);
    $this->assertNotContains('contact_type', $organizationFields);
    $this->assertNotContains('first_name', $organizationFields);
    $this->assertNotContains('household_name', $organizationFields);
  }

  public function testGetOptionsAddress() {
    $getFields = Address::getFields(FALSE)->addWhere('name', '=', 'state_province_id')->setLoadOptions(TRUE);

    $usOptions = $getFields->setValues(['country_id' => 1228])->execute()->first();

    $this->assertContains('Alabama', $usOptions['options']);
    $this->assertNotContains('Alberta', $usOptions['options']);

    $caOptions = $getFields->setValues(['country_id' => 1039])->execute()->first();

    $this->assertNotContains('Alabama', $caOptions['options']);
    $this->assertContains('Alberta', $caOptions['options']);
  }

  public function testGetFkFields() {
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

  public function testGetTagsFromFilterField() {
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
