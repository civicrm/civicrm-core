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
use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;

/**
 * @group headless
 */
class GetFieldsTest extends UnitTestCase {

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

  public function testTableAndColumnReturned() {
    $fields = Contact::getFields(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertEquals('civicrm_contact', $fields['display_name']['table_name']);
    $this->assertEquals('display_name', $fields['display_name']['column_name']);
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

}
