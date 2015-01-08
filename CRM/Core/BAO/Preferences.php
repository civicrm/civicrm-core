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
class CRM_Core_BAO_Preferences {

  /**
   * @param $params
   */
  static function fixAndStoreDirAndURL(&$params) {
    $sql = "
SELECT v.name as valueName, g.name as optionName
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  ( g.name = 'directory_preferences'
OR       g.name = 'url_preferences' )
AND    v.option_group_id = g.id
AND    v.is_active = 1
";

    $dirParams = array();
    $urlParams = array();
    $dao       = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!isset($params[$dao->valueName])) {
        continue;
      }
      if ($dao->optionName == 'directory_preferences') {
        $dirParams[$dao->valueName] = CRM_Utils_Array::value($dao->valueName, $params, '');
      }
      else {
        $urlParams[$dao->valueName] = CRM_Utils_Array::value($dao->valueName, $params, '');
      }
      unset($params[$dao->valueName]);
    }

    if (!empty($dirParams)) {
      CRM_Core_BAO_Preferences::storeDirectoryOrURLPreferences($dirParams, 'directory');
    }

    if (!empty($urlParams)) {
      CRM_Core_BAO_Preferences::storeDirectoryOrURLPreferences($urlParams, 'url');
    }
  }

  /**
   * @param $params
   * @param string $type
   */
  static function storeDirectoryOrURLPreferences(&$params, $type = 'directory') {
    $optionName = ($type == 'directory') ? 'directory_preferences' : 'url_preferences';

    $sql = "
UPDATE civicrm_option_value v,
       civicrm_option_group g
SET    v.value = %1,
       v.is_active = 1
WHERE  g.name = %2
AND    v.option_group_id = g.id
AND    v.name = %3
";

    foreach ($params as $name => $value) {
      // always try to store relative directory or url from CMS root
      if ($type == 'directory') {
        $value = CRM_Utils_File::relativeDirectory($value);
      }
      else {
        $value = CRM_Utils_System::relativeURL($value);
      }
      $sqlParams = array(1 => array($value, 'String'),
        2 => array($optionName, 'String'),
        3 => array($name, 'String'),
      );
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  /**
   * @param $params
   * @param bool $setInConfig
   */
  static function retrieveDirectoryAndURLPreferences(&$params, $setInConfig = FALSE) {
    if ($setInConfig) {
      $config = CRM_Core_Config::singleton();
    }

    $sql = "
SELECT v.name as valueName, v.value, g.name as optionName
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  ( g.name = 'directory_preferences'
OR       g.name = 'url_preferences' )
AND    v.option_group_id = g.id
AND    v.is_active = 1
";


    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!$dao->value) {
        continue;
      }
      if ($dao->optionName == 'directory_preferences') {
        $value = CRM_Utils_File::absoluteDirectory($dao->value);
      }
      else {
        // CRM-7622: we need to remove the language part
        $value = CRM_Utils_System::absoluteURL($dao->value, TRUE);
      }
      $params[$dao->valueName] = $value;
      if ($setInConfig) {
        $config->{$dao->valueName} = $value;
      }
    }
  }
}

