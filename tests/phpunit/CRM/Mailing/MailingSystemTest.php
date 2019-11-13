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
 * Test that content produced by CiviMail looks the way it's expected.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Class CRM_Mailing_MailingSystemTest
 *
 * MailingSystemTest checks that overall composition and delivery of
 * CiviMail blasts works. It extends CRM_Mailing_BaseMailingSystemTest
 * which provides the general test scenarios -- but this variation
 * checks that certain internal events/hooks fire.
 *
 * MailingSystemTest is the counterpart to FlexMailerSystemTest.
 *
 * @group headless
 * @group civimail
 * @see \Civi\FlexMailer\FlexMailerSystemTest
 */
class CRM_Mailing_MailingSystemTest extends CRM_Mailing_BaseMailingSystemTest {

  private $counts;

  public function setUp() {
    parent::setUp();
    Civi::settings()->add(['experimentalFlexMailerEngine' => FALSE]);

    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_alterMailParams',
      [$this, 'hook_alterMailParams']);
  }

  /**
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function hook_alterMailParams(&$params, $context = NULL) {
    $this->counts['hook_alterMailParams'] = 1;
    $this->assertEquals('civimail', $context);
  }

  public function tearDown() {
    parent::tearDown();
    $this->assertNotEmpty($this->counts['hook_alterMailParams']);
  }

  // ---- Boilerplate ----

  // The remainder of this class contains dummy stubs which make it easier to
  // work with the tests in an IDE.

  /**
   * Generate a fully-formatted mailing (with body_html content).
   *
   * @dataProvider urlTrackingExamples
   */
  public function testUrlTracking(
    $inputHtml,
    $htmlUrlRegex,
    $textUrlRegex,
    $params
  ) {
    parent::testUrlTracking($inputHtml, $htmlUrlRegex, $textUrlRegex, $params);
  }

  public function testBasicHeaders() {
    parent::testBasicHeaders();
  }

  public function testText() {
    parent::testText();
  }

  public function testHtmlWithOpenTracking() {
    parent::testHtmlWithOpenTracking();
  }

  public function testHtmlWithOpenAndUrlTracking() {
    parent::testHtmlWithOpenAndUrlTracking();
  }

  /**
   * Test to check Activity being created on mailing Job.
   *
   */
  public function testMailingActivityCreate() {
    $subject = uniqid('testMailingActivityCreate');
    $this->runMailingSuccess([
      'subject' => $subject,
      'body_html' => 'Test Mailing Activity Create',
      'scheduled_id' => $this->individualCreate(),
    ]);

    $this->callAPISuccessGetCount('activity', [
      'activity_type_id' => 'Bulk Email',
      'status_id' => 'Completed',
      'subject' => $subject,
    ], 1);
  }

}
