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
class CRM_Utils_Path {
  static function join() {
    $path_parts = array();
    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg)) {
        $path_parts = array_merge($path_parts, $arg);
      }
      else {
        $path_parts[] = $arg;
      }
    }
    return implode(DIRECTORY_SEPARATOR, $path_parts);
  }

  static function mkdir_p_if_not_exists($path) {
    if (file_exists($path)) {
      if (!is_dir($path)) {
        throw new Exception("Trying to make a directory at '$path', but there is already a file there with the same name.");
      }
    }
    else {
      $result = @mkdir($path, 0777, TRUE);
      if ($result === FALSE) {
        throw new Exception("Error trying to create directory '$path': " . print_r(error_get_last(), TRUE));
      }
    }
  }
}
