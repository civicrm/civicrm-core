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
    $examples = [
      'en_US' => 'Yes',
      'fr_CA' => 'Oui',
      'de_DE' => 'Ja',
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

    // If you switch back and forth among these languages, `ts()` should follow suit.
    for ($trial = 0; $trial < 3; $trial++) {
      foreach ($examples as $exLocale => $exString) {
        CRM_Core_I18n::singleton()->setLocale($exLocale);
        $this->assertEquals($exString, ts('Yes'), "Translate");
      }
    }

    \CRM_Core_DAO::executeQuery('UPDATE civicrm_option_value SET label_fr_CA = \'Planifié\' WHERE name = \'Scheduled\'',
      [], TRUE, NULL, FALSE, FALSE);

    // If you switch back and forth among these languages, labels should follow suit.
    for ($trial = 0; $trial < 3; $trial++) {
      \CRM_Core_I18n::singleton()->setLocale('en_US');
      $this->assertEquals('Scheduled', \CRM_Core_PseudoConstant::getLabel("CRM_Activity_BAO_Activity", "status_id", 1));

      \CRM_Core_I18n::singleton()->setLocale('fr_CA');
      $this->assertEquals('Planifié', \CRM_Core_PseudoConstant::getLabel("CRM_Activity_BAO_Activity", "status_id", 1));
    }

    CRM_Core_I18n::singleton()->setLocale('en_US');
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    Civi::$statics['CRM_Core_I18n']['singleton'] = [];
  }

  /**
   * Quirk in strtolower does not handle "I" as expected, compared to mb_strtolower.
   */
  public function testInsertTurkish() {
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS foo");
    CRM_Core_DAO::executeQuery("CREATE TABLE foo ( bar varchar(32) )");
    // Change locale - assert it actually changed.
    $this->assertEquals('tr_TR.utf8', setlocale(LC_ALL, 'tr_TR.utf8'));
    $dao = new CRM_Core_DAO();
    // When query() uses strtolower this returns NULL instead
    $this->assertEquals(1, $dao->query("INSERT INTO foo VALUES ('Turkish Delight')"));
    setlocale(LC_ALL, 'en_US');
    CRM_Core_DAO::executeQuery("DROP TABLE foo");
  }

}
