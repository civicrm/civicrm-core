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
    \Civi::settings()->revert('search_mysql_fts');
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
        ['email_primary.email' => 'c@c.c', 'email_billing.email' => 'bill@c.c', 'address_primary.city' => 'C Town'],
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

    // Search by email to match non-primary email address
    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->addFilter('Email.email', 'bill@c.c')
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('c@c.c :: bill@c.c', $result[0]['label']);
    // Duplicate email removed
    $this->assertCount(1, $result[0]['description']);
    $this->assertEquals('C Town', $result[0]['description'][0]);
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
    // note: only respected when FTS is off
    Setting::set(FALSE)
      ->addValue('includeWildCardInName', TRUE)
      ->addValue('includeEmailInName', TRUE)
      ->addValue('contact_autocomplete_options', [1, 2])
      ->addValue('search_mysql_fts', FALSE)
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

    // Turn off leading wildcard and verify behavior change
    Setting::set(FALSE)
      ->addValue('includeWildCardInName', FALSE)
      ->execute();

    $result = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('testXYZsmith')
      ->execute()->indexBy('id');

    // Should return TestXYZsmith... but *not* AttestXYZsmith
    $this->assertCount(1, $result);
    $this->assertEquals('TestXYZsmithson, William', $result[$contacts[1]['id']]['label']);
  }

  public function testQuicksearchFullTextSearch(): void {

    Setting::set(FALSE)
      ->addValue('search_mysql_fts', TRUE)
      ->addValue('includeEmailInName', TRUE)
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
          'legal_name' => 'Jaden Smith',
        ],
        [
          'first_name' => 'Mary',
          'nick_name' => 'Mags',
          'last_name' => 'TestXYZblacksmith',
          'email_primary.email' => 'mary@example.com',
        ],
      ],
    ]);

    // find contacts with two partial names in any order
    $results = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('test')
      ->execute();

    // find 2 contacts with partial leading match
    $this->assertCount(2, $results);

    // find contacts with two partial names in any order
    $results = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('will test')
      ->execute();

    $this->assertCount(1, $results);
    $result = $results->first();
    $this->assertEquals('TestXYZsmithson, William', $result['label']);
    $this->assertEquals(1, array_find_key((array) $contacts, fn ($contact) => $contact['id'] === $result['id']));

    // find contacts by nick_name - note with FTS
    // we ignore the "includeNickNameInName" setting
    // because there is no extra cost
    $results = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Mags')
      ->execute();

    $this->assertCount(1, $results);
    $result = $results->first();
    $this->assertEquals('TestXYZblacksmith, Mary "Mags"', $result['label']);
    $this->assertEquals(2, array_find_key((array) $contacts, fn ($contact) => $contact['id'] === $result['id']));

    // find contacts by legal_name
    $results = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setInput('Jaden')
      ->execute();

    $this->assertCount(1, $results);
    $result = $results->first();
    $this->assertEquals('TestXYZsmithson, William', $result['label']);
    $this->assertEquals(1, array_find_key((array) $contacts, fn ($contact) => $contact['id'] === $result['id']));
  }

  public function testAddressFieldQuickSearch(): void {
    // Enable Address fields in quick search
    Setting::set(FALSE)
      ->addValue('includeNickNameInName', FALSE)
      ->addValue('includeEmailInName', TRUE)
      ->addValue('contact_autocomplete_options', [1, 2, 4, 6, 7])
      ->execute();
    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        [
          'first_name' => 'Robert',
          'last_name' => 'TestXYZsmith',
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
          'address_primary.street_address' => '1300 Marigold Lane',
          'address_primary.state_province_id:abbr' => 'FL',
          'address_primary.country_id.name' => 'United States',
          'address_billing.street_address' => '1600 Marigold Lane',
          'address_billing.state_province_id:abbr' => 'FL',
          'address_billing.country_id.name' => 'United States',
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
    $this->assertEquals('TestXYZsmith, Robert', $result[$contacts[0]['id']]['label']);
    $this->assertEquals('bob@example.com', $result[$contacts[0]['id']]['description'][0]);
    $this->assertEquals('1270 Marigold Lane', $result[$contacts[0]['id']]['description'][1]);
    $this->assertEquals('FL', $result[$contacts[0]['id']]['description'][2]);
    $this->assertEquals('United States', $result[$contacts[0]['id']]['description'][3]);
    $this->assertEquals('TestXYZsmithson, William', $result[$contacts[1]['id']]['label']);

    // Search by non-primary address
    $result = Contact::autocomplete()
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->addFilter('Address.street_address', '1600 Marigold Lane')
      ->execute()
      ->indexBy('id');
    $this->assertCount(1, $result);
    $this->assertEquals('TestXYZblacksmith, Mary :: 1600 Marigold Lane', $result[$contacts[2]['id']]['label']);
  }

  public function testQuicksearchAutocompleteWithNonNumericPhone(): void {
    Setting::set(FALSE)
      ->addValue('quicksearch_options', ['sort_name', 'Phone.phone_numeric'])
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'Aaa', 'phone_primary.phone' => '123456'],
        ['first_name' => 'B', 'last_name' => 'Bbb', 'phone_primary.phone' => '789012'],
      ],
    ]);

    $cid = $contacts[0]['id'];

    // Search with non-numeric formatting in phone number
    $resultWithNonNumeric = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['Phone.phone_numeric' => '(123) 456'])
      ->execute();

    $resultNumeric = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['Phone.phone_numeric' => '123456'])
      ->execute();

    $this->assertEquals($resultNumeric, $resultWithNonNumeric);
    $this->assertCount(1, $resultNumeric);
    $this->assertEquals($cid, $resultNumeric[0]['id']);
  }

  public function testQuicksearchAutocompleteExactVsPartialMatches(): void {
    $this->createTestRecord('CustomGroup', [
      'name' => 'QuicksearchTestGroup',
    ]);
    $this->createTestRecord('CustomField', [
      'name' => 'TestString',
      'custom_group_id.name' => 'QuicksearchTestGroup',
    ]);
    // we want to check integer fields are exact matched
    $this->createTestRecord('CustomField', [
      'name' => 'TestInteger',
      'custom_group_id.name' => 'QuicksearchTestGroup',
      'data_type' => 'Int',
    ]);

    Setting::set(FALSE)
      ->addValue('quicksearch_options', ['Contribution.id', 'QuicksearchTestGroup.TestString', 'QuicksearchTestGroup.TestInteger'])
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'Aaa', 'QuicksearchTestGroup.TestInteger' => '123', 'QuicksearchTestGroup.TestString' => '123'],
        ['first_name' => 'B', 'last_name' => 'Bbb', 'QuicksearchTestGroup.TestInteger' => '12', 'QuicksearchTestGroup.TestString' => '12'],
      ],
    ]);

    // now we want to check exact match search for contribution 1 doesnt match contribution 10/11/12
    // to do this we add a Contribution for Contact B with Contribution ID at least 10
    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[0]['id'], 'total_amount' => '10'],
        ['contact_id' => $contacts[1]['id'], 'total_amount' => '10'],
      ],
    ]);
    $secondContactContributionId = $contributions[10]['id'];
    $this->assertTrue($secondContactContributionId >= 10);
    $partialMatch = intdiv($secondContactContributionId, 10);

    // Check we dont get Contact B with the partial match
    $partialMatchResult = (array) Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['Contribution.id' => $partialMatch])
      ->execute();

    // we'll probably get the first contact, but we cant be sure
    // the important thing is we dont get the second contact
    $secondContact = array_filter($partialMatchResult, fn ($item) => $item['id'] === $contacts[1]['id']);
    $this->assertCount(0, $secondContact);

    $customStringResult = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['QuicksearchTestGroup.TestString' => '12'])
      ->execute();

    // partial match => both contact should be returned
    $this->assertCount(2, $customStringResult);

    $customIntegerResult = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['QuicksearchTestGroup.TestInteger' => '12'])
      ->execute();

    // exact match => only the second contact should be returned
    $this->assertCount(1, $customIntegerResult);
    $this->assertEquals($contacts[1]['id'], $customIntegerResult[0]['id']);
  }

  public function testQuicksearchCustomFieldWithOptions(): void {
    $this->createTestRecord('OptionGroup', [
      'name' => 'CarTypes',
    ]);
    $this->saveTestRecords('OptionValue', [
      'records' => [
        ['label' => 'Ferrari', 'value' => 1, 'option_group_id:name' => 'CarTypes'],
        ['label' => 'Ford', 'value' => 2, 'option_group_id:name' => 'CarTypes'],
        ['label' => 'Mercedes', 'value' => 3, 'option_group_id:name' => 'CarTypes'],
        // value 10 contains value 1 - to check option values are not partial matched
        ['label' => 'Tesla', 'value' => 10, 'option_group_id:name' => 'CarTypes'],
      ],
    ]);
    $this->createTestRecord('CustomGroup', [
      'name' => 'QuicksearchTestGroup',
    ]);
    $this->createTestRecord('CustomField', [
      'name' => 'DreamCar',
      'custom_group_id.name' => 'QuicksearchTestGroup',
      'option_group_id:name' => 'CarTypes',
      'data_type' => 'Int',
      'html_type' => 'Select',
    ]);
    $this->createTestRecord('CustomField', [
      'name' => 'CarsOwned',
      'custom_group_id.name' => 'QuicksearchTestGroup',
      'option_group_id:name' => 'CarTypes',
      'data_type' => 'Int',
      'html_type' => 'Select',
      'serialize' => 1,
    ]);

    $contacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'Aaa', 'QuicksearchTestGroup.DreamCar:label' => 'Ferrari', 'QuicksearchTestGroup.CarsOwned:label' => ['Ford', 'Ferrari']],
        ['first_name' => 'B', 'last_name' => 'Bbb', 'QuicksearchTestGroup.DreamCar:label' => 'Ford', 'QuicksearchTestGroup.CarsOwned:label' => ['Ford']],
        ['first_name' => 'C', 'last_name' => 'Ccc', 'QuicksearchTestGroup.DreamCar:label' => 'Mercedes', 'QuicksearchTestGroup.CarsOwned:label' => ['Tesla']],
        ['first_name' => 'D', 'last_name' => 'Ddd', 'QuicksearchTestGroup.DreamCar:label' => 'Tesla'],
      ],
    ]);

    // NOTE: Option Custom Fields are exposed to quicksearch_options with :label suffix
    Setting::set(FALSE)
      ->addValue('quicksearch_options', ['QuicksearchTestGroup.DreamCar:label', 'QuicksearchTestGroup.CarsOwned:label'])
      ->execute();

    // Check we can search using human-readable option labels
    $mercedes = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['QuicksearchTestGroup.DreamCar:label' => 'Mercedes'])
      ->execute();

    $this->assertCount(1, $mercedes);
    $this->assertEquals($mercedes[0]['id'], $contacts[2]['id']);

    // Ferrari (1) should not match Tesla (10)
    $ferrari = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['QuicksearchTestGroup.DreamCar:label' => 'Ferrari'])
      ->execute();

    $this->assertCount(1, $ferrari);
    $this->assertEquals($ferrari[0]['id'], $contacts[0]['id']);

    // Multi-value fields match any one of the values, but still not partially
    $ownsFord = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['QuicksearchTestGroup.CarsOwned:label' => 'Ford'])
      ->execute();

    $this->assertEquals([$contacts[0]['id'], $contacts[1]['id']], array_column((array) $ownsFord, 'id'));

    $ownsFerrari = Contact::autocomplete(FALSE)
      ->setFormName('crmMenubar')
      ->setFieldName('crm-qsearch-input')
      ->setFilters(['QuicksearchTestGroup.CarsOwned:label' => 'Ferrari'])
      ->execute();

    $this->assertCount(1, $ownsFerrari);
    $this->assertEquals($ownsFerrari[0]['id'], $contacts[0]['id']);

    // TODO: the following seems like reasonable behaviour, but
    // it doesn't work... yet.

    // // Search should use contains so partial matches should be found
    // $beginsWithF = Contact::autocomplete(FALSE)
    //   ->setFormName('crmMenubar')
    //   ->setFieldName('crm-qsearch-input')
    //   ->setFilters(['QuicksearchTestGroup.DreamCar:label' => 'F'])
    //   ->execute();

    // // only the first two contacts should be returned
    // $this->assertCount(2, $beginsWithF);
  }

}
