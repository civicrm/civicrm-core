<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_BAO_WordReplacement extends CRM_Core_DAO_WordReplacement {

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
   * @return null|object CRM_Core_BAO_WordRepalcement
   * @access public
   * @static
   */
  static function getWordReplacement($reset = NULL) {
    static $wordReplacement = NULL;
    if (!$wordReplacement || $reset) {
      $wordReplacement = new CRM_Core_BAO_WordRepalcement();
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
    $query = "SELECT find_word,replace_word FROM civicrm_word_replacement WHERE is_active = 1 AND domain_id = ".CRM_Utils_Type::escape($id, 'Integer');
    $dao = CRM_Core_DAO::executeQuery($query);
    $wordReplacement = array();

    while ($dao->fetch()) {
      $wordReplacement[$dao->find_word] = $dao->replace_word;
    }

    $overrides['enabled']['wildcardMatch'] = $wordReplacement;

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
  static function rebuild() {
    $id = CRM_Core_Config::domainID();
    $stringOverride = self::getAllAsConfigArray($id);
    $params = array('locale_custom_strings' => serialize($stringOverride));
    $wordReplacementSettings = CRM_Core_BAO_Domain::edit($params, $id);

    if ($wordReplacementSettings) {
      // Reset navigation
      CRM_Core_BAO_Navigation::resetNavigation();
      // Clear js string cache
      CRM_Core_Resources::singleton()->flushStrings();

      return TRUE;
    }

    return FALSE;
  }
}

