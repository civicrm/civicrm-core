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

/**
 * Class SettingsMetadata
 * @package Civi\Core
 */
class SettingsMetadata {

  /**
   * @var array
   * Cache for boot settings metadata (which is used before Civi::cache is available)
   */
  protected static ?array $bootCache = NULL;

  /**
   * WARNING: This interface may change.
   *
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
   * @param array $filters
   * @param int $domainID
   * @param bool|array $loadOptions
   *
   * The final param optionally restricts to only boot-time settings
   *
   * @param bool $bootOnly - only
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
   *   - options
   *   - pseudoconstant
   *   - global_name (name for php constant / environment variable corresponding to this setting)
   *   - is_env_loadable (whether this setting should be read from corresponding environment variable)
   *   - is_constant (whether a PHP constant should be defined for this setting)
   */
  public static function getMetadata($filters = [], $domainID = NULL, $loadOptions = FALSE, $bootOnly = FALSE) {
    $settingsMetadata = $bootOnly ? self::getBootMetadata() : self::getFullMetadata($domainID);

    self::_filterSettingsSpecification($filters, $settingsMetadata);
    if ($loadOptions) {
      self::fillOptions($settingsMetadata, $loadOptions);
    }

    return $settingsMetadata;
  }

  /**
   * Get the final metadata for all settings
   *  - sources from extensions etc using alterSettingsFolders / alterSettingsMetadata hooks
   *
   * @param ?int $domainID
   *
   * @return array all settings metadata
   */
  protected static function getFullMetadata($domainID = NULL) {
    if ($domainID === NULL) {
      $domainID = \CRM_Core_Config::domainID();
    }

    $cache = \Civi::cache('settings');
    $cacheString = 'settingsMetadata_' . $domainID . '_';
    $settingsMetadata = $cache->get($cacheString);

    if (!is_array($settingsMetadata)) {
      global $civicrm_root;
      $metaDataFolders = [\CRM_Utils_File::addTrailingSlash($civicrm_root) . 'settings'];
      \CRM_Utils_Hook::alterSettingsFolders($metaDataFolders);
      $settingsMetadata = self::loadSettingsMetaDataFolders($metaDataFolders);
      \CRM_Utils_Hook::alterSettingsMetaData($settingsMetadata, $domainID, NULL);
      $cache->set($cacheString, $settingsMetadata);
    }

    return $settingsMetadata;
  }

  /**
   * Get the starting metadata for boot settings from core. No hooks are called.
   *
   * @return array boot settings metadata
   */
  protected static function getBootMetadata() {
    if (!is_array(self::$bootCache)) {
      self::$bootCache = self::loadSettingsMetadata(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'settings', '*.boot.setting.php');
    }
    return self::$bootCache;
  }

  /**
   * Load the settings files defined in a series of folders.
   * @param array $metaDataFolders
   *   List of folder paths.
   * @return array
   */
  protected static function loadSettingsMetaDataFolders($metaDataFolders) {
    $settingsMetadata = [];
    $loadedFolders = [];
    foreach ($metaDataFolders as $metaDataFolder) {
      $realFolder = realpath($metaDataFolder);
      if (is_dir($realFolder) && !isset($loadedFolders[$realFolder])) {
        $loadedFolders[$realFolder] = TRUE;
        $settingsMetadata = $settingsMetadata + self::loadSettingsMetadata($metaDataFolder);
      }
    }
    return $settingsMetadata;
  }

  /**
   * Load up settings metadata from files.
   *
   * @param string $metaDataFolder
   * @param string $filePattern
   *
   * @return array
   */
  protected static function loadSettingsMetadata($metaDataFolder, $filePattern = '*.setting.php') {
    $settingMetaData = [];
    $settingsFiles = \CRM_Utils_File::findFiles($metaDataFolder, $filePattern);
    foreach ($settingsFiles as $file) {
      $settings = include $file;
      $settingMetaData = array_merge($settingMetaData, $settings);
    }
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
  protected static function _filterSettingsSpecification($filters, &$settingSpec) {
    if (!empty($filters['name'])) {
      $settingSpec = array_intersect_key($settingSpec, array_flip((array) $filters['name']));
      // FIXME: This is a workaround for settingsBag::setDb() called by unit tests with settings names that don't exist
      $settingSpec += array_fill_keys((array) $filters['name'], []);
      unset($filters['name']);
    }
    if (!empty($filters)) {
      foreach ($settingSpec as $field => $fieldValues) {
        if (array_intersect_assoc($fieldValues, $filters) != $filters) {
          unset($settingSpec[$field]);
        }
      }
    }
  }

  /**
   * Retrieve options from settings metadata
   *
   * @param array $settingSpec
   * @param bool|array $optionsFormat
   *   TRUE for a flat array; otherwise an array of keys to return
   */
  protected static function fillOptions(&$settingSpec, $optionsFormat) {
    foreach ($settingSpec as &$spec) {
      if (empty($spec['pseudoconstant'])) {
        continue;
      }
      $pseudoconstant = $spec['pseudoconstant'];
      $spec['options'] = [];
      // It would be nice if we could leverage CRM_Core_PseudoConstant::get() somehow,
      // but it's tightly coupled to DAO/field. However, if you really need to support
      // more pseudoconstant types, then probably best to refactor it. For now, KISS.
      if (!empty($pseudoconstant['optionGroupName'])) {
        $keyColumn = $pseudoconstant['keyColumn'] ?? 'value';
        if (is_array($optionsFormat)) {
          $optionValues = \CRM_Core_OptionValue::getValues(['name' => $pseudoconstant['optionGroupName']]);
          foreach ($optionValues as $option) {
            $option['id'] = $option['value'];
            $spec['options'][] = $option;
          }
        }
        else {
          $spec['options'] = \CRM_Core_OptionGroup::values($pseudoconstant['optionGroupName'], FALSE, FALSE, TRUE, NULL, 'label', TRUE, FALSE, $keyColumn);
        }
        continue;
      }
      if (!empty($pseudoconstant['callback'])) {
        $options = Resolver::singleton()->call($pseudoconstant['callback'], []);
      }
      if (!empty($pseudoconstant['table'])) {
        $params = [
          'condition' => $pseudoconstant['condition'] ?? [],
          'keyColumn' => $pseudoconstant['keyColumn'] ?? NULL,
          'labelColumn' => $pseudoconstant['labelColumn'] ?? NULL,
        ];
        $options = \CRM_Core_PseudoConstant::renderOptionsFromTablePseudoconstant($pseudoconstant, $params, ($spec['localize_context'] ?? NULL), 'get');
      }
      if (is_array($optionsFormat)) {
        foreach ($options as $key => $value) {
          $spec['options'][] = [
            'id' => $key,
            'name' => $value,
            'label' => $value,
          ];
        }
      }
      else {
        $spec['options'] = $options;
      }
    }
  }

}
