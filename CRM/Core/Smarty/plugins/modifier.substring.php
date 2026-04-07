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
 * Smarty plugin
 * Type: modifier
 * Name: substring
 * Version: 0.1
 * Date: 2006-16-02
 * Author: Thorsten Albrecht <thor_REMOVE.THIS_@wolke7.net>
 * Purpose: "substring" allows you to retrieve a small part (substring) of a string.
 * Notes: The substring is specified by giving the start  position and the length.
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
