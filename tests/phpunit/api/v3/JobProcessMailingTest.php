<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC (c) 2004-2015
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */
require_once 'CiviTest/CiviUnitTestCase.php';
//@todo - why doesn't class loader find these (I tried renaming)
require_once 'CiviTest/CiviMailUtils.php';

/**
 * Class api_v3_JobTest
 */
class api_v3_JobProcessMailingTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public $DBResetRequired = FALSE;
  public $_entity = 'Job';
  public $_params = array();
  private $_groupID;
  private $_email;

  protected $defaultSettings;

  /**
   * @var CiviMailUtils
   */
  private $_mut;

  public function setUp() {
    $this->cleanupMailingTest();
    parent::setUp();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = array(
      'subject' => 'Accidents in cars cause children',
      'body_text' => 'BEWARE children need regular infusions of toys. Santa knows your {domain.address}. There is no {action.optOutUrl}.',
      'name' => 'mailing name',
      'created_id' => 1,
      'groups' => array('include' => array($this->_groupID)),
      'scheduled_date' => 'now',
    );
    $this->defaultSettings = array(
      'recipients' => 20, // int, #contacts to receive mailing
      'workers' => 1, // int, #concurrent cron jobs
      'iterations' => 1, // int, #times to spawn all the workers
      'lockHold' => 0, // int, #extra seconds each cron job should hold lock
      'mailerBatchLimit' => 0, // int, max# recipients to send in a given cron run
      'mailerJobsMax' => 0, // int, max# concurrent jobs
      'mailerJobSize' => 0, // int, max# recipients in each job
      'mailThrottleTime' => 0, // int, microseconds separating messages
    );
    $this->_mut = new CiviMailUtils($this, TRUE);
    $this->callAPISuccess('mail_settings', 'get', array('api.mail_settings.create' => array('domain' => 'chaos.org')));
  }

  /**
   */
  public function tearDown() {
    //$this->_mut->clearMessages();
    $this->_mut->stop();
    CRM_Utils_Hook::singleton()->reset();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    //$this->cleanupMailingTest();
    parent::tearDown();
  }

  public function testBasic() {
    $this->createContactsInGroup(10, $this->_groupID);
    $this->setSettings(array(
      'mailerBatchLimit' => 2,
    ));
    $this->callAPISuccess('mailing', 'create', $this->_params);
    $this->_mut->assertRecipients(array());
    $this->callAPISuccess('job', 'process_mailing', array());
    $this->_mut->assertRecipients($this->getRecipients(1, 2));
  }

  public function concurrencyExamples() {
    $es = array();

    // Launch 3 workers, but mailerJobsMax limits us to 1 worker.
    $es[0] = array(
      array(
        'recipients' => 20,
        'workers' => 3,
        // FIXME: lockHold is unrealistic/unrepresentative. In reality, this situation fails because
        // the data.* locks trample the worker.* locks. However, setting lockHold allows us to
        // approximate the behavior of what would happen *if* the lock-implementation didn't suffer
        // trampling effects.
        'lockHold' => 10,
        'mailerBatchLimit' => 4,
        'mailerJobsMax' => 1,
      ),
      array(
        0 => 2, // 2 jobs which produce 0 messages
        4 => 1, // 1 job which produces 4 messages
      ),
      4,
    );

    // Launch 3 workers, but mailerJobsMax limits us to 2 workers.
    $es[1] = array(
      array(// Settings.
        'recipients' => 20,
        'workers' => 3,
        // FIXME: lockHold is unrealistic/unrepresentative. In reality, this situation fails because
        // the data.* locks trample the worker.* locks. However, setting lockHold allows us to
        // approximate the behavior of what would happen *if* the lock-implementation didn't suffer
        // trampling effects.
        'lockHold' => 10,
        'mailerBatchLimit' => 5,
        'mailerJobsMax' => 2,
      ),
      array(// Tallies.
        0 => 1, // 1 job which produce 0 messages
        5 => 2, // 2 jobs which produce 5 messages
      ),
      10, // Total sent.
    );

    // Launch 3 workers and saturate them (mailerJobsMax=3)
    $es[2] = array(
      array(// Settings.
        'recipients' => 20,
        'workers' => 3,
        'mailerBatchLimit' => 6,
        'mailerJobsMax' => 3,
      ),
      array(// Tallies.
        6 => 3, // 3 jobs which produce 6 messages
      ),
      18, // Total sent.
    );

    // Launch 4 workers and saturate them (mailerJobsMax=0)
    $es[3] = array(
      array(// Settings.
        'recipients' => 20,
        'workers' => 4,
        'mailerBatchLimit' => 6,
        'mailerJobsMax' => 0,
      ),
      array(// Tallies.
        6 => 3, // 3 jobs which produce 6 messages
        2 => 1, // 1 job which produces 2 messages
      ),
      20, // Total sent.
    );

    // Launch 1 worker, 3 times in a row. Deliver everything.
    $es[4] = array(
      array(// Settings.
        'recipients' => 10,
        'workers' => 1,
        'iterations' => 3,
        'mailerBatchLimit' => 7,
      ),
      array(// Tallies.
        7 => 1, // 1 job which produces 7 messages
        3 => 1, // 1 job which produces 3 messages
        0 => 1, // 1 job which produces 0 messages
      ),
      10, // Total sent.
    );

    // Launch 2 worker, 3 times in a row. Deliver everything.
    $es[5] = array(
      array(// Settings.
        'recipients' => 10,
        'workers' => 2,
        'iterations' => 3,
        'mailerBatchLimit' => 3,
      ),
      array(// Tallies.
        3 => 3, // 3 jobs which produce 3 messages
        1 => 1, // 1 job which produces 1 messages
        0 => 2, // 2 jobs which produce 0 messages
      ),
      10, // Total sent.
    );

    return $es;
  }

  /**
   * Setup various mail configuration options (eg $mailerBatchLimit,
   * $mailerJobMax) and spawn multiple worker threads ($workers).
   * Allow the threads to complete. (Optionally, repeat the above
   * process.) Finally, check to see if the right number of
   * jobs delivered the right number of messages.
   *
   * @param array $settings
   *   An array of settings (eg mailerBatchLimit, workers). See comments
   *   for $this->defaultSettings.
   * @param array $expectedTallies
   *    A listing of the number cron-runs keyed by their size.
   *    For example, array(10=>2) means that there 2 cron-runs
   *    which delivered 10 messages each.
   * @param int $expectedTotal
   *    The total number of contacts for whom messages should have
   *    been sent.
   * @dataProvider concurrencyExamples
   */
  public function testConcurrency($settings, $expectedTallies, $expectedTotal) {
    $settings = array_merge($this->defaultSettings, $settings);

    $this->createContactsInGroup($settings['recipients'], $this->_groupID);
    $this->setSettings(CRM_Utils_Array::subset($settings, array(
      'mailerBatchLimit',
      'mailerJobsMax',
      'mailThrottleTime',
    )));

    $this->callAPISuccess('mailing', 'create', $this->_params);

    $this->_mut->assertRecipients(array());

    $allApiResults = array();
    for ($iterationId = 0; $iterationId < $settings['iterations']; $iterationId++) {
      $apiCalls = $this->createExternalAPI();
      $apiCalls->addEnv(array('CIVICRM_CRON_HOLD' => $settings['lockHold']));
      for ($workerId = 0; $workerId < $settings['workers']; $workerId++) {
        $apiCalls->addCall('job', 'process_mailing', array());
      }
      $apiCalls->start();
      $this->assertEquals($settings['workers'], $apiCalls->getRunningCount());

      $apiCalls->wait();
      $allApiResults = array_merge($allApiResults, $apiCalls->getResults());
    }

    $actualTallies = $this->tallyApiResults($allApiResults);
    $this->assertEquals($expectedTallies, $actualTallies, 'API tallies should match.' . print_r(array(
        'expectedTallies' => $expectedTallies,
        'actualTallies' => $actualTallies,
        'apiResults' => $allApiResults,
      ), TRUE));
    $this->_mut->assertRecipients($this->getRecipients(1, $expectedTotal));
    $this->assertEquals(0, $apiCalls->getRunningCount());
  }

  /**
   * @param int $count
   * @param int $groupID
   */
  public function createContactsInGroup($count, $groupID, $domain = 'nul.example.com') {
    for ($i = 1; $i <= $count; $i++) {
      $contactID = $this->individualCreate(array('first_name' => $count, 'email' => 'mail' . $i . '@' . $domain));
      $this->callAPISuccess('group_contact', 'create', array(
        'contact_id' => $contactID,
        'group_id' => $groupID,
        'status' => 'Added',
      ));
    }
  }

  /**
   * Construct the list of email addresses for $count recipients.
   *
   * @param int $start
   * @param int $count
   *
   * @return array
   */
  public function getRecipients($start, $count, $domain = 'nul.example.com') {
    $recipients = array();
    for ($i = $start; $i < ($start + $count); $i++) {
      $recipients[][0] = 'mail' . $i . '@' . $domain;
    }
    return $recipients;
  }

  /**
   * @param array $params
   *   - mailerBatchLimit
   *   - mailerJobSize
   *   - mailerJobsMax
   *   - mailThrottleTime
   */
  protected function setSettings($params) {
    // FIXME: These settings are not available via Setting API.
    // When they become available, use that instead.
    CRM_Core_BAO_ConfigSetting::create($params);
  }

  protected function cleanupMailingTest() {
    $this->quickCleanup(array(
      'civicrm_mailing',
      'civicrm_mailing_job',
      'civicrm_mailing_spool',
      'civicrm_mailing_group',
      'civicrm_mailing_recipients',
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_delivered',
      'civicrm_group',
      'civicrm_group_contact',
      'civicrm_contact',
    ));
  }

  /**
   * Categorize results based on (a) whether they succeeded
   * and (b) the number of messages sent.
   *
   * @param array $apiResults
   * @return array
   *   One key 'error' for all failures.
   *   A separate key for each distinct quantity.
   */
  protected function tallyApiResults($apiResults) {
    $ret = array();
    foreach ($apiResults as $apiResult) {
      $key = !empty($apiResult['is_error']) ? 'error' : $apiResult['values']['processed'];
      $ret[$key] = !empty($ret[$key]) ? 1 + $ret[$key] : 1;
    }
    return $ret;
  }

}
