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
"Activity Type","Subject","Activity Details"\r
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

}
