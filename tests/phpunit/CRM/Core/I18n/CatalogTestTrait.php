<?php

/**
 * Translates through a catalog held in memory, for tests that need translations without l10n files.
 *
 * Call resetCatalog() from tearDown().
 */
trait CRM_Core_I18n_CatalogTestTrait {

  /**
   * @var string|null
   */
  private $localeBeforeCatalog;

  /**
   * Switches to German and makes ts() translate through the given catalog.
   *
   * ts() keeps its translator until the locale changes, so the catalog needs a locale of its own.
   *
   * @param array<string, string> $translations
   *   msgid => msgstr; a plural entry joins its forms with "\0" on both sides.
   * @param array<string, array<string, string>> $domains
   *   Catalogs of extension domains, keyed by extension key.
   */
  protected function useCatalog(array $translations, array $domains = []): void {
    global $tsLocale;
    $reader = self::buildTranslator($translations);
    $this->localeBeforeCatalog = $tsLocale;
    $readers = ['civicrm' => $reader];
    foreach ($domains as $key => $domainTranslations) {
      $readers[$key] = self::buildTranslator($domainTranslations);
    }
    $translator = new CRM_Core_I18n('de_DE');
    (function () use ($reader, $readers) {
      $this->_phpgettext = $reader;
      $this->_extensioncache = $readers + $this->_extensioncache;
    })->call($translator);
    Civi::$statics['CRM_Core_I18n']['singleton']['de_DE'] = $translator;
    $tsLocale = 'de_DE';
  }

  protected function resetCatalog(): void {
    global $tsLocale;
    if ($this->localeBeforeCatalog !== NULL) {
      $tsLocale = $this->localeBeforeCatalog;
      $this->localeBeforeCatalog = NULL;
      unset(Civi::$statics['CRM_Core_I18n']['singleton']['de_DE']);
    }
  }

  /**
   * @param array<string, string> $translations
   */
  private static function buildTranslator(array $translations): \PhpMyAdmin\MoTranslator\Translator {
    $translator = new \PhpMyAdmin\MoTranslator\Translator(NULL);
    $translator->setTranslations([
      '' => "Content-Type: text/plain; charset=UTF-8\nPlural-Forms: nplurals=2; plural=(n != 1);\n",
    ] + $translations);
    return $translator;
  }

}
