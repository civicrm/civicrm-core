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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Custom;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use Civi\Api4\Individual;
use Civi\Api4\Organization;

/**
 * @group headless
 */
class CustomEntityReferenceTest extends CustomTestBase {

  /**
   * Ensure custom fields of type EntityReference correctly apply filters
   */
  public function testEntityReferenceCustomField(): void {
    $subject = uniqid();
    CustomGroup::create()->setValues([
      'title' => 'EntityRefFields',
      'extends' => 'Individual',
    ])->execute();
    $field = CustomField::create()->setValues([
      'label' => 'TestActivityReference',
      'custom_group_id.name' => 'EntityRefFields',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Activity',
      'filter' => "subject=$subject",
    ])->execute()->single();
    // Spec should only exist for Individuals
    $spec = Organization::getFields(FALSE)
      ->addWhere('name', '=', 'EntityRefFields.TestActivityReference')
      ->execute()->first();
    $this->assertNull($spec);
    // Check metadata
    $spec = Contact::getFields(FALSE)
      ->addWhere('name', '=', 'EntityRefFields.TestActivityReference')
      ->execute()->single();
    $this->assertNull($spec['suffixes']);
    $this->assertEquals('EntityRef', $spec['input_type']);
    $this->assertEquals('Activity', $spec['fk_entity']);
    $this->assertEquals('id', $spec['fk_column']);
    $this->assertEquals($subject, $spec['input_attrs']['filter']['subject']);
    // Check results
    $activities = $this->saveTestRecords('Activity', [
      'records' => [
        ['subject' => $subject],
        ['subject' => 'wrong one'],
        ['subject' => $subject],
      ],
    ]);
    // Filter using field name
    $result = Activity::autocomplete(FALSE)
      ->setFieldName("Contact.EntityRefFields.TestActivityReference")
      ->execute();
    $this->assertCount(2, $result);
    // No filter
    $result = Activity::autocomplete(FALSE)
      ->execute();
    $this->assertGreaterThan(2, $result->countFetched());
  }

  /**
   * Ensure custom fields of type EntityReference correctly apply filters
   */
  public function testEntityReferenceCustomFieldByContactType(): void {
    CustomGroup::create()->setValues([
      'title' => 'EntityRefFields',
      'extends' => 'Individual',
    ])->execute();
    CustomField::create()->setValues([
      'label' => 'TestOrgRef',
      'custom_group_id.name' => 'EntityRefFields',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Organization',
    ])->execute()->single();
    // Check metadata
    $spec = Individual::getFields(FALSE)
      ->addWhere('name', '=', 'EntityRefFields.TestOrgRef')
      ->execute()->single();
    $this->assertNull($spec['suffixes']);
    $this->assertEquals('EntityRef', $spec['input_type']);
    $this->assertEquals('Organization', $spec['fk_entity']);
    // Check results
    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['contact_type' => 'Organization'],
        ['contact_type' => 'Individual'],
        ['contact_type' => 'Household'],
      ],
    ])->indexBy('contact_type')->column('id');
    // Autocomplete by id
    $result = (array) Organization::autocomplete(FALSE)
      ->setFieldName("Contact.EntityRefFields.TestOrgRef")
      ->setInput((string) $contacts['Organization'])
      ->execute();
    $this->assertCount(1, $result);
    // Autocomplete by id
    $result = (array) Organization::autocomplete(FALSE)
      ->setFieldName("Contact.EntityRefFields.TestOrgRef")
      ->setInput((string) $contacts['Individual'])
      ->execute();
    $this->assertCount(0, $result);
    // Autocomplete by id
    $result = (array) Organization::autocomplete(FALSE)
      ->setFieldName("Contact.EntityRefFields.TestOrgRef")
      ->setInput((string) $contacts['Household'])
      ->execute();
    $this->assertCount(0, $result);
    // No field specified
    $result = Contact::autocomplete(FALSE)
      ->execute();
    $this->assertGreaterThan(2, $result->countFetched());
  }

}
