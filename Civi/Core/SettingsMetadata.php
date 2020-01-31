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
   * @param bool $loadOptions
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
   */
  public static function getMetadata($filters = [], $domainID = NULL, $loadOptions = FALSE) {
    if ($domainID === NULL) {
      $domainID = \CRM_Core_Config::domainID();
    }

    $cache = \Civi::cache('settings');
    $cacheString = 'settingsMetadata_' . $domainID . '_';
    $settingsMetadata = $cache->get($cacheString);

    if (!is_array($settingsMetadata)) {
      global $civicrm_root;
      $metaDataFolders = [$civicrm_root . '/settings'];
      \CRM_Utils_Hook::alterSettingsFolders($metaDataFolders);
      $settingsMetadata = self::loadSettingsMetaDataFolders($metaDataFolders);
      \CRM_Utils_Hook::alterSettingsMetaData($settingsMetadata, $domainID, NULL);
      $cache->set($cacheString, $settingsMetadata);
    }

    self::_filterSettingsSpecification($filters, $settingsMetadata);
    if ($loadOptions) {
      self::loadOptions($settingsMetadata);
    }

    return $settingsMetadata;
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
   * @param array $metaDataFolder
   *
   * @return array
   */
  protected static function loadSettingsMetadata($metaDataFolder) {
    $settingMetaData = [];
    $settingsFiles = \CRM_Utils_File::findFiles($metaDataFolder, '*.setting.php');
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
   */
  protected static function loadOptions(&$settingSpec) {
    foreach ($settingSpec as &$spec) {
      if (empty($spec['pseudoconstant'])) {
        continue;
      }
      // It would be nice if we could leverage CRM_Core_PseudoConstant::get() somehow,
      // but it's tightly coupled to DAO/field. However, if you really need to support
      // more pseudoconstant types, then probably best to refactor it. For now, KISS.
      if (!empty($spec['pseudoconstant']['callback'])) {
        $spec['options'] = Resolver::singleton()->call($spec['pseudoconstant']['callback'], []);
      }
      elseif (!empty($spec['pseudoconstant']['optionGroupName'])) {
        $keyColumn = \CRM_Utils_Array::value('keyColumn', $spec['pseudoconstant'], 'value');
        $spec['options'] = \CRM_Core_OptionGroup::values($spec['pseudoconstant']['optionGroupName'], FALSE, FALSE, TRUE, NULL, 'label', TRUE, FALSE, $keyColumn);
      }
    }
  }

}
