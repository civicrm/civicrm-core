<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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

}
