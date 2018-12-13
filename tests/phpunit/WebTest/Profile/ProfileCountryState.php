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
 * Class WebTest_Profile_ProfileCountryState
 */
class WebTest_Profile_ProfileCountryState extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testStateCountry() {
    $this->webtestLogin();
    $config = CRM_Core_Config::singleton();
    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');
    $this->click('newCiviCRMProfile-top');
    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'Country state province web test temp';
    $this->type('title', $profileTitle);

    // Standalone form or directory
    $this->click('uf_group_type_Profile');

    //click on save
    $this->click('_qf_Group_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->waitForText('crm-notification-container', "Profile '{$profileTitle}' has been added. You can add fields to this profile now.");
    $gid = $this->urlArg('gid');

    //Add Country field to profile
    $this->openCiviPage('admin/uf/group/field/add', array(
        'action' => 'add',
        'reset' => 1,
        'gid' => $gid,
      ), 'field_name[0]');
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=country');
    $this->click("//option[@value='country']");
    $this->click('is_required');

    $this->click('_qf_Field_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Add State field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=state_province');
    $this->click("xpath=//select[@id='field_name_1']/option[@value='state_province']");
    $this->click('is_required');
    //click on save
    $this->click('_qf_Field_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    if ($gid) {
      $this->openCiviPage('admin/setting/localization', 'reset=1', '_qf_Localization_next-bottom');
      $country = array(1001 => 'Afghanistan', 1013 => 'Australia', 1039 => 'Canada', 1101 => 'India');
      $enabledCountries = $this->getSelectOptions("countryLimit-t");
      $enabledStates = $this->getSelectOptions("provinceLimit-t");
      $newCountry = array();
      foreach ($country as $countryID => $countryName) {
        if (!in_array($countryName, $enabledCountries)) {
          $newCountry[$countryID] = $countryName;
          $this->addSelection("countryLimit-f", "label=$countryName");
          $this->click("xpath=//select[@id='countryLimit-f']/option[@value='$countryID']");
          $this->click("xpath=//tr[@class='crm-localization-form-block-countryLimit']/td[2]/table//tbody/tr/td[2]/input[@name='add']");
        }
        if (!in_array($countryName, $enabledStates)) {
          $this->addSelection("provinceLimit-f", "label=$countryName");
          $this->click("//option[@value='$countryID']");
          $this->click("xpath=//tr[@class='crm-localization-form-block-provinceLimit']/td[2]/table//tbody/tr/td[2]/input[@name='add']");
        }
        $added = TRUE;
      }
      if ($added) {
        $this->click("_qf_Localization_next-bottom");
        $this->waitForPageToLoad($this->getTimeoutMsec());
        $this->waitForText('crm-notification-container', "Saved");
      }
      $this->openCiviPage("profile/create", "gid=$gid&reset=1", NULL);

      $this->waitForElementPresent("xpath=//form[@id='Edit']/div[2]/div/div/div[2]/select");
      $this->click("xpath=//form[@id='Edit']/div[2]/div/div/div[2]/select");
      $countryID = array_rand($country);
      $states = CRM_Core_PseudoConstant::stateProvinceForCountry($countryID, 'id');
      $stateID = array_rand($states);
      $this->select("xpath=//form[@id='Edit']/div[2]/div/div/div[2]/select", "value=$countryID");
      $this->waitForElementPresent("xpath=//form[@id='Edit']/div[2]/div/div[2]/div[2]/select/option[@value=$stateID]");
      $this->click("xpath=//form[@id='Edit']/div[2]/div/div[2]/div[2]/select");
      $this->select("xpath=//form[@id='Edit']/div[2]/div/div[2]/div[2]/select", "value=$stateID");
      $this->clickLink('_qf_Edit_next', NULL);

      // Delete profile
      $this->openCiviPage('admin/uf/group', 'action=delete&id=' . $gid, '_qf_Group_next-bottom');
      $this->clickLink('_qf_Group_next-bottom', 'newCiviCRMProfile-bottom');
      $this->waitForText('crm-notification-container', "Profile '{$profileTitle}' has been deleted.");

      $this->openCiviPage("admin/setting/localization", "reset=1", "_qf_Localization_next-bottom");
      $enabledCountries = $this->getSelectOptions("countryLimit-t");
      $enabledStates = $this->getSelectOptions("provinceLimit-t");
      $removed = FALSE;
      foreach ($newCountry as $countryID => $countryName) {
        $this->addSelection("countryLimit-t", "label=$countryName");
        $this->click("xpath=//select[@id='countryLimit-t']/option[@value='$countryID']");
        $this->click("xpath=//tr[@class='crm-localization-form-block-countryLimit']/td[2]/table//tbody/tr/td[2]/input[@name='remove']");

        $this->addSelection("provinceLimit-t", "label=$countryName");
        $this->click("//option[@value='$countryID']");
        $this->click("xpath=//tr[@class='crm-localization-form-block-provinceLimit']/td[2]/table//tbody/tr/td[2]/input[@name='remove']");
        $removed = TRUE;
      }
      if ($removed) {
        $this->click("_qf_Localization_next-bottom");
        $this->waitForPageToLoad($this->getTimeoutMsec());
        $this->waitForText('crm-notification-container', "Saved");
      }
    }
  }

}
