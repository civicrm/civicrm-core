<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Smarty block function for multilingualizing upgrade SQL queries.
 * The string passed in $text is repeated locale-number times, with the
 * param field (if provided) appended with a different locale every time.
 *
 * @param array  $params   template call's parameters
 * @param string $text     {ts} block contents from the template
 * @param object $smarty   the Smarty object
 *
 * @return string  multilingualized query
 */
function smarty_block_localize($params, $text, &$smarty) {
  if (!$smarty->_tpl_vars['multilingual']) {
    return $text;
  }

  $lines = array();
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

