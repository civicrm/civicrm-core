<?php

namespace Civi\Test;

/**
 * Define helpers for testing multiple locales.
 *
 * Ex: Multilingual test with try/finally
 *   try {
 *     $this->enableMultilingual(['en_US' => 'fr_CA']);
 *     $this->assert(...);
 *   } finally {
 *     $this->disbleMultilingual();
 *   }
 *
 * Ex: Multilingual with auto-clean
 *   $cleanup = $this->useMultilingual(['en_US' => 'fr_CA']);
 */
trait LocaleTestTrait {

  /**
   * @var string
   */
  private static $defaultSystemLocale = 'en_US';

  /**
   * Temporarily use multilingual.
   *
   * @param array $addLocales
   *   A list of new locales to setup.
   *   A locale is initialized by copying from an existing locale.
   *
   *   Ex: Copy from en_US to fr_CA
   *       ['en_US' => 'fr_CA']
   *   Ex: Copy from en_US to fr_CA and de_DE
   *       ['en_US' => ['fr_CA', 'de_DE]]
   * @return \CRM_Utils_AutoClean
   *   A reference to the temporary configuration. Once removed, the system will revert to single language.
   */
  public function useMultilingual(array $addLocales): \CRM_Utils_AutoClean {
    $this->enableMultilingual($addLocales);
    return \CRM_Utils_AutoClean::with([$this, 'disableMultilingual']);
  }

  /**
   * Enable multilingual.
   *
   * @param array|null $addLocales
   *   A list of new locales to setup.
   *   A locale is initialized by copying from an existing locale.
   *
   *   Ex: Copy from en_US to fr_CA
   *       ['en_US' => 'fr_CA']
   *   Ex: Copy from en_US to fr_CA and de_DE
   *       ['en_US' => ['fr_CA', 'de_DE]]
   */
  public function enableMultilingual(?array $addLocales = NULL): void {
    $this->callAPISuccess('Setting', 'create', [
      'lcMessages' => static::$defaultSystemLocale,
      'languageLimit' => [
        static::$defaultSystemLocale => 1,
      ],
    ]);

    \CRM_Core_I18n_Schema::makeMultilingual(static::$defaultSystemLocale);

    global $dbLocale;
    $dbLocale = '_' . static::$defaultSystemLocale;

    if ($addLocales !== NULL) {
      foreach ($addLocales as $fromLocale => $toLocales) {
        foreach ((array) $toLocales as $toLocale) {
          \CRM_Core_I18n_Schema::addLocale($toLocale, $fromLocale);
        }
        \Civi::settings()->set('languageLimit', \Civi::settings()->get('languageLimit') + [$toLocale => '1']);
      }
    }
  }

  public function disableMultilingual(): void {
    \CRM_Core_I18n::singleton()->setLocale(static::$defaultSystemLocale);
    \CRM_Core_I18n_Schema::makeSinglelingual(static::$defaultSystemLocale);
    \Civi::settings()->revert('languageLimit');
    \Civi::$statics['CRM_Core_I18n']['singleton'] = [];
  }

}
