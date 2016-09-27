<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Test class for the option.language API parameter in multilingual.
 *
 * @package CiviCRM
 * @group headless
 */
class api_v3_MultilingualTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  public $DBResetRequired = FALSE;

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function tearDown() {
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    parent::tearDown();
  }

  public function testOptionLanguage() {
    civicrm_api3('Setting', 'create', array(
      'lcMessages' => 'en_US',
      'languageLimit' => array(
        'en_US' => 1,
      ),
    ));

    CRM_Core_I18n_Schema::makeMultilingual('en_US');

    global $dbLocale;
    $dbLocale = '_en_US';

    CRM_Core_I18n_Schema::addLocale('fr_CA', 'en_US');

    civicrm_api3('Setting', 'create', array(
      'languageLimit' => array(
        'en_US',
        'fr_CA',
      ),
    ));

    // Take a semi-random OptionGroup and test manually changing its label
    // in one language, while making sure it stays the same in English.
    $group = civicrm_api3('OptionGroup', 'getsingle', array(
      'name' => 'contact_edit_options',
    ));

    $english_original = civicrm_api3('OptionValue', 'getsingle', array(
      'option_group_id' => $group['id'],
      'name' => 'IM',
    ));

    civicrm_api3('OptionValue', 'create', array(
      'id' => $english_original['id'],
      'name' => 'IM',
      'label' => 'Messagerie instantanée',
      'option.language' => 'fr_CA',
    ));

    $french = civicrm_api3('OptionValue', 'getsingle', array(
      'option_group_id' => $group['id'],
      'name' => 'IM',
      'option.language' => 'fr_CA',
    ));

    $default = civicrm_api3('OptionValue', 'getsingle', array(
      'option_group_id' => $group['id'],
      'name' => 'IM',
      'option.language' => 'en_US',
    ));

    $this->assertEquals($french['label'], 'Messagerie instantanée');
    $this->assertEquals($default['label'], $english_original['label']);
  }

}
