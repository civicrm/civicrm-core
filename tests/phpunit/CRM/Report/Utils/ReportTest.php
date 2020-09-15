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
 * Test CRM_Report_Utils_Report functions.
 *
 * @group headless
 */
class CRM_Report_Utils_ReportTest extends CiviUnitTestCase {

  /**
   * Test makeCsv
   */
  public function testMakeCsv() {
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
<p>Here&#39;s some typical data from an activity details field.
  </p>
<p>дè some non-ascii and <strong>html</strong> styling and these ̋“weird” quotes’s.
  </p>
<p>Also some named entities &quot;hello&quot;. And &amp; &eacute;. Also some math like 2 &lt; 4.
  </p>
ENDDETAILS;

    $expectedOutput = <<<ENDOUTPUT
\xEF\xBB\xBF"Activity Type","Subject","Activity Details"\r
"Meeting","Meeting with the apostrophe's and that person who does ""air quotes"". Some non-ascii characters: дè","Here's some typical data from an activity details field.
  
дè some non-ascii and html styling and these ̋“weird” quotes’s.
  
Also some named entities ""hello"". And & é. Also some math like 2 < 4.
  "\r

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

  /**
   * Test when you choose Print from the actions dropdown.
   *
   * We're not too concerned about the actual report rows - there's other
   * tests for that - we're more interested in does it echo it in print
   * format.
   */
  public function testOutputPrint() {
    // Create many contacts, in particular so that the report would be more
    // than a one-pager.
    for ($i = 0; $i < 110; $i++) {
      $this->individualCreate([], $i, TRUE);
    }

    // Get the name of the expected last contact in the output in sort order.
    // Even in print format we can check the string contains the name which
    // we'll take as some assurance that it output all the rows.
    $last_contact = $this->callAPISuccess('Contact', 'get', [
      'return' => 'sort_name',
      'sequential' => 1,
      'options' => [
        'limit' => 1,
        'sort' => 'sort_name DESC',
      ],
    ])['values'][0];

    $report_instance = $this->createReportInstance();

    // avoid warnings
    if (empty($_SERVER['QUERY_STRING'])) {
      $_SERVER['QUERY_STRING'] = 'reset=1';
    }

    // A bit weird - it will send the output to the browser, which here is the
    // console, then throw a specific exception. So we capture the output
    // and the exception.
    try {
      ob_start();
      CRM_Report_Utils_Report::processReport([
        'instanceId' => $report_instance['id'],
        'format' => 'print',
        'sendmail' => FALSE,
      ]);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $contents = ob_get_contents();
      ob_end_clean();
    }
    $this->assertStringContainsString('<title>CiviCRM Report</title>', $contents);
    $this->assertStringContainsString('test report', $contents);
    $this->assertStringContainsString($last_contact['sort_name'], $contents);
  }

  /**
   * Test when you choose PDF from the actions dropdown.
   *
   * We're not too concerned about the actual report rows - there's other
   * tests for that - we're more interested in does it echo it in pdf
   * format.
   *
   * This isn't great but otherwise dompdf complains about headers already sent.
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testOutputPdf() {
    // Create many contacts, in particular so that the report would be more
    // than a one-pager.
    for ($i = 0; $i < 110; $i++) {
      $this->individualCreate([], $i, TRUE);
    }

    // Get the id of the expected last contact in the output in sort order.
    // Even in pdf format we can check the string contains the link to the
    // detail report for this contact which we'll take as some assurance that
    // it output all the rows.
    $last_contact = $this->callAPISuccess('Contact', 'get', [
      'return' => 'id',
      'options' => [
        'limit' => 1,
        'sort' => 'sort_name DESC',
      ],
    ]);

    $report_instance = $this->createReportInstance();

    // avoid warnings
    if (empty($_SERVER['QUERY_STRING'])) {
      $_SERVER['QUERY_STRING'] = 'reset=1';
    }

    // A bit weird - it will send the output to the browser, which here is the
    // console, then throw a specific exception. So we capture the output
    // and the exception.
    try {
      ob_start();
      CRM_Report_Utils_Report::processReport([
        'instanceId' => $report_instance['id'],
        'format' => 'pdf',
        'sendmail' => FALSE,
      ]);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $contents = ob_get_contents();
      ob_end_clean();
    }
    $this->assertStringStartsWith('%PDF', $contents);
    $this->assertStringContainsString("id_value={$last_contact['id']}", $contents);
  }

  /**
   * Test when you choose Csv from the actions dropdown.
   */
  public function testOutputCsv() {
    // Create many contacts, in particular so that the report would be more
    // than a one-pager.
    for ($i = 0; $i < 110; $i++) {
      $this->individualCreate([], $i, TRUE);
    }

    $report_instance = $this->createReportInstance();

    // avoid warnings
    if (empty($_SERVER['QUERY_STRING'])) {
      $_SERVER['QUERY_STRING'] = 'reset=1';
    }

    // A bit weird - it will send the output to the browser, which here is the
    // console, then throw a specific exception. So we capture the output
    // and the exception.
    try {
      ob_start();
      CRM_Report_Utils_Report::processReport([
        'instanceId' => $report_instance['id'],
        'format' => 'csv',
        'sendmail' => FALSE,
      ]);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $contents = ob_get_contents();
      ob_end_clean();
    }

    // Pull all the contacts to get our expected output.
    $contacts = $this->callAPISuccess('Contact', 'get', [
      'return' => 'sort_name',
      'options' => [
        'limit' => 0,
        'sort' => 'sort_name',
      ],
    ]);
    $rows = [];
    foreach ($contacts['values'] as $contact) {
      $rows[] = ['civicrm_contact_sort_name' => $contact['sort_name']];
    }
    // need this for makeCsv()
    $fakeForm = new CRM_Report_Form();
    $fakeForm->_columnHeaders = [
      'civicrm_contact_sort_name' => [
        'title' => 'Contact Name',
        'type' => 2,
      ],
    ];

    $this->assertEquals(
      CRM_Report_Utils_Report::makeCsv($fakeForm, $rows),
      $contents
    );
  }

  /**
   * Helper to create a report instance of the contact summary report.
   */
  private function createReportInstance() {
    return $this->callAPISuccess('ReportInstance', 'create', [
      'report_id' => 'contact/summary',
      'title' => 'test report',
      'form_values' => [
        serialize([
          'fields' => [
            'sort_name' => '1',
            'street_address' => '1',
            'city' => '1',
            'country_id' => '1',
          ],
          'sort_name_op' => 'has',
          'sort_name_value' => '',
          'source_op' => 'has',
          'source_value' => '',
          'id_min' => '',
          'id_max' => '',
          'id_op' => 'lte',
          'id_value' => '',
          'country_id_op' => 'in',
          'country_id_value' => [],
          'state_province_id_op' => 'in',
          'state_province_id_value' => [],
          'gid_op' => 'in',
          'gid_value' => [],
          'tagid_op' => 'in',
          'tagid_value' => [],
          'description' => 'Provides a list of address and telephone information for constituent records in your system.',
          'email_subject' => '',
          'email_to' => '',
          'email_cc' => '',
          'permission' => 'view all contacts',
          'groups' => '',
          'domain_id' => 1,
        ]),
      ],
    ]);
  }

}
