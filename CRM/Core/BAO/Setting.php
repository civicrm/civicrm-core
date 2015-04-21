<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * BAO object for civicrm_setting table. This table is used to store civicrm settings that are not used
 * very frequently (i.e. not on every page load)
 *
 * The group column is used for grouping together all settings that logically belong to the same set.
 * Thus all settings in the same group are retrieved with one DB call and then cached for future needs.
 *
 */
class CRM_Core_BAO_Setting extends CRM_Core_DAO_Setting {

  /**
   * Various predefined settings that have been migrated to the setting table.
   */
  const
    ADDRESS_STANDARDIZATION_PREFERENCES_NAME = 'Address Standardization Preferences',
    CAMPAIGN_PREFERENCES_NAME = 'Campaign Preferences',
    DEVELOPER_PREFERENCES_NAME = 'Developer Preferences',
    DIRECTORY_PREFERENCES_NAME = 'Directory Preferences',
    EVENT_PREFERENCES_NAME = 'Event Preferences',
    MAILING_PREFERENCES_NAME = 'Mailing Preferences',
    CONTRIBUTE_PREFERENCES_NAME = 'Contribute Preferences',
    MEMBER_PREFERENCES_NAME = 'Member Preferences',
    MULTISITE_PREFERENCES_NAME = 'Multi Site Preferences',
    PERSONAL_PREFERENCES_NAME = 'Personal Preferences',
    SYSTEM_PREFERENCES_NAME = 'CiviCRM Preferences',
    URL_PREFERENCES_NAME = 'URL Preferences',
    LOCALIZATION_PREFERENCES_NAME = 'Localization Preferences',
    SEARCH_PREFERENCES_NAME = 'Search Preferences';
  static $_cache = NULL;

  /**
   * Checks whether an item is present in the in-memory cache table
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param string $name
   *   (required) The name of the setting.
   * @param int $componentID
   *   The optional component ID (so components can share the same name space).
   * @param int $contactID
   *   If set, this is a contactID specific setting, else its a global setting.
   * @param bool|int $load if true, load from local cache (typically memcache)
   *
   * @param int $domainID
   * @param bool $force
   *
   * @return bool
   *   true if item is already in cache
   */
  public static function inCache(
    $group,
    $name,
    $componentID = NULL,
    $contactID = NULL,
    $load = FALSE,
    $domainID = NULL,
    $force = FALSE
  ) {
    if (!isset(self::$_cache)) {
      self::$_cache = array();
    }

    $cacheKey = "CRM_Setting_{$group}_{$componentID}_{$contactID}_{$domainID}";

    if (
      $load &&
      ($force || !isset(self::$_cache[$cacheKey]))
    ) {

      // check in civi cache if present (typically memcache)
      $globalCache = CRM_Utils_Cache::singleton();
      $result = $globalCache->get($cacheKey);
      if ($result) {

        self::$_cache[$cacheKey] = $result;
      }
    }

    return isset(self::$_cache[$cacheKey]) ? $cacheKey : NULL;
  }

  /**
   * Allow key o be cleared.
   * @param string $cacheKey
   */
  public static function flushCache($cacheKey) {
    unset(self::$_cache[$cacheKey]);
    $globalCache = CRM_Utils_Cache::singleton();
    $globalCache->delete($cacheKey);
  }

  /**
   * @param $values
   * @param $group
   * @param int $componentID
   * @param int $contactID
   * @param int $domainID
   *
   * @return string
   */
  public static function setCache(
    $values,
    $group,
    $componentID = NULL,
    $contactID = NULL,
    $domainID = NULL
  ) {
    if (!isset(self::$_cache)) {
      self::$_cache = array();
    }

    $cacheKey = "CRM_Setting_{$group}_{$componentID}_{$contactID}_{$domainID}";

    self::$_cache[$cacheKey] = $values;

    $globalCache = CRM_Utils_Cache::singleton();
    $result = $globalCache->set($cacheKey, $values);

    return $cacheKey;
  }

