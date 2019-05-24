<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Mailing_BAO_QueryTest extends CiviUnitTestCase {

  /**
   * @return CRM_Mailing_BAO_QueryTestDataProvider
   */
  public function dataProvider() {
    return new CRM_Mailing_BAO_QueryTestDataProvider();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_delivered',
      'civicrm_mailing_event_opened',
      'civicrm_mailing_event_reply',
      'civicrm_mailing_event_trackable_url_open',
      'civicrm_mailing_event_queue',
      'civicrm_mailing_trackable_url',
      'civicrm_mailing_job',
      'civicrm_mailing',
      'civicrm_mailing_recipients',
      'civicrm_email',
      'civicrm_contact',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test CRM_Contact_BAO_Query::searchQuery()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   */
  public function testSearch($fv, $count, $ids, $full) {
    $this->loadXMLDataSet(dirname(__FILE__) . '/queryDataset.xml');

    $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    $obj = new CRM_Contact_BAO_Query($params);

    // let's set useGroupBy=true, to prevent duplicate records
    $obj->_useGroupBy = TRUE;

    $dao = $obj->searchQuery();

    $contacts = array();
    while ($dao->fetch()) {
      $contacts[] = $dao->contact_id;
    }

    sort($contacts, SORT_NUMERIC);

    $this->assertEquals($ids, $contacts);
  }

  /**
   * CRM-20412: Test accurate count for unique open details
   */
  public function testOpenedMailingQuery() {

    $this->loadXMLDataSet(dirname(__FILE__) . '/queryDataset.xml');
    // ensure that total unique opened mail count is same while
    //   fetching rows and row count for mailing_id = 14
    $totalOpenedMailCount = CRM_Mailing_Event_BAO_Opened::getTotalCount(14, NULL, TRUE);
    $totalOpenedMail = CRM_Mailing_Event_BAO_Opened::getRows(14, NULL, TRUE);

    $this->assertEquals(4, $totalOpenedMailCount);
    $this->assertEquals(4, count($totalOpenedMail));
  }

  /**
   * CRM-21194: Test accurate count for unique trackable URLs
   */
  public function testTrackableUrlMailingQuery() {
    $this->loadXMLDataSet(dirname(__FILE__) . '/queryDataset.xml');

    // ensure that total unique clicked mail count is same while
    //   fetching rows and row count for mailing_id = 14 and
    //   trackable_url_id 12
    $totalDistinctTrackableUrlCount = CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount(14, NULL, TRUE, 13);
    $totalTrackableUrlCount = CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount(14, NULL, FALSE, 13);
    $totalTrackableUrlMail = CRM_Mailing_Event_BAO_TrackableURLOpen::getRows(14, NULL, TRUE, 13);

    $this->assertEquals(3, $totalDistinctTrackableUrlCount, "Accurately display distinct count of unique trackable URLs");
    $this->assertEquals(4, $totalTrackableUrlCount, "Accurately display count of unique trackable URLs");
    $this->assertEquals(3, count($totalTrackableUrlMail), "Accurately display list of unique trackable URLs and who clicked them.");
  }

}
