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
 * Smarty block function for multilingualizing upgrade SQL queries.
 * The string passed in $text is repeated locale-number times, with the
 * param field (if provided) appended with a different locale every time.
 *
 * @param array $params
 *   Template call's parameters. Should include `fields`.
 * @param string $text
 *   {ts} block contents from the template.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 * @param bool $repeat
 *   Confusing variable that means it's either the opening tag or you can use
 *   it to signal back not to repeat.
 *
 * @return string
 *   multilingualized query
 */
function smarty_block_localize($params, $text, $smarty, &$repeat) {
  if ($repeat) {
    // For opening tag text is always null
    return '';
  }
  $multiLingual = $smarty->getTemplateVars('multilingual');
  if (!$multiLingual) {
    return $text;
  }

  $lines = $fields = [];

  if (!empty($params['field'])) {
    $fields = array_map('trim', explode(',', $params['field']));
  }

  $locales = (array) $smarty->getTemplateVars('locales');
  foreach ($locales as $locale) {
    $line = $text;
    foreach ($fields as $field) {
      $line = preg_replace('/\b' . preg_quote($field) . '\b/', "{$field}_{$locale}", $line);
    }
    $lines[] = $line;
  }
  // In a typical use-case this adds to an existing comma-separated list within a sql statement
  $separator = ', ';
  // Or if the block ends with a `;`, then it's copying the entire statement
  if (str_ends_with(rtrim($text), ';')) {
    $separator = "\n";
  }
  return implode($separator, $lines);
}
