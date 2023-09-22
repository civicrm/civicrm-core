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
 * Replace a string with the first word in the string
 *
 * @param string $string
 *   The html to be tweaked with.
 *
 * @return string
 */
function smarty_modifier_crmFirstWord($string) {
  $string = trim($string);
  $words = explode(' ', $string);
  return $words[0];
}
