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
 * Convert a given text part a better HTML representation (add paragraphs and make URLs clickable)
 *
 * @param string $text
 *   Text to HTML-ize.
 *
 * @return string
 *   HTML-ized version of $text
 */
function smarty_modifier_htmlize($text) {
  $text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
  $text = nl2br($text);
  return $text;
}
