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
class WebTest_Profile_ProfileCountryState extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testStateCountry() {
    $this->webtestLogin();
    $config = CRM_Core_Config::singleton();
    $import = new CRM_Utils_Migrate_Import();
    global $civicrm_root;
    $path = $civicrm_root . '/tests/phpunit/WebTest/Profile/xml/CountryStateWebtest.xml';
    $import->run($path);
    $result = $this->webtest_civicrm_api('uf_group', 'get', array( 'name' => 'country_state_province_web_test_19' ));
    if($result['id']) {
      $gid = $result['id'];
      $this->openCiviPage("admin/setting/localization", "reset=1", "_qf_Localization_next-bottom");
      $country = array(1001 => 'Afghanistan', 1013 => 'Australia', 1039 => 'Canada', 1101 => 'India');
      $enabledCountries = $this->getSelectOptions("countryLimit-t");
      $enabledStates = $this->getSelectOptions("provinceLimit-t");

      foreach($country as $countryID => $countryName) {
        if(!in_array($countryName, $enabledCountries)) {
          $this->addSelection("countryLimit-f", "label=$countryName");
          $this->click("xpath=//select[@id='countryLimit-f']/option[@value='$countryID']");
          $this->click("xpath=//tr[@class='crm-localization-form-block-countryLimit']/td[2]/table//tbody/tr/td[2]/input[@name='add']");
        }
        if(!in_array($countryName, $enabledStates)) {
          $this->addSelection("provinceLimit-f", "label=$countryName");
          $this->click("//option[@value='$countryID']");
          $this->click("xpath=//tr[@class='crm-localization-form-block-provinceLimit']/td[2]/table//tbody/tr/td[2]/input[@name='add']");
        }
        $added = true;
      }
      if ($added) {
        $this->click("_qf_Localization_next-bottom");
        $this->waitForPageToLoad($this->getTimeoutMsec());
        $this->waitForText('crm-notification-container', "Saved");
      }
 
      $url = $this->sboxPath . "civicrm/profile/create?gid={$gid}&reset=1";

      $this->open($url);
      $this->waitForElementPresent("xpath=//form[@id='Edit']/div[2]/div/div/div[2]/select");

      $this->click("xpath=//form[@id='Edit']/div[2]/div/div/div[2]/select");
      $countryID = array_rand($country);
      $states = CRM_Core_PseudoConstant::stateProvinceForCountry($countryID, 'id');
      $stateID = array_rand($states);
      $this->select("xpath=//form[@id='Edit']/div[2]/div/div/div[2]/select", "value=$countryID");
      $this->waitForElementPresent("xpath=//form[@id='Edit']/div[2]/div/div[2]/div[2]/select");
      $this->click("xpath=//form[@id='Edit']/div[2]/div/div[2]/div[2]/select");
      $this->select("xpath=//form[@id='Edit']/div[2]/div/div[2]/div[2]/select", "value=$stateID");
      $this->clickLink('_qf_Edit_next', NULL);
    }
  }
}