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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Purify HTML to mitigate against XSS attacks
 *
 * @param string $text
 *   Input text, potentially containing XSS
 *
 * @return string
 *   Output text, containing only clean HTML
 */
function smarty_modifier_purify($text) {
  return CRM_Utils_String::purifyHTML($text);
}
