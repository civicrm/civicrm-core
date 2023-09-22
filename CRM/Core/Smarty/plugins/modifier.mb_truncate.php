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
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty mb_truncate modifier plugin
 *
 * Type:     modifier<br>
 * Name:     mb_truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *           optionally splitting in the middle of a word, and
 *           appending the $etc string. Multibyte version.
 * @link http://smarty.php.net/manual/en/language.modifier.truncate.php
 *          truncate (Smarty online manual)
 *
 * @param string $string
 * @param int $length
 * @param string $etc
 * @param bool $break_words
 *
 * @return string
 */
function smarty_modifier_mb_truncate($string, $length = 80, $etc = '...',
                                     $break_words = FALSE
) {
  if (function_exists('mb_internal_encoding') and function_exists('mb_strlen') and function_exists('mb_substr')) {
    mb_internal_encoding('UTF-8');
    $strlen = 'mb_strlen';
    $substr = 'mb_substr';
  }
  else {
    $strlen = 'strlen';
    $substr = 'substr';
  }

  if ($length == 0) {

    return '';

  }

  if ($string !== NULL && $strlen($string) > $length) {
    $length -= $strlen($etc);
    if (!$break_words) {
      $string = preg_replace('/\s+?(\S+)?$/', '', $substr($string, 0, $length + 1));
    }

    return $substr($string, 0, $length) . $etc;
  }
  else {
    return $string;
  }
}

/* vim: set expandtab: */
