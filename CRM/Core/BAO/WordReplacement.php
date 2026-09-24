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
 * Class CRM_Core_BAO_WordReplacement.
 */
class CRM_Core_BAO_WordReplacement extends CRM_Core_DAO_WordReplacement implements \Civi\Core\HookInterface {

  /**
   * Deprecated update function.
   *
   * @deprecated
   * @param array $params
   * @param int $id
   * @return array
   */
  public static function edit(&$params, &$id) {
    CRM_Core_Error::deprecatedWarning('APIv4');
    $wordReplacement = new CRM_Core_DAO_WordReplacement();
    $wordReplacement->id = $id;
    $wordReplacement->copyValues($params);
    $wordReplacement->save();
    if (!isset($params['options']) || ($params['options']['wp-rebuild'] ?? TRUE)) {
      self::rebuild();
    }
    return $wordReplacement;
  }

  /**
   * Deprecated create function.
   *
   * @deprecated
   * @param array $params
   * @return array
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    if (array_key_exists("domain_id", $params) === FALSE) {
      $params["domain_id"] = CRM_Core_Config::domainID();
    }
    $wordReplacement = new CRM_Core_DAO_WordReplacement();
    $wordReplacement->copyValues($params);
    $wordReplacement->save();
    if (!isset($params['options']) || ($params['options']['wp-rebuild'] ?? TRUE)) {
      self::rebuild();
    }
    return $wordReplacement;
  }

  /**
   * Deprecated delete function
   *
   * @deprecated
   * @param int $id
   * @return CRM_Core_DAO_WordReplacement
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return static::deleteRecord(['id' => $id]);
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'delete') {
      self::rebuild();
    }
  }

  /**
   * Efficient function to write multiple records then rebuild at the end
   *
   * @param array[] $records
   * @return CRM_Core_DAO_WordReplacement[]
   * @throws CRM_Core_Exception
   */
  public static function writeRecords(array $records): array {
    $records = parent::writeRecords($records);
    self::rebuild();
    return $records;
  }

  /**
   * Rebuild.
   *
   * @param bool $clearCaches
   *
   * @return bool
   */
  public static function rebuild($clearCaches = TRUE) {
    unset(Civi::$statics['CRM_Core_I18n']);

    // Partially mitigate the inefficiency introduced in CRM-13187 by doing this conditionally
    if ($clearCaches) {
      // Reset navigation
      CRM_Core_BAO_Navigation::resetNavigation();
      // Clear js localization
      CRM_Core_Resources::singleton()->flushStrings()->resetCacheCode();
    }

    return TRUE;
  }

  /**
   * @deprecated in 6.20 will be removed around 6.26
   */
  public static function rebuildWordReplacementTable() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_WordReplacement::rebuild');
    CRM_Core_BAO_WordReplacement::rebuild();
  }

  /**
   * Get WordReplacements for a locale.
   *
   * @param string $locale
   * @param int $domainId
   *
   * @return array
   *   List of word replacements (enabled/disabled) for the given locale.
   */
  public static function getLocaleCustomStrings($locale, $domainId = NULL) {
    $domainId ??= CRM_Core_Config::domainID();

    if (!CRM_Core_BAO_Domain::isDBVersionAtLeast('6.20.alpha1')) {
      $domain = CRM_Core_DAO::executeQuery('SELECT locale_custom_strings FROM civicrm_domain WHERE id = %1', [
        1 => [$domainId, 'Integer'],
      ], TRUE, NULL, FALSE, FALSE);
      while ($domain->fetch()) {
        $strings = empty($domain->locale_custom_strings) ? [] : CRM_Utils_String::unserialize($domain->locale_custom_strings);
        return $strings[$locale] ?? [];
      }
      return [];
    }

    // TODO: Would it be worthwhile using memcache here?
    $overrides = [];

    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_word_replacement WHERE domain_id = %1 AND language = %2 ORDER BY id ASC', [
      1 => [$domainId, 'Integer'],
      2 => [$locale, 'String'],
    ], TRUE, NULL, FALSE, FALSE);

    while ($dao->fetch()) {
      $status = $dao->is_active ? 'enabled' : 'disabled';
      $overrides[$status][$dao->match_type][$dao->find_word] = $dao->replace_word;
    }

    return $overrides;
  }

}
