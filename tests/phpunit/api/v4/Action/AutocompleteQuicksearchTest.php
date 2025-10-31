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

use Civi\Api4\Contact;
use Civi\Api4\Setting;

/**
 * @group headless
 */
class AutocompleteQuicksearchTest extends \api\v4\Api4TestBase {

  public function tearDown(): void {
    \Civi::settings()->revert('quicksearch_options');
    \Civi::settings()->revert('contact_autocomplete_options');
    \Civi::settings()->revert('includeNickNameInName');
    \Civi::settings()->revert('includeWildCardInName');
    parent::tearDown();
  }

  public function testQuicksearchAutocompleteOptionsDisplay(): void {
    // Name + email
    Setting::set(FALSE)
      ->addValue('contact_autocomplete_options', [1, 2])
      ->addValue('includeEmailInName', TRUE)
      ->addValue('includeNickNameInName', FALSE)
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'Aaa', 'email_primary.email' => 'a@a.a', 'address_primary.city' => 'A Town'],
        ['first_name' => 'B', 'last_name' => 'Bbb', 'email_primary.email' => 'b@b.b', 'address_primary.city' => 'B Town'],
        ['email_primary.email' => 'c@c.c'],
        ['first_name' => 'A', 'last_name' => 'Aaa', 'is_deleted' => TRUE],
      ],
    ]);
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Aaa, A')
      ->execute()->indexBy('id');

    $this->assertEquals('Aaa, A', $result[$contacts[0]['id']]['label']);
    $this->assertEquals('a@a.a', $result[$contacts[0]['id']]['description'][0]);
    // Non-matching contacts should not be included
    $this->assertArrayNotHasKey($contacts[1]['id'], $result);
    $this->assertArrayNotHasKey($contacts[2]['id'], $result);
    // Deleted contact should not be included
    $this->assertArrayNotHasKey($contacts[3]['id'], $result);

    // Name + city
    Setting::set(FALSE)
      ->addValue('contact_autocomplete_options', [1, 5])
      ->execute();

    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Bbb, b')
      ->execute()->indexBy('id');

    $this->assertEquals('Bbb, B', $result[$contacts[1]['id']]['label']);
    $this->assertEquals('b@b.b', $result[$contacts[1]['id']]['description'][0]);
    $this->assertEquals('B Town', $result[$contacts[1]['id']]['description'][1]);
    $this->assertArrayNotHasKey($contacts[0]['id'], $result);
    $this->assertArrayNotHasKey($contacts[2]['id'], $result);

    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('c@c.c')
      ->setDebug(TRUE)
      ->execute();

    // Contact email is identical to the display name. Ensure only 1 result is returned.
    $this->assertCount(1, $result);
    $this->assertStringContainsString('UNION DISTINCT', $result->debug['sql'][0]);
    $this->assertEquals('c@c.c', $result[0]['label']);
    $this->assertEquals('c@c.c', $result[0]['description'][0]);
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

  public function testQuicksearchAutocompleteWithNickname(): void {
    // Set includeNickNameInName to true
    Setting::set(FALSE)
      ->addValue('includeNickNameInName', TRUE)
      ->addValue('includeEmailInName', TRUE)
      ->addValue('contact_autocomplete_options', [1, 2])
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        [
          'first_name' => 'Robert',
          'last_name' => 'Smith',
          'nick_name' => 'Bob',
          'email_primary.email' => 'bob@example.com',
        ],
        [
          'first_name' => 'William',
          'last_name' => 'Jones',
          'nick_name' => 'Bill',
          'email_primary.email' => 'bill@example.com',
        ],
        [
          'first_name' => 'Mary',
          'last_name' => 'Smith',
          'nick_name' => '',
          'email_primary.email' => 'mary@example.com',
        ],
      ],
    ]);

    // Test contact with nickname
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Smith, Robert')
      ->execute()->indexBy('id');

    $this->assertEquals('Smith, Robert "Bob"', $result[$contacts[0]['id']]['label']);
    $this->assertEquals('bob@example.com', $result[$contacts[0]['id']]['description'][0]);
    $this->assertArrayNotHasKey($contacts[1]['id'], $result);
    $this->assertArrayNotHasKey($contacts[2]['id'], $result);

    // Test contact without nickname
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Smith, Mary')
      ->execute()->indexBy('id');

    $this->assertEquals('Smith, Mary', $result[$contacts[2]['id']]['label']);
    $this->assertEquals('mary@example.com', $result[$contacts[2]['id']]['description'][0]);
    $this->assertArrayNotHasKey($contacts[0]['id'], $result);
    $this->assertArrayNotHasKey($contacts[1]['id'], $result);

    // Test searching by nickname
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Bill')
      ->execute()->indexBy('id');

    $this->assertEquals('Jones, William "Bill"', $result[$contacts[1]['id']]['label']);
    $this->assertEquals('bill@example.com', $result[$contacts[1]['id']]['description'][0]);
    $this->assertArrayNotHasKey($contacts[0]['id'], $result);
    $this->assertArrayNotHasKey($contacts[2]['id'], $result);

    // Test searching by last name returns both Smith contacts
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Smith')
      ->execute()->indexBy('id');

    $this->assertCount(2, $result);
    $this->assertEquals('Smith, Robert "Bob"', $result[$contacts[0]['id']]['label']);
    $this->assertEquals('Smith, Mary', $result[$contacts[2]['id']]['label']);
  }

  public function testQuicksearchAutocompleteWithWildcard(): void {
    // Set includeWildCardInName to true
    Setting::set(FALSE)
      ->addValue('includeWildCardInName', TRUE)
      ->addValue('includeEmailInName', TRUE)
      ->addValue('contact_autocomplete_options', [1, 2])
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        [
          'first_name' => 'Robert',
          'last_name' => 'AttestXYZsmith',
          'email_primary.email' => 'bob@example.com',
        ],
        [
          'first_name' => 'William',
          'last_name' => 'TestXYZsmithson',
          'email_primary.email' => 'bill@example.com',
        ],
        [
          'first_name' => 'Mary',
          'last_name' => 'TestXYZblacksmith',
          'email_primary.email' => 'mary@example.com',
        ],
      ],
    ]);

    // Test that partial last name returns all matching contacts
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('testXYZsmith')
      ->execute()->indexBy('id');

    // Should return all contacts containing 'TestXYZsmith' in alphabetical order
    $this->assertCount(2, $result);
    $this->assertEquals('AttestXYZsmith, Robert', $result[$contacts[0]['id']]['label']);
    $this->assertEquals('bob@example.com', $result[$contacts[0]['id']]['description'][0]);
    $this->assertEquals('TestXYZsmithson, William', $result[$contacts[1]['id']]['label']);

    // Test that exact match still works
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('AttestXYZsmith, Robert')
      ->execute()->indexBy('id');

    $this->assertCount(1, $result);
    $this->assertEquals('AttestXYZsmith, Robert', $result[$contacts[0]['id']]['label']);

    // Turn off email. Now it won't use a UNION but should behave the same
    Setting::set(FALSE)
      ->addValue('contact_autocomplete_options', [1])
      ->addValue('includeEmailInName', FALSE)
      ->execute();

    // Test that partial last name returns all matching contacts
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('testXYZsmith')
      ->execute()->indexBy('id');

    // Should return all contacts containing 'TestXYZsmith' in alphabetical order
    $this->assertCount(2, $result);
    $this->assertEquals('AttestXYZsmith, Robert', $result[$contacts[0]['id']]['label']);
    $this->assertEmpty($result[$contacts[0]['id']]['description']);
    $this->assertEquals('TestXYZsmithson, William', $result[$contacts[1]['id']]['label']);

    // Turn off wildcard and verify behavior change
    Setting::set(FALSE)
      ->addValue('includeWildCardInName', FALSE)
      ->execute();

    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('AttestXYZ')
      ->execute()->indexBy('id');

    // Should only return exact matches now
    $this->assertCount(1, $result);
    $this->assertEquals('AttestXYZsmith, Robert', $result[$contacts[0]['id']]['label']);
  }

  public function testAddressFieldQuickSearch(): void {
    // Enable Address fields in quick search
    Setting::set(FALSE)
      ->addValue('includeNickNameInName', FALSE)
      ->addValue('includeEmailInName', TRUE)
      ->addValue('includeWildCardInName', TRUE)
      ->addValue('contact_autocomplete_options', [1, 2, 4, 6, 7])
      ->execute();
    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        [
          'first_name' => 'Robert',
          'last_name' => 'AttestXYZsmith',
          'email_primary.email' => 'bob@example.com',
          'address_primary.street_address' => '1270 Marigold Lane',
          'address_primary.state_province_id:abbr' => 'FL',
          'address_primary.country_id.name' => 'United States',
        ],
        [
          'first_name' => 'William',
          'last_name' => 'TestXYZsmithson',
          'email_primary.email' => 'bill@example.com',
          'address_primary.street_address' => '1100 Marigold Lane',
          'address_primary.state_province_id:abbr' => 'FL',
          'address_primary.country_id.name' => 'United States',
        ],
        [
          'first_name' => 'Mary',
          'last_name' => 'TestXYZblacksmith',
          'email_primary.email' => 'mary@example.com',
          'address_primary.street_address' => '1100 Marigold Lane',
          'address_primary.state_province_id:abbr' => 'FL',
          'address_primary.country_id.name' => 'United States',
        ],
      ],
    ]);
    $result = Contact::autocomplete()
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('testXYZsmith')
      ->execute()
      ->indexBy('id');
    $this->assertCount(2, $result);
    $this->assertEquals('AttestXYZsmith, Robert', $result[$contacts[0]['id']]['label']);
    $this->assertEquals('bob@example.com', $result[$contacts[0]['id']]['description'][0]);
    $this->assertEquals('1270 Marigold Lane', $result[$contacts[0]['id']]['description'][1]);
    $this->assertEquals('FL', $result[$contacts[0]['id']]['description'][2]);
    $this->assertEquals('United States', $result[$contacts[0]['id']]['description'][3]);
    $this->assertEquals('TestXYZsmithson, William', $result[$contacts[1]['id']]['label']);

  }

}