  /**
   * @param $group
   * @param null $name
   * @param int $componentID
   * @param int $contactID
   * @param int $domainID
   *
   * @return CRM_Core_DAO_Domain|CRM_Core_DAO_Setting
   */
  public static function dao(
    $group,
    $name = NULL,
    $componentID = NULL,
    $contactID = NULL,
    $domainID = NULL
  ) {
    if (self::isUpgradeFromPreFourOneAlpha1()) {
      // civicrm_setting table is not going to be present. For now we'll just
      // return a dummy object
      $dao = new CRM_Core_DAO_Domain();
      $dao->id = -1; // so ->find() doesn't fetch any data later on
      return $dao;
    }
    $dao = new CRM_Core_DAO_Setting();

    $dao->group_name = $group;
    $dao->name = $name;
    $dao->component_id = $componentID;
    if (empty($domainID)) {
      $dao->domain_id = CRM_Core_Config::domainID();
    }
    else {
      $dao->domain_id = $domainID;
    }

    if ($contactID) {
      $dao->contact_id = $contactID;
      $dao->is_domain = 0;
    }
    else {
      $dao->is_domain = 1;
    }

    return $dao;
  }

  /**
   * Retrieve the value of a setting from the DB table.
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param string $name
   *   (required) The name under which this item is stored.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   * @param string $defaultValue
   *   The default value to return for this setting if not present in DB.
   * @param int $contactID
   *   If set, this is a contactID specific setting, else its a global setting.
   *
   * @param int $domainID
   *
   * @return mixed
   *   The data if present in the setting table, else null
   */
  public static function getItem(
    $group,
    $name = NULL,
    $componentID = NULL,
    $defaultValue = NULL,
    $contactID = NULL,
    $domainID = NULL
  ) {

    $overrideGroup = array();
    if (NULL !== ($override = self::getOverride($group, $name, NULL))) {
      if (isset($name)) {
        return $override;
      }
      else {
        $overrideGroup = $override;
      }
    }

    if (empty($domainID)) {
      $domainID = CRM_Core_Config::domainID();
    }
    $cacheKey = self::inCache($group, $name, $componentID, $contactID, TRUE, $domainID);

    if ($group && !isset($name) && $cacheKey) {
      // check value against the cache, and unset key if values are different
      $valueDifference = CRM_Utils_Array::multiArrayDiff($overrideGroup, self::$_cache[$cacheKey]);
      if (!empty($valueDifference)) {
        $cacheKey = '';
      }
    }

    if (!$cacheKey) {
      $dao = self::dao($group, NULL, $componentID, $contactID, $domainID);
      $dao->find();

      $values = array();
      while ($dao->fetch()) {
        if (NULL !== ($override = self::getOverride($group, $dao->name, NULL))) {
          $values[$dao->name] = $override;
        }
        elseif ($dao->value) {
          $values[$dao->name] = unserialize($dao->value);
        }
        else {
          $values[$dao->name] = NULL;
        }
      }
      $dao->free();

      if (!isset($name)) {
        // merge db and override group values
        // When no $name is present, the getItem() function should return an array
        // consisting of the sum of all override settings + all settings present in
        // the database for the given $group (with the overrides taking precedence,
        // and applying even if the setting is not defined in the database).
        //
        $values = array_merge($values, $overrideGroup);
      }

      $cacheKey = self::setCache($values, $group, $componentID, $contactID, $domainID);
    }
    return $name ? CRM_Utils_Array::value($name, self::$_cache[$cacheKey], $defaultValue) : self::$_cache[$cacheKey];
  }

