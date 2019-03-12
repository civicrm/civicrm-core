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

}
