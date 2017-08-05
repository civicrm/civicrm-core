<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Test that content produced by CiviMail looks the way it's expected.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC (c) 2004-2016
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

require_once 'tests/phpunit/CRM/Mailing/BaseMailingSystemTest.php';

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
    CRM_Core_BAO_Setting::setItem(FALSE, 'Mailing Preferences', 'experimentalFlexMailerEngine');

    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_alterMailParams',
      array($this, 'hook_alterMailParams'));
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
    // $this->markTestSkipped('BaseMailingSystemTest::testHtmlWithOpenTracking does not apply to v4.6. CiviMail in v4.6 has old behavior.');
    parent::testHtmlWithOpenTracking();
  }

  public function testHtmlWithOpenAndUrlTracking() {
    // $this->markTestSkipped('BaseMailingSystemTest::testHtmlWithOpenAndUrlTracking does not apply. CiviMail in v4.6 has old behavior.');
    parent::testHtmlWithOpenAndUrlTracking();
  }

}
