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

/**
 * BAO object for civicrm_setting table. This table is used to store civicrm settings that are not used
 * very frequently (i.e. not on every page load)
 *
 * The group column is used for grouping together all settings that logically belong to the same set.
 * Thus all settings in the same group are retrieved with one DB call and then cached for future needs.
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
    MAP_PREFERENCES_NAME = 'Map Preferences',
    CONTRIBUTE_PREFERENCES_NAME = 'Contribute Preferences',
    MEMBER_PREFERENCES_NAME = 'Member Preferences',
    MULTISITE_PREFERENCES_NAME = 'Multi Site Preferences',
    PERSONAL_PREFERENCES_NAME = 'Personal Preferences',
    SYSTEM_PREFERENCES_NAME = 'CiviCRM Preferences',
    URL_PREFERENCES_NAME = 'URL Preferences',
    LOCALIZATION_PREFERENCES_NAME = 'Localization Preferences',
    SEARCH_PREFERENCES_NAME = 'Search Preferences';

  /**
   * Retrieve the value of a setting from the DB table.
   *
   * @param string|null $group
   *   The group name of the item (deprecated).
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
    /** @var \Civi\Core\SettingsManager $manager */
    $manager = \Civi::service('settings_manager');
    $settings = ($contactID === NULL) ? $manager->getBagByDomain($domainID) : $manager->getBagByContact($domainID, $contactID);
    if ($name === NULL) {
      CRM_Core_Error::deprecatedWarning("Deprecated: Group='$group'. Name should be provided.\n");
    }
    if ($componentID !== NULL) {
      CRM_Core_Error::deprecatedWarning("Deprecated: Group='$group'. Name='$name'. Component should be omitted\n");
    }
    if ($defaultValue !== NULL) {
      CRM_Core_Error::deprecatedWarning("Deprecated: Group='$group'. Name='$name'. Defaults should come from metadata\n");
    }
    return $name ? $settings->get($name) : $settings->all();
  }

  /**
   * Get multiple items from the setting table.
   *
   * @param array $params
   *   (required) An api formatted array of keys and values.
   * @param array $domains Array of domains to get settings for. Default is the current domain
   * @param $settingsToReturn
   *
   * @return array
   */
  public static function getItems(&$params, $domains, $settingsToReturn) {
    $originalDomain = CRM_Core_Config::domainID();
    if (empty($domains)) {
      $domains[] = $originalDomain;
    }
    if (!empty($settingsToReturn) && !is_array($settingsToReturn)) {
      $settingsToReturn = [$settingsToReturn];
    }

    $fields = $result = [];
    $fieldsToGet = self::validateSettingsInput(array_flip($settingsToReturn), $fields, FALSE);
    foreach ($domains as $domainID) {
      $result[$domainID] = [];
      foreach ($fieldsToGet as $name => $value) {
        $contactID = $params['contact_id'] ?? NULL;
        $setting = CRM_Core_BAO_Setting::getItem(NULL, $name, NULL, NULL, $contactID, $domainID);
        if (!is_null($setting)) {
          // we won't return if not set - helps in return all scenario - otherwise we can't indentify the missing ones
          // e.g for revert of fill actions
          $result[$domainID][$name] = $setting;
        }
      }
    }
    return $result;
  }

  /**
   * Store an item in the setting table.
   *
   * @param mixed $value
   *   (required) The value that will be serialized and stored.
   * @param string $group
   *   The group name of the item (deprecated).
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
   * @throws \CRM_Core_Exception
   *
   * @deprecated - refer docs https://docs.civicrm.org/dev/en/latest/framework/setting/
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
    CRM_Core_Error::deprecatedFunctionWarning('refer docs for correct methods https://docs.civicrm.org/dev/en/latest/framework/setting/');

    /** @var \Civi\Core\SettingsManager $manager */
    $manager = \Civi::service('settings_manager');
    $settings = ($contactID === NULL) ? $manager->getBagByDomain($domainID) : $manager->getBagByContact($domainID, $contactID);
    $settings->set($name, $value);
  }

  /**
   * Store multiple items in the setting table. Note that this will also store config keys
   * the storage is determined by the metdata and is affected by
   *  'name' setting's name
   *  'config_key' = the config key is different to the settings key - e.g. debug where there was a conflict
   *  'legacy_key' = rename from config or setting with this name
   *
   * @param array $params
   *   (required) An api formatted array of keys and values.
   * @param array|null $domains
   *
   * @throws CRM_Core_Exception
   * @domains array an array of domains to get settings for. Default is the current domain
   * @return array
   */
  public static function setItems(&$params, $domains = NULL) {
    $domains = empty($domains) ? [CRM_Core_Config::domainID()] : $domains;

    // FIXME: redundant validation
    // FIXME: this whole thing should just be a loop to call $settings->add() on each domain.

    $fields = [];
    $fieldsToSet = self::validateSettingsInput($params, $fields);

    foreach ($fieldsToSet as $settingField => &$settingValue) {
      if (empty($fields['values'][$settingField])) {
        CRM_Core_Error::deprecatedWarning('Deprecated Path: There is a setting (' . $settingField . ') not correctly defined. You may see unpredictability due to this. CRM_Core_Setting::setItems');
        $fields['values'][$settingField] = [];
      }
      self::validateSetting($settingValue, $fields['values'][$settingField]);
    }

    foreach ($domains as $domainID) {
      Civi::settings($domainID)->add($fieldsToSet);
      $result[$domainID] = $fieldsToSet;
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
   * @throws CRM_Core_Exception
   * @return array
   *   name => value array of the fields to be set (with extraneous removed)
   */
  public static function validateSettingsInput($params, &$fields, $createMode = TRUE) {
    $ignoredParams = [
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
      // CRM-18347: ignore params unintentionally passed by API explorer on WP
      'page',
      'noheader',
      // CRM-18347: ignore params unintentionally passed by wp CLI tool
      '',
      // CRM-19877: ignore params extraneously passed by Joomla
      'option',
      'task',
    ];
    $settingParams = array_diff_key($params, array_fill_keys($ignoredParams, TRUE));
    $getFieldsParams = ['version' => 3];
    if (count($settingParams) == 1) {
      // ie we are only setting one field - we'll pass it into getfields for efficiency
      [$name] = array_keys($settingParams);
      $getFieldsParams['name'] = $name;
    }
    $fields = civicrm_api3('setting', 'getfields', $getFieldsParams);
    $invalidParams = (array_diff_key($settingParams, $fields['values']));
    if (!empty($invalidParams)) {
      throw new CRM_Core_Exception(implode(',', array_keys($invalidParams)) . " not valid settings");
    }
    if (!empty($settingParams)) {
      $filteredFields = array_intersect_key($settingParams, $fields['values']);
    }
    else {
      // no filters so we are interested in all for get mode. In create mode this means nothing to set
      $filteredFields = $createMode ? [] : $fields['values'];
    }
    return $filteredFields;
  }

  /**
   * Validate & convert settings input.
   *
   * @param mixed $value
   *   value of the setting to be set
   * @param array $fieldSpec
   *   Metadata for given field (drawn from the xml)
   * @param bool $convertToSerializedString
   *   Deprecated mode
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function validateSetting(&$value, array $fieldSpec, $convertToSerializedString = TRUE) {
    // Deprecated guesswork - should use $fieldSpec['serialize']
    if ($convertToSerializedString && $fieldSpec['type'] == 'String' && is_array($value)) {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $value) . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    if (empty($fieldSpec['validate_callback'])) {
      return TRUE;
    }
    else {
      $cb = Civi\Core\Resolver::singleton()->get($fieldSpec['validate_callback']);
      if (!call_user_func_array($cb, [&$value, $fieldSpec])) {
        throw new CRM_Core_Exception("validation failed for {$fieldSpec['name']} = $value  based on callback {$fieldSpec['validate_callback']}");
      }
    }
  }

  /**
   * Validate & convert settings input - translate True False to 0 or 1.
   *
   * @param mixed $value value of the setting to be set
   * @param array $fieldSpec Metadata for given field (drawn from the xml)
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function validateBoolSetting(&$value, $fieldSpec) {
    if (!CRM_Utils_Rule::boolean($value)) {
      throw new CRM_Core_Exception("Boolean value required for {$fieldSpec['name']}");
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
    $filters = [],
    $domainID = NULL,
    $profile = NULL
  ) {
    return \Civi\Core\SettingsMetadata::getMetadata($filters, $domainID);
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
      $names = $labels = $nameAndLabels = [];
      if ($returnField == 'name') {
        $names = $groupValues;
        $labels = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'label');
      }
      else {
        $labels = $groupValues;
        $names = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'name');
      }
    }

    $returnValues = [];
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
   * @param string $group
   *   Deprecated parameter
   * @param string $name
   * @param mixed $value
   * @param bool $system
   * @param int $userID
   * @param string $keyField
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated
   */
  public static function setValueOption(
    $group,
    $name,
    $value,
    $system = TRUE,
    $userID = NULL,
    $keyField = 'name'
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('refer docs for correct methods https://docs.civicrm.org/dev/en/latest/framework/setting/');
    if (empty($value)) {
      $optionValue = NULL;
    }
    elseif (is_array($value)) {
      $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, FALSE, NULL, $keyField);

      $cbValues = [];
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
   * Check if environment is explicitly set.
   *
   * @param $setting
   *
   * @return bool
   */
  public static function isEnvironmentSet($setting): bool {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $environment = CRM_Core_Config::environment();
    return $setting === 'environment' && $environment;
  }

  /**
   * Check if job is able to be executed by API.
   *
   * @param $params
   *
   * @throws \CRM_Core_Exception
   */
  public static function isAPIJobAllowedToRun($params): void {
    $environment = CRM_Core_Config::environment(NULL, TRUE);
    if ($environment !== 'Production') {
      if (empty($params['runInNonProductionEnvironment'])) {
        throw new CRM_Core_Exception(ts('Job has not been executed as it is a %1 (non-production) environment.', [1 => $environment]));
      }
    }
  }

  /**
   * Setting Callback - On Change.
   *
   * Respond to changes in the "environment" setting.
   *
   * @param array $oldValue
   *   Value of old environment mode.
   * @param array $newValue
   *   Value of new environment mode.
   * @param array $metadata
   *   Specification of the setting (per *.settings.php).
   */
  public static function onChangeEnvironmentSetting($oldValue, $newValue, $metadata) {
    if ($newValue !== 'Production') {
      CRM_Core_Session::setStatus(ts('Outbound emails have been disabled. Scheduled jobs will not run unless runInNonProductionEnvironment=TRUE is added as a parameter for a specific job'), ts("Non-production environment set"), "success");
      Civi::settings()->set('mailing_backend', ['outBound_option' => CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED]);
    }
    if ($newValue == 'Production' && $oldValue != 'Production') {
      $mailing_backend = Civi::settings()->get('mailing_backend');
      if ($mailing_backend['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED) {
        CRM_Core_Session::setStatus(ts('Now that your site is in production mode, you may want to enable <a %1>outbound email</a>.', [1 => 'href="' . CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1') . '"']), ts("Production environment set"), "success");
      }
    }
  }

}
