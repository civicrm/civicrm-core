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
namespace Civi\FlexMailer;

/**
 * Test that content produced by CiviMail looks the way it's expected.
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */
use Civi\Core\Event\GenericHookEvent;

// For compat w/v4.6 phpunit
require_once 'tests/phpunit/CRM/Mailing/MailingSystemTestBase.php';

/**
 * Class FlexMailerSystemTest
 *
 * MailingSystemTest checks that overall composition and delivery of
 * CiviMail blasts works. It extends CRM_Mailing_MailingSystemTestBase
 * which provides the general test scenarios -- but this variation
 * checks that certain internal events/hooks fire.
 *
 * FlexMailerSystemTest is the counterpart to MailingSystemTest.
 * @group headless
 * @group civimail
 * @see CRM_Mailing_MailingSystemTest
 */
class FlexMailerSystemTest extends \CRM_Mailing_MailingSystemTestBase {

  private $counts;

  public function setUp(): void {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(['org.civicrm.flexmailer']);
    }

    parent::setUp();

    $dispatcher = \Civi::service('dispatcher');
    foreach (FlexMailer::getEventTypes() as $event => $class) {
      $dispatcher->addListener($event, [$this, 'handleEvent']);
    }

    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_alterMailParams',
      [$this, 'hook_alterMailParams']);
    $this->counts = [];
  }

  public function handleEvent(GenericHookEvent $e) {
    // We keep track of the events that fire during mail delivery.
    // At the end, we'll ensure that the correct events fired.
    $clazz = get_class($e);
    if (!isset($this->counts[$clazz])) {
      $this->counts[$clazz] = 1;
    }
    else {
      $this->counts[$clazz]++;
    }
  }

  /**
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function hook_alterMailParams(&$params, $context = NULL) {
    $this->counts["hook_alterMailParams::$context"] = 1;
  }

  public function tearDown(): void {
    parent::tearDown();
    $this->assertNotEmpty($this->counts['hook_alterMailParams::flexmailer']);
    $this->assertEmpty($this->counts['hook_alterMailParams::civimail'] ?? NULL);
    foreach (FlexMailer::getEventTypes() as $event => $class) {
      $this->assertTrue(
        $this->counts[$class] > 0,
        "If FlexMailer is active, $event should fire at least once."
      );
    }
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
  ): void {
    parent::testUrlTracking($inputHtml, $htmlUrlRegex, $textUrlRegex, $params);
  }

  public function testBasicHeaders(): void {
    parent::testBasicHeaders();
  }

  public function testText(): void {
    parent::testText();
  }

  public function testHtmlWithOpenTracking(): void {
    parent::testHtmlWithOpenTracking();
  }

  public function testHtmlWithOpenAndUrlTracking(): void {
    parent::testHtmlWithOpenAndUrlTracking();
  }

}
