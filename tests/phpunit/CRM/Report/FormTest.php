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
 *  File for the FormTest class
 *
 *  (PHP 5)
 *
 * @author Jon Goldberg <jon@megaphonetech.com>
 */

/**
 *  Test CRM_Report_Form functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Report_FormTest extends CiviUnitTestCase {

  /**
   * Used by testGetFromTo
   */
  private function fromToData() {
    $cases = [];
    // Absolute dates
    $cases['absolute'] = [
      'expectedFrom' => '20170901000000',
      'expectedTo' => '20170913235959',
      'relative' => 0,
      'from' => '09/01/2017',
      'to' => '09/13/2017',
    ];
    // "Today" relative date filter
    $date = new DateTime();
    $cases['today'] = [
      'expectedFrom' => $date->format('Ymd') . '000000',
      'expectedTo' => $date->format('Ymd') . '235959',
      'relative' => 'this.day',
      'from' => '',
      'to' => '',
    ];
    // "yesterday" relative date filter
    $date = new DateTime();
    $date->sub(new DateInterval('P1D'));
    $cases['yesterday'] = [
      'expectedFrom' => $date->format('Ymd') . '000000',
      'expectedTo' => $date->format('Ymd') . '235959',
      'relative' => 'previous.day',
      'from' => '',
      'to' => '',
    ];
    return $cases;
  }

  /**
   * Test that getFromTo returns the correct dates.
   */
  public function testGetFromTo() {
    $cases = $this->fromToData();
    foreach ($cases as $caseDescription => $case) {
      $obj = new CRM_Report_Form();
      list($calculatedFrom, $calculatedTo) = $obj->getFromTo($case['relative'], $case['from'], $case['to']);
      $this->assertEquals([$case['expectedFrom'], $case['expectedTo']], [$calculatedFrom, $calculatedTo], "fail on data set '{$caseDescription}'. Local php time is " . date('Y-m-d H:i:s') . ' and mysql time is ' . CRM_Core_DAO::singleValueQuery('SELECT NOW()'));
    }
  }

  /**
   * Test the processReportMode function.
   *
   * @dataProvider reportModeProvider
   *
   * @param array $input
   * @param array $expected
   */
  public function testProcessReportMode($input, $expected) {
    // This is a helper in the tests tree, not a real class in the main tree.
    $form = new CRM_Report_Form_SampleForm();

    $_REQUEST['output'] = $input['format'];
    $_REQUEST['sendmail'] = $input['sendmail'];

    $form->processReportMode();

    unset($_REQUEST['output']);
    unset($_REQUEST['sendmail']);

    $this->assertEquals($expected, [
      $form->getOutputMode(),
      $form->getAddPaging(),
      $form->printOnly,
      $form->_absoluteUrl,
    ]);
  }

  /**
   * dataprovider for testProcessReportMode
   *
   * @return array
   */
  public function reportModeProvider() {
    return [
      'print no mail' => [
        [
          'format' => 'report_instance.print',
          'sendmail' => NULL,
        ],
        [
          // _outputMode
          'print',
          // addPaging
          FALSE,
          // printOnly
          TRUE,
          // _absoluteUrl
          FALSE,
        ],
      ],
      'print and mail' => [
        [
          'format' => 'report_instance.print',
          'sendmail' => '1',
        ],
        ['print', FALSE, TRUE, TRUE],
      ],
      'csv no mail' => [
        [
          'format' => 'report_instance.csv',
          'sendmail' => NULL,
        ],
        ['csv', FALSE, TRUE, TRUE],
      ],
      'csv and mail' => [
        [
          'format' => 'report_instance.csv',
          'sendmail' => '1',
        ],
        ['csv', FALSE, TRUE, TRUE],
      ],
      'pdf no mail' => [
        [
          'format' => 'report_instance.pdf',
          'sendmail' => NULL,
        ],
        ['pdf', FALSE, TRUE, TRUE],
      ],
      'pdf and mail' => [
        [
          'format' => 'report_instance.pdf',
          'sendmail' => '1',
        ],
        ['pdf', FALSE, TRUE, TRUE],
      ],
      'unknown format no mail' => [
        [
          'format' => NULL,
          'sendmail' => NULL,
        ],
        [NULL, TRUE, FALSE, FALSE],
      ],
      'unknown format and mail' => [
        [
          'format' => NULL,
          'sendmail' => '1',
        ],
        // This is a bit inconsistent with the mail_report job which defaults
        // to pdf when you don't specify a format. But for now this is what
        // processReportMode does.
        ['print', FALSE, TRUE, TRUE],
      ],
    ];
  }

}
