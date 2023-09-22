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
 * Format the given date to RSS pubDate RFC822 format,
 * http://www.w3.org/Protocols/rfc822/#z28
 *
 * @param string $dateString
 *   Date which needs to converted to RFC822 format.
 *
 * @return string
 *   formatted text
 */
function smarty_modifier_crmRSSPubDate($dateString): string {
  // Use CRM_Utils_Time to avoid rollover problems in unit testing
  $now = new DateTime(CRM_Utils_Time::date('Y-m-d H:i:s'));

  if ($dateString) {
    try {
      $date = new DateTime($dateString);
      return $date->format($date::RSS);
    }
    catch (Exception $e) {
      // fall through
    }
  }
  return $now->format($now::RSS);
}
