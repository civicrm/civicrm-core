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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * File contains functions used in civicrm configuration.
 */
class CRM_Core_BAO_ConfigSetting {

  /**
   * Create civicrm settings. This is the same as add but it clears the cache and
   * reloads the config object
   *
   * @param array $params
   *   Associated array of civicrm variables.
   */
  public static function create($params) {
    self::add($params);
    $cache = CRM_Utils_Cache::singleton();
    $cache->delete('CRM_Core_Config');
    $cache->delete('CRM_Core_Config' . CRM_Core_Config::domainID());
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
  }

  /**
   * Add civicrm settings.
   *
   * @param array $params
   *   Associated array of civicrm variables.
   * @deprecated
   *   This method was historically used to access civicrm_domain.config_backend.
   *   However, that has been fully replaced by the settings system since v4.7.
   */
  public static function add(&$params) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $params = array_merge(unserialize($domain->config_backend), $params);
    }

    $params = CRM_Core_BAO_ConfigSetting::filterSkipVars($params);

    // also skip all Dir Params, we dont need to store those in the DB!
    foreach ($params as $name => $val) {
      if (substr($name, -3) == 'Dir') {
        unset($params[$name]);
      }
    }

    $domain->config_backend = serialize($params);
    $domain->save();
  }

  /**
   * Retrieve the settings values from db.
   *
   * @param $defaults
   *
   * @return array
   * @deprecated
   *   This method was historically used to access civicrm_domain.config_backend.
   *   However, that has been fully replaced by the settings system since v4.7.
   */
  public static function retrieve(&$defaults) {
    $domain = new CRM_Core_DAO_Domain();
    $isUpgrade = CRM_Core_Config::isUpgradeMode();

    //we are initializing config, really can't use, CRM-7863
    $urlVar = 'q';
    if (defined('CIVICRM_UF') && CIVICRM_UF == 'Joomla') {
      $urlVar = 'task';
    }

    $hasBackend = CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_domain', 'config_backend');
    if ($isUpgrade && $hasBackend) {
      $domain->selectAdd('config_backend');
    }
    else {
      $domain->selectAdd('locales');
    }

    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    if ($hasBackend && $domain->config_backend) {
      // This whole branch can probably be removed; the transitional loading
      // is in SettingBag::loadValues(). Moreover, since 4.7.alpha1 dropped
      // the column, anyone calling ::retrieve() has likely not gotten any data.
      $defaults = unserialize($domain->config_backend);
      if ($defaults === FALSE || !is_array($defaults)) {
        $defaults = [];
        return FALSE;
      }

      $skipVars = self::skipVars();
      foreach ($skipVars as $skip) {
        if (array_key_exists($skip, $defaults)) {
          unset($defaults[$skip]);
        }
      }
    }
    if (!$isUpgrade) {
      CRM_Core_BAO_ConfigSetting::applyLocale(Civi::settings($domain->id), $domain->locales);
    }
  }

  /**
   * Activate a chosen locale.
   *
   * The locale is set by updating the session and global variables.
   *
   * When there is a choice of permitted languages (set on the "Administer" ->
   * "Localisation" -> "Languages, Currency, Locations" screen) the locale to
   * be applied can come from a variety of sources. The list below is the order
   * of priority for deciding which of the sources "wins":
   *
   * - The request - when the "lcMessages" query variable is present in the URL.
   * - The session - when the "lcMessages" session variable has been set.
   * - Inherited from the CMS - when the "inheritLocale" setting is set.
   * - CiviCRM settings - the fallback when none of the above set the locale.
   *
   * Single-language installs skip this and always set the default locale.
   *
   * @param \Civi\Core\SettingsBag $settings
   * @param string $activatedLocales
   *   Imploded list of locales which are supported in the DB.
   */
  public static function applyLocale($settings, $activatedLocales) {

    // Declare access to locale globals.
    global $dbLocale, $tsLocale;

    // Grab session reference.
    $session = CRM_Core_Session::singleton();

    // Initialise the default and chosen locales.
    $defaultLocale = $settings->get('lcMessages');
    $chosenLocale = NULL;

    // Parse multi lang locales
    $multiLangLocales = $activatedLocales ? explode(CRM_Core_DAO::VALUE_SEPARATOR, $activatedLocales) : NULL;

    // On multilang, defaultLocale should be one of the activated locales
    if ($multiLangLocales && !in_array($defaultLocale, $multiLangLocales)) {
      $defaultLocale = NULL;
    }

    // When there is a choice of permitted languages.
    // Why would this be different from the locales?
    // @see https://github.com/civicrm/civicrm-core/pull/30533#discussion_r1756400531
    $permittedLanguages = CRM_Core_I18n::uiLanguages(TRUE);
    if (count($permittedLanguages) >= 2) {

      // Is the "lcMessages" query variable present in the URL?
      $requestLocale = CRM_Utils_Request::retrieve('lcMessages', 'String');
      if (in_array($requestLocale, $permittedLanguages)) {
        $chosenLocale = $requestLocale;
      }

      // Check the session if the chosen locale hasn't been set yet.
      if (empty($chosenLocale)) {
        $sessionLocale = $session->get('lcMessages');
        if (in_array($sessionLocale, $permittedLanguages)) {
          $chosenLocale = $sessionLocale;
        }
      }

      /*
       * Maybe inherit the language from the CMS.
       *
       * If the language is specified via "lcMessages" we skip this, since the
       * intention of the URL query var is to override all other sources.
       */
      if ($settings->get('inheritLocale')) {

        /*
         * FIXME: On multi-language installs, CRM_Utils_System::getUFLocale() in
         * many cases returns nothing if $dbLocale is not set, so set it to the
         * default - even if it's overridden later.
         */
        $dbLocale = $multiLangLocales && $defaultLocale ? "_{$defaultLocale}" : '';

        // Retrieve locale as reported by CMS.
        $cmsLocale = CRM_Utils_System::getUFLocale();
        if (in_array($cmsLocale, $permittedLanguages)) {
          $chosenLocale = $cmsLocale;
        }

        // Clear chosen locale if not activated in multi-language CiviCRM.
        if ($multiLangLocales && !in_array($chosenLocale, $multiLangLocales)) {
          $chosenLocale = NULL;
        }
      }

      // Assign the system default if the chosen locale hasn't been set.
      if (empty($chosenLocale)) {
        $chosenLocale = $defaultLocale;
      }

    }
    else {
      // CRM-11993 - Use default when it's a single-language install.
      $chosenLocale = $defaultLocale;
    }

    if ($chosenLocale && ($requestLocale ?? NULL) === $chosenLocale) {
      // If the locale is passed in via lcMessages key on GET or POST data,
      // and it's valid against our configured locales, we require the session
      // to store this, even if that means starting an anonymous session.
      $session->set('lcMessages', $chosenLocale);
    }

    /*
     * Set suffix for table names in multi-language installs.
     * Use views if more than one language.
     */
    $dbLocale = $multiLangLocales && $chosenLocale ? "_{$chosenLocale}" : '';

    // FIXME: an ugly hack to fix CRM-4041.
    $tsLocale = $chosenLocale;

    /*
     * FIXME: as bad a place as any to fix CRM-5428.
     * (to be moved to a sane location along with the above)
     */
    if (function_exists('mb_internal_encoding')) {
      mb_internal_encoding('UTF-8');
    }

  }

  /**
   * @param array $defaultValues
   *
   * @return string
   * @throws Exception
   */
  public static function doSiteMove($defaultValues = []) {
    $moveStatus = ts('Beginning site move process...') . '<br />';
    $settings = Civi::settings();

    foreach (array_merge(self::getPathSettings(), self::getUrlSettings()) as $key) {
      $value = $settings->get($key);
      if ($value && $value != $settings->getDefault($key)) {
        if ($settings->getMandatory($key) === NULL) {
          $settings->revert($key);
          $moveStatus .= ts("WARNING: The setting (%1) has been reverted.", [
            1 => $key,
          ]);
          $moveStatus .= '<br />';
        }
        else {
          $moveStatus .= ts("WARNING: The setting (%1) is overridden and could not be reverted.", [
            1 => $key,
          ]);
          $moveStatus .= '<br />';
        }
      }
    }

    $config = CRM_Core_Config::singleton();

    // clear the template_c and upload directory also
    $config->cleanup(3, TRUE);
    $moveStatus .= ts('Template cache and upload directory have been cleared.') . '<br />';

    // clear all caches
    CRM_Core_Config::clearDBCache();
    Civi::cache('session')->clear();
    $moveStatus .= ts('Database cache tables cleared.') . '<br />';

    $resetSessionTable = CRM_Utils_Request::retrieve('resetSessionTable',
      'Boolean',
      CRM_Core_DAO::$_nullArray,
      FALSE,
      FALSE
    );
    if ($config->userSystem->is_drupal &&
      $resetSessionTable
    ) {
      db_query("DELETE FROM {sessions} WHERE 1");
      $moveStatus .= ts('Drupal session table cleared.') . '<br />';
    }
    else {
      $session = CRM_Core_Session::singleton();
      $session->reset(2);
      $moveStatus .= ts('Session has been reset.') . '<br />';
    }

    return $moveStatus;
  }

  /**
   * Takes a componentName and enables it in the config.
   * Primarily used during unit testing
   *
   * @param string $componentName
   *   Name of the component to be enabled, needs to be valid.
   *
   * @return bool
   *   true if valid component name and enabling succeeds, else false
   */
  public static function enableComponent($componentName) {
    $enabledComponents = Civi::settings()->get('enable_components');
    if (in_array($componentName, $enabledComponents, TRUE)) {
      // Component is already enabled
      return TRUE;
    }

    // return if component does not exist
    if (!array_key_exists($componentName, CRM_Core_Component::getComponents())) {
      return FALSE;
    }

    // get enabled-components from DB and add to the list
    $enabledComponents[] = $componentName;
    self::setEnabledComponents($enabledComponents);

    return TRUE;
  }

  /**
   * Ensure all components are enabled
   * @throws CRM_Core_Exception
   */
  public static function enableAllComponents() {
    $allComponents = array_keys(CRM_Core_Component::getComponents());
    if (Civi::settings()->get('enable_components') != $allComponents) {
      self::setEnabledComponents($allComponents);
    }
  }

  /**
   * Disable specified component.
   *
   * @param string $componentName
   *
   * @return bool
   */
  public static function disableComponent($componentName) {
    $enabledComponents = Civi::settings()->get('enable_components');
    if (!in_array($componentName, $enabledComponents, TRUE)) {
      // Component is already disabled.
      return TRUE;
    }

    self::setEnabledComponents(array_diff($enabledComponents, [$componentName]));
    return TRUE;
  }

  /**
   * Set enabled components.
   *
   * @param array $enabledComponents
   */
  public static function setEnabledComponents($enabledComponents) {
    // The post_change trigger on this setting will sync component extensions, which will also flush caches
    Civi::settings()->set('enable_components', array_values($enabledComponents));
  }

  /**
   * @return array
   */
  public static function skipVars() {
    return [
      'dsn',
      'templateCompileDir',
      'userFrameworkDSN',
      'userFramework',
      'userFrameworkBaseURL',
      'userFrameworkClass',
      'userHookClass',
      'userPermissionClass',
      'userPermissionTemp',
      'userFrameworkURLVar',
      'userFrameworkVersion',
      'newBaseURL',
      'newBaseDir',
      'newSiteName',
      'configAndLogDir',
      'qfKey',
      'gettextResourceDir',
      'cleanURL',
      'entryURL',
      'locale_custom_strings',
      'localeCustomStrings',
      'autocompleteContactSearch',
      'autocompleteContactReference',
      'checksumTimeout',
      'checksum_timeout',
    ];
  }

  /**
   * @param array $params
   * @return array
   */
  public static function filterSkipVars($params) {
    $skipVars = self::skipVars();
    foreach ($skipVars as $var) {
      unset($params[$var]);
    }
    foreach (array_keys($params) as $key) {
      if (preg_match('/^_qf_/', $key)) {
        unset($params[$key]);
      }
    }
    return $params;
  }

  /**
   * @return array
   */
  private static function getUrlSettings() {
    return [
      'userFrameworkResourceURL',
      'imageUploadURL',
      'customCSSURL',
      'extensionsURL',
    ];
  }

  /**
   * @return array
   */
  private static function getPathSettings() {
    return [
      'uploadDir',
      'imageUploadDir',
      'customFileUploadDir',
      'customTemplateDir',
      'customPHPPathDir',
      'extensionsDir',
    ];
  }

}
