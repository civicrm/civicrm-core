<?php

declare(strict_types = 1);

use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
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

  /**
   * Test makeCsv functionality.
   *
   * Include some special characters to check they are handled.
   */
  public function testMakeCsv(): void {
    $form = new CRM_Report_Form();
    $form->_columnHeaders = [
      'civicrm_activity_activity_type_id' => [
        'title' => 'Activity Type',
        'type' => 2,
      ],
      'civicrm_activity_activity_subject' => [
        'title' => 'Subject',
        'type' => 2,
      ],
      'civicrm_activity_details' => [
        'title' => 'Activity Details',
        'type' => NULL,
      ],
    ];

    $details = <<<ENDDETAILS
<p>Here&#39;s some typical data from an activity details field.</p>
<p>дè some non-ascii and <strong>html</strong> styling and these ̋“weird” quotes - ’.</p>
<p>Also some named entities &quot;hello&quot;. And &amp; &eacute;. Also, some math like 2 &lt; 4.</p>
ENDDETAILS;

    $expectedOutput = <<<ENDOUTPUT
\xEF\xBB\xBF"Activity Type","Subject","Activity Details"\r
"Meeting","Meeting with the apostrophe's and that person who does ""air quotes"". Some non-ascii characters: дè","Here's some typical data from an activity details field.
дè some non-ascii and html styling and these ̋“weird” quotes - ’.
Also some named entities ""hello"". And & é. Also, some math like 2 < 4."\r

ENDOUTPUT;

    $rows = [
      [
        'civicrm_activity_activity_type_id' => 'Meeting',
        'civicrm_activity_activity_subject' => 'Meeting with the apostrophe\'s and that person who does "air quotes". Some non-ascii characters: дè',
        'civicrm_activity_details' => $details,
      ],
    ];

    $csvString = CRM_Report_Utils_Report::makeCsv($form, $rows);
    $this->assertEquals($expectedOutput, $csvString);
  }

}
