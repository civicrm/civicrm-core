<?php
namespace api\v4\SearchDisplay;

require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\Phone;

/**
 * @group headless
 */
class EntitySetTest extends Api4TestBase implements TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test SearchDisplay::run with an EntitySet saved search and result_row_num.
   */
  public function testSearchDisplayRunEntitySetWithResultRowNum(): void {
    $contact = Contact::create(FALSE)
      ->addValue('first_name', 'Test')
      ->addValue('last_name', 'User')
      ->execute()
      ->first();
    $contactId = $contact['id'];

    Email::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('email', 'test@example.com')
      ->execute();

    Phone::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('phone', '555-1234')
      ->execute();

    Phone::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('phone', '555-4321')
      ->addValue('is_primary', FALSE)
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'EntitySet',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'email',
            'contact_id',
            'result_row_num',
          ],
          'sets' => [
            [
              'UNION ALL',
              'Email',
              'get',
              [
                'select' => [
                  'id',
                  'email',
                  'contact_id',
                ],
                'orderBy' => [],
                'where' => [],
                'groupBy' => [],
                'join' => [],
              ],
            ],
            [
              'UNION ALL',
              'Phone',
              'get',
              [
                'select' => [
                  'id',
                  'phone',
                  'contact_id',
                ],
                'where' => [
                  ['is_primary', '=', TRUE],
                ],
              ],
            ],
          ],
          'where' => [
            [
              'contact_id',
              '=',
              $contactId,
            ],
          ],
          'having' => [],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'testDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'ID',
            ],
            [
              'type' => 'field',
              'key' => 'email',
              'label' => 'Email/Phone',
            ],
            [
              'type' => 'field',
              'key' => 'result_row_num',
              'label' => 'Row',
            ],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    // Verify values and result_row_num
    $this->assertEquals(1, $result[0]['columns'][2]['val']);
    $this->assertEquals(2, $result[1]['columns'][2]['val']);
  }

}
