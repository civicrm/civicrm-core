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
 * Show block conditionally, based on the permission
 *
 * /**
 * @param array $params
 *  Array containing the permission/s to check and optional contact ID.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 * @param bool $repeat
 *   Repeat is true for the opening tag, false for the closing tag
 *
 * @return string
 *   The content in the black, if allowed.
 * @noinspection PhpUnused
 */
function smarty_block_crmPermission($params, $content, &$smarty, &$repeat) {
  if (!$repeat) {
    if (CRM_Core_Permission::check($params['permission'], $params['contact_id'] ?? NULL)) {
      return $content;
    }
  }
  return '';
}
