<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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

class CRM_Core_AllCoreTables {ldelim}

  static protected $tables = array(
{foreach from=$tables key=tableName item=table}
    '{$tableName}' => '{$table.className}',
{/foreach} {* tables *}
  );

  static protected $daoToClass = array(
{foreach from=$tables item=table}
    '{$table.objectName}' => '{$table.className}',
{/foreach} {* tables *}
  );

  static public function getCoreTables() {ldelim}
    return self::$tables;
  {rdelim}

  static public function isCoreTable($tableName) {ldelim}
    return FALSE !== array_search($tableName, self::$tables);
  {rdelim}

  static public function getClasses() {ldelim}
    return array_values(self::$tables);
  {rdelim}

  static public function getClassForTable($tableName) {ldelim}
    return CRM_Utils_Array::value($tableName, self::$tables);
  {rdelim}

  static public function getFullName($daoName) {ldelim}
    return CRM_Utils_Array::value($daoName, self::$daoToClass);
  {rdelim}

{rdelim}
