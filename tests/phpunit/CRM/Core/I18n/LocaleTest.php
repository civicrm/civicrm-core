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
 * @group locale
 */
class CRM_Core_I18n_LocaleTest extends CiviUnitTestCase {

  /**
   *
   */
  public function testI18nLocaleChange(): void {
    $cleanup = $this->useMultilingual(['en_US' => 'fr_CA']);

    CRM_Core_I18n::singleton()->setLocale('fr_CA');
    $locale = CRM_Core_I18n::getLocale();
    $this->assertEquals('fr_CA', $locale);
  }

  public function testUiLanguages(): void {
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

    $cleanup = $this->useMultilingual(['en_US' => 'fr_CA']);

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
  }

  public function getPartialLocaleExamples(): array {
    $results = [/* array $settings, string $preferredLocale, array $expectLocale, string $expectYes */];
    $results['es_MX full support (partial mode) '] = [['partial_locales' => TRUE], 'es_MX', ['nominal' => 'es_MX', 'ts' => 'es_MX', 'moneyFormat' => 'es_MX'], 'Sí', 'USD 1,234.56'];
    $results['es_PR mixed mode'] = [['partial_locales' => TRUE], 'es_PR', ['nominal' => 'es_PR', 'ts' => 'es_MX', 'moneyFormat' => 'es_PR'], 'Sí', '$1,234.56'];
    $results['th_TH mixed mode'] = [['partial_locales' => TRUE], 'th_TH', ['nominal' => 'th_TH', 'ts' => 'en_US', 'moneyFormat' => 'th_TH'], 'Yes', 'US$1,234.56'];
    $results['es_MX full support (full mode) '] = [['partial_locales' => TRUE], 'es_MX', ['nominal' => 'es_MX', 'ts' => 'es_MX', 'moneyFormat' => 'es_MX'], 'Sí', 'USD 1,234.56'];
    $results['es_PR switched to es_MX'] = [['partial_locales' => FALSE], 'es_PR', ['nominal' => 'es_MX', 'ts' => 'es_MX', 'moneyFormat' => 'es_MX'], 'Sí', 'USD 1,234.56'];
    $results['th_TH switched to en_US'] = [['partial_locales' => FALSE], 'th_TH', ['nominal' => 'en_US', 'ts' => 'en_US', 'moneyFormat' => 'en_US'], 'Yes', '$1,234.56'];
    return $results;
  }

  /**
   * @param array $settings
   *   List of settings to apply during the test
   * @param string $preferred
   *   The locale that we should try to use.
   *   Ex : 'es_PR'
   * @param array $expectLocale
   *   The locale options that we expect to use.
   *   Ex: ['nominal' => 'es_PR', 'ts' => 'es_MX', 'moneyFormat' => 'es_PR']
   * @param string $expectYes
   *   The translation for "Yes" in our expected language.
   * @param string $expectAmount
   *   The expected rendering of `1234.56` (USD) in the given locale.
   * @dataProvider getPartialLocaleExamples
   */
  public function testPartialLocale(array $settings, string $preferred, array $expectLocale, string $expectYes, string $expectAmount) {
    if (count(\CRM_Core_I18n::languages(FALSE)) <= 1) {
      $this->markTestIncomplete('Full testing of localization requires l10n data.');
    }
    $cleanup = CRM_Utils_AutoClean::swapSettings($settings);
    \Civi\Api4\OptionValue::update()
      ->addWhere('option_group_id:name', '=', 'languages')
      ->addWhere('name', '=', 'es_PR')
      ->setValues(['is_active' => 1])
      ->execute();

    CRM_Core_I18n::singleton()->setLocale($preferred);
    global $civicrmLocale;
    $this->assertEquals($expectYes, ts('Yes'));
    $this->assertEquals($expectLocale['ts'], $civicrmLocale->ts);
    $this->assertEquals($expectLocale['moneyFormat'], $civicrmLocale->moneyFormat);
    $this->assertEquals($expectLocale['nominal'], $civicrmLocale->nominal);
    // Should getLocale() return nominal or ts?
    // $this->assertEquals($expectLocale['nominal'], CRM_Core_I18n::getLocale());

    $formattedAmount = Civi::format()->money(1234.56, 'USD');
    $this->assertEquals($expectAmount, $formattedAmount);
  }

  /**
   * Quirk in strtolower does not handle "I" as expected, compared to mb_strtolower.
   */
  public function testInsertTurkish(): void {
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS foo");
    CRM_Core_DAO::executeQuery("CREATE TABLE foo ( bar varchar(32) )");
    // Change locale - assert it actually changed.
    $this->assertEquals('tr_TR.utf8', setlocale(LC_ALL, 'tr_TR.utf8'));
    $dao = new CRM_Core_DAO();
    // When query() uses strtolower this returns NULL instead
    $this->assertEquals(1, $dao->query("INSERT INTO foo VALUES ('Turkish Delight')"));
    setlocale(LC_ALL, 'en_US.utf8');
    CRM_Core_DAO::executeQuery("DROP TABLE foo");
  }

}
