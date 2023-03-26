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

use Civi\Api4\Contact;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;

/**
 * @group headless
 */
class CustomEntityReferenceTest extends CustomTestBase {

  /**
   * Ensure custom fields of type EntityReference show up correctly in getFields metadata.
   */
  public function testEntityReferenceCustomField() {
    CustomGroup::create()->setValues([
      'title' => 'EntityRefFields',
      'extends' => 'Individual',
    ])->execute();
    CustomField::create()->setValues([
      'label' => 'TestActivityReference',
      'custom_group_id.name' => 'EntityRefFields',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Activity',
    ])->execute();
    $spec = Contact::getFields(FALSE)
      ->addWhere('name', '=', 'EntityRefFields.TestActivityReference')
      ->execute()->single();
    $this->assertNull($spec['suffixes']);
    $this->assertEquals('EntityRef', $spec['input_type']);
    $this->assertEquals('Activity', $spec['fk_entity']);
  }

}
