<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Core_I18n {

  /**
   * Constants for communication preferences.
   *
   * @var int
   */
  const NONE = 'none', AUTO = 'auto';

  /**
   * @var callable|NULL
   *   A callback function which handles SQL string encoding.
   *   Set NULL to use the default, CRM_Core_DAO::escapeString().
   *   This is used by `ts(..., [escape=>sql])`.
   *
   * This option is not intended for general consumption. It is only intended
   * for certain pre-boot/pre-install contexts.
   *
   * You might ask, "Why on Earth does string-translation have an opinion on
   * SQL escaping?" Good question!
   */
  public static $SQL_ESCAPER = NULL;

  /**
   * Encode a string for use in SQL.
   *
   * @param string $text
   * @return string
   */
  protected static function escapeSql($text) {
    if (self::$SQL_ESCAPER == NULL) {
      return CRM_Core_DAO::escapeString($text);
    }
    else {
      return call_user_func(self::$SQL_ESCAPER, $text);
    }
  }

  /**
   * A PHP-gettext instance for string translation;
   * should stay null if the strings are not to be translated (en_US).
   */
  private $_phpgettext = NULL;

  /**
   * Whether we are using native gettext or not.
   */
  private $_nativegettext = FALSE;

  /**
   * Gettext cache for extension domains/streamers, depending on if native or phpgettext.
   * - native gettext: we cache the value for textdomain()
   * - phpgettext: we cache the file streamer.
   */
  private $_extensioncache = array();

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
   * @param string $locale
   */
  protected function setPhpGettextLocale($locale) {

    // we support both the old file hierarchy format and the new:
    // pre-4.5:  civicrm/l10n/xx_XX/civicrm.mo
    // post-4.5: civicrm/l10n/xx_XX/LC_MESSAGES/civicrm.mo
    require_once 'PHPgettext/streams.php';
    require_once 'PHPgettext/gettext.php';

    $mo_file = CRM_Core_I18n::getResourceDir() . $locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'civicrm.mo';

    if (!file_exists($mo_file)) {
      // fallback to pre-4.5 mode
      $mo_file = CRM_Core_I18n::getResourceDir() . $locale . DIRECTORY_SEPARATOR . 'civicrm.mo';
    }

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
      $all = CRM_Contact_BAO_Contact::buildOptions('preferred_language');

      // get labels
      $rows = array();
      $labels = array();
      CRM_Core_OptionValue::getValues(array('name' => 'languages'), $rows);
      foreach ($rows as $id => $row) {
        $labels[$row['name']] = $row['label'];
      }

      // check which ones are available; add them to $all if not there already
      $codes = array();
      if (is_dir(CRM_Core_I18n::getResourceDir()) && $dir = opendir(CRM_Core_I18n::getResourceDir())) {
        while ($filename = readdir($dir)) {
          if (preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $filename)) {
            $codes[] = $filename;
            if (!isset($all[$filename])) {
              $all[$filename] = $labels[$filename];
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

      ksort($all);
    }

    if ($enabled === NULL) {
      $config = CRM_Core_Config::singleton();
      $enabled = array();
      if (isset($config->languageLimit) and $config->languageLimit) {
        foreach ($all as $code => $name) {
          if (in_array($code, array_keys($config->languageLimit))) {
            $enabled[$code] = $name;
          }
        }
      }
    }

    return $justEnabled ? $enabled : $all;
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
    $tr = array();
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
    static $dir = NULL;
    if ($dir === NULL) {
      $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'l10n' . DIRECTORY_SEPARATOR;
    }
    return $dir;
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
   *
   * @return string
   *   the translated string
   */
  public function crm_translate($text, $params = array()) {
    if (isset($params['escape'])) {
      $escape = $params['escape'];
      unset($params['escape']);
    }

    // sometimes we need to {ts}-tag a string, but don’t want to
    // translate it in the template (like civicrm_navigation.tpl),
    // because we handle the translation in a different way (CRM-6998)
    // in such cases we return early, only doing SQL/JS escaping
    if (isset($params['skip']) and $params['skip']) {
      if (isset($escape) and ($escape == 'sql')) {
        $text = self::escapeSql($text);
      }
      if (isset($escape) and ($escape == 'js')) {
        $text = addcslashes($text, "'");
      }
      return $text;
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

    // replace the numbered %1, %2, etc. params if present
    if (count($params) && !$raw) {
      $text = $this->strarg($text, $params);
    }

    // escape SQL if we were asked for it
    if (isset($escape) and ($escape == 'sql')) {
      $text = self::escapeSql($text);
    }

    // escape for JavaScript (if requested)
    if (isset($escape) and ($escape == 'js')) {
      $text = addcslashes($text, "'");
    }

    return $text;
  }

  /**
   * Lookup the raw translation of a string (without any extra escaping or interpolation).
   *
   * @param string $text
   * @param string|NULL $domain
   * @param int|NULL $count
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

    // FIXME: Is there a constant we can reference instead of hardcoding en_US?
    $replacementsLocale = $this->locale ? $this->locale : 'en_US';
    if (!isset(Civi::$statics[__CLASS__]) || !array_key_exists($replacementsLocale, Civi::$statics[__CLASS__])) {
      if (defined('CIVICRM_DSN') && !CRM_Core_Config::isUpgradeMode()) {
        Civi::$statics[__CLASS__][$replacementsLocale] = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings($replacementsLocale);
      }
      else {
        Civi::$statics[__CLASS__][$replacementsLocale] = array();
      }
    }
    $stringTable = Civi::$statics[__CLASS__][$replacementsLocale];

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
          $text = $this->_phpgettext->ngettext($text, $plural, $count);
        }
        else {
          // if the locale's not set, we do ngettext work by hand
          // if $count == 1 then $text = $text, else $text = $plural
          if ($count != 1) {
            $text = $plural;
          }
        }

        // expand %count in translated string to $count
        $text = strtr($text, array('%count' => $count));

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
    $params = array()
  ) {
    $tsLocale = CRM_Core_I18n::getLocale();

    if ($tsLocale == 'en_US') {
      return;
    }

    foreach ($array as & $value) {
      if ($value) {
        $value = ts($value, $params);
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
      elseif ((string ) $key == 'title') {
        $array[$key] = ts($value, array('context' => 'menu'));
      }
    }
  }

  /**
   * Binds a gettext domain, wrapper over bindtextdomain().
   *
   * @param $key
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
        $mapper = CRM_Extension_System::singleton()->getMapper();
        $path = $mapper->keyToBasePath($key);
        $info = $mapper->keyToInfo($key);
        $domain = $info->file;

        if ($this->_nativegettext) {
          bindtextdomain($domain, $path . DIRECTORY_SEPARATOR . 'l10n');
          bind_textdomain_codeset($domain, 'UTF-8');
          $this->_extensioncache[$key] = $domain;
        }
        else {
          // phpgettext
          $mo_file = $path . DIRECTORY_SEPARATOR . 'l10n' . DIRECTORY_SEPARATOR . $this->locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . $domain . '.mo';
          $streamer = new FileReader($mo_file);
          $this->_extensioncache[$key] = new gettext_reader($streamer);
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
   * Is the CiviCRM in multilingual mode.
   *
   * @return Bool
   *   True if CiviCRM is in multilingual mode.
   */
  public static function isMultilingual() {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    return (bool) $domain->locales;
  }

  /**
   * Is the language written "right-to-left"?
   *
   * @param $language
   *   Language (for example 'en_US', or 'fr_CA').
   *
   * @return Bool
   *   True if it is an RTL language.
   */
  public static function isLanguageRTL($language) {
    $rtl = CRM_Core_I18n_PseudoConstant::getRTLlanguages();
    $short = CRM_Core_I18n_PseudoConstant::shortForLong($language);

    return (in_array($short, $rtl));
  }

  /**
   * Change the processing language without changing the current user language
   *
   * @param $locale
   *   Locale (for example 'en_US', or 'fr_CA').
   *   True if the domain was changed for an extension.
   */
  public function setLocale($locale) {

    // Change the language of the CMS as well, for URLs.
    CRM_Utils_System::setUFLocale($locale);

    // change the gettext ressources
    if ($this->_nativegettext) {
      $this->setNativeGettextLocale($locale);
    }
    else {
      // phpgettext
      $this->setPhpGettextLocale($locale);
    }

    // for sql queries
    global $dbLocale;
    $dbLocale = "_{$locale}";

  }

  /**
   * Static instance provider - return the instance for the current locale.
   *
   * @return CRM_Core_I18n
   */
  public static function &singleton() {
    static $singleton = array();

    $tsLocale = CRM_Core_I18n::getLocale();
    if (!isset($singleton[$tsLocale])) {
      $singleton[$tsLocale] = new CRM_Core_I18n($tsLocale);
    }

    return $singleton[$tsLocale];
  }

  /**
   * Set the LC_TIME locale if it's not set already (for a given language choice).
   *
   * @return string
   *   the final LC_TIME that got set
   */
  public static function setLcTime() {
    static $locales = array();

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
      $language = civicrm_api3('setting', 'getvalue', array(
        'name' => 'lcMessages',
        'group' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
      ));
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
    return $tsLocale ? $tsLocale : 'en_US';
  }

}

/**
 * Short-named function for string translation, defined in global scope so it's available everywhere.
 *
 * @param string $text
 *   String string for translating.
 * @param array $params
 *   Array an array of additional parameters.
 *
 * @return string
 *   the translated string
 */
function ts($text, $params = array()) {
  static $areSettingsAvailable = FALSE;
  static $lastLocale = NULL;
  static $i18n = NULL;
  static $function = NULL;

  if ($text == '') {
    return '';
  }

  // When the settings become available, lookup customTranslateFunction.
  if (!$areSettingsAvailable) {
    $areSettingsAvailable = (bool) \Civi\Core\Container::getBootService('settings_manager');
    if ($areSettingsAvailable) {
      $config = CRM_Core_Config::singleton();
      if (isset($config->customTranslateFunction) and function_exists($config->customTranslateFunction)) {
        $function = $config->customTranslateFunction;
      }
    }
  }

  $activeLocale = CRM_Core_I18n::getLocale();
  if (!$i18n or $lastLocale != $activeLocale) {
    $i18n = CRM_Core_I18n::singleton();
    $lastLocale = $activeLocale;
  }

  if ($function) {
    return $function($text, $params);
  }
  else {
    return $i18n->crm_translate($text, $params);
  }
}
