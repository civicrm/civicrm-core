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

namespace Civi\Core;

use Civi;

/**
 * Define a locale.
 *
 * ## FULL AND PARTIAL LOCALES
 *
 * Compare:
 *
 *   // FULL LOCALE - All localization services support this locale.
 *   $quebecois = new Locale([
 *     'nominal' => 'fr_CA',
 *     'ts' => 'fr_CA',
 *     'db' => 'fr_CA',
 *     'moneyFormat' => 'fr_CA',
 *     'uf' => 'fr_CA',
 *   ]);
 *   $quebecois->apply();
 *
 *   // PARTIAL LOCALE - Some localization services are not available, but the locale is still used.
 *   $chicano = new Locale([
 *     'nominal' => 'es_US',
 *     'ts' => 'es_MX',
 *     'db' => NULL,
 *     'moneyFormat' => 'en_US',
 *     'uf' => 'es_US',
 *   ]);
 *   $chicano->apply();
 *
 * The existence of partial-locales is (perhaps) unfortunate but (at large scale) inevitable.
 * The software comes with a list of 200 communication-locales (OptionValues), and admins may
 * register more. There are only ~50 locales supported by `ts()` and 1-3 locales in the DB
 * (for a typical business-entity). If you use any of these other locales, then some services
 * must raise errors (or fallback to an alternate locale).
 *
 * ## NEGOTIATION
 *
 * The process of _negotiation_ takes a requested locale and determines how to configure
 * the localization services. For example, suppose a caller requests `es_US` (which isn't fully supported):
 *
 * - You could activate an adjacent locale which has full support (like `es_MX` or `en_US`).
 * - You could activate `es_US` and mix elements from different locales (eg `ts()` uses `es_MX`;
 *   workflow-messages use `es_US` or `es_MX`, as available).
 *
 * To negotiate an effective locale and apply it:
 *
 *   Locale::negotiate('es_US')->apply();
 *
 * At time of writing, the negotiation behavior is based on system-setting `partial_locales`
 * (which enables or disables support for partial locales). It may be useful to make this hookable.
 *
 * It is also possible to perform a re-negotiation. For example, suppose the user requests
 * locale `es_US`, and we're sending an automated email -- but we only have emails written for
 * three languages.
 *
 *   $msgs = ['es_MX' => 'Buenos dias', 'en_US' => 'Good day', 'fr_CA' => 'Bon jour'];
 *   $locale = Locale::negotiate('es_US')
 *     ->renegotiate(array_keys($msgs))
 *     ->apply();
 *   $msg = $msgs[$locale->nominal];
 *
 * In a world where you only allow fully supported locales, there would be no need for
 * re-negotiation. However, if you have partially supported locales (with different mix of
 * resources in each), then you need some defined behavior for unsupported edges
 * (either raising an error or using a fallback).
 */
class Locale {

  /**
   * The official/visible name of the current locale.
   *
   * This can be any active locale that appears in communication preferences
   * (eg `civicrm_contact.preferred_language`; ie option-group `languages`).
   *
   * @var string
   * @readonly
   */
  public $nominal = '';

  /**
   * Locale used for `ts()` and `l10n/**.mo` lookups.
   *
   * @var string
   * @readonly
   * @internal
   */
  public $ts;

  /**
   * Locale used for multilingual MySQL schema.
   *
   * Only defined on systems where multilingual is configured. Otherwise, null.
   *
   * @var string|null
   * @readonly
   * @internal
   */
  public $db;

  /**
   * Locale used for `Civi::format()` operations (dates and currencies).
   *
   * @var string
   * @readonly
   * @internal
   */
  public $moneyFormat;

  /**
   * Locale used by CMS.
   *
   * @var string
   * @readonly
   * @internal
   */
  public $uf;

  /**
   * Lookup details about the desired locale.
   *
   * @param string|null $locale
   *   The name of a locale that one wishes to use.
   *   The name may be NULL to use the current/active locale.
   * @return \Civi\Core\Locale
   */
  public static function resolve(?string $locale): Locale {
    return $locale === NULL ? static::detect() : static::negotiate($locale);
  }

  /**
   * Determine the current locale based on global properties.
   *
   * @return \Civi\Core\Locale
   */
  public static function detect(): Locale {
    // If anyone has ever called `setLocale()` (*which they should, ideally*), then we already have an object...
    global $civicrmLocale;
    if ($civicrmLocale) {
      return $civicrmLocale;
    }

    // If they haven't (*which wasn't required before*)... then we'll figure it out...
    global $tsLocale, $dbLocale;
    $locale = new Locale();
    $locale->nominal = $tsLocale;
    $locale->ts = $tsLocale;
    $locale->db = $dbLocale ? ltrim($dbLocale, '_') : NULL;
    $locale->moneyFormat = Civi::settings()->get('format_locale') ?? $tsLocale;
    $locale->uf = \CRM_Utils_System::getUFLocale();
    return $locale;
  }

