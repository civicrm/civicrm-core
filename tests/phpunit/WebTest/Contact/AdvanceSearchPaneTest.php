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

/**
 * Class WebTest_Contact_AdvanceSearchPaneTest
 */
class WebTest_Contact_AdvanceSearchPaneTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test individual pane seperatly.
   */
  public function testIndividualPanes() {
    $this->webtestLogin();

    // Get all default advance search panes.
    $allpanes = $this->_advanceSearchPanes();

    // Test Individual panes.
    foreach (array_keys($allpanes) as $pane) {
      // Go to the Advance Search
      $this->openCiviPage('contact/search/advanced', 'reset=1');

      // Select some fields from pane.
      $this->_selectPaneFields($pane);

      $this->click('_qf_Advanced_refresh');

      $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

      // check the opened panes.
      $this->_checkOpenedPanes(array($pane));
    }
  }

  /**
   * Test by selecting all panes at a time.
   */
  public function testAllPanes() {
    $this->webtestLogin();

    // Get all default advance search panes.
    $allpanes = $this->_advanceSearchPanes();

    // Go to the Advance Search
    $this->openCiviPage('contact/search/advanced', 'reset=1');

    // Select some fields from all default panes.
    foreach (array_keys($allpanes) as $pane) {
      $this->_selectPaneFields($pane);
    }

    $this->click('_qf_Advanced_refresh');

    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

    // check all opened panes.
    $this->_checkOpenedPanes(array_keys($allpanes));
  }

  /**
   * @param array $openedPanes
   */
  public function _checkOpenedPanes($openedPanes = array()) {
    if (!$this->isTextPresent('None found.')) {
      $this->click('css=div.crm-advanced_search_form-accordion div.crm-accordion-header');
    }

    $allPanes = $this->_advanceSearchPanes();

    foreach ($allPanes as $paneRef => $pane) {
      if (in_array($paneRef, $openedPanes)) {
        // assert for element present.
        $this->waitForElementPresent("css=div.crm-accordion-wrapper div.crm-accordion-body {$pane['bodyLocator']}");
      }
      else {
        $this->assertTrue(!$this->isElementPresent("css=div.crm-accordion-wrapper div.crm-accordion-body {$pane['bodyLocator']}"));
      }
    }
  }

  /**
   * @param $paneRef
   * @param array $selectFields
   */
  public function _selectPaneFields($paneRef, $selectFields = array()) {
    $pane = $this->_advanceSearchPanes($paneRef);

    $this->click("css=div.crm-accordion-wrapper {$pane['headerLocator']}");
    $this->waitForElementPresent("css=div.crm-accordion-wrapper div.crm-accordion-body {$pane['bodyLocator']}");

    foreach ($pane['fields'] as $fld => $field) {
      if (!empty($selectFields) && !in_array($fld, $selectFields)) {
        continue;
      }

      $fldLocator = isset($field['locator']) ? $field['locator'] : '';

      switch ($field['type']) {
        case 'text':
          $this->type($fldLocator, current($field['values']));
          break;

        case 'select':
          foreach ($field['values'] as $op) {
            $this->select($fldLocator, 'label=' . $op);
          }
          break;

        case 'checkbox':
          foreach ($field['values'] as $op) {
            if (!$this->isChecked($op)) {
              $this->click($op);
            }
          }
          break;

        case 'radio':
          foreach ($field['values'] as $op) {
            $this->click($op);
          }
          break;

        case 'multiselect2':
          foreach ($field['values'] as $op) {
            $this->waitForElementPresent($fldLocator);
            $this->multiselect2($fldLocator, $op);
          }
          break;

        case 'date':
          $this->webtestFillDate($fldLocator, current($field['values']));
          break;
      }
    }
  }

  /**
   * @param null $paneRef
   *
   * @return array
   */
  public function _advanceSearchPanes($paneRef = NULL) {
    static $_advance_search_panes;

    if (!isset($_advance_search_panes) || empty($_advance_search_panes)) {
      $_advance_search_panes = array(
        'location' => array(
          'headerLocator' => 'div#location',
          'bodyLocator' => 'select#country',
          'title' => 'Address Fields',
          'fields' => array(
            'Location Type' => array(
              'type' => 'multiselect2',
              'locator' => 'location_type',
              'values' => array(array('Home', 'Work')),
            ),
            'Country' => array(
              'type' => 'select',
              'locator' => 'country',
              'values' => array('UNITED STATES'),
            ),
            'State' => array(
              'type' => 'multiselect2',
              'locator' => 'state_province',
              'values' => array(
                array('Alabama', 'California', 'New Jersey', 'New York'),
              ),
            ),
          ),
        ),
        'custom' => array(
          'headerLocator' => 'div#custom',
          'bodyLocator' => 'div#constituent_information',
          'title' => 'Custom Data',
          'fields' => array(
            'Marital Status' => array(
              'type' => 'select',
              'locator' => 'custom_2',
              'values' => array('Single'),
            ),
          ),
        ),
        'activity' => array(
          'headerLocator' => 'div#activity',
          'bodyLocator' => 'input#activity_subject',
          'title' => 'Activities',
          'fields' => array(
            'Activity Type' => array(
              'type' => 'multiselect2',
              'locator' => 'activity_type_id',
              'values' => array(array('Contribution', 'Email', 'Event Registration', 'Membership Signup')),
            ),
            'Activity Subject' => array(
              'type' => 'text',
              'locator' => 'activity_subject',
              'values' => array('Test Subject'),
            ),
            'Activity Status' => array(
              'type' => 'multiselect2',
              'locator' => 'status_id',
              'values' => array(array('Scheduled', 'Completed')),
            ),
          ),
        ),
        'relationship' => array(
          'headerLocator' => 'div#relationship',
          'bodyLocator' => 'select#relation_type_id',
          'title' => 'Relationships',
          'fields' => array(
            'Relation Type' => array(
              'type' => 'select',
              'locator' => 'relation_type_id',
              'values' => array('Employee of'),
            ),
            'Relation Target' => array(
              'type' => 'text',
              'locator' => 'relation_target_name',
              'values' => array('Test Contact'),
            ),
          ),
        ),
        'demographics' => array(
          'headerLocator' => 'div#demographics',
          'bodyLocator' => 'input#birth_date_low',
          'title' => 'Demographics',
          'fields' => array(
            'Birth Date Range' => array(
              'type' => 'select',
              'locator' => 'birth_date_relative',
              'values' => array('Choose Date Range'),
            ),
            'Birth Date from' => array(
              'type' => 'date',
              'locator' => 'birth_date_low',
              'values' => array('10 September 1980'),
            ),
            'Birth Date to' => array(
              'type' => 'date',
              'locator' => 'birth_date_high',
              'values' => array('10 September 2000'),
            ),
          ),
        ),
        'note' => array(
          'headerLocator' => 'div#notes',
          'bodyLocator' => 'input#note',
          'title' => 'Notes',
          'fields' => array(
            'note' => array(
              'type' => 'text',
              'locator' => 'css=div#notes-search input#note',
              'values' => array('Test Note'),
            ),
          ),
        ),
        'change_log' => array(
          'headerLocator' => 'div#changeLog',
          'bodyLocator' => 'input#changed_by',
          'title' => 'Change Log',
          'fields' => array(
            'Modified By' => array(
              'type' => 'text',
              'locator' => 'changed_by',
              'values' => array('Test User'),
            ),
          ),
        ),
        'contribution' => array(
          'headerLocator' => 'div#CiviContribute',
          'bodyLocator' => 'select#financial_type_id',
          'title' => 'Contributions',
          'fields' => array(
            'Amount from' => array(
              'type' => 'text',
              'locator' => 'contribution_amount_low',
              'values' => array('10'),
            ),
            'Amount to' => array(
              'type' => 'text',
              'locator' => 'contribution_amount_high',
              'values' => array('1000'),
            ),
            'Financial Type' => array(
              'type' => 'select',
              'locator' => 'financial_type_id',
              'values' => array('Donation'),
            ),
            'Contribution Status' => array(
              'type' => 'multiselect2',
              'locator' => 'contribution_status_id',
              'values' => array(array('Completed', 'Pending')),
            ),
          ),
        ),
        'membership' => array(
          'headerLocator' => 'div#CiviMember',
          'bodyLocator' => 'input#member_source',
          'title' => 'Memberships',
          'fields' => array(
            'Membership Type' => array(
              'type' => 'select2',
              'locator' => 'membership_type_id',
              'values' => array(array('General', 'Student')),
            ),
            'Membership Status' => array(
              'type' => 'multiselect2',
              'locator' => 'membership_status_id',
              'values' => array(array('New', 'Current')),
            ),
          ),
        ),
        'event' => array(
          'headerLocator' => 'div#CiviEvent',
          'bodyLocator' => 'input#event_id',
          'title' => 'Events',
          'fields' => array(
            'Participant Status' => array(
              'type' => 'multiselect2',
              'locator' => 'participant_status_id',
              'values' => array(array('Registered', 'Attended')),
            ),
            'Participant Role' => array(
              'type' => 'multiselect2',
              'locator' => 'participant_role_id',
              'values' => array(array('Attendee', 'Volunteer')),
            ),
          ),
        ),
      );
    }

    if ($paneRef) {
      return $_advance_search_panes[$paneRef];
    }

    return $_advance_search_panes;
  }

}
