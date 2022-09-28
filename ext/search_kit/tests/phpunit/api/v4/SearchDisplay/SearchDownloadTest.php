<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Contact;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchDownloadTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test downloading array format.
   */
  public function testDownloadArray() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName],
      ['first_name' => 'Two', 'last_name' => $lastName],
      ['first_name' => 'Three', 'last_name' => $lastName],
      ['first_name' => 'Four', 'last_name' => $lastName],
    ];
    Contact::save(FALSE)->setRecords($sampleData)->execute();

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'array',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['last_name'],
          'where' => [],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'limit' => 2,
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'key' => 'last_name',
              'label' => 'First Last',
              'dataType' => 'String',
              'type' => 'field',
              'rewrite' => '[first_name] [last_name]',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'filters' => ['last_name' => $lastName],
      'afform' => NULL,
    ];

    $download = (array) civicrm_api4('SearchDisplay', 'download', $params);
    $header = array_shift($download);

    $this->assertEquals('First Last', $header[0]);

    foreach ($download as $rowNum => $data) {
      $this->assertEquals($sampleData[$rowNum]['first_name'] . ' ' . $lastName, $data[0]);
    }
  }

  /**
   * Test downloading CSV format.
   *
   * Must run in separate process to capture direct output to browser
   *
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testDownloadCSV() {
    $this->markTestIncomplete('Unable to get this test working in separate process, probably due to being in an extension');

    // Re-enable because this test has to run in a separate process
    \CRM_Extension_System::singleton()->getManager()->install('org.civicrm.search_kit');

    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName],
      ['first_name' => 'Two', 'last_name' => $lastName],
      ['first_name' => 'Three', 'last_name' => $lastName],
      ['first_name' => 'Four', 'last_name' => $lastName],
    ];
    Contact::save(FALSE)->setRecords($sampleData)->execute();

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'csv',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['last_name'],
          'where' => [],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'limit' => 2,
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'key' => 'last_name',
              'label' => 'First Last',
              'dataType' => 'String',
              'type' => 'field',
              'rewrite' => '[first_name] [last_name]',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'filters' => ['last_name' => $lastName],
      'afform' => NULL,
    ];

    // UTF-8 BOM
    $expectedOut = preg_quote("\xEF\xBB\xBF");
    $expectedOut .= preg_quote('"First Last"');
    foreach ($sampleData as $row) {
      $expectedOut .= '\s+' . preg_quote('"' . $row['first_name'] . ' ' . $lastName . '"');
    }
    $this->expectOutputRegex('#' . $expectedOut . '#');

    try {
      civicrm_api4('SearchDisplay', 'download', $params);
      $this->fail();
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      // All good, we expected the api to exit
    }
  }

}
