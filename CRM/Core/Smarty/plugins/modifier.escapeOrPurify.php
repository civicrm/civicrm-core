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
 * Smarty modifier to purify HTML if the string contains markup tags,
 * or escape it for HTML display without double-encoding existing entities.
 *
 * @param mixed $text
 *   Input text, potentially containing markup or plain text.
 *
 * @since 6.22
 *
 * @return string
 */
function smarty_modifier_escapeOrPurify($text): string {
  return CRM_Utils_String::escapeOrPurify((string) $text);
}
