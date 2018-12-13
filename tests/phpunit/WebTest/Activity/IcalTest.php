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
 * Class WebTest_Activity_IcalTest
 */
class WebTest_Activity_IcalTest extends CiviSeleniumTestCase {

  // This variable is a bit awkward, but the ezc callback function needed to walk through the email parts needs to be static, so use this variable to "report back" on whether we found what we're looking for or not.
  private static $foundIt = FALSE;

  protected function setUp() {
    parent::setUp();
  }

  public function testStandaloneActivityAdd() {
    $this->webtestLogin();

    $this->openCiviPage("admin/setting/preferences/display", "reset=1", "name=activity_assignee_notification_ics");

    // Notify assignees should be checked by default, so we just need to click the ical setting which is off by default.
    $this->check("name=activity_assignee_notification_ics");
    $this->click("_qf_Display_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Start spooling emails
    $mailer = new CiviMailUtils($this, TRUE);
    self::$foundIt = FALSE;

    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$firstName1", "Anderson", $firstName1 . "@anderson.com");

    $this->openCiviPage("activity", "reset=1&action=add&context=standalone", "_qf_Activity_upload");

    $this->select("activity_type_id", "value=1");

    $this->click("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: tokeninput has a slight delay
    sleep(1);
    $this->keyDown("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName1);
    $this->typeKeys("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName1);

    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");
    $this->clickAt("xpath=//div[@class='select2-result-label']");
    $this->waitForText("xpath=//div[@id='s2id_assignee_contact_id']", "$firstName1");

    $subject = "Testing Ical attachment for activity assignee";
    $this->type("subject", $subject);

    $location = 'Some location needs to be put in this field.';
    $this->type("location", $location);

    $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');
    $this->select("status_id", "value=1");

    $this->click("_qf_Activity_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', $subject);

    // check the resulting email
    $mail = $mailer->getMostRecentEmail('ezc');
    $this->assertNotNull($mail, ts('Assignee email not generated or problem locating it.'));
    $this->assertEquals($mail->subject, "$subject");
    $context = new ezcMailPartWalkContext(array(get_class($this), 'mailWalkCallback'));
    $mail->walkParts($context, $mail);

    $mailer->stop();

    $this->assertTrue(self::$foundIt, ts('Generated email does not contain an ical attachment.'));
  }

  /**
   * @param $context
   * @param $mailPart
   */
  public static function mailWalkCallback($context, $mailPart) {

    $disp = $mailPart->contentDisposition;
    if ($disp) {
      if ($disp->disposition == 'attachment') {
        if ($mailPart instanceof ezcMailText) {
          if ($mailPart->subType == 'calendar') {
            // For now we just check for existence.
            self::$foundIt = TRUE;
          }
        }
      }
    }
  }

}
