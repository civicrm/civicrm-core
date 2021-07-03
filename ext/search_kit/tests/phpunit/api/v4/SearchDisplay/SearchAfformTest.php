<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchAfformTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * Test running a searchDisplay within an afform.
   */
  public function testRunWithAfform() {
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
            'GROUP_CONCAT(DISTINCT Contact_Email_contact_id_01.email) AS GROUP_CONCAT_DISTINCT_Contact_Email_contact_id_01_email',
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
              'dataType' => 'Integer',
              'type' => 'field',
            ],
            [
              'key' => 'display_name',
              'label' => 'Display Name',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'key' => 'GROUP_CONCAT_DISTINCT_Contact_Email_contact_id_01_email',
              'label' => 'Emails',
              'dataType' => 'String',
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

    // Note that filters add a wildcard so the value `afform_test` matches all 3 sample contacts;
    // But the Afform markup contains `filters="{last_name: 'AfformTest'}"` which only matches 2.
    $params['filters'] = ['source' => 'afform_test'];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    // Filter by email address
    $params['filters'] = ['Contact_Email_contact_id_01.email' => $email];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
  }

}
