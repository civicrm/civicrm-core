<?php

namespace E2E\Core;

/**
 * Language prefixes and path aliases in Drupal 8+ URLs.
 *
 * The multilingual tests need Drupal languages with path prefixes, negotiated by URL for the
 * interface language, and matching CiviCRM languages; they are skipped otherwise.
 *
 * @package E2E\Core
 * @group e2e
 */
class Drupal8LanguageUrlTest extends \CiviEndToEndTestCase {

  /**
   * @var \Drupal\path_alias\PathAliasInterface|null
   */
  private $alias;

  /**
   * @var \Civi\Core\Locale|null
   */
  private $originalLocale;

  protected function setUp(): void {
    parent::setUp();
    if (CIVICRM_UF !== 'Drupal8') {
      $this->markTestSkipped('Drupal 8+ only');
    }
    $this->originalLocale = clone \Civi\Core\Locale::detect();
    $this->originalLocale->uf = \CRM_Utils_System::getUFLocale() ?? $this->originalLocale->uf;
  }

  protected function tearDown(): void {
    if ($this->alias) {
      $this->alias->delete();
    }
    if ($this->originalLocale) {
      \CRM_Core_I18n::singleton()->setLocale($this->originalLocale);
    }
    parent::tearDown();
  }

  /**
   * A relative URL is the path and query of the absolute URL.
   */
  public function testRelativeUrlMatchesAbsoluteUrl(): void {
    $absolute = \CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=1', TRUE, NULL, FALSE);
    $relative = \CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=1', FALSE, NULL, FALSE);
    $parts = parse_url($absolute);
    if (\Drupal::request()->getBasePath() !== rtrim(parse_url(CIVICRM_UF_BASEURL, PHP_URL_PATH) ?? '', '/')) {
      $this->markTestSkipped('The request base path differs from CIVICRM_UF_BASEURL');
    }
    $this->assertEquals($parts['path'] . '?' . $parts['query'], $relative);
  }

  /**
   * CiviCRM URLs are not rewritten to Drupal path aliases.
   */
  public function testPathAliasIsNotApplied(): void {
    if (!\Drupal::moduleHandler()->moduleExists('path_alias')) {
      $this->markTestSkipped('Needs the path_alias module');
    }
    $this->alias = \Drupal\path_alias\Entity\PathAlias::create([
      'path' => '/civicrm/e2e-alias-source',
      'alias' => '/e2e-alias-target',
      'langcode' => 'und',
    ]);
    $this->alias->save();
    \Drupal::service('path_alias.manager')->cacheClear();

    $this->assertStringEndsWith('/civicrm/e2e-alias-source?reset=1', \CRM_Utils_System::url('civicrm/e2e-alias-source', 'reset=1'));
    $this->assertStringEndsWith('/civicrm/e2e-alias-source?reset=1', \CRM_Utils_System::url('civicrm/e2e-alias-source', 'reset=1', TRUE));
  }

  /**
   * Relative URLs, absolute URLs and Civi::url() all follow setLocale().
   */
  public function testUrlsFollowSetLocale(): void {
    $i18n = \CRM_Core_I18n::singleton();
    foreach ($this->getPrefixedLocales() as $locale => $prefix) {
      $i18n->setLocale($locale);
      $this->assertStringContainsString("/$prefix/civicrm/a?x=1", \CRM_Utils_System::url('civicrm/a', 'x=1'), $locale);
      $this->assertStringContainsString("/$prefix/civicrm/a?x=1", \CRM_Utils_System::url('civicrm/a', 'x=1', TRUE), $locale);
      $this->assertStringContainsString("/$prefix/civicrm/a?x=1", (string) \Civi::url('frontend://civicrm/a?x=1', 'a'), $locale);
    }
  }

  /**
   * swapLocale() puts back the Drupal language that was active, even if it differs from the
   * CiviCRM locale.
   */
  public function testSwapLocaleRestoresDrupalLanguage(): void {
    $prefixedLocales = $this->getPrefixedLocales();
    $languageManager = \Drupal::languageManager();
    $pageLanguage = $languageManager->getLanguage(array_key_first($this->getDrupalPrefixes()));
    $languageManager->setConfigOverrideLanguage($pageLanguage);
    \CRM_Utils_System::flushBaseURLCache();
    $pageUrl = \CRM_Utils_System::url('civicrm/a', 'x=1', TRUE);

    $otherLocale = array_key_last($prefixedLocales);
    $swap = \CRM_Utils_AutoClean::swapLocale($otherLocale);
    $this->assertStringContainsString('/' . $prefixedLocales[$otherLocale] . '/civicrm/a', \CRM_Utils_System::url('civicrm/a', 'x=1', TRUE));
    unset($swap);

    $this->assertEquals($pageLanguage->getId(), $languageManager->getConfigOverrideLanguage()->getId());
    $this->assertEquals($pageUrl, \CRM_Utils_System::url('civicrm/a', 'x=1', TRUE));
  }

  /**
   * localizeUrl() gives a stored CiviCRM URL the current language's prefix.
   */
  public function testLocalizeUrl(): void {
    $i18n = \CRM_Core_I18n::singleton();
    $urls = [];
    foreach ($this->getPrefixedLocales() as $locale => $prefix) {
      $i18n->setLocale($locale);
      $urls[$locale] = [
        \CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=1'),
        \CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=1', TRUE),
      ];
    }
    foreach ($urls as $locale => $expected) {
      $i18n->setLocale($locale);
      foreach ($urls as $storedUrls) {
        $this->assertEquals($expected, array_map([\CRM_Utils_System::class, 'localizeUrl'], $storedUrls), $locale);
      }
    }
  }

  /**
   * @return string[]
   *   Path prefixes of Drupal languages, by langcode, if Drupal negotiates the interface language
   *   by URL path prefix.
   */
  private function getDrupalPrefixes(): array {
    if (!\Drupal::languageManager()->isMultilingual()) {
      return [];
    }
    $negotiation = \Drupal::config('language.negotiation')->get('url');
    $enabled = \Drupal::config('language.types')->get('negotiation.language_interface.enabled') ?: [];
    if (!isset($enabled['language-url']) || ($negotiation['source'] ?? NULL) !== 'path_prefix') {
      return [];
    }
    return array_filter(array_intersect_key($negotiation['prefixes'] ?? [], \Drupal::languageManager()->getLanguages()));
  }

  /**
   * Get CiviCRM locales whose Drupal language has a path prefix. Skips the test unless there are
   * at least two.
   *
   * @return string[]
   *   Path prefixes, by CiviCRM locale.
   */
  private function getPrefixedLocales(): array {
    $locales = [];
    $available = \CRM_Core_I18n::languages(FALSE);
    foreach ($this->getDrupalPrefixes() as $langcode => $prefix) {
      $locale = \CRM_Core_I18n_PseudoConstant::longForShort($langcode);
      if ($locale && isset($available[$locale])) {
        $locales[$locale] = $prefix;
      }
    }
    if (count($locales) < 2) {
      $this->markTestSkipped('Needs two Drupal languages with path prefixes, and matching CiviCRM languages');
    }
    return $locales;
  }

}
