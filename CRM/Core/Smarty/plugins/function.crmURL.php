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
 * @copyright CiviCRM LLC
 *
 */

/**
 * Generate a url
 *
 * @param array $params
 *   The values to construct the url.
 *   - p  string $path
 *   The path being linked to, such as "civicrm/add".
 *   - q array|string $query
 *   A query string to append to the link, or an array of key-value pairs.
 *   -a bool $absolute
 *   Whether to force the output to be an absolute link (beginning with a
 *   URI-scheme such as 'http:'). Useful for links that will be displayed
 *   outside the site, such as in an RSS feed.
 *   -f  string $fragment
 *   A fragment identifier (named anchor) to append to the link.
 *   -h  bool $htmlize
 *   Whether to encode special html characters such as &.
 *   -fe bool $frontend
 *   This link should be to the CMS front end (applies to WP & Joomla).
 *   -fb bool $forceBackend
 *   This link should be to the CMS back end (applies to WP & Joomla).
 *
 * @return string
 */
function smarty_function_crmURL($params) {
  return CRM_Utils_System::crmURL($params);
}
