<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
   */
  public static function retrieve(&$defaults) {
    $domain = new CRM_Core_DAO_Domain();
    $isUpgrade = CRM_Core_Config::isUpgradeMode();

    //we are initializing config, really can't use, CRM-7863
    $urlVar = 'q';
    if (defined('CIVICRM_UF') && CIVICRM_UF == 'Joomla') {
      $urlVar = 'task';
    }

    if ($isUpgrade && CRM_Core_DAO::checkFieldExists('civicrm_domain', 'config_backend')) {
      $domain->selectAdd('config_backend');
    }
    else {
      $domain->selectAdd('locales');
    }

    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $defaults = unserialize($domain->config_backend);
      if ($defaults === FALSE || !is_array($defaults)) {
        $defaults = array();
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
   * Evaluate locale preferences and activate a chosen locale by
   * updating session+global variables.
   *
   * @param \Civi\Core\SettingsBag $settings
   * @param string $activatedLocales
   *   Imploded list of locales which are supported in the DB.
   */
  public static function applyLocale($settings, $activatedLocales) {
    // are we in a multi-language setup?
    $multiLang = $activatedLocales ? TRUE : FALSE;

    // set the current language
    $chosenLocale = NULL;

    $session = CRM_Core_Session::singleton();

    // on multi-lang sites based on request and civicrm_uf_match
    if ($multiLang) {
      $languageLimit = array();
      if (is_array($settings->get('languageLimit'))) {
        $languageLimit = $settings->get('languageLimit');
      }

      $requestLocale = CRM_Utils_Request::retrieve('lcMessages', 'String');
      if (in_array($requestLocale, array_keys($languageLimit))) {
        $chosenLocale = $requestLocale;

        //CRM-8559, cache navigation do not respect locale if it is changed, so reseting cache.
        // Ed: This doesn't sound good.
        CRM_Core_BAO_Cache::deleteGroup('navigation');
      }
      else {
        $requestLocale = NULL;
      }

      if (!$requestLocale) {
        $sessionLocale = $session->get('lcMessages');
        if (in_array($sessionLocale, array_keys($languageLimit))) {
          $chosenLocale = $sessionLocale;
        }
        else {
          $sessionLocale = NULL;
        }
      }

      if ($requestLocale) {
        $ufm = new CRM_Core_DAO_UFMatch();
        $ufm->contact_id = $session->get('userID');
        if ($ufm->find(TRUE)) {
          $ufm->language = $chosenLocale;
          $ufm->save();
        }
        $session->set('lcMessages', $chosenLocale);
      }

      if (!$chosenLocale and $session->get('userID')) {
        $ufm = new CRM_Core_DAO_UFMatch();
        $ufm->contact_id = $session->get('userID');
        if ($ufm->find(TRUE) &&
          in_array($ufm->language, array_keys($languageLimit))
        ) {
          $chosenLocale = $ufm->language;
        }
        $session->set('lcMessages', $chosenLocale);
      }
    }
    global $dbLocale;

    // try to inherit the language from the hosting CMS
    if ($settings->get('inheritLocale')) {
      // FIXME: On multilanguage installs, CRM_Utils_System::getUFLocale() in many cases returns nothing if $dbLocale is not set
      $dbLocale = $multiLang ? ("_" . $settings->get('lcMessages')) : '';
      $chosenLocale = CRM_Utils_System::getUFLocale();
      if ($activatedLocales and !in_array($chosenLocale, explode(CRM_Core_DAO::VALUE_SEPARATOR, $activatedLocales))) {
        $chosenLocale = NULL;
      }
    }

    if (empty($chosenLocale)) {
      //CRM-11993 - if a single-lang site, use default
      $chosenLocale = $settings->get('lcMessages');
    }

    // set suffix for table names - use views if more than one language
    $dbLocale = $multiLang ? "_{$chosenLocale}" : '';

    // FIXME: an ugly hack to fix CRM-4041
    global $tsLocale;
    $tsLocale = $chosenLocale;

    // FIXME: as bad aplace as any to fix CRM-5428
    // (to be moved to a sane location along with the above)
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
  public static function doSiteMove($defaultValues = array()) {
    $moveStatus = ts('Beginning site move process...') . '<br />';
    $settings = Civi::settings();

    foreach (array_merge(self::getPathSettings(), self::getUrlSettings()) as $key) {
      $value = $settings->get($key);
      if ($value && $value != $settings->getDefault($key)) {
        if ($settings->getMandatory($key) === NULL) {
          $settings->revert($key);
          $moveStatus .= ts("WARNING: The setting (%1) has been reverted.", array(
            1 => $key,
          ));
          $moveStatus .= '<br />';
        }
        else {
          $moveStatus .= ts("WARNING: The setting (%1) is overridden and could not be reverted.", array(
            1 => $key,
          ));
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
    $moveStatus .= ts('Database cache tables cleared.') . '<br />';

    $resetSessionTable = CRM_Utils_Request::retrieve('resetSessionTable',
      'Boolean',
      CRM_Core_DAO::$_nullArray,
      FALSE,
      FALSE,
      'REQUEST'
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
    $config = CRM_Core_Config::singleton();
    if (in_array($componentName, $config->enableComponents)) {
      // component is already enabled
      return TRUE;
    }

    // return if component does not exist
    if (!array_key_exists($componentName, CRM_Core_Component::getComponents())) {
      return FALSE;
    }

    // get enabled-components from DB and add to the list
    $enabledComponents = Civi::settings()->get('enable_components');
    $enabledComponents[] = $componentName;

    self::setEnabledComponents($enabledComponents);

    return TRUE;
  }

  /**
   * Disable specified component.
   *
   * @param string $componentName
   *
   * @return bool
   */
  public static function disableComponent($componentName) {
    $config = CRM_Core_Config::singleton();
    if (!in_array($componentName, $config->enableComponents) ||
      !array_key_exists($componentName, CRM_Core_Component::getComponents())
    ) {
      // Post-condition is satisfied.
      return TRUE;
    }

    // get enabled-components from DB and add to the list
    $enabledComponents = Civi::settings()->get('enable_components');
    $enabledComponents = array_diff($enabledComponents, array($componentName));

    self::setEnabledComponents($enabledComponents);

    return TRUE;
  }

  /**
   * Set enabled components.
   *
   * @param array $enabledComponents
   */
  public static function setEnabledComponents($enabledComponents) {
    // fix the config object. update db.
    Civi::settings()->set('enable_components', $enabledComponents);

    // also force reset of component array
    CRM_Core_Component::getEnabledComponents(TRUE);
  }

  /**
   * @return array
   */
  public static function skipVars() {
    return array(
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
    );
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
    return array(
      'userFrameworkResourceURL',
      'imageUploadURL',
      'customCSSURL',
      'extensionsURL',
    );
  }

  /**
   * @return array
   */
  private static function getPathSettings() {
    return array(
      'uploadDir',
      'imageUploadDir',
      'customFileUploadDir',
      'customTemplateDir',
      'customPHPPathDir',
      'extensionsDir',
    );
  }

}
