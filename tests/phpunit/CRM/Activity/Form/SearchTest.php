<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Activity_Form_SearchTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->individualID = $this->individualCreate();
    $this->contributionCreate([
      'contact_id' => $this->individualID,
      'receive_date' => '2017-01-30',
    ]);
  }

  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_activity',
      'civicrm_activity_contact',
    ];
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   * Test submitted the search form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSearch(): void {

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
        'contact_type' => '<a href="/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=3&amp;snippet=4&amp;is_show_email_task=1" class="crm-summary-link"><div class="icon crm-icon Individual-icon"></div></a>',
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
   */
  public function testQill() {
    foreach ($this->getSearchCriteria() as $test_name => $data) {
      $selector = new CRM_Activity_Selector_Search($data['search_criteria']);
      $this->assertEquals($data['expected_qill'], $selector->getQILL(), "Failed for data set: $test_name");
    }
  }

  /**
   * Get criteria for activity testing.
   */
  public function getSearchCriteria() {
    $format = \Civi::settings()->get('dateformatDatetime');
    $dates['ending_60.day'] = CRM_Utils_Date::getFromTo('ending_60.day', NULL, NULL);
    $dates['earlier.year'] = CRM_Utils_Date::getFromTo('earlier.year', NULL, NULL);
    $dates['greater.year'] = CRM_Utils_Date::getFromTo('greater.year', NULL, NULL);
    return [
      'last 60' => [
        'search_criteria' => [
          ['activity_date_time_relative', '=', 'ending_60.day', 0, 0],
        ],
        'expected_qill' => [['Activity Date is Last 60 days including today (between ' . CRM_Utils_Date::customFormat($dates['ending_60.day'][0], $format) . ' and ' . CRM_Utils_Date::customFormat($dates['ending_60.day'][1], $format) . ')']],
      ],
      'end of previous year' => [
        'search_criteria' => [
          ['activity_date_time_relative', '=', 'earlier.year', 0, 0],
        ],
        'expected_qill' => [['Activity Date is To end of previous calendar year (to ' . CRM_Utils_Date::customFormat($dates['earlier.year'][1], $format) . ')']],
      ],
      'start of current year' => [
        'search_criteria' => [
          ['activity_date_time_relative', '=', 'greater.year', 0, 0],
        ],
        'expected_qill' => [['Activity Date is From start of current calendar year (from ' . CRM_Utils_Date::customFormat($dates['greater.year'][0], $format) . ')']],
      ],
      'between' => [
        'search_criteria' => [
          ['activity_date_time_low', '=', '2019-03-05', 0, 0],
          ['activity_date_time_high', '=', '2019-03-27', 0, 0],
        ],
        'expected_qill' => [['Activity Date - greater than or equal to "March 5th, 2019 12:00 AM" AND less than or equal to "March 27th, 2019 11:59 PM"']],
      ],
      'status is one of' => [
        'search_criteria' => [
          ['activity_status_id', '=', ['IN' => ['1', '2']], 0, 0],
        ],
        'expected_qill' => [['Activity Status In Scheduled, Completed']],
      ],
    ];
  }

  /**
   * This just checks there's no errors. It doesn't perform any tasks.
   * It's a little bit like choosing an action from the dropdown.
   * @dataProvider taskControllerProvider
   * @param int $task
   */
  public function testTaskController(int $task) {
    // It gets the task from the POST var
    $oldtask = $_POST['task'] ?? NULL;
    $_POST['task'] = $task;

    // yes it's the string 'null'
    new CRM_Activity_Controller_Search('Find Activities', TRUE, 'null');

    // clean up
    if (is_null($oldtask)) {
      unset($_POST['task']);
    }
    else {
      $_POST['task'] = $oldtask;
    }
  }

  /**
   * dataprovider for testTaskController
   * @return array
   */
  public function taskControllerProvider(): array {
    return [
      [CRM_Activity_Task::TASK_DELETE],
      [CRM_Activity_Task::TASK_PRINT],
      [CRM_Activity_Task::TASK_EXPORT],
      [CRM_Activity_Task::BATCH_UPDATE],
      [CRM_Activity_Task::TASK_EMAIL],
      [CRM_Activity_Task::PDF_LETTER],
      [CRM_Activity_Task::TASK_SMS],
      [CRM_Activity_Task::TAG_ADD],
      [CRM_Activity_Task::TAG_REMOVE],
    ];
  }

}
