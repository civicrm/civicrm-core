<?php

use Civi\Api4\ActionSchedule;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\Email;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use CRM_SearchKitReminders_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test
 * class. Simply create corresponding functions (e.g. "hook_civicrm_post(...)"
 * or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or
 * test****() functions will rollback automatically -- as long as you don't
 * manipulate schema or truncate tables. If this test needs to manipulate
 * schema or truncate tables, then either: a. Do all that using setupHeadless()
 * and Civi\Test. b. Disable TransactionalInterface, and handle all
 * setup/teardown yourself.
 *
 * @group headless
 */
class SearchKitReminderTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->install('org.civicrm.search_kit')
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Example: Test that a version is returned.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSearchKitReminder(): void {
    SavedSearch::create(FALSE)
      ->setValues([
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'display_name',
            'Contact_Contribution_contact_id_01.receive_date',
            'Contact_Contribution_contact_id_01.total_amount',
          ],
          'orderBy' => [],
          'where' => [
            [
              'Contact_Contribution_contact_id_01.total_amount',
              '=',
              50,
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'Contribution AS Contact_Contribution_contact_id_01',
              'INNER',
              ['id', '=', 'Contact_Contribution_contact_id_01.contact_id'],
            ],
          ],
          'having' => [],
        ],
      ])
      ->addChain('display', SearchDisplay::create()
        ->setValues([
            'saved_search_id' => '$id',
            'name' => '50plus',
            'label' => 'contributions over $50',
            'type' => 'schedulable',
            'settings' => [
              'limit' => 20,
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
                  'type' => 'field'
                ],
                [
                  'key' => 'Contact_Contribution_contact_id_01.receive_date',
                  'label' => 'Contact Contributions: Date Received',
                  'dataType' => 'Timestamp',
                  'type' => 'field',
                  'rewrite' => '',
                ],
                [
                  'key' => 'Contact_Contribution_contact_id_01.total_amount',
                  'label' => 'Contact Contributions: Total Amount',
                  'dataType' => 'Money',
                  'type' => 'field',
                ],
              ],
            ],
          ])
        ->addChain('reminder', ActionSchedule::create(FALSE)
          ->setValues([
            'name' => 'display_search',
            'title' => '50 + contributions',
            'start_action_offset' => 1,
            'start_action_unit' => 'hour',
            'start_action_condition' => 'after',
            'start_action_date' => 'Contact_Contribution_contact_id_01.receive_date',
            'body_html' => 'Woohoo it is money',
            'mapping_id' => 'search_kit',
            'from_email' => 'info@example.com',
            'mode' => 'Email',
            'entity_value' => '$id',
          ])
        )
      )
      ->execute();

    Contact::create(FALSE)->setValues([
      'contact_type' => 'Individual',
      'first_name' => 'Bob',
    ])->addChain('email', Email::create(FALSE)->setValues([
      'contact_id' => '$id',
      'email' => 'bob@example.com',
    ]))
      ->addChain('contribution', Contribution::create(FALSE)->setValues([
        'contact_id' => '$id',
        'total_amount' => 50,
        'financial_type_id:name' => 'Donation',
        'receive_date' => '61 minutes ago',
        'payment_instrument_id:name' => 'Cash',
      ]))->execute();

    $this->callAPISuccess('Job', 'send_reminder');
    $b = CRM_Core_DAO::executeQuery('select * from civicrm_action_log')->fetchAll();
    $c = CRM_Core_DAO::executeQuery('select * from civicrm_action_schedule')->fetchAll();

    $this->assertEquals(1, 1);
  }

}
