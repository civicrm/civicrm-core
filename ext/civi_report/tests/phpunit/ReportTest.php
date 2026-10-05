<?php

declare(strict_types = 1);

use Civi\Api4\Contact;
use Civi\Api4\ReportInstance;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\FormTrait;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test CiviReport functionality.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class ReportTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Test\EntityTrait;
  use FormTrait;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown(): void {
    if (!empty($this->ids['Contact'])) {
      Contact::delete()->addWhere('id', 'IN', $this->ids['Contact'])->setUseTrash(FALSE)->execute();
    }
    if (!empty($this->ids['ReportInstance'])) {
      ReportInstance::delete()->addWhere('id', 'IN', $this->ids['ReportInstance'])->execute();
    }
    parent::tearDown();
  }

  /**
   * Test makeCsv functionality, via a real report submission & export.
   *
   * Include some special characters to check they are handled.
   *
   * This runs an actual Activity report through the CSV export path (rather
   * than calling CRM_Report_Utils_Report::makeCsv() directly against
   * hand-built rows) so the escaping is proven against the real pipeline.
   * The Activity report's buildQuery() requires a target contact (it fills
   * a temp table via an INNER JOIN on "Activity Targets" first), so the
   * fixture activity needs source, assignee AND target contacts or it will
   * silently produce zero rows.
   */
  public function testMakeCsv(): void {
    $sourceContactID = $this->createTestEntity('Contact', [
      'first_name' => 'Source',
      'last_name' => 'Contact',
      'contact_type' => 'Individual',
    ], 'source')['id'];
    $targetContactID = $this->createTestEntity('Contact', [
      'first_name' => 'Target',
      'last_name' => 'Contact',
      'contact_type' => 'Individual',
    ], 'target')['id'];

    $subject = 'Meeting with the apostrophe\'s and that person who does "air quotes". Some non-ascii characters: дè';
    $details = <<<ENDDETAILS
<p>Here&#39;s some typical data from an activity details field.</p>
<p>дè some non-ascii and <strong>html</strong> styling and these ̋“weird” quotes - ’.</p>
<p>Also some named entities &quot;hello&quot;. And &amp; &eacute;. Also, some math like 2 &lt; 4.</p>
ENDDETAILS;

    $this->createTestEntity('Activity', [
      'activity_type_id:name' => 'Meeting',
      'subject' => $subject,
      'details' => $details,
      'activity_date_time' => '2024-01-15 10:00:00',
      'source_contact_id' => $sourceContactID,
      'target_contact_id' => [$targetContactID],
    ]);

    $reportInstanceID = $this->createTestEntity('ReportInstance', [
      'report_id' => 'activity',
      'title' => 'test activity report',
      'form_values' => serialize([
        'fields' => [
          'activity_type_id' => '1',
          'activity_subject' => '1',
          'details' => '1',
        ],
      ]),
    ])['id'];

    $expectedOutput = <<<ENDOUTPUT
\xEF\xBB\xBF"Activity Type","Subject","Activity Date","Activity Details"\r
"Meeting","Meeting with the apostrophe's and that person who does ""air quotes"". Some non-ascii characters: дè","2024-01-15 10:00","Here's some typical data from an activity details field.
дè some non-ascii and html styling and these ̋“weird” quotes - ’.
Also some named entities ""hello"". And & é. Also, some math like 2 < 4."\r

ENDOUTPUT;

    try {
      $this->getTestForm('CRM_Report_Form_Activity', [
        'fields' => [
          'activity_type_id' => '1',
          'activity_subject' => '1',
          'details' => '1',
        ],
        // Isolate this fixture's activity from any other activities in the
        // database - this filter applies to all 3 legs of the report's
        // target/assignee/source query, unlike the contact-based filters.
        'activity_subject_op' => 'has',
        'activity_subject_value' => 'дè',
        'task' => 'report_instance.csv',
      ], [
        'q' => 'civicrm/report/instance/' . $reportInstanceID,
        'reset' => 1,
        'output' => 'report_instance.csv',
      ])->processForm();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals($expectedOutput, $e->errorData['csv']);
      return;
    }
    $this->fail('Exception was not thrown.');
  }

}
