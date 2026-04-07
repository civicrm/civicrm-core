<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Action\Afform\Save;
use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Phone;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\Utils\CoreUtil;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchAfformTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use Api4TestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('has_local', '=', TRUE)->execute();
    $this->conditionallyDeleteTestRecords();
    parent::tearDown();
  }

  /**
   * Test running a searchDisplay within an afform.
   */
  public function testRunWithAfform(): void {
    $search = SavedSearch::create(FALSE)
      ->setValues([
        'name' => 'TestContactEmailSearch',
        'label' => 'TestContactEmailSearch',
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'display_name',
            'GROUP_CONCAT(DISTINCT Contact_Email_contact_id_01.email) AS GROUP_CONCAT_Contact_Email_contact_id_01_email',
            'YEAR(birth_date) AS YEAR_birth_date',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => ['id'],
          'join' => [
            [
              'Email AS Contact_Email_contact_id_01',
              'LEFT',
              ['id', '=', 'Contact_Email_contact_id_01.contact_id'],
            ],
          ],
          'having' => [],
        ],
      ])
      ->execute()->first();

    $display = SearchDisplay::create(FALSE)
      ->setValues([
        'name' => 'TestContactEmailDisplay',
        'label' => 'TestContactEmailDisplay',
        'saved_search_id.name' => 'TestContactEmailSearch',
        'type' => 'table',
        'settings' => [
          'limit' => 50,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'type' => 'field',
            ],
            [
              'key' => 'display_name',
              'label' => 'Display Name',
              'type' => 'field',
            ],
            [
              'key' => 'GROUP_CONCAT_Contact_Email_contact_id_01_email',
              'label' => 'Emails',
              'type' => 'field',
            ],
            [
              'key' => 'YEAR_birth_date',
              'label' => 'Contact ID',
              'type' => 'field',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ])
      ->execute()->first();

    $email = uniqid('tester@');

    Contact::create(FALSE)
      ->addValue('first_name', 'tester')
      ->addValue('last_name', 'AfformTest')
      ->addValue('source', 'afform_test')
      ->addValue('birth_date', '2020-01-01')
      ->addChain('emails', Email::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['email' => $email, 'location_type_id:name' => 'Home'])
        ->addRecord(['email' => $email, 'location_type_id:name' => 'Work'])
      )
      ->execute();

    Contact::create(FALSE)
      ->addValue('first_name', 'tester2')
      ->addValue('last_name', 'AfformTest')
      ->addValue('source', 'afform_test2')
      ->addValue('birth_date', '2010-01-01')
      ->addChain('emails', Email::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['email' => 'other@test.com', 'location_type_id:name' => 'Other'])
      )
      ->execute();

    Contact::create(FALSE)
      ->addValue('first_name', 'tester3')
      ->addValue('last_name', 'excluded from test')
      ->addValue('source', 'afform_test3')
      ->execute();

    $params = [
      'return' => 'page:1',
      'savedSearch' => $search['name'],
      'display' => $display['name'],
      'afform' => 'testContactEmailSearchForm',
    ];

    // Try a filter that is on the afform but not the search
    $params['filters'] = ['source' => 'afform_test2'];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);

    // That same param will not work without the afform
    $params['filters'] = ['source' => 'afform_test2'];
    $result = civicrm_api4('SearchDisplay', 'run', ['afform' => NULL] + $params);
    $this->assertGreaterThan(1, $result->count());

    // Try a filter that is in neither afform nor search - it should not work
    $params['filters'] = ['first_name' => 'tester2'];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertGreaterThan(1, $result->count());

    // For a filter with options, ensure labels are set
    $params['filters'] = ['contact_type' => ['Individual']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertGreaterThan(1, $result->count());
    $this->assertEquals(['Individual'], $result->labels);

    // Note that filters add a wildcard so the value `afform_test` matches all 3 sample contacts;
    // But the Afform markup contains `filters="{last_name: 'AfformTest'}"` which only matches 2.
    $params['filters'] = ['source' => 'afform_test'];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    // Filter by email address
    $params['filters'] = ['Contact_Email_contact_id_01.email' => $email];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);

    // Filter by YEAR(birth_date)
    $params['filters'] = [
      'YEAR_birth_date' => ['>=' => 2019],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
  }

  public function testRunMultipleSearchForm(): void {
    $email = uniqid('tester@');

    Contact::create(FALSE)
      ->addValue('first_name', 'tester')
      ->addValue('last_name', __FUNCTION__)
      ->addValue('source', 'afform_multi_test')
      ->addChain('emails', Email::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['email' => $email, 'location_type_id:name' => 'Home'])
        ->addRecord(['email' => $email, 'location_type_id:name' => 'Work'])
      )
      ->addChain('phones', Phone::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['phone' => '123-4567', 'location_type_id:name' => 'Home'])
        ->addRecord(['phone' => '234-5678', 'location_type_id:name' => 'Work'])
      )
      ->execute();

    Contact::create(FALSE)
      ->addValue('first_name', 'tester2')
      ->addValue('last_name', __FUNCTION__)
      ->addValue('source', 'afform_multi_test')
      ->addChain('emails', Email::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['email' => 'other@test.com', 'location_type_id:name' => 'Other'])
      )
      ->addChain('phones', Phone::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['phone' => '123-4567', 'location_type_id:name' => 'Home'])
        ->addRecord(['phone' => '234-5678', 'location_type_id:name' => 'Work'])
      )
      ->execute();

    // Decoy contact just to make sure we don't get false-positives
    Contact::create(FALSE)
      ->addValue('first_name', 'tester3')
      ->addValue('last_name', 'nobody')
      ->addValue('source', 'decoy')
      ->addChain('emails', Email::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['email' => $email, 'location_type_id:name' => 'Home'])
      )
      ->addChain('phones', Phone::save()
        ->addDefault('contact_id', '$id')
        ->addRecord(['phone' => '123-4567', 'location_type_id:name' => 'Home'])
        ->addRecord(['phone' => '234-5678', 'location_type_id:name' => 'Work'])
      )
      ->execute();

    $contactEmailSearch = SavedSearch::save(FALSE)
      ->addRecord([
        'name' => 'TestContactEmailSearch',
        'label' => 'TestContactEmailSearch',
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'display_name',
            'GROUP_CONCAT(DISTINCT Contact_Email_contact_id_01.email) AS GROUP_CONCAT_Contact_Email_contact_id_01_email',
          ],
          'orderBy' => [],
          'where' => [
            ['contact_type:name', '=', 'Individual'],
          ],
          'groupBy' => ['id'],
          'join' => [
            [
              'Email AS Contact_Email_contact_id_01',
              'LEFT',
              ['id', '=', 'Contact_Email_contact_id_01.contact_id'],
            ],
          ],
          'having' => [],
        ],
      ])
      ->setMatch(['name'])
      ->execute()->first();

    $contactEmailDisplay = SearchDisplay::save(FALSE)
      ->addRecord([
        'name' => 'TestContactEmailDisplay',
        'label' => 'TestContactEmailDisplay',
        'saved_search_id.name' => 'TestContactEmailSearch',
        'type' => 'table',
        'settings' => [
          'limit' => 50,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'type' => 'field',
            ],
            [
              'key' => 'display_name',
              'label' => 'Display Name',
              'type' => 'field',
            ],
            [
              'key' => 'GROUP_CONCAT_Contact_Email_contact_id_01_email',
              'label' => 'Emails',
              'type' => 'field',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ])
      ->setMatch(['name'])
      ->execute()->first();

    foreach (['Email', 'Phone'] as $entity) {
      SavedSearch::save(FALSE)
        ->addRecord([
          'name' => 'TestSearchFor' . $entity,
          'label' => 'TestSearchFor' . $entity,
          'api_entity' => $entity,
          'api_params' => [
            'version' => 4,
            'select' => [
              'id',
              'contact_id.display_name',
            ],
            'orderBy' => [],
            'where' => [],
            'groupBy' => [],
            'join' => [],
            'having' => [],
          ],
        ])
        ->setMatch(['name'])
        ->execute();
    }

    $params = [
      'return' => 'page:1',
      'display' => NULL,
      'afform' => 'testMultipleSearchForm',
    ];

    // This filter will not work because the search display is not within an <af-field>
    $params['savedSearch'] = 'TestSearchForPhone';
    $params['filters'] = ['location_type_id' => 1];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    $params['savedSearch'] = 'TestSearchForEmail';
    $params['filters'] = ['location_type_id' => 1, 'contact_id.display_name' => __FUNCTION__];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);

    // Email filter will not work because it's in the wrong fieldset on the form
    $params['filters'] = ['email' => $email, 'contact_id.display_name' => __FUNCTION__];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // No filters will work; they are in the fieldset belonging to the non-default display
    $params['savedSearch'] = 'TestContactEmailSearch';
    $params['filters'] = ['source' => 'afform_multi_test', 'Contact_Email_contact_id_01.location_type_id' => 1];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertGreaterThanOrEqual(3, $result->count());

    // Now the filters will work because they are in the fieldset for this display
    $params['display'] = 'TestContactEmailDisplay';
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
  }

  public function testSearchReferencesToAfform(): void {
    $search = SavedSearch::save(FALSE)
      ->addRecord([
        'name' => 'TestSearchToDelete',
        'label' => 'TestSearchToDelete',
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
        ],
      ])
      ->setMatch(['name'])
      ->execute()->first();

    $display = SearchDisplay::save(FALSE)
      ->addRecord([
        'name' => 'TestDisplayToDelete',
        'label' => 'TestDisplayToDelete',
        'saved_search_id.name' => 'TestSearchToDelete',
        'type' => 'table',
        'settings' => [
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'type' => 'field',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ])
      ->setMatch(['saved_search_id', 'name'])
      ->execute()->first();

    // The search should have one reference (its display)
    $refs = CoreUtil::getRefCount('SavedSearch', $search['id']);
    $this->assertCount(1, $refs);

    // The display should have zero references
    $refs = CoreUtil::getRefCount('SearchDisplay', $display['id']);
    $this->assertCount(0, $refs);

    Afform::create(FALSE)
      ->addValue('name', 'TestAfformToDelete')
      ->addValue('title', 'TestAfformToDelete')
      ->setLayoutFormat('html')
      ->addValue('layout', '<div><crm-search-display-table search-name="TestSearchToDelete" display-name="TestDisplayToDelete"></crm-search-display-table></div>')
      ->execute();

    $this->assertCount(1, Afform::get(FALSE)->addWhere('search_displays', 'CONTAINS', 'TestSearchToDelete.TestDisplayToDelete')->execute());

    // The search should now have two references (its display + Afform)
    $refs = CoreUtil::getRefCount('SavedSearch', $search['id']);
    $this->assertCount(2, $refs);

    // The display should now have one reference (the Afform)
    $refs = CoreUtil::getRefCount('SearchDisplay', $display['id']);
    $this->assertCount(1, $refs);
    $this->assertEquals('Afform', $refs[0]['type']);

    // Create an afform that uses the search default display
    Afform::create(FALSE)
      ->addValue('name', 'TestAfformToDelete2')
      ->addValue('title', 'TestAfformToDelete2')
      ->setLayoutFormat('html')
      ->addValue('layout', '<div><crm-search-display-table search-name="TestSearchToDelete"></crm-search-display-table></div>')
      ->execute();

    $this->assertCount(1, Afform::get(FALSE)->addWhere('search_displays', 'CONTAINS', 'TestSearchToDelete')->execute());
    $this->assertCount(2, Afform::get(FALSE)->addWhere('name', 'CONTAINS', 'TestAfformToDelete')->execute());

    // The search should now have three references (its display + 2 Afforms)
    $refs = CoreUtil::getRefCount('SavedSearch', $search['id']);
    $this->assertCount(2, $refs);
    $this->assertEquals(2, array_column($refs, 'count', 'type')['Afform']);

    // The display should still have one reference (the Afform)
    $refs = CoreUtil::getRefCount('SearchDisplay', $display['id']);
    $this->assertCount(1, $refs);
    $this->assertEquals('Afform', $refs[0]['type']);

    SearchDisplay::delete(FALSE)
      ->addWhere('name', '=', 'TestDisplayToDelete')
      ->execute();

    $this->assertCount(1, Afform::get(FALSE)->addWhere('name', 'CONTAINS', 'TestAfformToDelete')->execute());

    SavedSearch::delete(FALSE)
      ->addWhere('name', '=', 'TestSearchToDelete')
      ->execute();

    $this->assertCount(0, Afform::get(FALSE)->addWhere('search_displays', 'CONTAINS', 'TestSearchToDelete.TestDisplayToDelete')->execute());
    $this->assertCount(0, Afform::get(FALSE)->addWhere('name', 'CONTAINS', 'TestAfformToDelete')->execute());
  }

  public function testDisplaysSharingSameFieldset(): void {
    OptionGroup::save(FALSE)
      ->addRecord([
        'title' => 'search_test_options',
      ])
      ->setMatch(['title'])
      ->execute();
    OptionValue::save(FALSE)
      ->setDefaults(['option_group_id.name' => 'search_test_options'])
      ->addRecord([
        'label' => 'option_a',
        'value' => 'a',
      ])
      ->addRecord([
        'label' => 'option_b',
        'value' => 'b',
      ])
      ->addRecord([
        'label' => 'option_c',
        'value' => 'c',
        'is_active' => FALSE,
      ])
      ->setMatch(['name', 'option_group_id'])
      ->execute();

    $search = SavedSearch::save(FALSE)
      ->addRecord([
        'name' => 'testDisplaysSharingSameFieldset',
        'label' => 'testDisplaysSharingSameFieldset',
        'api_entity' => 'OptionValue',
        'api_params' => [
          'version' => 4,
          'select' => ['value'],
        ],
      ])
      ->setMatch(['name'])
      ->execute()->first();

    $display = SearchDisplay::save(FALSE)
      ->addRecord([
        'name' => 'testDisplaysSharingSameFieldset',
        'label' => 'testDisplaysSharingSameFieldset',
        'saved_search_id.name' => 'testDisplaysSharingSameFieldset',
        'type' => 'table',
        'settings' => [
          'columns' => [
            [
              'key' => 'value',
              'type' => 'field',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ])
      ->setMatch(['saved_search_id', 'name'])
      ->execute()->first();

    $baseParams = [
      'return' => 'page:1',
      'savedSearch' => $search['name'],
      'display' => $display['name'],
      'afform' => 'testDisplaysSharingSameFieldset',
      'filters' => ['option_group_id:name' => 'search_test_options'],
    ];

    // Afform has 2 copies of the same display, with different values for the filter is_active
    // This should allow the is_active filters to be set in the params
    $params = $baseParams;
    $params['filters']['is_active'] = TRUE;
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    $params = $baseParams;
    $params['filters']['is_active'] = FALSE;
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
    $this->assertEquals('c', $result[0]['columns'][0]['val']);

    // Because the 2 displays share a fieldset, the filter field should work on both
    $params = $baseParams;
    $params['filters']['is_active'] = TRUE;
    $params['filters']['label'] = 'b';
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
    $this->assertEquals('b', $result[0]['columns'][0]['val']);

    $params = $baseParams;
    $params['filters']['is_active'] = FALSE;
    $params['filters']['label'] = 'b';
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(0, $result);
  }

  public function testRunWithJoinFilters(): void {
    $lastName = uniqid();

    $this->createTestRecord('SavedSearch', [
      'name' => 'Contacts_and_activities',
      'label' => 'Contacts and activities',
      'form_values' => [
        'join' => [
          'Contact_ActivityContact_Activity_01' => 'The Activities',
        ],
      ],
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
        'select' => [
          'id',
          'sort_name',
          'Contact_ActivityContact_Activity_01.subject',
        ],
        'orderBy' => [],
        'where' => [
          ['last_name', '=', $lastName],
        ],
        'groupBy' => [],
        'join' => [
          [
            'Activity AS Contact_ActivityContact_Activity_01',
            'LEFT',
            'ActivityContact',
            ['id', '=', 'Contact_ActivityContact_Activity_01.contact_id'],
            ['Contact_ActivityContact_Activity_01.record_type_id:name', '=', '"Activity Source"'],
          ],
        ],
        'having' => [],
      ],
    ]);

    $markupWithJoinClause = <<<HTML
      <div af-fieldset="">
        <af-field name="Contact_ActivityContact_Activity_01.status_id" defn="{input_attrs: {multiple: true}, join_clause: 'Contact_ActivityContact_Activity_01'}" />
        <af-field name="Contact_ActivityContact_Activity_01.subject" defn="{join_clause: 'Contact_ActivityContact_Activity_01'}" />
        <crm-search-display-table search-name="Contacts_and_activities" display-name=""></crm-search-display-table>
      </div>
      HTML;

    $markupWithoutJoinClause = <<<HTML
      <div af-fieldset="">
        <af-field name="Contact_ActivityContact_Activity_01.status_id" defn="{input_attrs: {multiple: true}, join_clause: ''}" />
        <af-field name="Contact_ActivityContact_Activity_01.subject" />
        <crm-search-display-table search-name="Contacts_and_activities" display-name=""></crm-search-display-table>
      </div>
      HTML;

    Afform::create(FALSE)
      ->addValue('name', 'TestAfformWithSearch')
      ->addValue('title', 'TestAfformWithSearch')
      ->setLayoutFormat('html')
      ->addValue('layout', $markupWithJoinClause)
      ->execute();

    $cid = $this->saveTestRecords('Individual', [
      'records' => 5,
      'defaults' => ['last_name' => $lastName],
    ])->column('id');

    $this->saveTestRecords('Activity', [
      'records' => [
        ['source_contact_id' => $cid[0], 'status_id' => 1, 'subject' => 'Activity 1'],
        ['source_contact_id' => $cid[1], 'status_id' => 2, 'subject' => 'Activity 2'],
        ['source_contact_id' => $cid[2], 'status_id' => 2, 'subject' => 'Activity 3'],
      ],
    ]);

    $params = [
      'return' => 'page:1',
      'savedSearch' => 'Contacts_and_activities',
      'afform' => 'TestAfformWithSearch',
      'filters' => [],
      'sort' => [['id', 'ASC']],
      'debug' => TRUE,
    ];

    // Test with join clause: status_id filter
    $params['filters'] = ['Contact_ActivityContact_Activity_01.status_id' => [2]];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(5, $result);
    $this->assertNull($result[0]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertEquals('Activity 2', $result[1]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertEquals('Activity 3', $result[2]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertNull($result[3]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertNull($result[4]['data']['Contact_ActivityContact_Activity_01.subject']);

    // Test with join clause: subject filter
    $params['filters'] = ['Contact_ActivityContact_Activity_01.subject' => 'Activity 2'];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(5, $result);
    $this->assertNull($result[0]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertEquals('Activity 2', $result[1]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertNull($result[2]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertNull($result[3]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertNull($result[4]['data']['Contact_ActivityContact_Activity_01.subject']);

    Afform::update(FALSE)
      ->addWhere('name', '=', 'TestAfformWithSearch')
      ->addValue('layout', $markupWithoutJoinClause)
      ->execute();

    // Test without join clause: status_id filter
    $params['filters'] = ['Contact_ActivityContact_Activity_01.status_id' => [2]];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals('Activity 2', $result[0]['data']['Contact_ActivityContact_Activity_01.subject']);
    $this->assertEquals('Activity 3', $result[1]['data']['Contact_ActivityContact_Activity_01.subject']);
  }

}
