<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class CRM_Core_BAO_PreferencesTest
 * @group headless
 */
class CRM_Core_BAO_PreferencesTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testValueOptions() {

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options'
    );

    // street_address should be set
    $this->assertEquals($addressOptions['street_address'], 1, 'Street Address is not set in address options');
    $this->assertEquals($addressOptions['country'], 1, 'Country is not set in address options');
  }

  public function testSetValueOptions() {
    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options'
    );
    $addressOptions['county'] = 1;
    CRM_Core_BAO_Setting::setValueOption(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options',
      $addressOptions
    );
    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options'
    );

    $this->assertEquals($addressOptions['county'], 1, 'County was set but did not stick in db');
  }

}