  /**
   * Negotiate an effective locale, based on the user's preference.
   *
   * @param string $preferred
   *   The locale that is preferred by the user.
   *   Ex: `en_US`, `es_ES`, `fr_CA`
   * @return \Civi\Core\Locale
   *   The effective locale specification.
   * @throws \CRM_Core_Exception
   */
  public static function negotiate(string $preferred): Locale {
    // Create a locale for the requested language
    if (!preg_match(';^[a-z][a-z]_[A-Z][A-Z]$;', $preferred)) {
      throw new \CRM_Core_Exception("Cannot instantiate malformed locale: $preferred");
    }

    $systemDefault = \Civi::settings()->get('lcMessages');

    if (\Civi::settings()->get('partial_locales')) {
      \CRM_Core_OptionValue::getValues(['name' => 'languages'], $optionValues, 'weight', TRUE);
      $validNominalLocales = array_column($optionValues, 'label', 'name');
      $validTsLocales = \CRM_Core_I18n::languages(FALSE); /* Active OV _and_ available MO */
    }
    else {
      $validNominalLocales = $validTsLocales = $validFormatLocales
        = \CRM_Core_I18n::languages(FALSE);
      // Or stricter? array_fill_keys(\CRM_Core_I18n::uiLanguages(TRUE), TRUE);
    }

    $locale = new static();
    $locale->nominal = static::pickFirstLocale(array_keys($validNominalLocales), static::getAllFallbacks($preferred)) ?: $systemDefault;
    $fallbacks = static::getAllFallbacks($locale->nominal);

    $locale->ts = static::pickFirstLocale(array_keys($validTsLocales), $fallbacks) ?: $systemDefault;
    $locale->moneyFormat = $locale->nominal;
    if (!\CRM_Core_I18n::isMultiLingual()) {
      $locale->db = NULL;
    }
    else {
      $validDbLocales = \Civi::settings()->get('languageLimit');
      $locale->db = static::pickFirstLocale(array_keys($validDbLocales), $fallbacks) ?: $systemDefault;
    }

    // Determine locale for UF APIs: This next bit is a little bit wrong.
    // We should have something like `$validUfLanguages` and pick the closest match.
    // Or perhaps each `CRM_Utils_System_{$UF}` should have a `negotiate()` helper.
    // But it's a academic... D7/D8/BD are the only UF's which implement `setUFLocale`/`getUFLocale`,
    // and they drop the country-code - which basically addresses the goal (falling back to a more generic locale).
    $locale->uf = $locale->ts;

    return $locale;
  }

  public static function null(): Locale {
    return new Locale([
      'nominal' => NULL,
      'ts' => NULL,
      'moneyFormat' => NULL,
      'db' => \CRM_Core_I18n::isMultiLingual() ? \Civi::settings()->get('lcMessages') : NULL  ,
    ]);
  }

  public function __construct(array $params = []) {
    foreach ($params as $key => $value) {
      $this->{$key} = $value;
    }
  }

  /**
   * Activate this locale, updating any active PHP services that rely on it.
   *
   * @return static
   */
  public function apply(): Locale {
    \CRM_Core_I18n::singleton()->setLocale($this);
    return $this;
  }

  /**
   * Re-negotiate the effective locale.
   *
   * This is useful if you are beginning some business-transaction where the business
   * record has localized resources. For example, a CiviContribute receipt might have
   * different templates for a handful of locales -- in which case, you should choose
   * among those locales.
   *
   * The current implementation prefers to match the nominal language.
   *
   * @param string[] $availableLocales
   *   List of locales that you know how to serve.
   *   Ex: ['en_US', 'fr_CA', 'es_MX']
   * @return \Civi\Core\Locale
   *   The chosen locale.
   *   If no good locales could be chosen, then NULL.
   */
  public function renegotiate(array $availableLocales): ?Locale {
    $picked = static::pickFirstLocale($availableLocales, static::getAllFallbacks($this->nominal));
    return $picked ? static::negotiate($picked) : NULL;
  }

  /**
   * (Internal helper) Given a list of available locales and a general preference, pick the best match.
   *
   * @param array $availableLocales
   *   Ex: ['en_US', 'es_MX', 'es_ES', 'fr_CA']
   * @param array $preferredLocales
   *   Ex: ['es_PR', 'es_419', 'es_MX', 'es_ES']
   * @return string|null
   *   The available locale with the highest preference.
   *   Ex: 'es_MX'
   */
  private static function pickFirstLocale(array $availableLocales, array $preferredLocales): ?string {
    foreach ($preferredLocales as $locale) {
      if (in_array($locale, $availableLocales, TRUE)) {
        return $locale;
      }
    }
    return NULL;
  }

  /**
   * @param string|null $preferred
   *   ex: 'es_PR'
   * @return array
   *   Ex: ['es_PR', 'es_419', 'es_MX', 'es_ES', 'en_US', 'en_GB]
   */
  private static function getAllFallbacks(?string $preferred): array {
    return array_merge(
    // We'd like to stay in the active locale (or something closely related)
      ($preferred ? static::getLocalePrecedence($preferred) : []),
      // If we can't, then try the system locale (or something closely related)
      static::getLocalePrecedence(\Civi::settings()->get('lcMessages'))
    );
  }

  /**
   * (Internal helper) Given a $preferred locale, determine a prioritized list of alternate locales.
   *
   * @param string $preferred
   *   Ex: 'es_PR'
   * @return string[]
   *   Ex: ['es_PR', 'es_419', 'es_MX', 'es_ES']
   */
  private static function getLocalePrecedence(string $preferred): array {
    [$lang] = explode('_', $preferred);

    // (Eileen) In this situation we have multiple language options but no exact match.
    // This might be, for example, a case where we have, for example, a US English and
    // a British English, but no Kiwi English. In that case the best is arguable
    // but I think we all agree that we want to avoid Aussie English here.
    $defaultLanguages = [
      'de' => ['de_DE'],
      'en' => ['en_US', 'en_GB', 'en_AU', 'en_NZ'],
      'fr' => ['fr_FR', 'fr_CA'],
      'es' => ['es_419', 'es_MX', 'es_ES'],
      'nl' => ['nl_NL'],
      'pt' => ['pt_PT', 'pt_BR'],
      'zh' => ['zh_TW'],
    ];
    $fallbacks = $defaultLanguages[$lang] ?? [];
    array_unshift($fallbacks, $preferred);
    return $fallbacks;
  }

}
