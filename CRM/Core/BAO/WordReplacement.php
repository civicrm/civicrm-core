<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2020                                |
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
 * @copyright CiviCRM LLC (c) 2004-2020
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
   * Rebuild.
   *
   * @param bool $clearCaches
   *
   * @return bool
   */
  public static function rebuild($clearCaches = TRUE) {
    $id = CRM_Core_Config::domainID();

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
   * Rebuild word replacements.
   *
   * @deprecated
   */
  public static function rebuildWordReplacementTable() {
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

    // TODO: Would it be worthwhile using memcache here?
    $overrides = [];

    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_word_replacement WHERE domain_id = %1 AND language = %2 ORDER BY id ASC', [
      1 => [$domainId, 'Integer'],
      2 => [$locale, 'String'],
    ]);

    while ($dao->fetch()) {
      $status = $dao->is_active ? 'enabled' : 'disabled';
      $overrides[$status][$dao->match_type][$dao->find_word] = $dao->replace_word;
    }

    return $overrides;
  }

}
