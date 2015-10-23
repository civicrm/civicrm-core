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

namespace Civi\Core;

/**
 * Class SettingsMetadata
 * @package Civi\Core
 */
class SettingsMetadata {

  const ALL = 'all';

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
  public static function getMetadata($filters = array(), $domainID = NULL) {
    if ($domainID === NULL) {
      $domainID = \CRM_Core_Config::domainID();
    }

    $cache = \Civi::cache('settings');
    $cacheString = 'settingsMetadata_' . $domainID . '_';
    // the caching into 'All' seems to be a duplicate of caching to
    // settingsMetadata__ - I think the reason was to cache all settings as defined & then those altered by a hook
    $settingsMetadata = $cache->get($cacheString);
    $cached = is_array($settingsMetadata);

    if (!$cached) {
      $settingsMetadata = $cache->get(self::ALL);
      if (empty($settingsMetadata)) {
        global $civicrm_root;
        $metaDataFolders = array($civicrm_root . '/settings');
        \CRM_Utils_Hook::alterSettingsFolders($metaDataFolders);
        $settingsMetadata = self::loadSettingsMetaDataFolders($metaDataFolders);
        $cache->set(self::ALL, $settingsMetadata);
      }
    }

    \CRM_Utils_Hook::alterSettingsMetaData($settingsMetadata, $domainID, NULL);

    if (!$cached) {
      $cache->set($cacheString, $settingsMetadata);
    }

    self::_filterSettingsSpecification($filters, $settingsMetadata);

    return $settingsMetadata;
  }

  /**
   * Load the settings files defined in a series of folders.
   * @param array $metaDataFolders
   *   List of folder paths.
   * @return array
   */
  protected static function loadSettingsMetaDataFolders($metaDataFolders) {
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
   *
   * @param array $metaDataFolder
   *
   * @return array
   */
  protected static function loadSettingsMetadata($metaDataFolder) {
    $settingMetaData = array();
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
    if (empty($filters)) {
      return;
    }
    elseif (array_keys($filters) == array('name')) {
      $settingSpec = array($filters['name'] => \CRM_Utils_Array::value($filters['name'], $settingSpec, ''));
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

}