  /**
   * Store multiple items in the setting table.
   *
   * @param array $params
   *   (required) An api formatted array of keys and values.
   * @param array $domains Array of domains to get settings for. Default is the current domain
   * @param $settingsToReturn
   *
   * @return array
   */
  public static function getItems(&$params, $domains = NULL, $settingsToReturn) {
    $originalDomain = CRM_Core_Config::domainID();
    if (empty($domains)) {
      $domains[] = $originalDomain;
    }
    if (!empty($settingsToReturn) && !is_array($settingsToReturn)) {
      $settingsToReturn = array($settingsToReturn);
    }
    $reloadConfig = FALSE;

    $fields = $result = array();
    $fieldsToGet = self::validateSettingsInput(array_flip($settingsToReturn), $fields, FALSE);
    foreach ($domains as $domainID) {
      if ($domainID != CRM_Core_Config::domainID()) {
        $reloadConfig = TRUE;
        CRM_Core_BAO_Domain::setDomain($domainID);
      }
      $config = CRM_Core_Config::singleton($reloadConfig, $reloadConfig);
      $result[$domainID] = array();
      foreach ($fieldsToGet as $name => $value) {
        if (!empty($fields['values'][$name]['prefetch'])) {
          if (isset($params['filters']) && isset($params['filters']['prefetch'])
            && $params['filters']['prefetch'] == 0
          ) {
            // we are filtering out the prefetches from the return array
            // so we will skip
            continue;
          }
          $configKey = CRM_Utils_Array::value('config_key', $fields['values'][$name], $name);
          if (isset($config->$configKey)) {
            $setting = $config->$configKey;
          }
        }
        else {
          $setting = CRM_Core_BAO_Setting::getItem(
            $fields['values'][$name]['group_name'],
            $name,
            CRM_Utils_Array::value('component_id', $params),
            NULL,
            CRM_Utils_Array::value('contact_id', $params),
            $domainID
          );
        }
        if (!is_null($setting)) {
          // we won't return if not set - helps in return all scenario - otherwise we can't indentify the missing ones
          // e.g for revert of fill actions
          $result[$domainID][$name] = $setting;
        }
      }
      CRM_Core_BAO_Domain::resetDomain();
    }
    return $result;
  }

  /**
   * Store an item in the setting table.
   *
   * _setItem() is the common logic shared by setItem() and setItems().
   *
   * @param object $value
   *   (required) The value that will be serialized and stored.
   * @param string $group
   *   (required) The group name of the item.
   * @param string $name
   *   (required) The name of the setting.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   * @param int $contactID
   * @param int $createdID
   *   An optional ID to assign the creator to. If not set, retrieved from session.
   *
   * @param int $domainID
   *
   * @return void
   */
  public static function setItem(
    $value,
    $group,
    $name,
    $componentID = NULL,
    $contactID = NULL,
    $createdID = NULL,
    $domainID = NULL
  ) {
    $fields = array();
    $fieldsToSet = self::validateSettingsInput(array($name => $value), $fields);
    //We haven't traditionally validated inputs to setItem, so this breaks things.
    //foreach ($fieldsToSet as $settingField => &$settingValue) {
    //  self::validateSetting($settingValue, $fields['values'][$settingField]);
    //}

    return self::_setItem($fields['values'][$name], $value, $group, $name, $componentID, $contactID, $createdID, $domainID);
  }

  /**
   * Store an item in a setting table.
   *
   * _setItem() is the common logic shared by setItem() and setItems().
   *
   * @param array $metadata
   *   Metadata describing this field.
   * @param $value
   * @param $group
   * @param string $name
   * @param int $componentID
   * @param int $contactID
   * @param int $createdID
   * @param int $domainID
   */
  public static function _setItem(
    $metadata,
    $value,
    $group,
    $name,
    $componentID = NULL,
    $contactID = NULL,
    $createdID = NULL,
    $domainID = NULL
  ) {
    if (empty($domainID)) {
      $domainID = CRM_Core_Config::domainID();
    }

    $dao = self::dao($group, $name, $componentID, $contactID, $domainID);
    $dao->find(TRUE);

    if (isset($metadata['on_change'])) {
      foreach ($metadata['on_change'] as $callback) {
        call_user_func(
          Civi\Core\Resolver::singleton()->get($callback),
          unserialize($dao->value),
          $value,
          $metadata
        );
      }
    }

    if (CRM_Utils_System::isNull($value)) {
      $dao->value = 'null';
    }
    else {
      $dao->value = serialize($value);
    }

    $dao->created_date = date('Ymdhis');

    if ($createdID) {
      $dao->created_id = $createdID;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $createdID = $session->get('userID');

      if ($createdID) {
        // ensure that this is a valid contact id (for session inconsistency rules)
        $cid = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $createdID,
          'id',
          'id'
        );
        if ($cid) {
          $dao->created_id = $session->get('userID');
        }
      }
    }

    $dao->save();
    $dao->free();

