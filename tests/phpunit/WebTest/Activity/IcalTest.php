<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
require_once 'ezc/Base/src/ezc_bootstrap.php';
require_once 'ezc/autoload/mail_autoload.php';

class WebTest_Activity_IcalTest extends CiviSeleniumTestCase {

    // This variable is a bit awkward, but the ezc callback function needed to walk through the email parts needs to be static, so use this variable to "report back" on whether we found what we're looking for or not.
    private static $foundIt = false;


    protected function setUp() {
        parent::setUp();
    }

    function testStandaloneActivityAdd() {

        $this->open($this->sboxPath);
        $this->webtestLogin();

        $this->openCivipage("admin/setting/preferences/display", "reset=1", "name=activity_assignee_notification_ics");

        // Notify assignees should be checked by default, so we just need to click the ical setting which is off by default.
        $this->check("name=activity_assignee_notification_ics");
        $this->click("_qf_Display_next");
        $this->waitForPageToLoad($this->getTimeoutMsec());

        // Start spooling emails
        $mailer = new CiviMailUtils( $this, true );
        self::$foundIt = false;

        $firstName1 = substr(sha1(rand()), 0, 7);
        $this->webtestAddContact("$firstName1", "Anderson", $firstName1 . "@anderson.com");

        $this->openCivipage("activity", "reset=1&action=add&context=standalone", "_qf_Activity_upload");

        $this->select("activity_type_id", "value=1");

        $this->click("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id");
        $this->typeKeys("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", "$firstName1");

        $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
        $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");
        $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");
        $this->waitForElementPresent("css=tr.crm-activity-form-block-assignee_contact_id td ul li span.token-input-delete-token-facebook");

        $subject = "Testing Ical attachment for activity assignee";
        $this->type("subject", $subject);

        $location = 'Some location needs to be put in this field.';
        $this->type("location", $location);

        $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');
        $this->select("status_id", "value=1");

        $this->click("_qf_Activity_upload");
        $this->waitForPageToLoad($this->getTimeoutMsec());

        $this->assertElementContainsText('crm-notification-container', "Activity '$subject' has been saved.", "Status message didn't show up after saving!");

        // check the resulting email
        $mail = $mailer->getMostRecentEmail( 'ezc' );
        $this->assertNotNull( $mail, ts('Assignee email not generated or problem locating it.') );
        $this->assertEquals( $mail->subject, "$subject" );
        $context = new ezcMailPartWalkContext( array( get_class($this), 'mailWalkCallback' ) );
        $mail->walkParts( $context, $mail );

        $mailer->stop();

        $this->assertTrue( self::$foundIt, ts('Generated email does not contain an ical attachment.') );
    }

    public static function mailWalkCallback( $context, $mailPart ) {
        // echo "Class: " . get_class($mailPart) . "\n";
        $disp = $mailPart->contentDisposition;
        if ( $disp ) {
            if ( $disp->disposition == 'attachment' ) {
                if ( $mailPart instanceof ezcMailText ) {
                    if ( $mailPart->subType == 'calendar' ) {
                        // For now we just check for existence.
                        self::$foundIt = true;

                        // echo $mailPart->generateBody() . "\n";
                    }
                }
            }
        }
    }
}

