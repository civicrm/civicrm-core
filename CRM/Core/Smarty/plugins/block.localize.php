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
 * $Id$
 *
 */

/**
 * Smarty block function for multilingualizing upgrade SQL queries.
 * The string passed in $text is repeated locale-number times, with the
 * param field (if provided) appended with a different locale every time.
 *
 * @param array $params
 *   Template call's parameters.
 * @param string $text
 *   {ts} block contents from the template.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   multilingualized query
 */
function smarty_block_localize($params, $text, &$smarty) {
  if (!$smarty->_tpl_vars['multilingual']) {
    return $text;
  }

  $lines = [];
  foreach ($smarty->_tpl_vars['locales'] as $locale) {
    $line = $text;
    if ($params['field']) {
      $fields = explode(',', $params['field']);
      foreach ($fields as $field) {
        $field = trim($field);
        $line = preg_replace('/\b' . preg_quote($field) . '\b/', "{$field}_{$locale}", $line);
      }
    }
    $lines[] = $line;
  }

  return implode(', ', $lines);
}