    // also save in cache if needed
    $cacheKey = self::inCache($group, $name, $componentID, $contactID, FALSE, $domainID);
    if ($cacheKey) {
      self::$_cache[$cacheKey][$name] = $value;
    }
  }

  /**
   * Store multiple items in the setting table. Note that this will also store config keys
   * the storage is determined by the metdata and is affected by
   *  'name' setting's name
   *  'prefetch' = store in config
   *  'config_only' = don't store in settings
   *  'config_key' = the config key is different to the settings key - e.g. debug where there was a conflict
   *  'legacy_key' = rename from config or setting with this name
   *
   * _setItem() is the common logic shared by setItem() and setItems().
   *
   * @param array $params
   *   (required) An api formatted array of keys and values.
   * @param null $domains
   *
   * @throws api_Exception
   * @domains array an array of domains to get settings for. Default is the current domain
   * @return array
   */
  public static function setItems(&$params, $domains = NULL) {
    $originalDomain = CRM_Core_Config::domainID();
    if (empty($domains)) {
      $domains[] = $originalDomain;
    }
    $reloadConfig = FALSE;
    $fields = $config_keys = array();
    $fieldsToSet = self::validateSettingsInput($params, $fields);

    foreach ($fieldsToSet as $settingField => &$settingValue) {
      self::validateSetting($settingValue, $fields['values'][$settingField]);
    }

    foreach ($domains as $domainID) {
      if ($domainID != CRM_Core_Config::domainID()) {
        $reloadConfig = TRUE;
        CRM_Core_BAO_Domain::setDomain($domainID);
      }
      $result[$domainID] = array();
      foreach ($fieldsToSet as $name => $value) {
        if (empty($fields['values'][$name]['config_only'])) {
          CRM_Core_BAO_Setting::_setItem(
            $fields['values'][$name],
            $value,
            $fields['values'][$name]['group_name'],
            $name,
            CRM_Utils_Array::value('component_id', $params),
            CRM_Utils_Array::value('contact_id', $params),
            CRM_Utils_Array::value('created_id', $params),
            $domainID
          );
        }
        if (!empty($fields['values'][$name]['prefetch'])) {
          if (!empty($fields['values'][$name]['config_key'])) {
            $name = $fields['values'][$name]['config_key'];
          }
          $config_keys[$name] = $value;
        }
        $result[$domainID][$name] = $value;
      }
      if ($reloadConfig) {
        CRM_Core_Config::singleton($reloadConfig, $reloadConfig);
      }

      if (!empty($config_keys)) {
        CRM_Core_BAO_ConfigSetting::create($config_keys);
      }
      if ($reloadConfig) {
        CRM_Core_BAO_Domain::resetDomain();
      }
    }

    return $result;
  }

  /**
   * Gets metadata about the settings fields (from getfields) based on the fields being passed in
   *
   * This function filters on the fields like 'version' & 'debug' that are not settings
   *
   * @param array $params
   *   Parameters as passed into API.
   * @param array $fields
   *   Empty array to be populated with fields metadata.
   * @param bool $createMode
   *
   * @throws api_Exception
   * @return array
   *   name => value array of the fields to be set (with extraneous removed)
   */
  public static function validateSettingsInput($params, &$fields, $createMode = TRUE) {
    $group = CRM_Utils_Array::value('group', $params);

    $ignoredParams = array(
      'version',
      'id',
      'domain_id',
      'debug',
      'created_id',
      'component_id',
      'contact_id',
      'filters',
      'entity_id',
      'entity_table',
      'sequential',
      'api.has_parent',
      'IDS_request_uri',
      'IDS_user_agent',
      'check_permissions',
      'options',
      'prettyprint',
    );
    $settingParams = array_diff_key($params, array_fill_keys($ignoredParams, TRUE));
    $getFieldsParams = array('version' => 3);
    if (count($settingParams) == 1) {
      // ie we are only setting one field - we'll pass it into getfields for efficiency
      list($name) = array_keys($settingParams);
      $getFieldsParams['name'] = $name;
    }
    $fields = civicrm_api3('setting', 'getfields', $getFieldsParams);
    $invalidParams = (array_diff_key($settingParams, $fields['values']));
    if (!empty($invalidParams)) {
      throw new api_Exception(implode(',', array_keys($invalidParams)) . " not valid settings");
    }
    if (!empty($settingParams)) {
      $filteredFields = array_intersect_key($settingParams, $fields['values']);
    }
    else {
      // no filters so we are interested in all for get mode. In create mode this means nothing to set
      $filteredFields = $createMode ? array() : $fields['values'];
    }
    return $filteredFields;
  }

  /**
   * Validate & convert settings input
   *
   * @value mixed value of the setting to be set
   * @fieldSpec array Metadata for given field (drawn from the xml)
   */
  public static function validateSetting(&$value, $fieldSpec) {
    if ($fieldSpec['type'] == 'String' && is_array($value)) {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $value) . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    if (empty($fieldSpec['validate_callback'])) {
      return TRUE;
    }
    else {
      $cb = Civi\Core\Resolver::singleton()->get($fieldSpec['validate_callback']);
      if (!call_user_func_array($cb, array(&$value, $fieldSpec))) {
        throw new api_Exception("validation failed for {$fieldSpec['name']} = $value  based on callback {$fieldSpec['validate_callback']}");
      }
    }
  }

  /**
   * Validate & convert settings input - translate True False to 0 or 1
   *
   * @value mixed value of the setting to be set
   * @fieldSpec array Metadata for given field (drawn from the xml)
   */
  public static function validateBoolSetting(&$value, $fieldSpec) {
    if (!CRM_Utils_Rule::boolean($value)) {
      throw new api_Exception("Boolean value required for {$fieldSpec['name']}");
    }
    if (!$value) {
      $value = 0;
    }
    else {
      $value = 1;
    }
    return TRUE;
  }

  /**
   * This provides information about the setting - similar to the fields concept for DAO information.
   * As the setting is serialized code creating validation setting input needs to know the data type
   * This also helps move information out of the form layer into the data layer where people can interact with
   * it via the API or other mechanisms. In order to keep this consistent it is important the form layer
   * also leverages it.
   *
   * Note that this function should never be called when using the runtime getvalue function. Caching works
   * around the expectation it will be called during setting administration
   *
   * Function is intended for configuration rather than runtime access to settings
   *
   * The following params will filter the result. If none are passed all settings will be returns
   *
   * @param int $componentID
   *   Id of relevant component.
   * @param array $filters
   * @param int $domainID
   * @param null $profile
   *
   * @return array
   *   the following information as appropriate for each setting
   *   - name
   *   - type
   *   - default
   *   - add (CiviCRM version added)
   *   - is_domain
   *   - is_contact
   *   - description
   *   - help_text
   */
  public static function getSettingSpecification(
    $componentID = NULL,
    $filters = array(),
    $domainID = NULL,
    $profile = NULL
  ) {
    $cacheString = 'settingsMetadata_' . $domainID . '_' . $profile;
    foreach ($filters as $filterField => $filterString) {
      $cacheString .= "_{$filterField}_{$filterString}";
    }
    $cached = 1;
    // the caching into 'All' seems to be a duplicate of caching to
    // settingsMetadata__ - I think the reason was to cache all settings as defined & then those altered by a hook
    $settingsMetadata = CRM_Core_BAO_Cache::getItem('CiviCRM setting Specs', $cacheString, $componentID);
    if ($settingsMetadata === NULL) {
      $settingsMetadata = CRM_Core_BAO_Cache::getItem('CiviCRM setting Spec', 'All', $componentID);
      if (empty($settingsMetadata)) {
        global $civicrm_root;
        $metaDataFolders = array($civicrm_root . '/settings');
        CRM_Utils_Hook::alterSettingsFolders($metaDataFolders);
        $settingsMetadata = self::loadSettingsMetaDataFolders($metaDataFolders);
        CRM_Core_BAO_Cache::setItem($settingsMetadata, 'CiviCRM setting Spec', 'All', $componentID);
      }
      $cached = 0;
    }

    CRM_Utils_Hook::alterSettingsMetaData($settingsMetadata, $domainID, $profile);
    self::_filterSettingsSpecification($filters, $settingsMetadata);

    if (!$cached) {
      // this is a bit 'heavy' if you are using hooks but this function
      // is expected to only be called during setting administration
      // it should not be called by 'getvalue' or 'getitem
      CRM_Core_BAO_Cache::setItem(
        $settingsMetadata,
        'CiviCRM setting Specs',
        $cacheString,
        $componentID
      );
    }
    return $settingsMetadata;

  }

  /**
   * Load the settings files defined in a series of folders.
   * @param array $metaDataFolders
   *   List of folder paths.
   * @return array
   */
  public static function loadSettingsMetaDataFolders($metaDataFolders) {
    $settingsMetadata = array();
    $loadedFolders = array();
    foreach ($metaDataFolders as $metaDataFolder) {
      $realFolder = realpath($metaDataFolder);
      if (is_dir($realFolder) && !isset($loadedFolders[$realFolder])) {
        $loadedFolders[$realFolder] = TRUE;
        $settingsMetadata = $settingsMetadata + self::loadSettingsMetaData($metaDataFolder);
      }
    }
    return $settingsMetadata;
  }

  /**
   * Load up settings metadata from files.
   */
  public static function loadSettingsMetadata($metaDataFolder) {
    $settingMetaData = array();
    $settingsFiles = CRM_Utils_File::findFiles($metaDataFolder, '*.setting.php');
    foreach ($settingsFiles as $file) {
      $settings = include $file;
      $settingMetaData = array_merge($settingMetaData, $settings);
    }
    CRM_Core_BAO_Cache::setItem($settingMetaData, 'CiviCRM setting Spec', 'All');
    return $settingMetaData;
  }

  /**
   * Filter the settings metadata according to filters passed in. This is a convenience filter
   * and allows selective reverting / filling of settings
   *
   * @param array $filters
   *   Filters to match against data.
   * @param array $settingSpec
   *   Metadata to filter.
   */
  public static function _filterSettingsSpecification($filters, &$settingSpec) {
    if (empty($filters)) {
      return;
    }
    elseif (array_keys($filters) == array('name')) {
      $settingSpec = array($filters['name'] => CRM_Utils_Array::value($filters['name'], $settingSpec, ''));
      return;
    }
    else {
      foreach ($settingSpec as $field => $fieldValues) {
        if (array_intersect_assoc($fieldValues, $filters) != $filters) {
          unset($settingSpec[$field]);
        }
      }
      return;
    }
  }

  /**
   * Look for any missing settings and convert them from config or load default as appropriate.
   * This should be run from GenCode & also from upgrades to add any new defaults.
   *
   * Multisites have often been overlooked in upgrade scripts so can be expected to be missing
   * a number of settings
   */
  public static function updateSettingsFromMetaData() {
    $apiParams = array(
      'version' => 3,
      'domain_id' => 'all',
      'filters' => array('prefetch' => 0),
    );
    $existing = civicrm_api('setting', 'get', $apiParams);

    if (!empty($existing['values'])) {
      $allSettings = civicrm_api('setting', 'getfields', array('version' => 3));
      foreach ($existing['values'] as $domainID => $domainSettings) {
        CRM_Core_BAO_Domain::setDomain($domainID);
        $missing = array_diff_key($allSettings['values'], $domainSettings);
        foreach ($missing as $name => $settings) {
          self::convertConfigToSetting($name, $domainID);
        }
        CRM_Core_BAO_Domain::resetDomain();
      }
    }
  }

  /**
   * Move an item from being in the config array to being stored as a setting
   * remove from config - as appropriate based on metadata
   *
   * Note that where the key name is being changed the 'legacy_key' will give us the old name
   */
  public static function convertConfigToSetting($name, $domainID = NULL) {
    // we have to force this here in case more than one domain is in play.
    // whenever there is a possibility of more than one domain we must force it
    $config = CRM_Core_Config::singleton();
    if (empty($domainID)) {
      $domainID = CRM_Core_Config::domainID();
    }
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = $domainID;
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $values = unserialize($domain->config_backend);
    }
    else {
      $values = array();
    }
    $spec = self::getSettingSpecification(NULL, array('name' => $name), $domainID);
    $configKey = CRM_Utils_Array::value('config_key', $spec[$name], CRM_Utils_Array::value('legacy_key', $spec[$name], $name));
    //if the key is set to config_only we don't need to do anything
    if (empty($spec[$name]['config_only'])) {
      if (!empty($values[$configKey])) {
        civicrm_api('setting', 'create', array('version' => 3, $name => $values[$configKey], 'domain_id' => $domainID));
      }
      else {
        civicrm_api('setting', 'fill', array('version' => 3, 'name' => $name, 'domain_id' => $domainID));
      }

      if (empty($spec[$name]['prefetch']) && !empty($values[$configKey])) {
        unset($values[$configKey]);
        $domain->config_backend = serialize($values);
        $domain->save();
        unset($config->$configKey);
      }
    }
  }

  /**
   * @param $group
   * @param string $name
   * @param bool $system
   * @param int $userID
   * @param bool $localize
   * @param string $returnField
   * @param bool $returnNameANDLabels
   * @param null $condition
   *
   * @return array
   */
  public static function valueOptions(
    $group,
    $name,
    $system = TRUE,
    $userID = NULL,
    $localize = FALSE,
    $returnField = 'name',
    $returnNameANDLabels = FALSE,
    $condition = NULL
  ) {
    $optionValue = self::getItem($group, $name);

    $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, $returnField);

    //enabled name => label require for new contact edit form, CRM-4605
    if ($returnNameANDLabels) {
      $names = $labels = $nameAndLabels = array();
      if ($returnField == 'name') {
        $names = $groupValues;
        $labels = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'label');
      }
      else {
        $labels = $groupValues;
        $names = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'name');
      }
    }

    $returnValues = array();
    foreach ($groupValues as $gn => $gv) {
      $returnValues[$gv] = 0;
    }

    if ($optionValue && !empty($groupValues)) {
      $dbValues = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        substr($optionValue, 1, -1)
      );

      if (!empty($dbValues)) {
        foreach ($groupValues as $key => $val) {
          if (in_array($key, $dbValues)) {
            $returnValues[$val] = 1;
            if ($returnNameANDLabels) {
              $nameAndLabels[$names[$key]] = $labels[$key];
            }
          }
        }
      }
    }
    return ($returnNameANDLabels) ? $nameAndLabels : $returnValues;
  }

  /**
   * @param $group
   * @param string $name
   * @param $value
   * @param bool $system
   * @param int $userID
   * @param string $keyField
   */
  public static function setValueOption(
    $group,
    $name,
    $value,
    $system = TRUE,
    $userID = NULL,
    $keyField = 'name'
  ) {
    if (empty($value)) {
      $optionValue = NULL;
    }
    elseif (is_array($value)) {
      $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, FALSE, NULL, $keyField);

      $cbValues = array();
      foreach ($groupValues as $key => $val) {
        if (!empty($value[$val])) {
          $cbValues[$key] = 1;
        }
      }

      if (!empty($cbValues)) {
        $optionValue = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
            array_keys($cbValues)
          ) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
      else {
        $optionValue = NULL;
      }
    }
    else {
      $optionValue = $value;
    }

    self::setItem($optionValue, $group, $name);
  }

  /**
   * @param array $params
   * @param int $domainID
   */
  public static function fixAndStoreDirAndURL(&$params, $domainID = NULL) {
    if (self::isUpgradeFromPreFourOneAlpha1()) {
      return;
    }

    if (empty($domainID)) {
      $domainID = CRM_Core_Config::domainID();
    }
    $sql = "
 SELECT name, group_name
 FROM   civicrm_setting
 WHERE domain_id = %1
 AND ( group_name = %2
 OR  group_name = %3 )
";
    $sqlParams = array(
      1 => array($domainID, 'Integer'),
      2 => array(self::DIRECTORY_PREFERENCES_NAME, 'String'),
      3 => array(self::URL_PREFERENCES_NAME, 'String'),
    );

    $dirParams = array();
    $urlParams = array();

    $dao = CRM_Core_DAO::executeQuery($sql,
      $sqlParams,
      TRUE,
      NULL,
      FALSE,
      TRUE,
      // trap exceptions as error
      TRUE
    );

    if (is_a($dao, 'DB_Error')) {
      if (CRM_Core_Config::isUpgradeMode()) {
        // seems like this is a 4.0 -> 4.1 upgrade, so we suppress this error and continue
        return;
      }
      else {
        echo "Fatal DB error, exiting, seems like your schema does not have civicrm_setting table\n";
        exit();
      }
    }

    while ($dao->fetch()) {
      if (!isset($params[$dao->name])) {
        continue;
      }
      if ($dao->group_name == self::DIRECTORY_PREFERENCES_NAME) {
        $dirParams[$dao->name] = CRM_Utils_Array::value($dao->name, $params, '');
      }
      else {
        $urlParams[$dao->name] = CRM_Utils_Array::value($dao->name, $params, '');
      }
      unset($params[$dao->name]);
    }

    if (!empty($dirParams)) {
      self::storeDirectoryOrURLPreferences($dirParams,
        self::DIRECTORY_PREFERENCES_NAME
      );
    }

    if (!empty($urlParams)) {
      self::storeDirectoryOrURLPreferences($urlParams,
        self::URL_PREFERENCES_NAME
      );
    }
  }

  /**
   * @param array $params
   * @param $group
   */
  public static function storeDirectoryOrURLPreferences(&$params, $group) {
    foreach ($params as $name => $value) {
      // always try to store relative directory or url from CMS root
      $value = ($group == self::DIRECTORY_PREFERENCES_NAME) ? CRM_Utils_File::relativeDirectory($value) : CRM_Utils_System::relativeURL($value);

      self::setItem($value, $group, $name);
    }
  }

  /**
   * @param array $params
   * @param bool $setInConfig
   */
  public static function retrieveDirectoryAndURLPreferences(&$params, $setInConfig = FALSE) {
    if (CRM_Core_Config::isUpgradeMode()) {
      $isJoomla = (defined('CIVICRM_UF') && CIVICRM_UF == 'Joomla') ? TRUE : FALSE;
      // hack to set the resource base url so that js/ css etc is loaded correctly
      if ($isJoomla) {
        $params['userFrameworkResourceURL'] = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/') . str_replace('administrator', '', CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', 'userFrameworkResourceURL', 'value', 'name'));
      }
      if (self::isUpgradeFromPreFourOneAlpha1()) {
        return;
      }
    }

    if ($setInConfig) {
      $config = CRM_Core_Config::singleton();
    }

    $sql = "
