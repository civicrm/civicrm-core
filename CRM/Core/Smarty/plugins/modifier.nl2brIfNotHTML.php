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
 * If the given string looks like HTML, do nothing and return it.
 * If it doesn't, replace newlines with br tags.
 * The HTML check is somewhat greedy and may not add br tags
 * to some non-HTML text that contains angle brackets.
 *
 * @param string $text
 *
 * @return string
 *   Text with br tags if input was non-HTML.
 */
function smarty_modifier_nl2brIfNotHTML($text) {
  if ($text && $text === strip_tags($text)) {
    $text = nl2br($text);
  }
  return $text;
}
