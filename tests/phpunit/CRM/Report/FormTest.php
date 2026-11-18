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
  public function testGetFromTo(): void {
    $cases = $this->fromToData();
    foreach ($cases as $caseDescription => $case) {
      $obj = new CRM_Report_Form();
      [$calculatedFrom, $calculatedTo] = $obj->getFromTo($case['relative'], $case['from'], $case['to']);
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
  public static function reportModeProvider() {
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

  /**
   * Test whereClause quotes T_TEXT and T_LONGTEXT fields to prevent SQL injection.
   */
  public function testWhereClauseQuotesTextFields(): void {
    $form = new CRM_Report_Form();

    // T_TEXT with eq operator
    $field = [
      'name' => 'details',
      'dbAlias' => 'civicrm_activity.details',
      'type' => CRM_Utils_Type::T_TEXT,
    ];
    $clause = $form->whereClause($field, 'eq', 'NULL OR 1=1', NULL, NULL);
    $this->assertEquals("( civicrm_activity.details = 'NULL OR 1=1' )", $clause);

    // T_LONGTEXT with neq operator and single quote
    $fieldLong = [
      'name' => 'body',
      'dbAlias' => 'civicrm_email.body',
      'type' => CRM_Utils_Type::T_LONGTEXT,
    ];
    $clause = $form->whereClause($fieldLong, 'neq', "foo' OR 1=1 --", NULL, NULL);
    $this->assertEquals("( civicrm_email.body != 'foo\' OR 1=1 --' )", $clause);

    // T_TEXT with in operator
    $clause = $form->whereClause($field, 'in', 'NULL) OR (1=1,second', NULL, NULL);
    $this->assertEquals("( ( civicrm_activity.details IN ( 'NULL) OR (1=1' , 'second') ) )", $clause);

    // T_TEXT with between operator
    $clause = $form->whereClause($field, 'bw', NULL, 'aaa', 'zzz');
    $this->assertEquals("( civicrm_activity.details >= 'aaa' ) AND ( civicrm_activity.details <= 'zzz' )", $clause);
  }

  /**
   * Test whereClause validates integer values for 'in' / 'notin' filters.
   */
  public function testWhereClauseValidatesInteger(): void {
    $form = new CRM_Report_Form();

    $field = [
      'name' => 'event_id',
      'dbAlias' => 'civicrm_participant.event_id',
      'type' => CRM_Utils_Type::T_INT,
    ];

    // Valid integer list as string
    $clause = $form->whereClause($field, 'in', '1, 2, 3', NULL, NULL);
    $this->assertEquals('( civicrm_participant.event_id IN (1, 2, 3) )', $clause);

    // Valid integer list as array
    $clause = $form->whereClause($field, 'in', [1, 2, 3], NULL, NULL);
    $this->assertEquals('( civicrm_participant.event_id IN (1, 2, 3) )', $clause);

    // SQL injection attempt throws exception
    $this->expectException(CRM_Core_Exception::class);
    $form->whereClause($field, 'in', '0) OR (1=1', NULL, NULL);
  }

  /**
   * Test whereClause validates integer array for 'in' / 'notin' filters.
   */
  public function testWhereClauseValidatesIntegerArray(): void {
    $form = new CRM_Report_Form();
    $field = [
      'name' => 'event_id',
      'dbAlias' => 'civicrm_participant.event_id',
      'type' => CRM_Utils_Type::T_INT,
    ];
    $this->expectException(CRM_Core_Exception::class);
    $form->whereClause($field, 'in', ['0) OR (1=1'], NULL, NULL);
  }

  /**
   * Test whereSubtypeClause escapes strings to prevent injection and does not double-escape via whereClause.
   */
  public function testWhereSubtypeClauseEscaping(): void {
    $form = new CRM_Report_Form();
    $field = [
      'name' => 'contact_sub_type',
      'dbAlias' => 'civicrm_contact.contact_sub_type',
      'type' => CRM_Utils_Type::T_STRING,
    ];

    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    $clause = $form->whereSubtypeClause($field, ["' OR '1'='1"], 'in');
    $this->assertEquals("( ( civicrm_contact.contact_sub_type LIKE '%{$sep}\' OR \'1\'=\'1{$sep}%' ) )", $clause);

    $clauseFromWhereClause = $form->whereClause($field, 'in', ["' OR '1'='1"], NULL, NULL);
    $this->assertEquals("( ( civicrm_contact.contact_sub_type LIKE '%{$sep}\' OR \'1\'=\'1{$sep}%' ) )", $clauseFromWhereClause);
  }

  /**
   * Test tag and membership where clauses sanitize integer inputs.
   */
  public function testEntityFilterClausesSanitizeIntegers(): void {
    $form = new CRM_Report_Form();
    $form->_aliases = [
      'civicrm_contact' => 'civicrm_contact',
      'civicrm_tag' => 'civicrm_tag',
    ];

    $field = ['dbAlias' => 'civicrm_entity_tag.tag_id'];

    // Valid integer produces valid clause
    $clause = $form->whereTagClause($field, '1, 2', 'in');
    $this->assertStringContainsString('civicrm_entity_tag.tag_id IN (1, 2)', $clause);

    // Injection attempt throws exception
    try {
      $form->whereTagClause($field, '0) OR (1=1', 'in');
      $this->fail('Expected CRM_Core_Exception for whereTagClause injection');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE);
    }

    try {
      $form->whereMembershipOrgClause('0) OR (1=1', 'in');
      $this->fail('Expected CRM_Core_Exception for whereMembershipOrgClause injection');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE);
    }

    try {
      $form->whereMembershipTypeClause('0) OR (1=1', 'in');
      $this->fail('Expected CRM_Core_Exception for whereMembershipTypeClause injection');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Test storeOrderByArray validates order direction.
   */
  public function testStoreOrderByArrayValidatesDirection(): void {
    $form = new CRM_Report_Form_Activity();
    $form->setParams([
      'order_bys' => [
        1 => [
          'column' => 'activity_date_time',
          'order' => 'ASC, (SELECT 1)',
        ],
      ],
    ]);

    $this->expectException(CRM_Core_Exception::class);
    $form->storeOrderByArray();
  }

  /**
   * Test Event_Summary::getEventFilter sanitizes event IDs.
   */
  public function testEventSummaryGetEventFilterSanitizesIntegers(): void {
    $form = new CRM_Report_Form_Event_Summary();
    $form->setParams(['id_value' => '1, 2']);
    $this->assertEquals(' AND civicrm_participant.event_id IN ( 1,2) ', $form->getEventFilter());

    $form->setParams(['id_value' => '0) OR (1=1']);
    $this->expectException(CRM_Core_Exception::class);
    $form->getEventFilter();
  }

}
