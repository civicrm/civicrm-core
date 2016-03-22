<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Class CRM_Core_BAO_WordReplacement.
 */
class CRM_Core_BAO_WordReplacement extends CRM_Core_DAO_WordReplacement {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Function that must have never worked & should be removed.
   *
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_WordReplacement
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_WordRepalcement', $params, $defaults);
  }

  /**
   * Get the domain BAO.
   *
   * @param null $reset
   *
   * @return null|CRM_Core_BAO_WordReplacement
   */
  public static function getWordReplacement($reset = NULL) {
    static $wordReplacement = NULL;
    if (!$wordReplacement || $reset) {
      $wordReplacement = new CRM_Core_BAO_WordReplacement();
      $wordReplacement->id = CRM_Core_Config::wordReplacementID();
      if (!$wordReplacement->find(TRUE)) {
        CRM_Core_Error::fatal();
      }
    }
    return $wordReplacement;
  }


  /**
   * Save the values of a WordReplacement.
   *
   * @param array $params
   * @param int $id
   *
   * @return array
   */
  public static function edit(&$params, &$id) {
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
   * Create a new WordReplacement.
   *
   * @param array $params
   *
   * @return array
   */
  public static function create($params) {
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
   * Delete website.
   *
   * @param int $id
   *   WordReplacement id.
   *
   * @return object
   */
  public static function del($id) {
    $dao = new CRM_Core_DAO_WordReplacement();
    $dao->id = $id;
    $dao->delete();
    if (!isset($params['options']) || CRM_Utils_Array::value('wp-rebuild', $params['options'], TRUE)) {
      self::rebuild();
    }
    return $dao;
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
    $params = array(1 => array($id, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $overrides = array();

    while ($dao->fetch()) {
      if ($dao->is_active == 1) {
        $overrides['enabled'][$dao->match_type][$dao->find_word] = $dao->replace_word;
      }
      else {
        $overrides['disabled'][$dao->match_type][$dao->find_word] = $dao->replace_word;
      }
    }
    $config = CRM_Core_Config::singleton();
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);

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
   * Get all the word-replacements stored in config-arrays
   * and convert them to params for the WordReplacement.create API.
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
    $wordReplacementCreateParams = array();
    // get all domains
    $result = civicrm_api3('domain', 'get', array(
      'return' => array('locale_custom_strings'),
    ));
    if (!empty($result["values"])) {
      foreach ($result["values"] as $value) {
        $params = array();
        $params["domain_id"] = $value["id"];
        $params["options"] = array('wp-rebuild' => $rebuildEach);
        // Unserialize word match string.
        $localeCustomArray = unserialize($value["locale_custom_strings"]);
        if (!empty($localeCustomArray)) {
          $wordMatchArray = array();
          // Traverse Language array
          foreach ($localeCustomArray as $localCustomData) {
            // Traverse status array "enabled" "disabled"
            foreach ($localCustomData as $status => $matchTypes) {
              $params["is_active"] = ($status == "enabled") ? TRUE : FALSE;
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
    civicrm_api3('word_replacement', 'replace', array(
      'options' => array('match' => array('domain_id', 'find_word')),
      'values' => self::getConfigArraysAsAPIParams(FALSE),
    ));
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
    if ($domainId === NULL) {
      $domainId = CRM_Core_Config::domainID();
    }

    return CRM_Utils_Array::value($locale, self::_getLocaleCustomStrings($domainId));
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
    $domain = CRM_Core_DAO::executeQuery('SELECT locale_custom_strings FROM civicrm_domain WHERE id = %1', array(
      1 => array($domainId, 'Integer'),
    ));
    while ($domain->fetch()) {
      return empty($domain->locale_custom_strings) ? array() : unserialize($domain->locale_custom_strings);
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
    CRM_Core_DAO::executeQuery("UPDATE civicrm_domain SET locale_custom_strings = %1 WHERE id = %2", array(
      1 => array(serialize($lcs), 'String'),
      2 => array($domainId, 'Integer'),
    ));
  }

}
