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
 * $Id$
 *
 */

/**
 * Smarty plugin
 * Type: modifier
 * Name: substring
 * Version: 0.1
 * Date: 2006-16-02
 * Author: Thorsten Albrecht <thor_REMOVE.THIS_@wolke7.net>
 * Purpose: "substring" allows you to retrieve a small part (substring) of a string.
 * Notes: The substring is specified by giving the start  position and the length.
 * Unlike the original function substr() in PHP the position of the characters
 * in the string starts at 1 (not at 0 as usual in php).
 * Example smarty code:
 *   {$my_string|substring:2:4}
 *   returns substring from character 2 until character 6
 * @link based on substr(): http://www.zend.com/manual/function.substr.php
 * @param string $string
 * @param int $position
 *   startposition of the substring, beginning with 0
 * @param int $length
 *   length of substring
 * @return string
 */
function smarty_modifier_substring($string, $position, $length) {
  return substr($string, $position, $length);
}
