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
 * Generate a URL.
 *
 * Ex: `{crmURL p='civicrm/acl/entityrole' q='reset=1'}`
 * Ex: `{crmURL p='civicrm/profile/create' q='id=123&reset=1' fe=1}`
 *
 * Each URL component uses an abbreviation (e.g. "p"<=>"path"; "q"<=>"query").
 *
 * @param array $params
 *   List of URL properties.
 *   - "p" (string $path)
 *     The path being linked to, such as "civicrm/add".
 *   - "q" (array|string $query)
 *     A query string to append to the link, or an array of key-value pairs.
 *   - "a" (bool $absolute)
 *     Whether to force the output to be an absolute link (beginning with a
 *     URI-scheme such as 'http:'). Useful for links that will be displayed
 *     outside the site, such as in an RSS feed.
 *   - "f" (string $fragment)
 *     A "#" fragment to append to the link. This could a named anchor (as
 *     in `#section2`) or a client-side route (as in `#/mailing/new`).
 *   - "h" (bool $htmlize)
 *     Whether to encode special html characters such as &.
 *   - "fe" (bool $frontend)
 *     This link should be to the CMS front end (applies to WP & Joomla).
 *   - "fb" (bool $forceBackend)
 *     This link should be to the CMS back end (applies to WP & Joomla).
 * @return string
 */
function smarty_function_crmURL($params) {
  return CRM_Utils_System::crmURL($params);
}
