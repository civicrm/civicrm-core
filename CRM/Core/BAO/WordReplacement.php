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
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

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
    if (!isset($params['options']) || CRM_Utils_Array::value('wp-rebuild', $params['options'], TRUE)) {
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
    if (!isset($params['options']) || CRM_Utils_Array::value('wp-rebuild', $params['options'], TRUE)) {
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
   * Get all word-replacements in the form of an array.
   *
   * @param int $id
   *   Domain ID.
   *
   * @return array
   * @see civicrm_domain.locale_custom_strings
   */
  public static function getAllAsConfigArray($id) {
    $query = "
SELECT find_word,replace_word,is_active,match_type
FROM   civicrm_word_replacement
WHERE  domain_id = %1
";
    $params = [1 => [$id, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $overrides = [];

    while ($dao->fetch()) {
      if ($dao->is_active == 1) {
        $overrides['enabled'][$dao->match_type][$dao->find_word] = $dao->replace_word;
      }
      else {
        $overrides['disabled'][$dao->match_type][$dao->find_word] = $dao->replace_word;
      }
    }
    $config = CRM_Core_Config::singleton();

    // So. Weird. Some bizarre/probably-broken multi-lingual thing where
    // data isn't really stored in civicrm_word_replacements. Probably
    // shouldn't exist.
    $stringOverride = self::_getLocaleCustomStrings($id);
    $stringOverride[$config->lcMessages] = $overrides;

    return $stringOverride;
  }

  /**
   * Rebuild.
   *
   * @param bool $clearCaches
   *
   * @return bool
   */
  public static function rebuild($clearCaches = TRUE) {
    $id = CRM_Core_Config::domainID();
    self::_setLocaleCustomStrings($id, self::getAllAsConfigArray($id));

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
   * Get word replacements for the api.
   *
   * Get all the word-replacements stored in config-arrays for the
   * configured language, and convert them to params for the
   * WordReplacement.create API.
   *
   * Note: This function is duplicated in CRM_Core_BAO_WordReplacement and
   * CRM_Upgrade_Incremental_php_FourFour to ensure that the incremental upgrade
   * step behaves consistently even as the BAO evolves in future versions.
   * However, if there's a bug in here prior to 4.4.0, we should apply the
   * bug-fix in both places.
   *
   * @param bool $rebuildEach
   *   Whether to perform rebuild after each individual API call.
   *
   * @return array
   *   Each item is $params for WordReplacement.create
   * @see CRM_Core_BAO_WordReplacement::convertConfigArraysToAPIParams
   */
  public static function getConfigArraysAsAPIParams($rebuildEach) {
    $settingsResult = civicrm_api3('Setting', 'get', [
      'return' => 'lcMessages',
    ]);
    $returnValues = CRM_Utils_Array::first($settingsResult['values']);
    $lang = $returnValues['lcMessages'];

    $wordReplacementCreateParams = [];
    // get all domains
    $result = civicrm_api3('domain', 'get', [
      'return' => ['locale_custom_strings'],
    ]);
    if (!empty($result["values"])) {
      foreach ($result["values"] as $value) {
        $params = [];
        $params["domain_id"] = $value["id"];
        $params["options"] = ['wp-rebuild' => $rebuildEach];
        // Unserialize word match string.
        $localeCustomArray = CRM_Utils_String::unserialize($value["locale_custom_strings"]);
        if (!empty($localeCustomArray)) {
          $wordMatchArray = [];
          // Only return the replacement strings of the current language,
          // otherwise some replacements will be duplicated, which will
          // lead to undesired results, like CRM-19683.
          $localCustomData = $localeCustomArray[$lang];
          // Traverse status array "enabled" "disabled"
          foreach ($localCustomData as $status => $matchTypes) {
            $params["is_active"] = $status == "enabled";
            // Traverse Match Type array "wildcardMatch" "exactMatch"
            foreach ($matchTypes as $matchType => $words) {
              $params["match_type"] = $matchType;
              foreach ($words as $word => $replace) {
                $params["find_word"] = $word;
                $params["replace_word"] = $replace;
                $wordReplacementCreateParams[] = $params;
              }
            }
          }
        }
      }
    }
    return $wordReplacementCreateParams;
  }

  /**
   * Rebuild word replacements.
   *
   * Get all the word-replacements stored in config-arrays
   * and write them out as records in civicrm_word_replacement.
   *
   * Note: This function is duplicated in CRM_Core_BAO_WordReplacement and
   * CRM_Upgrade_Incremental_php_FourFour to ensure that the incremental upgrade
   * step behaves consistently even as the BAO evolves in future versions.
   * However, if there's a bug in here prior to 4.4.0, we should apply the
   * bug-fix in both places.
   */
  public static function rebuildWordReplacementTable() {
    civicrm_api3('word_replacement', 'replace', [
      'options' => ['match' => ['domain_id', 'find_word']],
      'values' => self::getConfigArraysAsAPIParams(FALSE),
    ]);
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
    return self::_getLocaleCustomStrings($domainId)[$locale] ?? [];
  }

  /**
   * Get custom locale strings.
   *
   * @param int $domainId
   *
   * @return array|mixed
   */
  private static function _getLocaleCustomStrings($domainId) {
    // TODO: Would it be worthwhile using memcache here?
    // Disable i18n rewrite in query to avoid infinite recursion as this function is called from ts() and the rewrite fetches the schema which also uses ts()
    $domain = CRM_Core_DAO::executeQuery('SELECT locale_custom_strings FROM civicrm_domain WHERE id = %1', [
      1 => [$domainId, 'Integer'],
    ], TRUE, NULL, FALSE, FALSE);
    while ($domain->fetch()) {
      return empty($domain->locale_custom_strings) ? [] : CRM_Utils_String::unserialize($domain->locale_custom_strings);
    }
  }

  /**
   * Set locale strings.
   *
   * @param string $locale
   * @param array $values
   * @param int $domainId
   */
  public static function setLocaleCustomStrings($locale, $values, $domainId = NULL) {
    if ($domainId === NULL) {
      $domainId = CRM_Core_Config::domainID();
    }

    $lcs = self::_getLocaleCustomStrings($domainId);
    $lcs[$locale] = $values;

    self::_setLocaleCustomStrings($domainId, $lcs);
  }

  /**
   * Set locale strings.
   *
   * @param int $domainId
   * @param string $lcs
   */
  private static function _setLocaleCustomStrings($domainId, $lcs) {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_domain SET locale_custom_strings = %1 WHERE id = %2", [
      1 => [serialize($lcs), 'String'],
      2 => [$domainId, 'Integer'],
    ]);
  }

}
