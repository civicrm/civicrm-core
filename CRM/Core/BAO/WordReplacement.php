<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_BAO_WordReplacement extends CRM_Core_DAO_WordReplacement {

  /**
   * class constructor
   *
   * @access public
   * @return \CRM_Core_DAO_WordReplacement
   */
  /**
   *
   */
  function __construct() {
    parent::__construct();
  }
  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects.
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_WordRepalcement object
   * @access public
   * @static
   */

  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_WordRepalcement', $params, $defaults);
  }

  /**
   * Get the domain BAO
   *
   * @param null $reset
   *
   * @return null|object CRM_Core_BAO_WordRepalcement
   * @access public
   * @static
   */
  static function getWordReplacement($reset = NULL) {
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
   * Save the values of a WordReplacement
   *
   * @param $params
   * @param $id
   *
   * @return WordReplacement array
   * @access public
   */
  static function edit(&$params, &$id) {
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
   * Create a new WordReplacement
   *
   * @param $params
   *
   * @return WordReplacement array
   * @access public
   */
  static function create($params) {
    if(array_key_exists("domain_id",$params) === FALSE) {
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
   * Delete website
   *
   * @param int $id WordReplacement id
   *
   * @return object
   * @static
   */
  static function del($id) {
    $dao = new CRM_Core_DAO_WordReplacement();
    $dao->id = $id;
    $dao->delete();
    if (!isset($params['options']) || CRM_Utils_Array::value('wp-rebuild', $params['options'], TRUE)) {
      self::rebuild();
    }
    return $dao;
  }

  /**
   * Get all word-replacements in the form of an array
   *
   * @param int $id domain ID
   * @return array
   * @see civicrm_domain.locale_custom_strings
   */
  public static function getAllAsConfigArray($id) {
    $query = "
SELECT find_word,replace_word,is_active,match_type
FROM   civicrm_word_replacement
WHERE  domain_id = %1
";
    $params = array( 1 => array($id, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $overrides = array();

    while ($dao->fetch()) {
      if ($dao->is_active==1) {
        $overrides['enabled'][$dao->match_type][$dao->find_word] = $dao->replace_word;
      }
      else {
        $overrides['disabled'][$dao->match_type][$dao->find_word] = $dao->replace_word;
      }
    }
    $config = CRM_Core_Config::singleton();
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);

    if ($domain->locales && $config->localeCustomStrings) {
      // for multilingual
      $addReplacements = $config->localeCustomStrings;
      $addReplacements[$config->lcMessages] = $overrides;
      $stringOverride = $addReplacements;
    }
    else {
      // for single language
      $stringOverride = array($config->lcMessages => $overrides);
    }

    return $stringOverride;
  }

  /**
   * Rebuild
   */
  static function rebuild($clearCaches = TRUE) {
    $id = CRM_Core_Config::domainID();
    $stringOverride = self::getAllAsConfigArray($id);
    $params = array('locale_custom_strings' => serialize($stringOverride));
    $wordReplacementSettings = CRM_Core_BAO_Domain::edit($params, $id);
    if ($wordReplacementSettings) {
      CRM_Core_Config::singleton()->localeCustomStrings = $stringOverride;

      // Partially mitigate the inefficiency introduced in CRM-13187 by doing this conditionally
      if ($clearCaches) {
        // Reset navigation
        CRM_Core_BAO_Navigation::resetNavigation();
        // Clear js localization
        CRM_Core_Resources::singleton()->flushStrings()->resetCacheCode();
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get all the word-replacements stored in config-arrays
   * and convert them to params for the WordReplacement.create API.
   *
   * Note: This function is duplicated in CRM_Core_BAO_WordReplacement and
   * CRM_Upgrade_Incremental_php_FourFour to ensure that the incremental upgrade
   * step behaves consistently even as the BAO evolves in future versions.
   * However, if there's a bug in here prior to 4.4.0, we should apply the
   * bugfix in both places.
   *
   * @param bool $rebuildEach whether to perform rebuild after each individual API call
   * @return array Each item is $params for WordReplacement.create
   * @see CRM_Core_BAO_WordReplacement::convertConfigArraysToAPIParams
   */
  static function getConfigArraysAsAPIParams($rebuildEach) {
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
        // unserialize word match string
        $localeCustomArray = unserialize($value["locale_custom_strings"]);
        if (!empty($localeCustomArray)) {
          $wordMatchArray = array();
          // Traverse Language array
          foreach ($localeCustomArray as $localCustomData) {
          // Traverse status array "enabled" "disabled"
            foreach ($localCustomData as $status => $matchTypes) {
              $params["is_active"] = ($status == "enabled")?TRUE:FALSE;
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
   * Get all the word-replacements stored in config-arrays
   * and write them out as records in civicrm_word_replacement.
   *
   * Note: This function is duplicated in CRM_Core_BAO_WordReplacement and
   * CRM_Upgrade_Incremental_php_FourFour to ensure that the incremental upgrade
   * step behaves consistently even as the BAO evolves in future versions.
   * However, if there's a bug in here prior to 4.4.0, we should apply the
   * bugfix in both places.
   */
  public static function rebuildWordReplacementTable() {
    civicrm_api3('word_replacement', 'replace', array(
      'options' => array('match' => array('domain_id', 'find_word')),
      'values' => self::getConfigArraysAsAPIParams(FALSE),
    ));
    CRM_Core_BAO_WordReplacement::rebuild();
  }
}

