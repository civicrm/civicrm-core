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
    // Check metadata
    $spec = Contact::getFields(FALSE)
      ->addWhere('name', '=', 'EntityRefFields.TestActivityReference')
      ->execute()->single();
    $this->assertNull($spec['suffixes']);
    $this->assertEquals('EntityRef', $spec['input_type']);
    $this->assertEquals('Activity', $spec['fk_entity']);
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

}
