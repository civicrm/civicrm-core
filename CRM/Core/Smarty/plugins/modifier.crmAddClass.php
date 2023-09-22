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
 * Add a class to an html element
 *
 * @param string $string
 *   The html to be tweaked.
 * @param string $class
 *   The new class or classes to add (separate with a space).
 *
 * @return string
 *   the new modified html string
 */
function smarty_modifier_crmAddClass($string, $class) {
  // Standardize white space
  $string = str_replace(['class ="', 'class= "', 'class = "'], 'class="', $string);
  if (strpos($string, 'class="') !== FALSE) {
    $string = str_replace('class="', 'class="' . "$class ", $string);
  }
  else {
    $string = str_replace('>', ' class="' . $class . '">', $string);
  }
  return $string;
}