SELECT name, group_name, value
FROM   civicrm_setting
WHERE  ( group_name = %1
OR       group_name = %2 )
AND domain_id = %3
";
    $sqlParams = array(
      1 => array(self::DIRECTORY_PREFERENCES_NAME, 'String'),
      2 => array(self::URL_PREFERENCES_NAME, 'String'),
      3 => array(CRM_Core_Config::domainID(), 'Integer'),
    );

    $dao = CRM_Core_DAO::executeQuery($sql,
      $sqlParams,
      TRUE,
      NULL,
      FALSE,
      TRUE,
      // trap exceptions as error
      TRUE
    );

    if (is_a($dao, 'DB_Error')) {
      echo "Fatal DB error, exiting, seems like your schema does not have civicrm_setting table\n";
      exit();
    }

    while ($dao->fetch()) {
      $value = self::getOverride($dao->group_name, $dao->name, NULL);
      if ($value === NULL && $dao->value) {
        $value = unserialize($dao->value);
        if ($dao->group_name == self::DIRECTORY_PREFERENCES_NAME) {
          $value = CRM_Utils_File::absoluteDirectory($value);
        }
        else {
          // CRM-7622: we need to remove the language part
          $value = CRM_Utils_System::absoluteURL($value, TRUE);
        }
      }
      // CRM-10931, If DB doesn't have any value, carry on with any default value thats already available
      if (!isset($value) && !empty($params[$dao->name])) {
        $value = $params[$dao->name];
      }
      $params[$dao->name] = $value;

      if ($setInConfig) {
        $config->{$dao->name} = $value;
      }
    }
  }

  /**
   * Determine what, if any, overrides have been provided
   * for a setting.
   *
   * @param $group
   * @param string $name
   * @param $default
   *
   * @return mixed, NULL or an overriden value
   */
  protected static function getOverride($group, $name, $default) {
    global $civicrm_setting;
    if ($group && $name && isset($civicrm_setting[$group][$name])) {
      return $civicrm_setting[$group][$name];
    }
    elseif ($group && !isset($name) && isset($civicrm_setting[$group])) {
      return $civicrm_setting[$group];
    }
    else {
      return $default;
    }
  }

  /**
   * Civicrm_setting didn't exist before 4.1.alpha1 and this function helps taking decisions during upgrade
   *
   * @return bool
   */
  public static function isUpgradeFromPreFourOneAlpha1() {
    if (CRM_Core_Config::isUpgradeMode()) {
      $currentVer = CRM_Core_BAO_Domain::version();
      if (version_compare($currentVer, '4.1.alpha1') < 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
