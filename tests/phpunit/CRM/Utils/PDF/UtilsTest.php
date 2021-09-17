<?php

/**
 * Class CRM_Utils_PDF_UtilsTest
 * @group headless
 */
class CRM_Utils_PDF_UtilsTest extends CiviUnitTestCase {

  /**
   * Test user-supplied settings for DOMPDF
   */
  public function testDOMPDFSettings() {
    $old_setting = \Civi::settings()->get('dompdf_log_output_file');

    $log_file = tempnam(sys_get_temp_dir(), 'pdf');
    \Civi::settings()->set('dompdf_log_output_file', $log_file);

    $pdf_output = CRM_Utils_PDF_Utils::html2pdf('<p>Some output</p>', 'civicrm.pdf', TRUE);
    // Not much of a test but this isn't the main thing we're testing.
    $this->assertEquals('%PDF', substr($pdf_output, 0, 4));

    // If the setting worked, we should have some debug output in this file.
    // The exact contents might change between dompdf versions but it's likely
    // to contain a span tag.
    // If this is too brittle, it might be ok to just check it's not empty,
    // since if it's empty then our setting didn't work.
    $this->assertStringContainsString('<span', file_get_contents($log_file));
    unlink($log_file);

    \Civi::settings()->set('dompdf_log_output_file', $old_setting);
  }

}
