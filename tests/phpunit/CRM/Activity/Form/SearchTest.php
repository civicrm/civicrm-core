<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Activity_Form_SearchTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->individualID = $this->individualCreate();
    $this->contributionCreate([
      'contact_id' => $this->individualID,
      'receive_date' => '2017-01-30',
    ]);
  }

  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_activity',
      'civicrm_activity_contact',
    ];
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test submitted the search form.
   */
  public function testSearch() {

    $form = new CRM_Activity_Form_Search();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Activity_Controller_Search();
    $form->preProcess();
    $form->postProcess();
    $qfKey = $form->controller->_key;
    $rows = $form->controller->get('rows');
    $this->assertEquals([
      [
        'contact_id' => '3',
        'contact_type' => '<a href="/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=3&amp;snippet=4" class="crm-summary-link"><div class="icon crm-icon Individual-icon"></div></a>',
        'sort_name' => 'Anderson, Anthony',
        'display_name' => 'Mr. Anthony Anderson II',
        'activity_id' => '1',
        'activity_date_time' => '2017-01-30 00:00:00',
        'activity_status_id' => '2',
        'activity_status' => 'Completed',
        'activity_subject' => '$ 100.00 - SSF',
        'source_record_id' => '1',
        'activity_type_id' => '6',
        'activity_type' => 'Contribution',
        'activity_is_test' => '0',
        'target_contact_name' => [],
        'assignee_contact_name' => [],
        'source_contact_id' => '3',
        'source_contact_name' => 'Anderson, Anthony',
        'checkbox' => 'mark_x_1',
        'mailingId' => '',
        'action' => '<span><a href="/index.php?q=civicrm/contact/view/contribution&amp;action=view&amp;reset=1&amp;id=1&amp;cid=3&amp;context=search&amp;searchContext=activity&amp;key=' . $qfKey . '" class="action-item crm-hover-button" title=\'View Activity\' >View</a></span>',
        'campaign' => NULL,
        'campaign_id' => NULL,
        'repeat' => '',
      ],
    ], $rows);
  }

  /**
   * Test the Qill for activity Date time.
   *
   * @dataProvider getSearchCriteria
   *
   * @param array $searchCriteria
   * @param array $expectedQill
   */
  public function testQill($searchCriteria, $expectedQill) {
    $selector = new CRM_Activity_Selector_Search($searchCriteria);
    $this->assertEquals($expectedQill, $selector->getQILL());
  }

  /**
   * Get criteria for activity testing.
   */
  public function getSearchCriteria() {

    // We have to define format because tests crash trying to access the config param from the dataProvider
    // perhaps because there is no property on config?
    $format = '%B %E%f, %Y %l:%M %P';
    $dates['ending_60.day'] = CRM_Utils_Date::getFromTo('ending_60.day', NULL, NULL);
    $dates['earlier.year'] = CRM_Utils_Date::getFromTo('earlier.year', NULL, NULL);
    $dates['greater.year'] = CRM_Utils_Date::getFromTo('greater.year', NULL, NULL);
    return [
      [
        'search_criteria' => [
          ['activity_date_time_relative', '=', 'ending_60.day', 0, 0],
        ],
        'expected_qill' => [['Activity Date is Last 60 days including today (between ' . CRM_Utils_Date::customFormat($dates['ending_60.day'][0], $format) . ' and ' . CRM_Utils_Date::customFormat($dates['ending_60.day'][1], $format) . ')']],
      ],
      [
        'search_criteria' => [
          ['activity_date_time_relative', '=', 'earlier.year', 0, 0],
        ],
        'expected_qill' => [['Activity Date is To end of previous calendar year (to ' . CRM_Utils_Date::customFormat($dates['earlier.year'][1], $format) . ')']],
      ],
      [
        'search_criteria' => [
          ['activity_date_time_relative', '=', 'greater.year', 0, 0],
        ],
        'expected_qill' => [['Activity Date is From start of current calendar year (from ' . CRM_Utils_Date::customFormat($dates['greater.year'][0], $format) . ')']],
      ],
      [
        'search_criteria' => [
          ['activity_date_time_low', '=', '2019-03-05', 0, 0],
          ['activity_date_time_high', '=', '2019-03-27', 0, 0],
        ],
        'expected_qill' => [['Activity Date - greater than or equal to "March 5th, 2019 12:00 AM" AND less than or equal to "March 27th, 2019 11:59 PM"']],
      ],
      [
        'search_criteria' => [
          ['activity_status_id', '=', ['IN' => ['1', '2']], 0, 0],
        ],
        'expected_qill' => [['Activity Status In Scheduled, Completed']],
      ],
    ];
  }

}
