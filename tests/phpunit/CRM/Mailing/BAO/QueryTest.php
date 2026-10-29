<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Mailing_BAO_QueryTest extends CiviUnitTestCase {

  /**
   * @return CRM_Mailing_BAO_QueryTestDataProvider
   */
  public static function dataProvider() {
    return new CRM_Mailing_BAO_QueryTestDataProvider();
  }

  public function tearDown(): void {
    $tablesToTruncate = [
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
    ];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
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

    $contacts = [];
    while ($dao->fetch()) {
      $contacts[] = $dao->contact_id;
    }

    sort($contacts, SORT_NUMERIC);

    // The data provider gives the contacts' ids in the dataset.
    $ids = array_map(fn($id) => $this->getXMLDataSetID('civicrm_contact', $id), $ids);
    $this->assertEquals($ids, $contacts);
  }

  /**
   * CRM-20412: Test accurate count for unique open details
   */
  public function testOpenedMailingQuery(): void {

    $this->loadXMLDataSet(dirname(__FILE__) . '/queryDataset.xml');
    // ensure that total unique opened mail count is same while
    //   fetching rows and row count for mailing_id = 14
    $mailingID = $this->getXMLDataSetID('civicrm_mailing', 14);
    $totalOpenedMailCount = CRM_Mailing_Event_BAO_MailingEventOpened::getTotalCount($mailingID, NULL, TRUE);
    $totalOpenedMail = CRM_Mailing_Event_BAO_MailingEventOpened::getRows($mailingID, NULL, TRUE);

    $this->assertEquals(4, $totalOpenedMailCount);
    $this->assertEquals(4, count($totalOpenedMail));
  }

  /**
   * CRM-21194: Test accurate count for unique trackable URLs
   */
  public function testTrackableUrlMailingQuery(): void {
    $this->loadXMLDataSet(dirname(__FILE__) . '/queryDataset.xml');

    // ensure that total unique clicked mail count is same while
    //   fetching rows and row count for mailing_id = 14 and
    //   trackable_url_id 13 (in the dataset)
    $mailingID = $this->getXMLDataSetID('civicrm_mailing', 14);
    $trackableURLID = $this->getXMLDataSetID('civicrm_mailing_trackable_url', 13);
    $totalDistinctTrackableUrlCount = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTotalCount($mailingID, NULL, TRUE, $trackableURLID);
    $totalTrackableUrlCount = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTotalCount($mailingID, NULL, FALSE, $trackableURLID);
    $totalTrackableUrlMail = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getRows($mailingID, NULL, TRUE, $trackableURLID);

    $this->assertEquals(3, $totalDistinctTrackableUrlCount, "Accurately display distinct count of unique trackable URLs");
    $this->assertEquals(4, $totalTrackableUrlCount, "Accurately display count of unique trackable URLs");
    $this->assertEquals(3, count($totalTrackableUrlMail), "Accurately display list of unique trackable URLs and who clicked them.");
  }

}
