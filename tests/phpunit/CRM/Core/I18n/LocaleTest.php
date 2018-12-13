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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
/**
 * Class CRM_Core_I18n_LocaleTest
 * @group headless
 */
class CRM_Core_I18n_LocaleTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    parent::tearDown();
  }

  /**
   *
   */
  public function testI18nLocaleChange() {
    $this->enableMultilingual();
    CRM_Core_I18n_Schema::addLocale('fr_CA', 'en_US');

    CRM_Core_I18n::singleton()->setLocale('fr_CA');
    $locale = CRM_Core_I18n::getLocale();

    $this->assertEquals($locale, 'fr_CA');
    CRM_Core_I18n::singleton()->setLocale('en_US');
    Civi::$statics['CRM_Core_I18n']['singleton'] = [];
  }

}
