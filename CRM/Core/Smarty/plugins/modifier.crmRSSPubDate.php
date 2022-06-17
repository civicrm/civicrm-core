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
  $now = new DateTime('Now');

  if ($dateString) {
    try {
      $date = new DateTime($dateString);
      return $date->format($date::RFC822);
    }
    catch (Exception $e) {
      return $now->format($now::RFC822);
    }
  }
  return $now->format($now::RFC822);
}
