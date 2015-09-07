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

    // unset any of the variables we read from file that should not be stored in the database
    // the username and certpath are stored flat with _test and _live
    // check CRM-1470
    $skipVars = self::skipVars();
    foreach ($skipVars as $var) {
      unset($params[$var]);
    }

    // also skip all Dir Params, we dont need to store those in the DB!
    foreach ($params as $name => $val) {
      if (substr($name, -3) == 'Dir') {
        unset($params[$name]);
      }
    }

    //keep user preferred language up to date, CRM-7746
    $session = CRM_Core_Session::singleton();
    $lcMessages = CRM_Utils_Array::value('lcMessages', $params);
    if ($lcMessages && $session->get('userID')) {
      $languageLimit = CRM_Utils_Array::value('languageLimit', $params);
      if (is_array($languageLimit) &&
        !in_array($lcMessages, array_keys($languageLimit))
      ) {
        $lcMessages = $session->get('lcMessages');
      }

      $ufm = new CRM_Core_DAO_UFMatch();
      $ufm->contact_id = $session->get('userID');
      if ($lcMessages && $ufm->find(TRUE)) {
        $ufm->language = $lcMessages;
        $ufm->save();
        $session->set('lcMessages', $lcMessages);
        $params['lcMessages'] = $lcMessages;
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

    //we are initializing config, really can't use, CRM-7863
    $urlVar = 'q';
    if (defined('CIVICRM_UF') && CIVICRM_UF == 'Joomla') {
      $urlVar = 'task';
    }

    if (CRM_Core_Config::isUpgradeMode()) {
      $domain->selectAdd('config_backend');
    }
    else {
      $domain->selectAdd('config_backend, locales');
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

      // are we in a multi-language setup?
      $multiLang = $domain->locales ? TRUE : FALSE;

      // set the current language
      $lcMessages = NULL;

      $session = CRM_Core_Session::singleton();

      // on multi-lang sites based on request and civicrm_uf_match
      if ($multiLang) {
        $lcMessagesRequest = CRM_Utils_Request::retrieve('lcMessages', 'String', $this);
        $languageLimit = array();
        if (array_key_exists('languageLimit', $defaults) && is_array($defaults['languageLimit'])) {
          $languageLimit = $defaults['languageLimit'];
        }

        if (in_array($lcMessagesRequest, array_keys($languageLimit))) {
          $lcMessages = $lcMessagesRequest;

          //CRM-8559, cache navigation do not respect locale if it is changed, so reseting cache.
          CRM_Core_BAO_Cache::deleteGroup('navigation');
        }
        else {
          $lcMessagesRequest = NULL;
        }

        if (!$lcMessagesRequest) {
          $lcMessagesSession = $session->get('lcMessages');
          if (in_array($lcMessagesSession, array_keys($languageLimit))) {
            $lcMessages = $lcMessagesSession;
          }
          else {
            $lcMessagesSession = NULL;
          }
        }

        if ($lcMessagesRequest) {
          $ufm = new CRM_Core_DAO_UFMatch();
          $ufm->contact_id = $session->get('userID');
          if ($ufm->find(TRUE)) {
            $ufm->language = $lcMessages;
            $ufm->save();
          }
          $session->set('lcMessages', $lcMessages);
        }

        if (!$lcMessages and $session->get('userID')) {
          $ufm = new CRM_Core_DAO_UFMatch();
          $ufm->contact_id = $session->get('userID');
          if ($ufm->find(TRUE) &&
            in_array($ufm->language, array_keys($languageLimit))
          ) {
            $lcMessages = $ufm->language;
          }
          $session->set('lcMessages', $lcMessages);
        }
      }
      global $dbLocale;

      // try to inherit the language from the hosting CMS
      if (!empty($defaults['inheritLocale'])) {
        // FIXME: On multilanguage installs, CRM_Utils_System::getUFLocale() in many cases returns nothing if $dbLocale is not set
        $dbLocale = $multiLang ? "_{$defaults['lcMessages']}" : '';
        $lcMessages = CRM_Utils_System::getUFLocale();
        if ($domain->locales and !in_array($lcMessages, explode(CRM_Core_DAO::VALUE_SEPARATOR,
            $domain->locales
          ))
        ) {
          $lcMessages = NULL;
        }
      }

      if (empty($lcMessages)) {
        //CRM-11993 - if a single-lang site, use default
        $lcMessages = CRM_Utils_Array::value('lcMessages', $defaults);
      }

      // set suffix for table names - use views if more than one language
      $dbLocale = $multiLang ? "_{$lcMessages}" : '';

      // FIXME: an ugly hack to fix CRM-4041
      global $tsLocale;
      $tsLocale = $lcMessages;

      // FIXME: as bad aplace as any to fix CRM-5428
      // (to be moved to a sane location along with the above)
      if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
      }
    }
  }

  /**
   * @return array
   */
  public static function getConfigSettings() {
    $config = CRM_Core_Config::singleton();

    $url = $dir = $siteName = $siteRoot = NULL;
    if ($config->userFramework == 'Joomla') {
      $url = preg_replace(
        '|administrator/components/com_civicrm/civicrm/|',
        '',
        $config->userFrameworkResourceURL
      );

      // lets use imageUploadDir since we dont mess around with its values
      // in the config object, lets kep it a bit generic since folks
      // might have different values etc

      //CRM-15365 - Fix preg_replace to handle backslash for Windows File Paths
      if (DIRECTORY_SEPARATOR == '\\') {
        $dir = preg_replace(
          '|civicrm[/\\\\]templates_c[/\\\\].*$|',
          '',
          $config->templateCompileDir
        );
      }
      else {
        $dir = preg_replace(
          '|civicrm/templates_c/.*$|',
          '',
          $config->templateCompileDir
        );
      }

      $siteRoot = preg_replace(
        '|/media/civicrm/.*$|',
        '',
        $config->imageUploadDir
      );
    }
    elseif ($config->userFramework == 'WordPress') {
      $url = preg_replace(
        '|wp-content/plugins/civicrm/civicrm/|',
        '',
        $config->userFrameworkResourceURL
      );

      // lets use imageUploadDir since we dont mess around with its values
      // in the config object, lets kep it a bit generic since folks
      // might have different values etc

      //CRM-15365 - Fix preg_replace to handle backslash for Windows File Paths
      if (DIRECTORY_SEPARATOR == '\\') {
        $dir = preg_replace(
          '|civicrm[/\\\\]templates_c[/\\\\].*$|',
          '',
          $config->templateCompileDir
        );
      }
      else {
        $dir = preg_replace(
          '|civicrm/templates_c/.*$|',
          '',
          $config->templateCompileDir
        );
      }

      $siteRoot = preg_replace(
        '|/wp-content/plugins/files/civicrm/.*$|',
        '',
        $config->imageUploadDir
      );
    }
    else {
      $url = preg_replace(
        '|sites/[\w\.\-\_]+/modules/civicrm/|',
        '',
        $config->userFrameworkResourceURL
      );

      // lets use imageUploadDir since we dont mess around with its values
      // in the config object, lets kep it a bit generic since folks
      // might have different values etc

      //CRM-15365 - Fix preg_replace to handle backslash for Windows File Paths
      if (DIRECTORY_SEPARATOR == '\\') {
        $dir = preg_replace(
          '|[/\\\\]files[/\\\\]civicrm[/\\\\].*$|',
          '\\\\files\\\\',
          $config->imageUploadDir
        );
      }
      else {
        $dir = preg_replace(
          '|/files/civicrm/.*$|',
          '/files/',
          $config->imageUploadDir
        );
      }

      $matches = array();
      if (preg_match(
        '|/sites/([\w\.\-\_]+)/|',
        $config->imageUploadDir,
        $matches
      )) {
        $siteName = $matches[1];
        if ($siteName) {
          $siteName = "/sites/$siteName/";
          $siteNamePos = strpos($dir, $siteName);
          if ($siteNamePos !== FALSE) {
            $siteRoot = substr($dir, 0, $siteNamePos);
          }
        }
      }
    }

    return array($url, $dir, $siteName, $siteRoot);
  }

  /**
   * Return likely default settings.
   * @return array
   *   site settings
   *   - $url
   *   - $dir Base Directory
   *   - $siteName
   *   - $siteRoot
   */
  public static function getBestGuessSettings() {
    $config = CRM_Core_Config::singleton();

    //CRM-15365 - Fix preg_replace to handle backslash for Windows File Paths
    if (DIRECTORY_SEPARATOR == '\\') {
      $needle = 'civicrm[/\\\\]templates_c[/\\\\].*$';
    }
    else {
      $needle = 'civicrm/templates_c/.*$';
    }

    $dir = preg_replace(
      "|$needle|",
      '',
      $config->templateCompileDir
    );

    list($url, $siteName, $siteRoot) = $config->userSystem->getDefaultSiteSettings($dir);
    return array($url, $dir, $siteName, $siteRoot);
  }

  /**
   * @param array $defaultValues
   *
   * @return string
   * @throws Exception
   */
  public static function doSiteMove($defaultValues = array()) {
    $moveStatus = ts('Beginning site move process...') . '<br />';
    // get the current and guessed values
    list($oldURL, $oldDir, $oldSiteName, $oldSiteRoot) = self::getConfigSettings();
    list($newURL, $newDir, $newSiteName, $newSiteRoot) = self::getBestGuessSettings();

    // retrieve these values from the argument list
    $variables = array('URL', 'Dir', 'SiteName', 'SiteRoot', 'Val_1', 'Val_2', 'Val_3');
    $states = array('old', 'new');
    foreach ($variables as $varSuffix) {
      foreach ($states as $state) {
        $var = "{$state}{$varSuffix}";
        if (!isset($$var)) {
          if (isset($defaultValues[$var])) {
            $$var = $defaultValues[$var];
          }
          else {
            $$var = NULL;
          }
        }
        $$var = CRM_Utils_Request::retrieve($var,
          'String',
          CRM_Core_DAO::$_nullArray,
          FALSE,
          $$var,
          'REQUEST'
        );
      }
    }

    $from = $to = array();
    foreach ($variables as $varSuffix) {
      $oldVar = "old{$varSuffix}";
      $newVar = "new{$varSuffix}";
      //skip it if either is empty or both are exactly the same
      if ($$oldVar &&
        $$newVar &&
        $$oldVar != $$newVar
      ) {
        $from[] = $$oldVar;
        $to[] = $$newVar;
      }
    }

    $sql = "
SELECT config_backend
FROM   civicrm_domain
WHERE  id = %1
";
    $params = array(1 => array(CRM_Core_Config::domainID(), 'Integer'));
    $configBackend = CRM_Core_DAO::singleValueQuery($sql, $params);
    if (!$configBackend) {
      CRM_Core_Error::fatal(ts('Returning early due to unexpected error - civicrm_domain.config_backend column value is NULL. Try visiting CiviCRM Home page.'));
    }
    $configBackend = unserialize($configBackend);

    $configBackend = str_replace($from,
      $to,
      $configBackend
    );

    $configBackend = serialize($configBackend);
    $sql = "
UPDATE civicrm_domain
SET    config_backend = %2
WHERE  id = %1
";
    $params[2] = array($configBackend, 'String');
    CRM_Core_DAO::executeQuery($sql, $params);

    // Apply the changes to civicrm_option_values
    $optionGroups = array('url_preferences', 'directory_preferences');
    foreach ($optionGroups as $option) {
      foreach ($variables as $varSuffix) {
        $oldVar = "old{$varSuffix}";
        $newVar = "new{$varSuffix}";

        $from = $$oldVar;
        $to = $$newVar;

        if ($from && $to && $from != $to) {
          $sql = '
UPDATE civicrm_option_value
SET    value = REPLACE(value, %1, %2)
WHERE  option_group_id = (
  SELECT id
  FROM   civicrm_option_group
  WHERE  name = %3 )
';
          $params = array(
            1 => array($from, 'String'),
            2 => array($to, 'String'),
            3 => array($option, 'String'),
          );
          CRM_Core_DAO::executeQuery($sql, $params);
        }
      }
    }

    $moveStatus .=
      ts('Directory and Resource URLs have been updated in the moved database to reflect current site location.') .
      '<br />';

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
    $config = CRM_Core_Config::singleton();

    // fix the config object
    $config->enableComponents = $enabledComponents;

    // also force reset of component array
    CRM_Core_Component::getEnabledComponents(TRUE);

    // update DB
    CRM_Core_BAO_Setting::setItem($enabledComponents,
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'enable_components');
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
      'locale_custom_strings',
      'localeCustomStrings',
      'autocompleteContactSearch',
      'autocompleteContactReference',
      'checksumTimeout',
      'checksum_timeout',
    );
  }

}
