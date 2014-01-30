<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
class WebTest_Event_AdditionalPaymentTest extends CiviSeleniumTestCase {
  protected function setUp() {
    parent::setUp();
  }

  // CRM-13964
  function testParticipantParitalPaymentInitiation() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', TRUE);
    $contactName = "Anderson, $firstName";
    $displayName = "$firstName Anderson";

    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone", "_qf_Participant_upload-bottom");

    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select('event_id', "label=regexp:Rain-forest Cup Youth Soccer Tournament.");

    // Select role
    $this->click('role_id[2]');

    // Choose Registration Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('register_date', 'now');
    $today = date('F jS, Y', strtotime('now'));
    // May 5th, 2010

    // Select participant status
    $this->select('status_id', 'value=1');

    // Setting registration source
    $this->type('source', 'Event Partially Paid Webtest');

    // Since we're here, let's check of screen help is being displayed properly
    $this->assertTrue($this->isTextPresent('Source for this registration (if applicable).'));

    // Select an event fee
    $this->waitForElementPresent('priceset');

    $this->click("xpath=//input[@class='form-radio']");
    sleep(1);
    // record payment total amount
    // amount populated after fee selection
    $amtTotalOwed = (int) $this->getValue('id=total_amount');
    $this->assertEquals($amtTotalOwed, 800, 'The amount owed doesn\'t match to fee amount selected');

    // now change the amount to lesser amount value
    $this->type('total_amount', '400');

    // Select payment method = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', '1044');

    // give some time for js to process
    sleep(1);
    $this->verifySelectedLabel("status_id", 'Partially paid');

    // later on change the status
    $this->select('status_id', 'value=1');

    // Clicking save.
    // check for proper info message displayed regarding status
    $this->chooseCancelOnNextConfirmation();
    $this->click('_qf_Participant_upload-bottom');
    $this->assertTrue((bool)preg_match("/Payment amount is less than the amount owed. Expected participant status is 'Partially paid'. Are you sure you want to set the participant status to Registered/", $this->getConfirmation()));

    // select partially paid status again and click on save
    $this->select('status_id', 'label=Partially paid');

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Event registration for $displayName has been added");
    $this->waitForElementPresent("xpath=//form[@id='Search']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->click("xpath=//form[@id='Search']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => 'Rain-forest Cup Youth Soccer Tournament',
        'Participant Role' => 'Attendee',
        'Status' => 'Partially paid',
        'Event Source' => 'Event Partially Paid Webtest',
      )
    );

    // check the fee amount
    $feeAmt = 800.00;
    $this->assertElementContainsText("xpath=//td[@id='payment-info']/table[@id='info']/tbody/tr[2]/td", "$ {$feeAmt}", 'Missing text: appropriate fee amount');
    // check paid amount
    $amtPaid = 400.00;
    $this->assertElementContainsText("xpath=//td[@id='payment-info']/table[@id='info']/tbody/tr[2]/td[2]/a", "$ {$amtPaid}", 'Missing text: appropriate fee amount');

    // check contribution record as well
    //click through to the contribution view screen
    $this->click("xpath=id('ParticipantView')/div[2]/table[@class='selector']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'From' => $displayName,
        'Financial Type' => 'Event Fee',
        'Total Amount' => '$ 800.00',
        'Contribution Status' => 'Partially paid',
        'Paid By' => 'Check',
        'Check Number' => '1044',
      )
    );
  }
}