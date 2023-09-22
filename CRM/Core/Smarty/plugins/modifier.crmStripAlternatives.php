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
 * Given an ezComponents-parsed representation of
 * a text with alternatives return only the first one
 *
 * @param string $full
 *   All alternatives as a long string (or some other text).
 *
 * @return string
 *   only the first alternative found (or the text without alternatives)
 */
function smarty_modifier_crmStripAlternatives($full) {
  return CRM_Utils_String::stripAlternatives($full);
}
