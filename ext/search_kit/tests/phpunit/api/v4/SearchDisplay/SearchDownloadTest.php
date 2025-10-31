<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\Event;
use Civi\Api4\OptionValue;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
    $cid = Contact::create(FALSE)->execute()->single()['id'];
    $subject = uniqid(__FUNCTION__);
    $sampleData = [
      ['duration' => 1, 'subject' => $subject, 'details' => '<p>Markup</p>'],
      ['duration' => 3, 'subject' => $subject, 'details' => 'Plain &amp; simple'],
      ['duration' => 3, 'subject' => $subject],
      ['duration' => 4, 'subject' => $subject],
    ];
    Activity::save(FALSE)
      ->setRecords($sampleData)
      ->setDefaults(['activity_type_id:name' => 'Meeting', 'source_contact_id' => $cid])
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'array',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['subject', 'details'],
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
              'key' => 'subject',
              'label' => 'Duration Subject',
              'type' => 'field',
              'rewrite' => '[duration] [subject]',
            ],
            // This column ought to be removed by the download action
            [
              'type' => 'links',
              'links' => [],
            ],
            [
              'key' => 'details',
              'label' => 'Details',
              'type' => 'html',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'filters' => ['subject' => $subject],
      'afform' => NULL,
    ];

    $download = (array) civicrm_api4('SearchDisplay', 'download', $params);
    $header = array_shift($download);

    $this->assertEquals('Duration Subject', $header[0]);
    $this->assertEquals('Details', $header[1]);

    foreach ($download as $rowNum => $data) {
      $this->assertEquals($sampleData[$rowNum]['duration'] . ' ' . $subject, $data[0]);
    }
    // Markup should be formatted as plain text
    $this->assertEquals('Markup', $download[0][1]);
    $this->assertEquals('Plain & simple', $download[1][1]);
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

  /**
   * Test downloading xlsx format.
   */
  public function testDownloadXlsxContact(): void {
    $cid = Contact::create(FALSE)
      ->setValues([
        'first_name' => 'Test',
        'birth_date' => '2020-05-23',
        'do_not_email' => FALSE,
        'do_not_mail' => TRUE,
      ])->execute()->single()['id'];

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'xlsx',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'where' => [['id', '=', $cid]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'test',
        'settings' => [
          'actions' => TRUE,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'Contact ID',
            ],
            [
              'type' => 'field',
              'key' => 'first_name',
              'label' => 'First Name',
            ],
            [
              'type' => 'field',
              'key' => 'birth_date',
              'label' => 'Birth Date',
            ],
            [
              'type' => 'field',
              'key' => 'do_not_email',
              'label' => 'Do Not Email',
            ],
            [
              'type' => 'field',
              'key' => 'do_not_mail',
              'label' => 'Do Not Mail (rewrite)',
              'rewrite' => 'rewrite: [do_not_mail]',
            ],
          ],
        ],
      ],
      'afform' => NULL,
    ];

    ob_start();
    try {
      civicrm_api4('SearchDisplay', 'download', $params);
      static::fail();
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      // All good, we expected the api to exit
    }

    $xlsx = ob_get_clean();
    $tmpFile = tempnam(sys_get_temp_dir(), 'SearchDownloadTestXslx');
    try {
      file_put_contents($tmpFile, $xlsx);
      $reader = IOFactory::createReader('Xlsx');
      $spreadsheet = $reader->load($tmpFile);
      $sheet = $spreadsheet->getSheet(0);

      static::assertSame(2, $sheet->getHighestRow());
      static::assertSame('E', $sheet->getHighestColumn());

      static::assertSame('Contact ID', $sheet->getCell('A1')->getValue());
      static::assertSame($cid, $sheet->getCell('A2')->getValue());
      static::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('A2')->getDataType());
      static::assertSame('General', $sheet->getCell('A2')->getStyle()->getNumberFormat()->getFormatCode());

      static::assertSame('First Name', $sheet->getCell('B1')->getValue());
      static::assertSame('Test', $sheet->getCell('B2')->getValue());
      static::assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());

      static::assertSame('Birth Date', $sheet->getCell('C1')->getValue());
      static::assertSame(43974.0, $sheet->getCell('C2')->getValue());
      static::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('C2')->getDataType());
      static::assertSame('mmmm d, yyyy', $sheet->getCell('C2')->getStyle()->getNumberFormat()->getFormatCode());

      static::assertSame('Do Not Email', $sheet->getCell('D1')->getValue());
      static::assertFalse($sheet->getCell('D2')->getValue());
      static::assertSame(DataType::TYPE_BOOL, $sheet->getCell('D2')->getDataType());

      static::assertSame('Do Not Mail (rewrite)', $sheet->getCell('E1')->getValue());
      static::assertSame('rewrite: 1', $sheet->getCell('E2')->getValue());
      static::assertSame(DataType::TYPE_STRING, $sheet->getCell('E2')->getDataType());
    }
    finally {
      unlink($tmpFile);
    }
  }

  /**
   * Test downloading xlsx format.
   */
  public function testDownloadXlsxEvent(): void {
    $eventTypeId = OptionValue::create(FALSE)
      ->setValues([
        'option_group_id.name' => 'event_type',
        'name' => 'test',
        'label' => 'test',
      ])
      ->execute()->single()['value'];
    $eventId = Event::create(FALSE)
      ->setValues([
        'title' => 'test',
        'event_type_id' => $eventTypeId,
        'start_date' => '2020-05-23',
        'end_date' => '2020-05-24 01:02:03',
        'min_initial_amount' => 1.23,
      ])->execute()->single()['id'];
    // API doesn't allow to set created_date.
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_contact SET created_date = "2025-05-23 01:02:03" WHERE id = ' . $eventId);

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'xlsx',
      'savedSearch' => [
        'api_entity' => 'Event',
        'api_params' => [
          'version' => 4,
          'where' => [['id', '=', $eventId]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'test',
        'settings' => [
          'actions' => TRUE,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'Event ID',
            ],
            [
              'type' => 'field',
              'key' => 'start_date',
              'label' => 'Start Date',
              // Use default format.
              'format' => '',
            ],
            [
              'type' => 'field',
              'key' => 'end_date',
              'label' => 'End Date',
              'format' => 'dateformatshortdate',
            ],
            [
              'type' => 'field',
              'key' => 'min_initial_amount',
              'label' => 'Minimum Initial Amount',
            ],
          ],
        ],
      ],
      'afform' => NULL,
    ];

    ob_start();
    try {
      civicrm_api4('SearchDisplay', 'download', $params);
      static::fail();
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      // All good, we expected the api to exit
    }

    $xlsx = ob_get_clean();
    $tmpFile = tempnam(sys_get_temp_dir(), 'SearchDownloadTestXslx');
    try {
      file_put_contents($tmpFile, $xlsx);
      $reader = IOFactory::createReader('Xlsx');
      $spreadsheet = $reader->load($tmpFile);
      $sheet = $spreadsheet->getSheet(0);

      static::assertSame(2, $sheet->getHighestRow());
      static::assertSame('D', $sheet->getHighestColumn());

      static::assertSame('Event ID', $sheet->getCell('A1')->getValue());
      static::assertSame($eventId, $sheet->getCell('A2')->getValue());
      static::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('A2')->getDataType());
      static::assertSame('General', $sheet->getCell('A2')->getStyle()->getNumberFormat()->getFormatCode());

      static::assertSame('Start Date', $sheet->getCell('B1')->getValue());
      static::assertSame(43974.0, $sheet->getCell('B2')->getValue());
      static::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('B2')->getDataType());
      static::assertSame('mmmm d, yyyy  h:mm AM/PM', $sheet->getCell('B2')->getStyle()->getNumberFormat()->getFormatCode());

      static::assertSame('End Date', $sheet->getCell('C1')->getValue());
      static::assertSame(43975.043090278, $sheet->getCell('C2')->getValue());
      static::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('C2')->getDataType());
      static::assertSame('mm/dd/yyyy', $sheet->getCell('C2')->getStyle()->getNumberFormat()->getFormatCode());

      static::assertSame('Minimum Initial Amount', $sheet->getCell('D1')->getValue());
      static::assertSame(1.23, $sheet->getCell('D2')->getValue());
      static::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('D2')->getDataType());
      static::assertSame('[$$-en-US]#,##0.00', $sheet->getCell('D2')->getStyle()->getNumberFormat()->getFormatCode());
    }
    finally {
      unlink($tmpFile);
    }
  }

}
