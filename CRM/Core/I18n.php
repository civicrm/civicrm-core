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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_I18n {

  /**
   * Constants for communication preferences.
   *
   * @var string
   */
  const NONE = 'none', AUTO = 'auto';

  /**
   * @var string
   * @deprecated
   *   This variable has 1-2 references in contrib, which -probably- aren't functionally
   *   necessary. (Extensions don't load in pre-installation environments...)
   *   But we'll keep the property stub just to prevent crashes.
   *
   *   Replaced by $GLOBALS['CIVICRM_SQL_ESCAPER'].
   */
  public static $SQL_ESCAPER = NULL;

  /**
   * Escape a string if a mode is specified, otherwise return string unmodified.
   *
   * @param string $text
   * @param string $mode
   * @return string
   */
  protected static function escape($text, $mode) {
    switch ($mode) {
      case 'sql':
        return CRM_Core_DAO::escapeString($text);

      case 'js':
        return substr(json_encode($text, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 1, -1);

      case 'htmlattribute':
        return htmlspecialchars($text, ENT_QUOTES);
    }
    return $text;
  }

  /**
   * A PHP-gettext instance for string translation;
   * should stay null if the strings are not to be translated (en_US).
   * @var object
   */
  private $_phpgettext = NULL;

  /**
   * Whether we are using native gettext or not.
   * @var bool
   */
  private $_nativegettext = FALSE;

  /**
   * Gettext cache for extension domains/streamers, depending on if native or phpgettext.
   * - native gettext: we cache the value for textdomain()
   * - phpgettext: we cache the file streamer.
   * @var array
   */
  private $_extensioncache = [];

  /**
   * @var string
   */
  private $locale;

  /**
   * A locale-based constructor that shouldn't be called from outside of this class (use singleton() instead).
   *
   * @param string $locale
   *   the base of this certain object's existence.
   *
   * @return \CRM_Core_I18n
   */
  public function __construct($locale) {
    $this->locale = $locale;
    if ($locale != '' and $locale != 'en_US') {
      if (defined('CIVICRM_GETTEXT_NATIVE') && CIVICRM_GETTEXT_NATIVE && function_exists('gettext')) {
        // Note: the file hierarchy for .po must be, for example: l10n/fr_FR/LC_MESSAGES/civicrm.mo

        $this->_nativegettext = TRUE;
        $this->setNativeGettextLocale($locale);
        return;
      }

      // Otherwise, use PHP-gettext
      $this->setPhpGettextLocale($locale);
    }
  }

  /**
   * Returns whether gettext is running natively or using PHP-Gettext.
   *
   * @return bool
   *   True if gettext is native
   */
  public function isNative() {
    return $this->_nativegettext;
  }

  /**
   * Set native locale for getText.
   *
   * @param string $locale
   */
  protected function setNativeGettextLocale($locale) {
    $locale .= '.utf8';
    putenv("LANG=$locale");

    // CRM-11833 Avoid LC_ALL because of LC_NUMERIC and potential DB error.
    setlocale(LC_TIME, $locale);
    setlocale(LC_MESSAGES, $locale);
    setlocale(LC_CTYPE, $locale);

    bindtextdomain('civicrm', CRM_Core_I18n::getResourceDir());
    bind_textdomain_codeset('civicrm', 'UTF-8');
    textdomain('civicrm');

    $this->_phpgettext = new CRM_Core_I18n_NativeGettext();
    $this->_extensioncache['civicrm'] = 'civicrm';
  }

  /**
   * Set getText locale.
   *
   * Since CiviCRM 4.5, expected dir structure is civicrm/l10n/xx_XX/LC_MESSAGES/civicrm.mo
   * because that is what native gettext expects. Fallback support for the pre-4.5 structure
   * was removed in CiviCRM 5.51.
   *
   * CiviCRM 5.23 added support for the CIVICRM_L10N_BASEDIR constant (and [civicrm.l10n])
   * so that mo files can be stored elsewhere (such as in a web-writable directory, to
   * support extensions sur as l10nupdate.
   *
   * @param string $locale
   */
  protected function setPhpGettextLocale($locale) {
    require_once 'PHPgettext/streams.php';
    require_once 'PHPgettext/gettext.php';

    $mo_file = CRM_Core_I18n::getResourceDir() . $locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'civicrm.mo';
    $streamer = new FileReader($mo_file);
    $this->_phpgettext = new gettext_reader($streamer);
    $this->_extensioncache['civicrm'] = $this->_phpgettext;
  }

  /**
   * Return languages available in this instance of CiviCRM.
   *
   * @param bool $justEnabled
   *   whether to return all languages or just the enabled ones.
   *
   * @return array
   *   Array of code/language name mappings
   */
  public static function languages($justEnabled = FALSE) {
    static $all = NULL;
    static $enabled = NULL;

    if (!$all) {
      $optionValues = [];
      // Use `getValues`, not `buildOptions` to bypass hook_civicrm_fieldOptions.  See dev/core#1132.
      CRM_Core_OptionValue::getValues(['name' => 'languages'], $optionValues, 'weight', TRUE);
      $all = array_column($optionValues, 'label', 'name');

      // FIXME: How is this not duplicative of the lines above?
      // get labels
      $rows = [];
      $labels = [];
      CRM_Core_OptionValue::getValues(['name' => 'languages'], $rows);
      foreach ($rows as $id => $row) {
        $labels[$row['name']] = $row['label'];
      }

      // check which ones are available; add them to $all if not there already
      $codes = [];
      if (is_dir(CRM_Core_I18n::getResourceDir()) && $dir = opendir(CRM_Core_I18n::getResourceDir())) {
        while ($filename = readdir($dir)) {
          if (preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $filename)) {
            $codes[] = $filename;
            if (!isset($all[$filename])) {
              $all[$filename] = $labels[$filename] ?? "($filename)";
            }
          }
        }
        closedir($dir);
      }

      // drop the unavailable languages (except en_US)
      foreach (array_keys($all) as $code) {
        if ($code == 'en_US') {
          continue;
        }
        if (!in_array($code, $codes)) {
          unset($all[$code]);
        }
      }

      asort($all);
    }

    if ($enabled === NULL) {
      $languageLimit = Civi::settings()->get('languageLimit');
      $enabled = [];
      if ($languageLimit) {
        foreach ($all as $code => $name) {
          if (array_key_exists($code, $languageLimit)) {
            $enabled[$code] = $name;
          }
        }
      }
    }

    return $justEnabled ? $enabled : $all;
  }

  /**
   * Get the options available for format locale.
   *
   * Note the pseudoconstant can't be used as the key is the name not the value.
   *
   * @return array
   */
  public static function getFormatLocales(): array {
    $values = CRM_Core_OptionValue::getValues(['name' => 'languages'], $optionValues, 'label', TRUE);
    $return = [];
    $return[NULL] = ts('Inherit from language');
    foreach ($values as $value) {
      $return[$value['name']] = $value['label'];
    }
    // Sorry not sorry.
    // Hacking in for now since the is probably the most important use-case for
    // money formatting in an English speaking non-US locale based on any reasonable
    // metric.
    $return['en_NZ'] = ts('English (New Zealand)');
    return $return;
  }

  /**
   * Return the available UI languages
   * @return array|string
   *   array(string languageCode => string languageName) if !$justCodes
   */
  public static function uiLanguages($justCodes = FALSE) {
    // In multilang we only allow the languages that are configured in db
    // Otherwise, the languages configured in uiLanguages
    $settings = Civi::settings();
    if (CRM_Core_I18n::isMultiLingual()) {
      $codes = array_keys((array) $settings->get('languageLimit'));
    }
    else {
      $codes = $settings->get('uiLanguages');
      if (!$codes) {
        $codes = [$settings->get('lcMessages')];
      }
    }
    return $justCodes ? $codes
        : CRM_Utils_Array::subset(CRM_Core_I18n::languages(), $codes);
  }

  /**
   * Replace arguments in a string with their values. Arguments are represented by % followed by their number.
   *
   * @param string $str
   *   source string.
   *
   * @return string
   *   modified string
   */
  public function strarg($str) {
    $tr = [];
    $p = 0;
    for ($i = 1; $i < func_num_args(); $i++) {
      $arg = func_get_arg($i);
      if (is_array($arg)) {
        foreach ($arg as $aarg) {
          $tr['%' . ++$p] = $aarg;
        }
      }
      else {
        $tr['%' . ++$p] = $arg;
      }
    }
    return strtr($str, $tr);
  }

  /**
   * Get the directory for l10n resources.
   *
   * @return string
   */
  public static function getResourceDir() {
    return CRM_Utils_File::addTrailingSlash(\Civi::paths()->getPath('[civicrm.l10n]/.'));
  }

  /**
   * Smarty block function, provides gettext support for smarty.
   *
   * The block content is the text that should be translated.
   *
   * Any parameter that is sent to the function will be represented as %n in the translation text,
   * where n is 1 for the first parameter. The following parameters are reserved:
   *   - escape - sets escape mode:
   *       - 'html' for HTML escaping, this is the default.
   *       - 'js' for javascript escaping.
   *       - 'no'/'off'/0 - turns off escaping
   *   - plural - The plural version of the text (2nd parameter of ngettext())
   *   - count - The item count for plural mode (3rd parameter of ngettext())
   *   - context - gettext context of that string (for homonym handling)
   *
   * @param string $text
   *   the original string.
   * @param array $params
   *   The params of the translation (if any).
   *   - domain: string|array a list of translation domains to search (in order)
   *   - context: string
   *   - skip_translation: flag (do only escape/replacement, skip the actual translation)
   *
   * @return string
   *   the translated string
   */
  public function crm_translate($text, $params = []) {
    $escape = $params['escape'] ?? NULL;
    unset($params['escape']);

    // sometimes we need to {ts}-tag a string, but don’t want to
    // translate it in the template (like civicrm_navigation.tpl),
    // because we handle the translation in a different way (CRM-6998)
    // in such cases we return early, only doing SQL/JS escaping
    if (isset($params['skip']) and $params['skip']) {
      return self::escape($text, $escape);
    }

    $plural = $count = NULL;
    if (isset($params['plural'])) {
      $plural = $params['plural'];
      unset($params['plural']);
      if (isset($params['count'])) {
        $count = $params['count'];
      }
    }

    if (isset($params['context'])) {
      $context = $params['context'];
      unset($params['context']);
    }
    else {
      $context = NULL;
    }

    if (isset($params['domain'])) {
      $domain = $params['domain'];
      unset($params['domain']);
    }
    else {
      $domain = NULL;
    }

    $raw = !empty($params['raw']);
    unset($params['raw']);

    if (!isset($params['skip_translation'])) {
      if (!empty($domain)) {
        // It might be prettier to cast to an array, but this is high-traffic stuff.
        if (is_array($domain)) {
          foreach ($domain as $d) {
            $candidate = $this->crm_translate_raw($text, $d, $count, $plural, $context);
            if ($candidate != $text) {
              $text = $candidate;
              break;
            }
          }
        }
        else {
          $text = $this->crm_translate_raw($text, $domain, $count, $plural, $context);
        }
      }
      else {
        $text = $this->crm_translate_raw($text, NULL, $count, $plural, $context);
      }
    }

    // replace the numbered %1, %2, etc. params if present
    if (count($params) && !$raw) {
      $text = $this->strarg($text, $params);
    }

    return self::escape($text, $escape);
  }

  /**
   * Lookup the raw translation of a string (without any extra escaping or interpolation).
   *
   * @param string $text
   * @param string|null $domain
   * @param int|null $count
   * @param string $plural
   * @param string $context
   *
   * @return string
   */
  protected function crm_translate_raw($text, $domain, $count, $plural, $context) {
    // gettext domain for extensions
    $domain_changed = FALSE;
    if (!empty($domain) && $this->_phpgettext) {
      if ($this->setGettextDomain($domain)) {
        $domain_changed = TRUE;
      }
    }

    // do all wildcard translations first

    $stringTable = $this->getWordReplacements();

    $exactMatch = FALSE;
    if (isset($stringTable['enabled']['exactMatch'])) {
      foreach ($stringTable['enabled']['exactMatch'] as $search => $replace) {
        if ($search === $text) {
          $exactMatch = TRUE;
          $text = $replace;
          break;
        }
      }
    }

    if (
      !$exactMatch &&
      isset($stringTable['enabled']['wildcardMatch'])
    ) {
      $search = array_keys($stringTable['enabled']['wildcardMatch']);
      $replace = array_values($stringTable['enabled']['wildcardMatch']);
      $text = str_replace($search, $replace, $text);
    }

    // dont translate if we've done exactMatch already
    if (!$exactMatch) {
      // use plural if required parameters are set
      if (isset($count) && isset($plural)) {

        if ($this->_phpgettext) {
          $text = $this->_phpgettext->ngettext($text, $plural, (int) $count);
        }
        else {
          // if the locale's not set, we do ngettext work by hand
          // if $count == 1 then $text = $text, else $text = $plural
          if ($count != 1) {
            $text = $plural;
          }
        }

        // expand %count in translated string to $count
        $text = strtr($text, ['%count' => $count]);

        // if not plural, but the locale's set, translate
      }
      elseif ($this->_phpgettext) {
        if ($context) {
          $text = $this->_phpgettext->pgettext($context, $text);
        }
        else {
          $text = $this->_phpgettext->translate($text);
        }
      }
    }

    if ($domain_changed) {
      $this->setGettextDomain('civicrm');
    }

    return $text;
  }

  /**
   * Translate a string to the current locale.
   *
   * @param string $string
   *   this string should be translated.
   *
   * @return string
   *   the translated string
   */
  public function translate($string) {
    return ($this->_phpgettext) ? $this->_phpgettext->translate($string) : $string;
  }

  /**
   * Localize (destructively) array values.
   *
   * @param array $array
   *   the array for localization (in place).
   * @param array $params
   *   an array of additional parameters.
   */
  public function localizeArray(
    &$array,
    $params = []
  ) {
    $tsLocale = CRM_Core_I18n::getLocale();

    if ($tsLocale == 'en_US') {
      return;
    }

    foreach ($array as & $value) {
      if ($value) {
        $value = _ts($value, $params);
      }
    }
  }

  /**
   * Localize (destructively) array elements with keys of 'title'.
   *
   * @param array $array
   *   the array for localization (in place).
   */
  public function localizeTitles(&$array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $this->localizeTitles($value);
        $array[$key] = $value;
      }
      else {
        $key = (string) $key;
        if ($key == 'title' || $key == 'desc') {
          $array[$key] = _ts($value, ['context' => 'menu']);
        }
      }
    }
  }

  /**
   * Binds a gettext domain, wrapper over bindtextdomain().
   *
   * @param string $key
   *   Key of the extension (can be 'civicrm', or 'org.example.foo').
   *
   * @return Bool
   *   True if the domain was changed for an extension.
   */
  public function setGettextDomain($key) {
    /* No domain changes for en_US */
    if (!$this->_phpgettext) {
      return FALSE;
    }

    // It's only necessary to find/bind once
    if (!isset($this->_extensioncache[$key])) {
      try {
        $path = CRM_Core_I18n::getResourceDir();
        $mapper = CRM_Extension_System::singleton()->getMapper();
        $info = $mapper->keyToInfo($key);
        $domain = $info->file;

        // Support extension .mo files outside the CiviCRM codebase (relates to dev/translation#52)
        if (!file_exists(CRM_Core_I18n::getResourceDir() . $this->locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . $domain . '.mo')) {
          // Extensions that are not on Transifed might have their .po/mo files in their git repo
          $path = $mapper->keyToBasePath($key) . DIRECTORY_SEPARATOR . 'l10n' . DIRECTORY_SEPARATOR;
        }

        if ($this->_nativegettext) {
          bindtextdomain($domain, $path);
          bind_textdomain_codeset($domain, 'UTF-8');
          $this->_extensioncache[$key] = $domain;
        }
        else {
          // phpgettext
          $mo_file = $path . $this->locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . $domain . '.mo';
          $streamer = new FileReader($mo_file);
          $this->_extensioncache[$key] = $streamer->length() ? new gettext_reader($streamer) : NULL;
        }
      }
      catch (CRM_Extension_Exception $e) {
        // Intentionally not translating this string to avoid possible infinite loops
        // Only developers should see this string, if they made a mistake in their ts() usage.
        CRM_Core_Session::setStatus('Unknown extension key in a translation string: ' . $key, '', 'error');
        $this->_extensioncache[$key] = FALSE;
      }
    }

    if (isset($this->_extensioncache[$key]) && $this->_extensioncache[$key]) {
      if ($this->_nativegettext) {
        textdomain($this->_extensioncache[$key]);
      }
      else {
        $this->_phpgettext = $this->_extensioncache[$key];
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Is the current CiviCRM domain in multilingual mode.
   *
   * @return bool
   *   True if CiviCRM is in multilingual mode.
   */
  public static function isMultilingual() {
    $domain = CRM_Core_BAO_Domain::getDomain();
    return (bool) $domain->locales;
  }

  /**
   * Returns languages if domain is in multilingual mode.
   *
   * @return array|bool
   */
  public static function getMultilingual() {
    $domain = CRM_Core_BAO_Domain::getDomain();
    return $domain->locales ? CRM_Core_DAO::unSerializeField($domain->locales, CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED) : FALSE;
  }

  /**
   * Is the language written "right-to-left"?
   *
   * @param string $language
   *   Language (for example 'en_US', or 'fr_CA').
   *
   * @return bool
   *   True if it is an RTL language.
   */
  public static function isLanguageRTL($language) {
    $rtl = CRM_Core_I18n_PseudoConstant::getRTLlanguages();
    $short = CRM_Core_I18n_PseudoConstant::shortForLong($language);

    return (in_array($short, $rtl));
  }

  /**
   * If you switch back/forth between locales/drivers, it may be necessary
   * to reset some options.
   */
  protected function reactivate() {
    if ($this->_nativegettext) {
      $this->setNativeGettextLocale($this->locale);
    }

  }

  /**
   * Change the processing language without changing the current user language
   *
   * @param string|\Civi\Core\Locale $locale
   *   Locale (for example 'en_US', or 'fr_CA').
   *   True if the domain was changed for an extension.
   */
  public function setLocale($locale) {
    global $civicrmLocale;
    if ($locale === NULL) {
      $civicrmLocale = \Civi\Core\Locale::null();
    }
    elseif (is_object($locale)) {
      $civicrmLocale = $locale;
    }
    else {
      $civicrmLocale = \Civi\Core\Locale::negotiate($locale);
    }

    // Change the language of the CMS as well, for URLs.
    CRM_Utils_System::setUFLocale($civicrmLocale->uf);

    // For sql queries, if running in DB multi-lingual mode.
    global $dbLocale;

    if ($dbLocale) {
      $dbLocale = '_' . $civicrmLocale->db;
    }

    // For self::getLocale()
    global $tsLocale;
    $tsLocale = $civicrmLocale->ts;

    CRM_Core_I18n::singleton()->reactivate();
  }

  public static function clearLocale(): void {
    unset($GLOBALS['tsLocale'], $GLOBALS['dbLocale'], $GLOBALS['civicrmLocale']);
  }

  /**
   * Static instance provider - return the instance for the current locale.
   *
   * @return CRM_Core_I18n
   */
  public static function &singleton() {
    if (!isset(Civi::$statics[__CLASS__]['singleton'])) {
      Civi::$statics[__CLASS__]['singleton'] = [];
    }
    $tsLocale = CRM_Core_I18n::getLocale();
    if (!isset(Civi::$statics[__CLASS__]['singleton'][$tsLocale])) {
      Civi::$statics[__CLASS__]['singleton'][$tsLocale] = new CRM_Core_I18n($tsLocale);
    }

    return Civi::$statics[__CLASS__]['singleton'][$tsLocale];
  }

  /**
   * Set the LC_TIME locale if it's not set already (for a given language choice).
   *
   * @return string
   *   the final LC_TIME that got set
   */
  public static function setLcTime() {
    static $locales = [];

    $tsLocale = CRM_Core_I18n::getLocale();
    if (!isset($locales[$tsLocale])) {
      // with the config being set to pl_PL: try pl_PL.UTF-8,
      // then pl_PL, if neither present fall back to C
      $locales[$tsLocale] = setlocale(LC_TIME, $tsLocale . '.UTF-8', $tsLocale, 'C');
    }

    return $locales[$tsLocale];
  }

  /**
   * Get the default language for contacts where no language is provided.
   *
   * Note that NULL is a valid option so be careful with checking for empty etc.
   *
   * NULL would mean 'we don't know & we don't want to hazard a guess'.
   *
   * @return string
   */
  public static  function getContactDefaultLanguage() {
    $language = Civi::settings()->get('contact_default_language');
    if ($language == 'undefined') {
      return NULL;
    }
    if (empty($language) || $language === '*default*') {
      $language = civicrm_api3('setting', 'getvalue', [
        'name' => 'lcMessages',
        'group' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
      ]);
    }
    elseif ($language == 'current_site_language') {
      return CRM_Core_I18n::getLocale();
    }

    return $language;
  }

  /**
   * Get the current locale
   *
   * @return string
   */
  public static function getLocale() {
    global $tsLocale;
    return $tsLocale ?: 'en_US';
  }

  /**
   * @return array
   *   Ex: $stringTable['enabled']['wildcardMatch']['foo'] = 'bar';
   */
  private function getWordReplacements() {
    if (defined('CIVI_SETUP') || isset(Civi\Test::$statics['testPreInstall'])) {
      return [];
    }

    // FIXME: Is there a constant we can reference instead of hardcoding en_US?
    $replacementsLocale = $this->locale ?: 'en_US';
    if ((!isset(Civi::$statics[__CLASS__]) || !array_key_exists($replacementsLocale, Civi::$statics[__CLASS__]))) {
      if (defined('CIVICRM_DSN') && !CRM_Core_Config::isUpgradeMode()) {
        Civi::$statics[__CLASS__][$replacementsLocale] = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings($replacementsLocale);
      }
      else {
        Civi::$statics[__CLASS__][$replacementsLocale] = [];
      }
    }
    return Civi::$statics[__CLASS__][$replacementsLocale];
  }

}
