<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

/**
 *  Include dataProvider for tests
 */
class CRM_Mailing_BAO_QueryTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Mailing BAO Query',
      'description' => 'Test all Mailing_BAO_Query methods.',
      'group' => 'CiviMail BAO Query Tests',
    );
  }

  public function dataProvider() {
    return new CRM_Mailing_BAO_QueryTestDataProvider;
  }

  function setUp() {
    parent::setUp();
  }

  function tearDown() {
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
      'civicrm_email',
      'civicrm_contact',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test CRM_Contact_BAO_Query::searchQuery()
   *  @dataProvider dataProvider
   */
  function testSearch($fv, $count, $ids, $full) {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/queryDataset.xml'
      )
    );

    $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    $obj    = new CRM_Contact_BAO_Query($params);
    $dao    = $obj->searchQuery();

    $contacts = array();
    while ($dao->fetch()) {
      $contacts[] = $dao->contact_id;
    }

    sort($contacts, SORT_NUMERIC);

    $this->assertEquals($ids, $contacts, 'In line ' . __LINE__);
  }
}

