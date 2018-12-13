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

/**
 * Description of AddRecurringEventTest
 *
 * @author Priyanka
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Event_AddRecurringEventTest
 */
class WebTest_Event_AddRecurringEventTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testRecurringEvent() {
    $this->webtestLogin();

    //Add repeat configuration for an event
    $this->openCiviPage("event/manage/repeat", "reset=1&action=update&id=1", '_qf_Repeat_cancel-bottom');

    $this->click('repetition_frequency_unit');
    $this->select('repetition_frequency_unit', 'label=week');
    $this->click('repetition_frequency_interval');
    $this->select('repetition_frequency_interval', 'label=1');
    $this->click('start_action_condition_monday');
    $this->click('start_action_condition_tuesday');
    $this->click('CIVICRM_QFID_1_ends');

    $occurrences = rand(3, 5);
    if (!$occurrences) {
      $occurrences = 3;
    }
    $this->select('start_action_offset', $occurrences);
    $this->multiselect2('exclude_date_list', array('05/11/2015', '05/12/2015'), TRUE);
    $this->click('_qf_Repeat_submit-bottom');
    $this->waitForTextPresent('A repeating set will be created with the following dates.');
    $this->click("xpath=//button//span[text()='Continue']");
    $this->waitForAjaxContent();
    $this->checkCRMAlert('Repeat Configuration has been saved');

    //Check if assertions are correct
    $this->waitForElementPresent("xpath=//div[@id='recurring-entity-block']/following-sibling::div//div[@class='crm-accordion-body']/div/table/tbody/tr");
    $count = $this->getXpathCount("xpath=//div[@id='recurring-entity-block']/following-sibling::div//div[@class='crm-accordion-body']/div/table/tbody/tr");
    $this->assertEquals($occurrences, $count);

    //Lets go to find participant page and see our repetitive events there
    $this->openCiviPage("event/manage", "reset=1");
    $eventTitle = "Fall Fundraiser Dinner";
    $this->type("title", $eventTitle);
    $this->click("_qf_SearchEvent_refresh");
    $this->assertTrue($this->isTextPresent("Repeating"));

    //Update Mode Cascade Changes
    $this->click('event-configure-1');
    $this->waitForElementPresent("xpath=//span[@id='event-configure-1']/ul[@class='panel']/li/a[text()='Info and Settings']");
    $this->click("xpath=//span[@id='event-configure-1']/ul[@class='panel']/li/a[text()='Info and Settings']");
    $this->waitForTextPresent("Event Title");
    $this->type('title', 'CiviCon');
    $this->click('_qf_EventInfo_upload_done-top');
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Cancel']");
    $this->click("recur-all-entity");
    $this->click("xpath=//button//span[text()='Continue']");
    $this->waitForAjaxContent();
    $this->openCiviPage("event/manage", "reset=1");
    $newEventTitle = "CiviCon";
    $this->type("title", $newEventTitle);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad();
    $countOfEvents = $this->getXpathCount("xpath=//div[@id='option11_wrapper']/table[@id='option11']/tbody/tr");
    if ($countOfEvents) {
      for ($i = 0; $i <= $countOfEvents; $i++) {
        $this->verifyText("xpath=//div[@id='option11_wrapper']/table[@id='option11']/tbody/tr/td[1]/a", 'CiviCon');
      }
    }
  }

}
