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
 * $Id: modifier.crmICalText.php 45499 2013-02-08 12:31:05Z kurund $
 *
 */

/**
 * Format the given text in an ical suitable format
 *
 * @param string $str
 *
 * @return string
 *   formatted text
 */
function smarty_modifier_crmICalText($str, $keep_html = FALSE, $position = 0) {
  return CRM_Utils_ICalendar::formatText($str, $keep_html, $position);
}
