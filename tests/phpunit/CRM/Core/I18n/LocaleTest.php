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
 * Class CRM_Core_I18n_LocaleTest
 * @group headless
 */
class CRM_Core_I18n_LocaleTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
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
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    Civi::$statics['CRM_Core_I18n']['singleton'] = [];
  }

  public function testUiLanguages() {
    $languages = [
      'en_US' => 'English (United States)',
      'fr_CA' => 'French (Canada)',
      'de_DE' => 'German',
    ];
    $codes = array_keys($languages);
    Civi::settings()->set('uiLanguages', $codes);

    // Check we can retrieve the setting
    $result = Civi::settings()->get('uiLanguages');
    $this->assertEquals($codes, $result);

    // Monolingual, codes
    $result = CRM_Core_I18n::uiLanguages(TRUE);
    $this->assertArrayValuesEqual($codes, $result);

    // Monolingual, codes and language labels
    $result = CRM_Core_I18n::uiLanguages();
    $this->assertTreeEquals($languages, $result);

    $this->enableMultilingual();
    // Add fr_CA in db
    CRM_Core_I18n_Schema::addLocale('fr_CA', 'en_US');
    // Make fr_CA 'available'
    Civi::settings()->set('languageLimit', ['en_US' => 1, 'fr_CA' => 1]);

    // Multilingual, codes
    $result = CRM_Core_I18n::uiLanguages(TRUE);
    $this->assertArrayValuesEqual(['en_US', 'fr_CA'], $result);

    // Multilingual, codes and language labels
    $result = CRM_Core_I18n::uiLanguages();
    $this->assertTreeEquals([
      'en_US' => 'English (United States)',
      'fr_CA' => 'French (Canada)',
    ], $result);

    CRM_Core_I18n::singleton()->setLocale('en_US');
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    Civi::$statics['CRM_Core_I18n']['singleton'] = [];
  }

}
