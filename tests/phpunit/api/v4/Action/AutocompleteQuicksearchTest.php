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


namespace Civi\tests\phpunit\api\v4\Action;

use Civi\Api4\Contact;
use Civi\Api4\Setting;

/**
 * @group headless
 */
class AutocompleteQuicksearchTest extends \api\v4\Api4TestBase {

  public function tearDown(): void {
    \Civi::settings()->revert('quicksearch_options');
    \Civi::settings()->revert('contact_autocomplete_options');
    parent::tearDown();
  }

  public function testQuicksearchAutocompleteOptionsDisplay(): void {
    // Name + email
    Setting::set(FALSE)
      ->addValue('contact_autocomplete_options', [1, 2])
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'Aaa', 'email_primary.email' => 'a@a.a', 'address_primary.city' => 'A Town'],
        ['first_name' => 'B', 'last_name' => 'Bbb', 'email_primary.email' => 'b@b.b', 'address_primary.city' => 'B Town'],
      ],
    ]);
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Aaa, A')
      ->execute()->indexBy('id');

    $this->assertEquals('Aaa, A :: a@a.a', $result[$contacts[0]['id']]['label']);
    $this->assertArrayNotHasKey($contacts[1]['id'], $result);

    // Name + city
    Setting::set(FALSE)
      ->addValue('contact_autocomplete_options', [1, 5])
      ->execute();

    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Bbb, b')
      ->execute()->indexBy('id');

    $this->assertEquals('Bbb, B :: b@b.b', $result[$contacts[1]['id']]['label']);
    $this->assertEquals('B Town', $result[$contacts[1]['id']]['description'][0]);
    $this->assertArrayNotHasKey($contacts[0]['id'], $result);
  }

  public function testQuicksearchAutocompleteWithMultiRecordCustomField(): void {
    $this->createTestRecord('CustomGroup', [
      'name' => __FUNCTION__,
      'is_multiple' => TRUE,
    ]);
    $this->createTestRecord('CustomField', [
      'name' => 'Test',
      'custom_group_id.name' => __FUNCTION__,
    ]);
    $customFieldName = __FUNCTION__ . '.Test';

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'Aaa', 'email_primary.email' => 'a@a.a'],
        ['first_name' => 'B', 'last_name' => 'Bbb', 'email_primary.email' => 'b@b.b'],
        ['first_name' => 'C', 'last_name' => 'Ccc', 'email_primary.email' => 'c@c.c'],
      ],
    ]);

    civicrm_api4("Custom_" . __FUNCTION__, 'save', [
      'records' => [
        ['entity_id' => $contacts[0]['id'], 'Test' => 'Righto'],
        ['entity_id' => $contacts[1]['id'], 'Test' => 'Wrongo'],
      ],
    ]);

    Setting::set(FALSE)
      ->addValue('quicksearch_options', [
        'sort_name',
        'id',
        $customFieldName,
      ])
      ->execute();

    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters([$customFieldName => 'Right'])
      ->execute()->indexBy('id');

    $this->assertEquals('Aaa, A :: Righto', $result[$contacts[0]['id']]['label']);
    $this->assertArrayNotHasKey($contacts[1]['id'], $result);
    $this->assertArrayNotHasKey($contacts[2]['id'], $result);
  }

}
