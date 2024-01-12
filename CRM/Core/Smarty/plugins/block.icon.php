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
 * @author Andie Hunt, AGH Strategies
 */

/**
 * Display an icon with some alternative text.
 *
 * This is a wrapper around CRM_Core_Page::icon().
 *
 * @param $params
 *   - condition: if present and falsey, return empty
 *   - icon: the icon class to display instead of fa-check
 *   - anything else is passed along as attributes for the icon
 *
 * @param $text
 *   The translated text to include in the icon's title and screen-reader text.
 *
 * @param $smarty
 *
 * @param bool $repeat
 *   Repeat is true for the opening tag, false for the closing tag
 *
 * @return string|null
 */
function smarty_block_icon($params, $text, &$smarty, &$repeat) {
  if (!$repeat) {
    $condition = array_key_exists('condition', $params) ? $params['condition'] : 1;
    $icon = $params['icon'] ?? 'fa-check';
    $dontPass = [
      'condition' => 1,
      'icon' => 1,
    ];
    return CRM_Core_Page::crmIcon($icon, $text, $condition, array_diff_key($params, $dontPass));
  }
}
