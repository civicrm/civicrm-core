<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

/**
 * Class CRM_Utils_Api
 */
class CRM_Utils_Api {
  /**
   * Attempts to retrieve the API entity name from any calling class.
   * FIXME: This is a bit hackish but the naming convention for forms is not very strict
   *
   * @param string|object $classNameOrObject
   *
   * @return string
   * @throws CRM_Core_Exception
   */
  public static function getEntityName($classNameOrObject) {
    require_once 'api/api.php';
    $className = is_string($classNameOrObject) ? $classNameOrObject : get_class($classNameOrObject);

    // First try the obvious replacements
    $daoName = str_replace(array('_BAO_', '_Form_', '_Page_'), '_DAO_', $className);
    $entityName = CRM_Core_DAO_AllCoreTables::getBriefName($daoName);

    // If that didn't work, try a different pattern
    if (!$entityName) {
      list(, $parent, , $child) = explode('_', $className);
      $daoName = "CRM_{$parent}_DAO_$child";
      $entityName = CRM_Core_DAO_AllCoreTables::getBriefName($daoName);
    }

    // If that didn't work, try a different pattern
    if (!$entityName) {
      $daoName = "CRM_{$parent}_DAO_$parent";
      $entityName = CRM_Core_DAO_AllCoreTables::getBriefName($daoName);
    }

    // If that didn't work, try a different pattern
    if (!$entityName) {
      $daoName = "CRM_Core_DAO_$child";
      $entityName = CRM_Core_DAO_AllCoreTables::getBriefName($daoName);
    }

    // If that didn't work, try using just the trailing name
    if (!$entityName) {
      $entityName = CRM_Core_DAO_AllCoreTables::getFullName($child) ? $child : NULL;
    }

    // If that didn't work, try using just the leading name
    if (!$entityName) {
      $entityName = CRM_Core_DAO_AllCoreTables::getFullName($parent) ? $parent : NULL;
    }

    if (!$entityName) {
      throw new CRM_Core_Exception('Could not find api name for supplied class');
    }
    return $entityName;
  }

}
