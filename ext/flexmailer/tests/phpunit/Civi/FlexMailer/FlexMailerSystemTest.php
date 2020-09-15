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
use Symfony\Component\EventDispatcher\Event;

// For compat w/v4.6 phpunit
require_once 'tests/phpunit/CRM/Mailing/BaseMailingSystemTest.php';

/**
 * Class FlexMailerSystemTest
 *
 * MailingSystemTest checks that overall composition and delivery of
 * CiviMail blasts works. It extends CRM_Mailing_BaseMailingSystemTest
 * which provides the general test scenarios -- but this variation
 * checks that certain internal events/hooks fire.
 *
 * FlexMailerSystemTest is the counterpart to MailingSystemTest.
 * @group headless
 * @group civimail
 * @see CRM_Mailing_MailingSystemTest
 */
class FlexMailerSystemTest extends \CRM_Mailing_BaseMailingSystemTest {

  private $counts;

  public function setUp() {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(array('org.civicrm.flexmailer'));
    }

    parent::setUp();
    \Civi::settings()->set('flexmailer_traditional', 'flexmailer');

    $dispatcher = \Civi::service('dispatcher');
    foreach (FlexMailer::getEventTypes() as $event => $class) {
      $dispatcher->addListener($event, array($this, 'handleEvent'));
    }

    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_alterMailParams',
      array($this, 'hook_alterMailParams'));
    $this->counts = array();
  }

  public function handleEvent(Event $e) {
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
    $this->counts['hook_alterMailParams'] = 1;
    $this->assertEquals('flexmailer', $context);
  }

  public function tearDown() {
    parent::tearDown();
    $this->assertNotEmpty($this->counts['hook_alterMailParams']);
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

}
