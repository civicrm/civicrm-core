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
    require_once 'PHPgettext/streams.php';
    require_once 'PHPgettext/gettext.php';
    global $tsLocale;
    $reader = new gettext_reader(new StringReader(self::compileCatalog($translations)));
    $this->localeBeforeCatalog = $tsLocale;
    $readers = ['civicrm' => $reader];
    foreach ($domains as $key => $domainTranslations) {
      $readers[$key] = new gettext_reader(new StringReader(self::compileCatalog($domainTranslations)));
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
   * @return string
   *   The catalog in gettext's .mo format, with German plural rules.
   */
  private static function compileCatalog(array $translations): string {
    $translations = ['' => "Content-Type: text/plain; charset=UTF-8\nPlural-Forms: nplurals=2; plural=(n != 1);\n"] + $translations;
    ksort($translations, SORT_STRING);
    $count = count($translations);
    $offset = 28 + 16 * $count;
    $tables = ['', ''];
    $data = '';
    foreach ([array_keys($translations), array_values($translations)] as $side => $texts) {
      foreach ($texts as $text) {
        $tables[$side] .= pack('VV', strlen($text), $offset + strlen($data));
        $data .= $text . "\0";
      }
    }
    return pack('VVVVVVV', 0x950412DE, 0, $count, 28, 28 + 8 * $count, 0, 0) . $tables[0] . $tables[1] . $data;
  }

}
