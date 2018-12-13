<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';
require_once 'CiviTest/CiviMailUtils.php';

/**
 * Class WebTest_Mailing_SpoolTest
 */
class WebTest_Mailing_SpoolTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testSpooledMailing() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // Start spooling mail
    $mut = new CiviMailUtils($this, TRUE);

    // Add a contact
    $fname = substr(sha1(rand()), 0, 6);
    $lname = substr(sha1(rand()), 0, 6);
    $email = $this->webtestAddContact($fname, $lname, TRUE);

    // Get the contact id of the newly added contact
    $cid = $this->urlArg('cid');

    // Send an email to the added contact
    $this->openCiviPage("activity/email/add", "action=add&reset=1&cid={$cid}&selectedChild=activity&atype=3");
    $this->type('subject', 'test spool');
    $this->fillRichTextField('html_message', 'Unit tests keep children safe.');
    $this->click("_qf_Email_upload");

    // Retrieve an ezc mail object version of the email
    $msg = $mut->getMostRecentEmail('ezc');

    $this->assertNotEmpty($msg, 'Mail message empty or not found.');
    $this->assertEquals($msg->subject, 'test spool');
    // should really walk through the 'to' array, but this is legal according to the docs
    $this->assertContains($email, implode(';', $msg->to), 'Recipient incorrect.');

    $context = new ezcMailPartWalkContext(array(get_class($this), 'mailWalkCallback'));
    $msg->walkParts($context, $msg);

    /*
     *  Now try a regular activity with cc to assignee
     */
    $this->WebtestAddActivity();
    $msg = $mut->getMostRecentEmail('raw');
    $this->assertNotEmpty($msg, 'Mail message empty or not found.');
    $this->assertContains('Subject: This is subject of test activity', $msg, 'Subject of email is wrong.');

    $mut->stop();
  }

  /**
   * @param $context
   * @param $mailPart
   */
  public static function mailWalkCallback($context, $mailPart) {
    if ($mailPart instanceof ezcMailText) {
      self::assertEquals($mailPart->subType, 'html');
      self::assertContains('Unit tests keep children safe', $mailPart->generateBody());
    }

    $disp = $mailPart->contentDisposition;
    if ($disp) {

    }
  }

}
