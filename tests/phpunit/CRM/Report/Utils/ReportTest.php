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
   * Test when you choose Print from the actions dropdown.
   *
   * We're not too concerned about the actual report rows - there's other
   * tests for that - we're more interested in does it echo it in print
   * format.
   */
  public function testOutputPrint(): void {
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
      $contents = ob_get_clean();
    }
    $this->assertStringContainsString('<title>CiviCRM Report</title>', $contents);
    $this->assertStringContainsString('test report', $contents);
    $this->assertStringContainsString($last_contact['sort_name'], $contents);
  }

  /**
   * Test when you choose PDF from the actions dropdown.
   *
   * We're not too concerned about the actual report rows - there's other
   * tests for that - we're more interested in does it hit the pdf output function.
   */
  public function testOutputPdf(): void {
    $contents = '';
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

    // In the unit text context it throws an exception for us to check.
    try {
      CRM_Report_Utils_Report::processReport([
        'instanceId' => $report_instance['id'],
        'format' => 'pdf',
        'sendmail' => FALSE,
      ]);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $contents = $e->errorData['html'];
      $this->assertEquals('pdf', $e->errorData['output']);
    }
    $this->assertStringContainsString("id_value={$last_contact['id']}", $contents);
  }

  /**
   * Test when you choose Csv from the actions dropdown.
   */
  public function testOutputCsv(): void {
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
