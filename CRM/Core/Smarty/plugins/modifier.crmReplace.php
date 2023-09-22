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
 * Replace the value of an attribute in the input string. Assume
 * the the attribute is well formed, of the type name="value". If
 * no replacement is mentioned the value is inserted at the end of
 * the form element
 *
 * @param string $string
 *   The html to be tweaked with.
 * @param string $attribute
 *   The attribute to modify.
 * @param string $value
 *   The new attribute value.
 *
 * @return string
 *   the new modified html string
 */
function smarty_modifier_crmReplace($string, $attribute, $value) {
  // we need to search and replace the string: $attribute=XXX or $attribute="XXX"
  // with $attribute=\"$value\"
  $pattern = '/' . $attribute . '="([^"]+?)"/';
  return preg_replace($pattern, $attribute . '="' . $value . '"', $string);
}
